/*
  ============================================================
          SMART HYDROPONIC IoT - ESP32 (HydroSense by agronex)
  ============================================================

  SENSOR & AKTUATOR:
  - TDS Sensor Analog -> GPIO 34
  - DS18B20 Suhu Air  -> GPIO 4 (Pull-up resistor 4.7k ohm ke 3.3V)
  - Relay Pompa IN    -> GPIO 26 (Active LOW)

  FITUR SISTEM:
  1. Koneksi Otomatis ke WiFi & Server Laravel (API REST).
  2. Fallback Mode Mandiri (Access Point + Captive Portal):
     - Jika belum ada WiFi atau koneksi putus, ESP32 memancarkan
       WiFi sendiri: "SMART-HYDROPONIC" (Password: 12345678).
     - Buka browser ke: http://192.168.4.1 atau http://192.168.4.1/wifi
     - Terdapat menu Pemantauan, Pengaturan WiFi/Server, & Tools JSON.
     - Pilihan pindah menu berfungsi lancar (baik via tab maupun URL langsung).
     - Terdapat pemindaian daftar jaringan WiFi sekitar otomatis.
     - Kredensial WiFi tersimpan permanen di memori Flash (Preferences NVS).
  3. Kontrol Dua Arah (Bidirectional Control):
     - ESP32 mengirim data sensor secara modular (POST /api/sensor/data).
     - Server Laravel membalas status Pompa, Mode Auto, & Target TDS.
  4. Serial Monitor Lengkap:
     - Angka suhu air, nilai nutrisi TDS, tegangan sensor, target nutrisi,
       status pompa, dan status koneksi dicetak berkala ke Serial Monitor.

  LIBRARY YANG DIPERLUKAN:
  - OneWire (oleh Paul Stoffregen)
  - DallasTemperature (oleh Miles Burton)
  - ArduinoJson (oleh Benoit Blanchon - versi 6 atau 7)
  ============================================================
*/

#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Preferences.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>
#include <DNSServer.h>
#include <math.h>

// ============================================================
// KONFIGURASI PIN
// ============================================================
#define TDS_PIN       34
#define TEMP_PIN      4
#define RELAY_PIN     26

// ============================================================
// KONFIGURASI LOGIKA RELAY
// ============================================================
#define RELAY_ON      LOW
#define RELAY_OFF     HIGH

// ============================================================
// ACCESS POINT DEFAULT (FALLBACK JIKA OFFLINE)
// ============================================================
const char* AP_SSID = "SMART-HYDROPONIC";
const char* AP_PASSWORD = "12345678";
const byte DNS_PORT = 53;

// Identitas Perangkat di Dashboard Laravel
String savedDeviceCode = "alat1sumedang";

// ============================================================
// OBJEK & VARIABEL GLOBAL
// ============================================================
WebServer server(80);
DNSServer dnsServer;
Preferences preferences;

OneWire oneWire(TEMP_PIN);
DallasTemperature waterTemperature(&oneWire);

// Konfigurasi WiFi & Server (Tersimpan di Flash Preferences)
String savedSSID = "";
String savedPassword = "";
String savedServerUrl = "https://hydrosense.agronex.id/api/sensor/alat1sumedang/data";

bool isWifiConnected = false;
bool apModeActive = false;

// Variabel Sensor & Aktuator
float temperature = 25.0;
float tds = 800.0;
float voltage = 1.25;

bool pumpStatus = false;
bool autoMode = true;
float targetTDS = 800.0;

// Pengatur Jadwal (Timer Non-blocking)
unsigned long lastSensorRead = 0;
const unsigned long SENSOR_INTERVAL = 2000;       // Baca sensor tiap 2 detik

unsigned long lastSerialPrint = 0;
const unsigned long SERIAL_PRINT_INTERVAL = 2000; // Cetak angka ke Serial tiap 2 detik

unsigned long lastServerSync = 0;
const unsigned long SERVER_SYNC_INTERVAL = 3000;  // Kirim data ke server tiap 3 detik

unsigned long lastWifiCheck = 0;
const unsigned long WIFI_CHECK_INTERVAL = 15000;  // Pengecekan WiFi tiap 15 detik

// Penjadwalan Sambung Ulang WiFi Setelah Simpan Pengaturan
bool pendingWifiReconnect = false;
unsigned long reconnectTriggerTime = 0;

// ============================================================
// KONTROL POMPA RELAY
// ============================================================
void pumpON() {
  digitalWrite(RELAY_PIN, RELAY_ON);
  pumpStatus = true;
  Serial.println(F("[AKTUATOR POMPA] -> MENYALA (ON)"));
}

void pumpOFF() {
  digitalWrite(RELAY_PIN, RELAY_OFF);
  pumpStatus = false;
  Serial.println(F("[AKTUATOR POMPA] -> MATI (OFF)"));
}

// ============================================================
// BACA SUHU DS18B20
// ============================================================
float readTemperature() {
  waterTemperature.requestTemperatures();
  float temp = waterTemperature.getTempCByIndex(0);
  if (temp == DEVICE_DISCONNECTED_C || temp < -40.0) {
    return 25.0; // Nilai acuan normal jika probe belum terpasang
  }
  return temp;
}

