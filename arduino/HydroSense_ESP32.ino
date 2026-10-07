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
  2. Fallback Mode Mandiri (Access Point):
     - Jika belum ada WiFi atau koneksi putus, ESP32 memancarkan
       WiFi sendiri: "SMART-HYDROPONIC" (Password: 12345678).
     - Buka browser ke: http://192.168.4.1
     - Terdapat form untuk scan & ganti WiFi serta IP server.
     - Kredensial WiFi tersimpan permanen di memori Flash (Preferences NVS).
  3. Kontrol Dua Arah (Bidirectional Control):
     - ESP32 mengirim data sensor (POST /api/sensor/data).
     - Server Laravel membalas status Pompa, Mode Auto, & Target TDS
       yang dikontrol dari Web Dashboard.
  4. Web Dashboard Lokal & Online sinkron.

  LIBRARY YANG DIPERLUKAN:
  - OneWire (oleh Paul Stoffregen)
  - DallasTemperature (oleh Miles Burton)
  - ArduinoJson (oleh Benoit Blanchon - versi 6 atau 7)
  ============================================================
*/

#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <ArduinoJson.h>
#include <math.h>

// ============================================================
// PIN CONFIGURATION
// ============================================================
#define TDS_PIN       34
#define TEMP_PIN      4
#define RELAY_PIN     26

// ============================================================
// RELAY CONFIGURATION
// ============================================================
#define RELAY_ON      LOW
#define RELAY_OFF     HIGH

// ============================================================
// ACCESS POINT DEFAULT (FALLBACK JIKA OFFLINE)
// ============================================================
const char* AP_SSID = "SMART-HYDROPONIC";
const char* AP_PASSWORD = "12345678";

// Identitas Perangkat di Dashboard Laravel
const char* DEVICE_CODE = "HYDROSENSE-01";

// ============================================================
// OBJEK & VARIABEL GLOBAL
// ============================================================
WebServer server(80);
Preferences preferences;

OneWire oneWire(TEMP_PIN);
DallasTemperature waterTemperature(&oneWire);

// Konfigurasi WiFi & Server (Tersimpan di Preferences)
String savedSSID = "";
String savedPassword = "";
String savedServerUrl = "http://192.168.1.100:8000/api/sensor/data"; // Sesuaikan IP laptop/server

bool isWifiConnected = false;
bool apModeActive = false;

// Variabel Sensor & Aktuator
float temperature = 0.0;
float tds = 0.0;
float voltage = 0.0;

bool pumpStatus = false;
bool autoMode = true;
float targetTDS = 800.0;

// Timer Pembacaan & Pengiriman Data
unsigned long lastSensorRead = 0;
const unsigned long SENSOR_INTERVAL = 2000;      // 2 detik baca sensor

unsigned long lastServerSync = 0;
const unsigned long SERVER_SYNC_INTERVAL = 3000;  // 3 detik kirim ke Laravel

unsigned long lastWifiCheck = 0;
const unsigned long WIFI_CHECK_INTERVAL = 15000; // 15 detik cek status koneksi