// ============================================================
// BACA TDS DENGAN KOMPENSASI SUHU
// ============================================================
float readTDS(float temp) {
  int adcValue = analogRead(TDS_PIN);
  voltage = adcValue * (3.3 / 4095.0);

  // Kompensasi suhu terhadap 25 derajat Celsius
  float compensationCoefficient = 1.0 + 0.02 * (temp - 25.0);
  float compensatedVoltage = voltage / compensationCoefficient;

  float tdsValue = (133.42 * pow(compensatedVoltage, 3))
                 - (255.86 * pow(compensatedVoltage, 2))
                 + (857.39 * compensatedVoltage);
  tdsValue *= 0.5;

  if (tdsValue < 0) tdsValue = 0;
  return tdsValue;
}

// ============================================================
// KONTROL OTOMATIS BERDASARKAN TARGET NUTRISI
// ============================================================
void automaticControl() {
  if (!autoMode) return;

  // Jika TDS di bawah target -> Pompa nutrisi diaktifkan
  // Jika TDS sudah mencukupi target -> Pompa dimatikan
  if (tds < targetTDS) {
    if (!pumpStatus) pumpON();
  } else {
    if (pumpStatus) pumpOFF();
  }
}

// ============================================================
// CETAK ANGKA DAN STATUS KE SERIAL MONITOR
// ============================================================
void printSerialStatus() {
  Serial.println(F("\n=================================================="));
  Serial.print(F("WAKTU SISTEM   : ")); Serial.print(millis() / 1000); Serial.println(F(" detik"));
  Serial.print(F("KODE ALAT      : ")); Serial.println(savedDeviceCode);
  Serial.println(F("--------------------------------------------------"));
  Serial.print(F("SUHU AIR       : ")); Serial.print(temperature, 2); Serial.println(F(" C"));
  Serial.print(F("NUTRISI TDS    : ")); Serial.print(tds, 1); Serial.println(F(" PPM"));
  Serial.print(F("TEGANGAN ADC   : ")); Serial.print(voltage, 3); Serial.println(F(" Volt"));
  Serial.print(F("TARGET NUTRISI : ")); Serial.print(targetTDS, 1); Serial.println(F(" PPM"));
  Serial.print(F("STATUS POMPA   : ")); Serial.println(pumpStatus ? F("MENYALA (ON)") : F("MATI (OFF)"));
  Serial.print(F("MODE KERJA     : ")); Serial.println(autoMode ? F("OTOMATIS (AUTO)") : F("MANUAL"));
  Serial.println(F("--------------------------------------------------"));
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println(F("STATUS KONEKSI : TERHUBUNG KE JARINGAN"));
    Serial.print(F("SSID WIFI      : ")); Serial.println(WiFi.SSID());
    Serial.print(F("IP ALAT        : ")); Serial.println(WiFi.localIP());
    Serial.print(F("KEKUATAN SINYAL: ")); Serial.print(WiFi.RSSI()); Serial.println(F(" dBm"));
    Serial.print(F("SERVER TUJUAN  : ")); Serial.println(savedServerUrl);
  } else {
    Serial.println(F("STATUS KONEKSI : MODE AP CADANGAN (MANDIRI)"));
    Serial.print(F("SSID AP        : ")); Serial.println(AP_SSID);
    Serial.print(F("KATA SANDI AP  : ")); Serial.println(AP_PASSWORD);
    Serial.print(F("IP PORTAL WEB  : ")); Serial.println(WiFi.softAPIP());
  }
  Serial.println(F("=================================================="));
}

// ============================================================
// AKTIFKAN MODE ACCESS POINT MANDIRI (FALLBACK)
// ============================================================
void startAccessPoint() {
  WiFi.mode(WIFI_AP_STA);
  WiFi.softAP(AP_SSID, AP_PASSWORD);
  apModeActive = true;

  // Jalankan DNS Server untuk Captive Portal
  dnsServer.setErrorReplyCode(DNSReplyCode::NoError);
  dnsServer.start(DNS_PORT, "*", WiFi.softAPIP());

  Serial.println(F("\n[ACCESS POINT] Mode Mandiri Aktif"));
  Serial.print(F("[ACCESS POINT] Nama WiFi (SSID): ")); Serial.println(AP_SSID);
  Serial.print(F("[ACCESS POINT] Kata Sandi      : ")); Serial.println(AP_PASSWORD);
  Serial.print(F("[ACCESS POINT] Alamat Portal   : http://")); Serial.println(WiFi.softAPIP());
}

// ============================================================
// KONEKSI KE WIFI STA
// ============================================================
bool connectToWiFi(String ssid, String pass, int timeoutSeconds = 12) {
  if (ssid.length() == 0) {
    Serial.println(F("[WIFI] SSID belum ditentukan di pengaturan."));
    return false;
  }

  Serial.print(F("[WIFI] Menyambungkan ke WiFi: "));
  Serial.println(ssid);

  WiFi.begin(ssid.c_str(), pass.c_str());

  int elapsed = 0;
  while (WiFi.status() != WL_CONNECTED && elapsed < (timeoutSeconds * 2)) {
    delay(500);
    Serial.print(F("."));
    elapsed++;
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    isWifiConnected = true;
    Serial.println(F("[WIFI] Berhasil Terhubung ke Jaringan!"));
    Serial.print(F("[WIFI] Alamat IP Alat: ")); Serial.println(WiFi.localIP());
    return true;
  } else {
    isWifiConnected = false;
    Serial.println(F("[WIFI] Gagal terhubung ke WiFi."));
    return false;
  }
}

// ============================================================
// TOOLS PENGIRIM TELEMETRI JSON MODULAR (NoSQL)
// ============================================================
String sendModularTelemetry(String customJson = "") {
  if (WiFi.status() != WL_CONNECTED) {
    return "{\"status\":\"error\",\"message\":\"WiFi belum tersambung ke jaringan internet\"}";
  }

  HTTPClient http;
  WiFiClient client;
  WiFiClientSecure secureClient;

  if (savedServerUrl.startsWith("https://")) {
    secureClient.setInsecure(); // Mengizinkan HTTPS tanpa sertifikat CA statis yang bisa kadaluarsa
    http.begin(secureClient, savedServerUrl);
  } else {
    http.begin(client, savedServerUrl);
  }

  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");

  String requestBody;

  if (customJson.length() > 0) {
    requestBody = customJson;
  } else {
    // Susun dokumen JSON modular secara dinamis
    StaticJsonDocument<512> doc;
    doc["device_code"] = savedDeviceCode;
    doc["temperature"] = serialized(String(temperature, 2));
    doc["tds"]         = serialized(String(tds, 2));
    doc["voltage"]     = serialized(String(voltage, 2));
    doc["pump"]        = pumpStatus;
    doc["auto"]        = autoMode;
    doc["target_tds"]  = serialized(String(targetTDS, 1));

    // Kontrol Pompa Multi-saluran
    JsonObject pumps = doc.createNestedObject("pumps");
    pumps["pompa_sirkulasi"] = pumpStatus;

    doc["ip_address"]  = WiFi.localIP().toString();
    doc["wifi_ssid"]   = WiFi.SSID();

    serializeJson(doc, requestBody);
  }

  Serial.println(F("\n[KIRIM TELEMETRI KE SERVER]"));
  Serial.print(F("URL Server   : ")); Serial.println(savedServerUrl);
  Serial.print(F("Payload JSON : ")); Serial.println(requestBody);

  int httpCode = http.POST(requestBody);
  String response = "";

  if (httpCode == HTTP_CODE_OK || httpCode == 201) {
    response = http.getString();
    Serial.print(F("Respon Server: HTTP ")); Serial.print(httpCode);
    Serial.print(F(" -> ")); Serial.println(response);

    // Baca balasan kontrol dari server Laravel
    StaticJsonDocument<512> resDoc;
    DeserializationError error = deserializeJson(resDoc, response);

    if (!error && resDoc["status"] == "success" && resDoc.containsKey("control")) {
      JsonObject control = resDoc["control"];

      if (control.containsKey("auto")) autoMode = control["auto"];
      if (control.containsKey("target_tds")) targetTDS = control["target_tds"];

      if (control.containsKey("pumps")) {
        JsonObject serverPumps = control["pumps"];
        if (serverPumps.containsKey("pompa_sirkulasi")) {
          bool state = serverPumps["pompa_sirkulasi"];
          if (!autoMode) {
            if (state && !pumpStatus) pumpON();
            else if (!state && pumpStatus) pumpOFF();
          }
        }
      } else if (!autoMode && control.containsKey("pump")) {
        bool serverPump = control["pump"];
        if (serverPump && !pumpStatus) pumpON();
        else if (!serverPump && pumpStatus) pumpOFF();
      }
    }
  } else {
    Serial.print(F("Respon Gagal : HTTP Error ")); Serial.println(httpCode);
    response = "{\"status\":\"error\",\"http_code\":" + String(httpCode) + "}";
  }

  http.end();
  return response;
}

// ============================================================
// PEMINDAIAN DAFTAR JARINGAN WIFI SEKITAR
// ============================================================
String getScannedWifiOptions() {
  int n = WiFi.scanNetworks();
  String options = "<option value=''>-- Pilih dari Daftar WiFi Terdeteksi --</option>";
  if (n > 0) {
    for (int i = 0; i < n; ++i) {
      String ssidName = WiFi.SSID(i);
      int rssiVal = WiFi.RSSI(i);
      options += "<option value='" + ssidName + "'>" + ssidName + " (" + String(rssiVal) + " dBm)</option>";
    }
  } else {
    options += "<option value='' disabled>Tidak ada jaringan terdeteksi</option>";
  }
  return options;
}