// ============================================================
// HTML WEB SERVER LOKAL & PENGATURAN WIFI (FALLBACK)
// ============================================================
const char LOCAL_PAGE[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html lang="id">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>HydroSense - Local Gateway</title>
<style>
* { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
body { margin: 0; background: #f0fdf4; color: #1e293b; padding: 15px; }
.card { background: white; border-radius: 18px; padding: 20px; margin-bottom: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.header { text-align: center; margin-bottom: 20px; }
.header h1 { margin: 0; color: #15803d; font-size: 22px; }
.header p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
.badge { display: inline-block; padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
.badge-online { background: #dcfce7; color: #166534; }
.badge-ap { background: #fef3c7; color: #92400e; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.metric { background: #f8fafc; border-radius: 14px; padding: 14px; text-align: center; border: 1px solid #e2e8f0; }
.metric-title { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; }
.metric-val { font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 4px; }
.metric-val span { font-size: 13px; font-weight: normal; color: #64748b; }
button { width: 100%; border: none; padding: 12px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; margin-bottom: 8px; }
.btn-on { background: #16a34a; color: white; }
.btn-off { background: #e11d48; color: white; }
.btn-mode { background: #2563eb; color: white; }
.btn-submit { background: #0f172a; color: white; }
input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 12px; font-size: 14px; }
label { font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px; }
</style>
</head>
<body>

<div class="header">
  <h1>🌱 HydroSense by agronex</h1>
  <p>ESP32 Local Gateway & Configuration</p>
  <div style="margin-top:8px;">
    <span id="connBadge" class="badge badge-ap">Mode Access Point (AP)</span>
  </div>
</div>

<div class="card">
  <div class="grid">
    <div class="metric">
      <div class="metric-title">Suhu Air</div>
      <div class="metric-val"><span id="temp">--</span> <span>°C</span></div>
    </div>
    <div class="metric">
      <div class="metric-title">TDS Nutrisi</div>
      <div class="metric-val"><span id="tds">--</span> <span>ppm</span></div>
    </div>
    <div class="metric">
      <div class="metric-title">Status Pompa</div>
      <div class="metric-val" id="pump">--</div>
    </div>
    <div class="metric">
      <div class="metric-title">Mode Kontrol</div>
      <div class="metric-val" id="mode">--</div>
    </div>
  </div>

  <button class="btn-on" onclick="callApi('/pump/on')">HIDUPKAN POMPA (ON)</button>
  <button class="btn-off" onclick="callApi('/pump/off')">MATIKAN POMPA (OFF)</button>
  <button class="btn-mode" onclick="callApi('/mode/toggle')">GANTI MODE (AUTO / MANUAL)</button>
</div>

<div class="card">
  <h3 style="margin-top:0; font-size:16px; color:#0f172a;">⚙️ Pengaturan WiFi & Server Laravel</h3>
  <form action="/save-wifi" method="POST">
    <label>Nama WiFi (SSID):</label>
    <input type="text" name="ssid" placeholder="Contoh: WiFi_Rumah" required>

    <label>Password WiFi:</label>
    <input type="password" name="password" placeholder="Password WiFi">

    <label>URL Endpoint Laravel:</label>
    <input type="text" name="server" value="http://192.168.1.100:8000/api/sensor/data" placeholder="http://<IP_KOMPUTER>:8000/api/sensor/data" required>

    <button type="submit" class="btn-submit">💾 SIMPAN & SAMBUNGKAN</button>
  </form>
</div>

<script>
function refreshData() {
  fetch('/data')
    .then(r => r.json())
    .then(d => {
      document.getElementById('temp').innerText = d.temperature.toFixed(1);
      document.getElementById('tds').innerText = d.tds.toFixed(0);
      document.getElementById('pump').innerText = d.pump ? 'ON' : 'OFF';
      document.getElementById('pump').style.color = d.pump ? '#16a34a' : '#64748b';
      document.getElementById('mode').innerText = d.auto ? 'AUTO' : 'MANUAL';
      
      const badge = document.getElementById('connBadge');
      if (d.wifi_connected) {
        badge.className = 'badge badge-online';
        badge.innerText = 'Online (Terhubung ke: ' + d.ssid + ')';
      } else {
        badge.className = 'badge badge-ap';
        badge.innerText = 'Mode Fallback AP (SMART-HYDROPONIC)';
      }
    }).catch(e => console.log(e));
}
function callApi(url) {
  fetch(url).then(() => refreshData());
}
refreshData();
setInterval(refreshData, 2000);
</script>
</body>
</html>
)rawliteral";

// ============================================================
// KONTROL POMPA RELAY
// ============================================================
void pumpON() {
  digitalWrite(RELAY_PIN, RELAY_ON);
  pumpStatus = true;
  Serial.println("[POMPA] -> AKTIF (ON)");
}

void pumpOFF() {
  digitalWrite(RELAY_PIN, RELAY_OFF);
  pumpStatus = false;
  Serial.println("[POMPA] -> MATI (OFF)");
}

// ============================================================
// BACA SUHU DS18B20
// ============================================================
float readTemperature() {
  waterTemperature.requestTemperatures();
  float temp = waterTemperature.getTempCByIndex(0);
  if (temp == DEVICE_DISCONNECTED_C || temp < -40.0) {
    return 25.0; // Nilai default wajar jika sensor belum terpasang
  }
  return temp;
}

// ============================================================
// BACA TDS DENGAN KOMPENSASI SUHU
// ============================================================
float readTDS(float temp) {
  int adcValue = analogRead(TDS_PIN);
  voltage = adcValue * (3.3 / 4095.0);

  // Kompensasi koefisien suhu terhadap 25°C
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
// LOGIKA OTOMATISASI TDS
// ============================================================
void automaticControl() {
  if (!autoMode) return;

  // Jika TDS di bawah target -> Hidupkan pompa nutrisi
  // Jika TDS sudah memenuhi -> Matikan pompa
  if (tds < targetTDS) {
    pumpON();
  } else {
    pumpOFF();
  }
}

// ============================================================
// AKTIFKAN MODE AP (ACCESS POINT FALLBACK)
// ============================================================
void startAccessPoint() {
  WiFi.mode(WIFI_AP_STA);
  WiFi.softAP(AP_SSID, AP_PASSWORD);
  apModeActive = true;
  
  Serial.println("\n[AP MODE] Access Point Aktif!");
  Serial.print("[AP MODE] SSID     : "); Serial.println(AP_SSID);
  Serial.print("[AP MODE] Password : "); Serial.println(AP_PASSWORD);
  Serial.print("[AP MODE] IP Portal: "); Serial.println(WiFi.softAPIP());
}

// ============================================================
// KONEKSI KE WIFI STA
// ============================================================
bool connectToWiFi(String ssid, String pass, int timeoutSeconds = 15) {
  if (ssid.length() == 0) {
    Serial.println("[WIFI] SSID belum diatur.");
    return false;
  }

  Serial.print("[WIFI] Menyambungkan ke: ");
  Serial.println(ssid);

  WiFi.begin(ssid.c_str(), pass.c_str());

  int elapsed = 0;
  while (WiFi.status() != WL_CONNECTED && elapsed < (timeoutSeconds * 2)) {
    delay(500);
    Serial.print(".");
    elapsed++;
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    isWifiConnected = true;
    Serial.println("[WIFI] Berhasil Terhubung!");
    Serial.print("[WIFI] IP ESP32 : "); Serial.println(WiFi.localIP());
    return true;
  } else {
    isWifiConnected = false;
    Serial.println("[WIFI] Gagal terhubung ke WiFi.");
    return false;
  }
}

// ============================================================
// KIRIM DATA KE LARAVEL API & TERIMA KONTROL TERBARU
// ============================================================
void syncWithLaravelServer() {
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }

  HTTPClient http;
  http.begin(savedServerUrl);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");

  // Siapkan Payload JSON untuk Laravel
  StaticJsonDocument<300> doc;
  doc["device_code"] = DEVICE_CODE;
  doc["temperature"] = serialized(String(temperature, 2));
  doc["tds"]         = serialized(String(tds, 2));
  doc["voltage"]     = serialized(String(voltage, 2));
  doc["pump"]        = pumpStatus;
  doc["auto"]        = autoMode;
  doc["ip_address"]  = WiFi.localIP().toString();
  doc["wifi_ssid"]   = WiFi.SSID();

  String requestBody;
  serializeJson(doc, requestBody);

  int httpCode = http.POST(requestBody);

  if (httpCode == HTTP_CODE_OK || httpCode == 201) {
    String response = http.getString();
    
    // Parse Respon Kontrol dari Laravel
    StaticJsonDocument<400> resDoc;
    DeserializationError error = deserializeJson(resDoc, response);

    if (!error && resDoc["status"] == "success" && resDoc.containsKey("control")) {
      JsonObject control = resDoc["control"];
      
      bool serverAuto = control["auto"];
      float serverTarget = control["target_tds"];
      bool serverPump = control["pump"];

      // Update parameter dari Dashboard Web Laravel
      autoMode = serverAuto;
      targetTDS = serverTarget;

      // Jika dalam mode manual, ikuti status pompa dari server
      if (!autoMode) {
        if (serverPump && !pumpStatus) {
          pumpON();
        } else if (!serverPump && pumpStatus) {
          pumpOFF();
        }
      }
      
      Serial.println("[API SYNC] Sukses! Sinkronisasi kontrol dari Laravel aktif.");
    }
  } else {
    Serial.print("[API SYNC] HTTP Error Code: ");
    Serial.println(httpCode);
  }

  http.end();
}

// ============================================================
// HANDLER ROUTE WEB SERVER LOKAL
// ============================================================
void handleRoot() {
  server.send(200, "text/html", LOCAL_PAGE);
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
}

void handleSaveWifi() {
  if (server.hasArg("ssid") && server.hasArg("server")) {
    savedSSID = server.arg("ssid");
    savedPassword = server.arg("password");
    savedServerUrl = server.arg("server");

    // Simpan permanen ke Preferences NVS
    preferences.begin("hydro", false);
    preferences.putString("ssid", savedSSID);
    preferences.putString("pass", savedPassword);
    preferences.putString("server", savedServerUrl);
    preferences.end();

    String msg = "<!DOCTYPE html><html><body style='font-family:sans-serif; text-align:center; padding:40px; background:#f0fdf4;'>";
    msg += "<h2 style='color:#15803d;'>WiFi Berhasil Disimpan!</h2>";
    msg += "<p>ESP32 sedang mencoba menyambungkan ke: <b>" + savedSSID + "</b></p>";
    msg += "<p>Tunggu 5 detik, lalu buka kembali browser.</p>";
    msg += "<a href='/' style='display:inline-block; padding:10px 20px; background:#15803d; color:white; border-radius:8px; text-decoration:none;'>Kembali ke Beranda</a>";
    msg += "</body></html>";
    server.send(200, "text/html", msg);

    Serial.println("[CONFIG] Kredensial WiFi baru berhasil disimpan.");
    delay(1000);

    // Coba konek ke WiFi baru
    connectToWiFi(savedSSID, savedPassword, 15);
  } else {
    server.send(400, "text/plain", "Data tidak lengkap");
  }
}

// ============================================================
// SETUP
// ============================================================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n========================================");
  Serial.println("  HydroSense by agronex - ESP32 IoT");
  Serial.println("========================================");

  // Setup Pin
  pinMode(RELAY_PIN, OUTPUT);
  pumpOFF();
  analogReadResolution(12);

  // Inisialisasi Sensor Suhu
  waterTemperature.begin();

  // Buka memori Preferences NVS
  preferences.begin("hydro", true);
  savedSSID = preferences.getString("ssid", "");
  savedPassword = preferences.getString("pass", "");
  savedServerUrl = preferences.getString("server", savedServerUrl);
  preferences.end();

  Serial.print("[CONFIG] SSID Tersimpan: "); Serial.println(savedSSID.length() > 0 ? savedSSID : "(Belum ada)");
  Serial.print("[CONFIG] API Endpoint  : "); Serial.println(savedServerUrl);

  // Coba sambungkan WiFi
  bool connected = false;
  if (savedSSID.length() > 0) {
    connected = connectToWiFi(savedSSID, savedPassword, 12);
  }

  // Jika gagal atau belum ada WiFi, hidupkan Access Point Mandiri (Fallback)
  if (!connected) {
    Serial.println("[WIFI] Menyalakan Fallback Access Point...");
    startAccessPoint();
  } else {
    // Tetap aktifkan AP_STA agar pengguna tetap bisa akses via hotspot darurat jika diperlukan
    WiFi.mode(WIFI_AP_STA);
    WiFi.softAP(AP_SSID, AP_PASSWORD);
  }

  // Daftarkan Routes Web Server Lokal ESP32
  server.on("/", handleRoot);
  server.on("/data", handleData);
  server.on("/pump/on", handlePumpOn);
  server.on("/pump/off", handlePumpOff);
  server.on("/mode/toggle", handleModeToggle);
  server.on("/save-wifi", HTTP_POST, handleSaveWifi);

  server.begin();
  Serial.println("[SERVER] Web Server Lokal Aktif pada port 80");
  Serial.println("========================================\n");
}

// ============================================================
// LOOP UTAMA
// ============================================================
void loop() {
  // Tangani request HTTP dari web lokal
  server.handleClient();

  unsigned long currentMillis = millis();

  // 1. Pembacaan Sensor
  if (currentMillis - lastSensorRead >= SENSOR_INTERVAL) {
    lastSensorRead = currentMillis;

    temperature = readTemperature();
    tds = readTDS(temperature);

    // Jalankan logika otomatis jika mode AUTO aktif
    automaticControl();
  }

  // 2. Sinkronisasi Data & Kontrol dengan Laravel Server
  if (currentMillis - lastServerSync >= SERVER_SYNC_INTERVAL) {
    lastServerSync = currentMillis;

    if (WiFi.status() == WL_CONNECTED) {
      syncWithLaravelServer();
    }
  }

  // 3. Pengecekan Rutin Status WiFi (Koneksi Mandiri)
  if (currentMillis - lastWifiCheck >= WIFI_CHECK_INTERVAL) {
    lastWifiCheck = currentMillis;

    if (WiFi.status() != WL_CONNECTED && savedSSID.length() > 0) {
      Serial.println("[WIFI WATCHDOG] Koneksi terputus, mencoba menyambungkan kembali...");
      WiFi.begin(savedSSID.c_str(), savedPassword.c_str());
    }
  }
}