// ============================================================
// GENERATOR HALAMAN WEB LOKAL (LENGKAP, RESPONSIF & SUPER STABIL)
// ============================================================
String buildLocalHtml(String activeTab) {
  String html = F("<!DOCTYPE html>\n<html lang='id'>\n<head>\n"
  "<meta charset='utf-8'>\n"
  "<meta name='viewport' content='width=device-width, initial-scale=1'>\n"
  "<title>HydroSense - Portal Alat Kebun</title>\n"
  "<style>\n"
  "* { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }\n"
  "body { margin: 0; background: #f0fdf4; color: #1e293b; padding: 14px; }\n"
  ".header { text-align: center; margin-bottom: 14px; }\n"
  ".header h1 { margin: 0; color: #15803d; font-size: 20px; font-weight: 800; }\n"
  ".header p { margin: 3px 0 0; color: #64748b; font-size: 12px; }\n"
  ".badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; margin-top: 6px; }\n"
  ".badge-online { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }\n"
  ".badge-ap { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }\n"
  ".nav-bar { display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap; }\n"
  ".nav-link { flex: 1; min-width: 95px; padding: 10px 8px; border-radius: 10px; border: 1px solid #cbd5e1; background: white; color: #475569; font-size: 12px; font-weight: 700; text-align: center; text-decoration: none; cursor: pointer; }\n"
  ".nav-link.active { background: #15803d; color: white; border-color: #15803d; }\n"
  ".card { background: white; border-radius: 16px; padding: 18px; margin-bottom: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }\n"
  ".grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }\n"
  ".metric { background: #f8fafc; border-radius: 12px; padding: 12px; text-align: center; border: 1px solid #e2e8f0; }\n"
  ".metric-title { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; }\n"
  ".metric-val { font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 3px; }\n"
  ".metric-val span { font-size: 12px; font-weight: normal; color: #64748b; }\n"
  ".btn { width: 100%; border: none; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; margin-bottom: 8px; text-decoration: none; display: block; text-align: center; }\n"
  ".btn-on { background: #16a34a; color: white; }\n"
  ".btn-off { background: #e11d48; color: white; }\n"
  ".btn-mode { background: #2563eb; color: white; }\n"
  ".btn-dark { background: #0f172a; color: white; }\n"
  ".btn-preset { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; font-size: 11px; padding: 8px 10px; border-radius: 8px; font-weight: 700; cursor: pointer; text-align: left; width: 100%; margin-bottom: 6px; }\n"
  "input, select, textarea { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 12px; font-size: 13px; }\n"
  "textarea.code-box { font-family: monospace; font-size: 11px; background: #0f172a; color: #34d399; line-height: 1.5; resize: vertical; }\n"
  "label { font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px; }\n"
  ".tab-content { display: none; }\n"
  ".tab-content.active { display: block; }\n"
  ".response-box { background: #0f172a; border-radius: 10px; padding: 12px; color: #38bdf8; font-family: monospace; font-size: 11px; white-space: pre-wrap; word-break: break-all; max-height: 160px; overflow-y: auto; }\n"
  "</style>\n"
  "</head>\n<body>\n"
  "<div class='header'>\n"
  "  <h1>HydroSense by agronex</h1>\n"
  "  <p>Gateway & Manajemen Alat Kebun Hidroponik</p>\n"
  "  <div>\n");

  if (WiFi.status() == WL_CONNECTED) {
    html += "<span class='badge badge-online'>Online (Terhubung: " + WiFi.SSID() + ")</span>";
  } else {
    html += "<span class='badge badge-ap'>Mode Mandiri (AP: SMART-HYDROPONIC)</span>";
  }

  html += F("  </div>\n</div>\n\n"
  "<!-- Navigasi Menu Multi-Akses (Tab & Link Langsung) -->\n"
  "<div class='nav-bar'>\n");

  html += "<a id='btn-tab-monitor' class='nav-link" + String(activeTab == "monitor" ? " active" : "") + "' href='/' onclick='return switchTab(\"tab-monitor\");'>1. Pemantauan</a>";
  html += "<a id='btn-tab-settings' class='nav-link" + String(activeTab == "settings" ? " active" : "") + "' href='/wifi' onclick='return switchTab(\"tab-settings\");'>2. Sambung WiFi</a>";
  html += "<a id='btn-tab-tools' class='nav-link" + String(activeTab == "tools" ? " active" : "") + "' href='/tools' onclick='return switchTab(\"tab-tools\");'>3. Tools JSON</a>";

  html += F("</div>\n\n"
  "<!-- MENU 1: PEMANTAUAN & KENDALI -->\n");
  html += "<div id='tab-monitor' class='tab-content" + String(activeTab == "monitor" ? " active" : "") + "'>\n";
  html += F("  <div class='card'>\n"
  "    <div class='grid'>\n"
  "      <div class='metric'>\n"
  "        <div class='metric-title'>Suhu Air</div>\n"
  "        <div class='metric-val'><span id='m_temp'>");
  html += String(temperature, 1);
  html += F("</span> <span>°C</span></div>\n"
  "      </div>\n"
  "      <div class='metric'>\n"
  "        <div class='metric-title'>Nutrisi TDS</div>\n"
  "        <div class='metric-val'><span id='m_tds'>");
  html += String(tds, 0);
  html += F("</span> <span>PPM</span></div>\n"
  "      </div>\n"
  "      <div class='metric'>\n"
  "        <div class='metric-title'>Status Pompa</div>\n"
  "        <div class='metric-val' id='m_pump'>");
  html += pumpStatus ? "MENYALA" : "MATI";
  html += F("</div>\n"
  "      </div>\n"
  "      <div class='metric'>\n"
  "        <div class='metric-title'>Mode Kerja</div>\n"
  "        <div class='metric-val' id='m_mode'>");
  html += autoMode ? "OTOMATIS" : "MANUAL";
  html += F("</div>\n"
  "      </div>\n"
  "    </div>\n\n"
  "    <button type='button' class='btn btn-on' onclick='apiCall(\"/pump/on\")'>HIDUPKAN POMPA SIRKULASI (ON)</button>\n"
  "    <button type='button' class='btn btn-off' onclick='apiCall(\"/pump/off\")'>MATIKAN POMPA SIRKULASI (OFF)</button>\n"
  "    <button type='button' class='btn btn-mode' onclick='apiCall(\"/mode/toggle\")'>GANTI MODE (OTOMATIS / MANUAL)</button>\n"
  "  </div>\n"
  "</div>\n\n"
  "<!-- MENU 2: PENGATURAN WIFI & SERVER -->\n");

  html += "<div id='tab-settings' class='tab-content" + String(activeTab == "settings" ? " active" : "") + "'>\n";
  html += F("  <div class='card'>\n"
  "    <h3 style='margin-top:0; font-size:15px; color:#0f172a; font-weight:800;'>Pengaturan Sambungan WiFi & Server</h3>\n"
  "    <p style='font-size:11px; color:#64748b; margin-top:2px; margin-bottom:12px;'>Hubungkan alat ke WiFi kebun dan server web HydroSense.</p>\n"
  "    <form action='/save-wifi' method='POST'>\n"
  "      <label>Kode Alat Terdaftar:</label>\n"
  "      <input type='text' name='code' value='");
  html += savedDeviceCode;
  html += F("' placeholder='Contoh: alat1sumedang' required>\n\n"
  "      <label>Pilih WiFi Sekitar (Otomatis Terdeteksi):</label>\n"
  "      <select onchange='document.getElementById(\"fieldSsid\").value=this.value;'>\n");
  html += getScannedWifiOptions();
  html += F("      </select>\n\n"
  "      <label>Nama WiFi Kebun (SSID):</label>\n"
  "      <input id='fieldSsid' type='text' name='ssid' value='");
  html += savedSSID;
  html += F("' placeholder='Nama jaringan WiFi' required>\n\n"
  "      <label>Kata Sandi WiFi:</label>\n"
  "      <input type='password' name='password' value='");
  html += savedPassword;
  html += F("' placeholder='Kata sandi WiFi'>\n\n"
  "      <label>Alamat Server API Laravel:</label>\n"
  "      <input type='text' name='server' value='");
  html += savedServerUrl;
  html += F("' placeholder='https://hydrosense.agronex.id/api/sensor/alat1sumedang/data' required>\n\n"
  "      <button type='submit' class='btn btn-dark'>SIMPAN & SAMBUNGKAN KE JARINGAN</button>\n"
  "    </form>\n"
  "  </div>\n"
  "</div>\n\n"
  "<!-- MENU 3: TOOLS FORMAT JSON MODULAR (NoSQL) -->\n");

  html += "<div id='tab-tools' class='tab-content" + String(activeTab == "tools" ? " active" : "") + "'>\n";
  html += F("  <div class='card'>\n"
  "    <h3 style='margin-top:0; font-size:15px; color:#0f172a; font-weight:800;'>Tools Pengirim Format JSON (Modular NoSQL)</h3>\n"
  "    <p style='font-size:11px; color:#64748b; margin-top:2px; margin-bottom:12px;'>Uji kirim struktur sensor atau kontrol pompa apa saja ke database.</p>\n\n"
  "    <label>Pilih Contoh Preset JSON:</label>\n"
  "    <div style='display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:12px;'>\n"
  "      <button type='button' class='btn-preset' onclick='applyPreset(\"standar\")'>1. Paket Standar</button>\n"
  "      <button type='button' class='btn-preset' onclick='applyPreset(\"lengkap\")'>2. 5 Pompa + pH</button>\n"
  "      <button type='button' class='btn-preset' onclick='applyPreset(\"pantau\")'>3. Pos Pantau</button>\n"
  "      <button type='button' class='btn-preset' onclick='applyPreset(\"live\")'>4. Nilai Sensor Saat Ini</button>\n"
  "    </div>\n\n"
  "    <label>Dokumen JSON:</label>\n"
  "    <textarea id='jsonBox' class='code-box' rows='8'></textarea>\n\n"
  "    <button type='button' id='btnKirimJson' class='btn btn-dark' onclick='submitJsonTool()'>KIRIM JSON KE SERVER</button>\n\n"
  "    <div id='toolResArea' style='display:none; margin-top:10px;'>\n"
  "      <label>Respon Balasan Server:</label>\n"
  "      <div id='toolResBox' class='response-box'>Menunggu...</div>\n"
  "    </div>\n"
  "  </div>\n"
  "</div>\n\n"
  "<script>\n"
  "function switchTab(targetId) {\n"
  "  try {\n"
  "    var tabs = document.getElementsByClassName('tab-content');\n"
  "    for (var i = 0; i < tabs.length; i++) {\n"
  "      tabs[i].style.display = 'none';\n"
  "      tabs[i].className = 'tab-content';\n"
  "    }\n"
  "    var links = document.getElementsByClassName('nav-link');\n"
  "    for (var j = 0; j < links.length; j++) {\n"
  "      links[j].className = 'nav-link';\n"
  "    }\n"
  "    var el = document.getElementById(targetId);\n"
  "    if (el) {\n"
  "      el.style.display = 'block';\n"
  "      el.className = 'tab-content active';\n"
  "    }\n"
  "    var linkEl = document.getElementById('btn-' + targetId);\n"
  "    if (linkEl) linkEl.className = 'nav-link active';\n"
  "    return false;\n"
  "  } catch(e) { return true; }\n"
  "}\n"
  "function apiCall(url) {\n"
  "  fetch(url).then(function() { pollData(); });\n"
  "}\n"
  "function pollData() {\n"
  "  fetch('/data').then(function(r){ return r.json(); }).then(function(d){\n"
  "    var t = document.getElementById('m_temp'); if(t) t.innerText = d.temperature.toFixed(1);\n"
  "    var n = document.getElementById('m_tds'); if(n) n.innerText = d.tds.toFixed(0);\n"
  "    var p = document.getElementById('m_pump'); if(p) {\n"
  "      p.innerText = d.pump ? 'MENYALA' : 'MATI';\n"
  "      p.style.color = d.pump ? '#16a34a' : '#64748b';\n"
  "    }\n"
  "    var m = document.getElementById('m_mode'); if(m) m.innerText = d.auto ? 'OTOMATIS' : 'MANUAL';\n"
  "  }).catch(function(e){ console.log(e); });\n"
  "}\n"
  "function applyPreset(type) {\n"
  "  var payload = {};\n"
  "  var code = '");
  html += savedDeviceCode;
  html += F("';\n"
  "  if (type === 'standar') {\n"
  "    payload = { device_code: code, temperature: 25.5, tds: 820.0, voltage: 1.25, pump: true, auto: true };\n"
  "  } else if (type === 'lengkap') {\n"
  "    payload = { device_code: code, temperature: 26.2, tds: 910.0, ph: 6.35, voltage: 1.30, pumps: { pompa_sirkulasi: true, pompa_pupuk_a: false, pompa_pupuk_b: false } };\n"
  "  } else if (type === 'pantau') {\n"
  "    payload = { device_code: code, temperature: 24.8, tds: 780.0, ph: 6.20, water_level: 85 };\n"
  "  } else if (type === 'live') {\n"
  "    payload = { device_code: code, temperature: ");
  html += String(temperature, 2);
  html += F(", tds: ");
  html += String(tds, 1);
  html += F(", voltage: ");
  html += String(voltage, 2);
  html += F(", pump: ");
  html += pumpStatus ? "true" : "false";
  html += F(", auto: ");
  html += autoMode ? "true" : "false";
  html += F(" };\n"
  "  }\n"
  "  document.getElementById('jsonBox').value = JSON.stringify(payload, null, 2);\n"
  "}\n"
  "function submitJsonTool() {\n"
  "  var txt = document.getElementById('jsonBox').value.trim();\n"
  "  if (!txt) { alert('Data JSON belum diisi'); return; }\n"
  "  var btn = document.getElementById('btnKirimJson');\n"
  "  var area = document.getElementById('toolResArea');\n"
  "  var box = document.getElementById('toolResBox');\n"
  "  btn.disabled = true; btn.innerText = 'Mengirim...';\n"
  "  area.style.display = 'block'; box.innerText = 'Mengirim ke server...';\n"
  "  fetch('/tools/send-json', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: txt })\n"
  "    .then(function(r){ return r.json(); })\n"
  "    .then(function(res){\n"
  "      btn.disabled = false; btn.innerText = 'KIRIM JSON KE SERVER';\n"
  "      box.innerText = JSON.stringify(res, null, 2);\n"
  "    }).catch(function(err){\n"
  "      btn.disabled = false; btn.innerText = 'KIRIM JSON KE SERVER';\n"
  "      box.innerText = 'Error: ' + err;\n"
  "    });\n"
  "}\n"
  "applyPreset('standar');\n"
  "setInterval(pollData, 2000);\n"
  "</script>\n"
  "</body>\n</html>\n");

  return html;
}

// ============================================================
// HANDLER ROUTE WEB SERVER LOKAL
// ============================================================
void handleRoot() {
  server.send(200, "text/html", buildLocalHtml("monitor"));
}

void handleWifiPage() {
  server.send(200, "text/html", buildLocalHtml("settings"));
}

void handleToolsPage() {
  server.send(200, "text/html", buildLocalHtml("tools"));
}

void handleData() {
  String json = "{";
  json += "\"temperature\":" + String(temperature, 2) + ",";
  json += "\"tds\":" + String(tds, 2) + ",";
  json += "\"voltage\":" + String(voltage, 2) + ",";
  json += "\"pump\":" + String(pumpStatus ? "true" : "false") + ",";
  json += "\"auto\":" + String(autoMode ? "true" : "false") + ",";
  json += "\"target\":" + String(targetTDS, 2) + ",";
  json += "\"wifi_connected\":" + String(WiFi.status() == WL_CONNECTED ? "true" : "false") + ",";
  json += "\"ssid\":\"" + WiFi.SSID() + "\"";
  json += "}";
  server.send(200, "application/json", json);
}

void handlePumpOn() {
  autoMode = false;
  pumpON();
  server.send(200, "text/plain", "OK");
}

void handlePumpOff() {
  autoMode = false;
  pumpOFF();
  server.send(200, "text/plain", "OK");
}

void handleModeToggle() {
  autoMode = !autoMode;
  server.send(200, "text/plain", autoMode ? "AUTO" : "MANUAL");
  Serial.print(F("[MODE DIUBAH] -> ")); Serial.println(autoMode ? F("OTOMATIS (AUTO)") : F("MANUAL"));
}

void handleSaveWifi() {
  if (server.hasArg("ssid") && server.hasArg("server")) {
    if (server.hasArg("code") && server.arg("code").length() > 0) {
      savedDeviceCode = server.arg("code");
    }
    savedSSID = server.arg("ssid");
    savedPassword = server.arg("password");
    savedServerUrl = server.arg("server");

    // Simpan permanen ke Preferences Flash NVS
    preferences.begin("hydro", false);
    preferences.putString("code", savedDeviceCode);
    preferences.putString("ssid", savedSSID);
    preferences.putString("pass", savedPassword);
    preferences.putString("server", savedServerUrl);
    preferences.end();

    String msg = "<!DOCTYPE html><html lang='id'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Disimpan</title></head>";
    msg += "<body style='font-family:sans-serif; text-align:center; padding:30px; background:#f0fdf4;'>";
    msg += "<h2 style='color:#15803d; margin-bottom:8px;'>Pengaturan Berhasil Disimpan</h2>";
    msg += "<p style='color:#334155;'>Kode Alat: <b>" + savedDeviceCode + "</b></p>";
    msg += "<p style='color:#334155;'>Menghubungkan ke WiFi: <b>" + savedSSID + "</b></p>";
    msg += "<p style='color:#64748b; font-size:13px;'>Tunggu beberapa saat, lalu segarkan halaman ini.</p>";
    msg += "<a href='/' style='display:inline-block; margin-top:10px; padding:10px 20px; background:#15803d; color:white; border-radius:8px; text-decoration:none; font-weight:700;'>Kembali ke Beranda</a>";
    msg += "</body></html>";
    server.send(200, "text/html", msg);

    Serial.println(F("\n[KONFIGURASI BARU DISIMPAN]"));
    Serial.print(F("Kode Alat : ")); Serial.println(savedDeviceCode);
    Serial.print(F("SSID      : ")); Serial.println(savedSSID);
    Serial.print(F("Server    : ")); Serial.println(savedServerUrl);

    // Jadwalkan sambung ulang tanpa memutuskan respon HTTP secara tiba-tiba
    pendingWifiReconnect = true;
    reconnectTriggerTime = millis() + 1500;
  } else {
    server.send(400, "text/plain", "Data pengaturan tidak lengkap");
  }
}

void handleToolsSendJson() {
  if (server.hasArg("plain")) {
    String payload = server.arg("plain");
    String res = sendModularTelemetry(payload);
    server.send(200, "application/json", res);
  } else {
    server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Data payload kosong\"}");
  }
}

void handleNotFound() {
  if (apModeActive) {
    server.sendHeader("Location", String("http://") + WiFi.softAPIP().toString() + "/", true);
    server.send(302, "text/plain", "");
  } else {
    server.send(404, "text/plain", "Halaman Tidak Ditemukan");
  }
}

// ============================================================
// STATUS & KONTROL RELAY SERIAL MONITOR
// ============================================================
void showRelayStatus() {
  int pinLevel = digitalRead(RELAY_PIN);
  Serial.println(F("\n=================================================="));
  Serial.println(F("              STATUS RELAY POMPA D26              "));
  Serial.println(F("=================================================="));
  Serial.print(F("Pin GPIO       : D")); Serial.println(RELAY_PIN);
  Serial.print(F("Status Sistem  : ")); Serial.println(pumpStatus ? F("MENYALA (ON)") : F("MATI (OFF)"));
  Serial.print(F("Logika Fisik   : "));
  if (pinLevel == LOW) {
    Serial.println(F("LOW (0V - Aktif / Terhubung)"));
  } else {
    Serial.println(F("HIGH (3.3V - Non-aktif / Terputus)"));
  }
  Serial.print(F("Mode Kerja     : ")); Serial.println(autoMode ? F("OTOMATIS (AUTO)") : F("MANUAL"));
  Serial.print(F("Target Nutrisi : ")); Serial.print(targetTDS, 1); Serial.println(F(" PPM"));
  Serial.println(F("=================================================="));
}

void testRelaySequence() {
  Serial.println(F("\n[UJI RELAY] Memulai uji coba relay pompa..."));
  Serial.println(F("[UJI RELAY] 1. Menyalakan relay (ON) selama 3 detik..."));
  autoMode = false;
  pumpON();
  delay(3000);

  Serial.println(F("[UJI RELAY] 2. Mematikan relay (OFF)..."));
  pumpOFF();
  delay(1000);
  Serial.println(F("[UJI RELAY] Selesai. Sistem siap."));
}

void printSerialHelp() {
  Serial.println(F("\n=================================================="));
  Serial.println(F("          PERINTAH SERIAL MONITOR ESP32           "));
  Serial.println(F("=================================================="));
  Serial.println(F("ON       : Menyalakan relay pompa (Mode Manual)"));
  Serial.println(F("OFF      : Mematikan relay pompa (Mode Manual)"));
  Serial.println(F("TOGGLE   : Membalik status relay (ON <-> OFF)"));
  Serial.println(F("STATUS   : Melihat kondisi fisik pin & relay pompa"));
  Serial.println(F("TEST     : Uji coba relay otomatis (ON 3 detik -> OFF)"));
  Serial.println(F("AUTO     : Mengaktifkan kontrol nutrisi otomatis"));
  Serial.println(F("MANUAL   : Mengaktifkan kontrol pompa manual"));
  Serial.println(F("HELP     : Menampilkan bantuan perintah ini"));
  Serial.println(F("{...}    : Mengirim payload JSON modular ke server"));
  Serial.println(F("=================================================="));
}

void handleSerialCommand() {
  if (Serial.available() == 0) return;

  String cmd = Serial.readStringUntil('\n');
  cmd.trim();

  if (cmd.length() == 0) return;

  // Cek apakah payload JSON NoSQL
  if (cmd.startsWith("{") && cmd.endsWith("}")) {
    Serial.println(F("[SERIAL TOOLS] Menerima format JSON, mengirim ke server..."));
    sendModularTelemetry(cmd);
    return;
  }

  cmd.toUpperCase();

  if (cmd == "ON") {
    autoMode = false;
    pumpON();
    Serial.println(F("[SERIAL] Perintah ON diterima. Mode diubah ke MANUAL."));
  }
  else if (cmd == "OFF") {
    autoMode = false;
    pumpOFF();
    Serial.println(F("[SERIAL] Perintah OFF diterima. Mode diubah ke MANUAL."));
  }
  else if (cmd == "TOGGLE") {
    autoMode = false;
    if (pumpStatus) pumpOFF();
    else pumpON();
    Serial.println(F("[SERIAL] Status pompa dibalik. Mode diubah ke MANUAL."));
  }
  else if (cmd == "STATUS") {
    showRelayStatus();
  }
  else if (cmd == "TEST") {
    testRelaySequence();
  }
  else if (cmd == "AUTO") {
    autoMode = true;
    Serial.println(F("[SERIAL] Mode Kerja diubah ke OTOMATIS (AUTO)."));
  }
  else if (cmd == "MANUAL") {
    autoMode = false;
    Serial.println(F("[SERIAL] Mode Kerja diubah ke MANUAL."));
  }
  else if (cmd == "HELP") {
    printSerialHelp();
  }
  else {
    Serial.print(F("[SERIAL] Perintah tidak dikenal: \""));
    Serial.print(cmd);
    Serial.println(F("\". Ketik HELP untuk daftar perintah."));
  }
}

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println(F("\n=================================================="));
  Serial.println(F("       HydroSense by agronex - ESP32 IoT          "));
  Serial.println(F("=================================================="));

  // Inisialisasi Pin Relay (Safety Glitch-Free: HIGH sebelum OUTPUT)
  digitalWrite(RELAY_PIN, RELAY_OFF);
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, RELAY_OFF);
  pumpStatus = false;
  analogReadResolution(12);

  // Inisialisasi Probe Suhu
  waterTemperature.begin();

  // Baca Memori Preferences NVS
  preferences.begin("hydro", true);
  savedDeviceCode = preferences.getString("code", savedDeviceCode);
  savedSSID = preferences.getString("ssid", "");
  savedPassword = preferences.getString("pass", "");
  savedServerUrl = preferences.getString("server", savedServerUrl);
  preferences.end();

  Serial.print(F("[MEMORI] Kode Alat      : ")); Serial.println(savedDeviceCode);
  Serial.print(F("[MEMORI] SSID Tersimpan : ")); Serial.println(savedSSID.length() > 0 ? savedSSID : "(Belum diatur)");
  Serial.print(F("[MEMORI] URL Server     : ")); Serial.println(savedServerUrl);

  // Coba sambungkan WiFi
  bool connected = false;
  if (savedSSID.length() > 0) {
    connected = connectToWiFi(savedSSID, savedPassword, 12);
  }

  // Jika gagal atau belum ada SSID tersimpan, hidupkan mode AP mandiri
  if (!connected) {
    startAccessPoint();
  } else {
    // Tetap aktifkan AP_STA agar pengguna selalu bisa akses via hotspot darurat jika diperlukan
    WiFi.mode(WIFI_AP_STA);
    WiFi.softAP(AP_SSID, AP_PASSWORD);
  }

  // Daftarkan Endpoint Web Server Lokal
  server.on("/", handleRoot);
  server.on("/wifi", handleWifiPage);
  server.on("/tools", handleToolsPage);
  server.on("/data", handleData);
  server.on("/pump/on", handlePumpOn);
  server.on("/pump/off", handlePumpOff);
  server.on("/mode/toggle", handleModeToggle);
  server.on("/save-wifi", HTTP_POST, handleSaveWifi);
  server.on("/tools/send-json", HTTP_POST, handleToolsSendJson);
  server.onNotFound(handleNotFound);

  server.begin();
  Serial.println(F("[SERVER] Web Server Lokal Aktif"));
  Serial.println(F("Ketik HELP di Serial Monitor untuk perintah kontrol pompa."));
  Serial.println(F("==================================================\n"));
}

// ============================================================
// LOOP UTAMA
// ============================================================
void loop() {
  // Tangani request HTTP dari web lokal
  server.handleClient();

  // Tangani request DNS jika AP aktif
  if (apModeActive) {
    dnsServer.processNextRequest();
  }

  unsigned long currentMillis = millis();

  // 1. Pembacaan Sensor & Kontrol Otomatis
  if (currentMillis - lastSensorRead >= SENSOR_INTERVAL) {
    lastSensorRead = currentMillis;

    temperature = readTemperature();
    tds = readTDS(temperature);

    // Jalankan logika kontrol otomatis
    automaticControl();
  }

  // 2. Cetak Angka & Status Teratur ke Serial Monitor
  if (currentMillis - lastSerialPrint >= SERIAL_PRINT_INTERVAL) {
    lastSerialPrint = currentMillis;
    printSerialStatus();
  }

  // 3. Sinkronisasi Data Telemetri ke Server Laravel
  if (currentMillis - lastServerSync >= SERVER_SYNC_INTERVAL) {
    lastServerSync = currentMillis;

    if (WiFi.status() == WL_CONNECTED) {
      sendModularTelemetry("");
    }
  }

  // 4. Watchdog Koneksi WiFi
  if (currentMillis - lastWifiCheck >= WIFI_CHECK_INTERVAL) {
    lastWifiCheck = currentMillis;

    if (WiFi.status() != WL_CONNECTED && savedSSID.length() > 0) {
      Serial.println(F("[WIFI WATCHDOG] Koneksi terputus, mencoba menyambungkan ulang..."));
      WiFi.begin(savedSSID.c_str(), savedPassword.c_str());
    }
  }

  // 5. Eksekusi Sambung Ulang WiFi Setelah Simpan Pengaturan
  if (pendingWifiReconnect && currentMillis >= reconnectTriggerTime) {
    pendingWifiReconnect = false;
    Serial.println(F("[PENGATURAN] Memulai proses penyambungan ke jaringan WiFi baru..."));
    connectToWiFi(savedSSID, savedPassword, 12);
  }

  // 6. Tools Serial Monitor: Perintah relay dan format JSON dari Serial
  handleSerialCommand();
}
