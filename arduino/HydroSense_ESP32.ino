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

// Identitas Perangkat di Dashboard Laravel (Bisa Diubah di Portal Lokal)
String savedDeviceCode = "alat1sumedang";

// ============================================================
// OBJEK & VARIABEL GLOBAL
// ============================================================
WebServer server(80);
Preferences preferences;

OneWire oneWire(TEMP_PIN);
DallasTemperature waterTemperature(&oneWire);

// Konfigurasi WiFi & Server (Tersimpan di Preferences Flash)
String savedSSID = "";
String savedPassword = "";
String savedServerUrl = "http://192.168.1.100:8000/api/sensor/alat1sumedang/data"; // Sesuaikan IP server

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
<title>HydroSense - Gateway & Tools Perangkat</title>
<style>
* { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
body { margin: 0; background: #f0fdf4; color: #1e293b; padding: 14px; }
.header { text-align: center; margin-bottom: 16px; }
.header h1 { margin: 0; color: #15803d; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
.header p { margin: 3px 0 0; color: #64748b; font-size: 12px; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
.badge-online { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.badge-ap { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.nav-tabs { display: flex; gap: 6px; margin-bottom: 14px; overflow-x: auto; padding-bottom: 2px; }
.tab-btn { flex: 1; padding: 9px 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: white; color: #475569; font-size: 12px; font-weight: 700; cursor: pointer; text-align: center; white-space: nowrap; }
.tab-btn.active { background: #15803d; color: white; border-color: #15803d; }
.card { background: white; border-radius: 16px; padding: 18px; margin-bottom: 14px; box-shadow: 0 3px 12px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
.metric { background: #f8fafc; border-radius: 12px; padding: 12px; text-align: center; border: 1px solid #e2e8f0; }
.metric-title { font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700; }
.metric-val { font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 3px; }
.metric-val span { font-size: 12px; font-weight: normal; color: #64748b; }
button.action-btn { width: 100%; border: none; padding: 11px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; margin-bottom: 8px; transition: 0.2s; }
.btn-on { background: #16a34a; color: white; }
.btn-off { background: #e11d48; color: white; }
.btn-mode { background: #2563eb; color: white; }
.btn-submit { background: #0f172a; color: white; }
.btn-preset { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-size: 11px; padding: 7px 10px; border-radius: 8px; font-weight: 700; cursor: pointer; text-align: left; }
.btn-preset:hover { background: #e2e8f0; }
input, textarea { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 10px; font-size: 13px; }
textarea.code-box { font-family: monospace; font-size: 11px; background: #0f172a; color: #34d399; line-height: 1.5; resize: vertical; }
label { font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.response-box { background: #0f172a; border-radius: 10px; padding: 12px; color: #38bdf8; font-family: monospace; font-size: 11px; white-space: pre-wrap; word-break: break-all; max-height: 160px; overflow-y: auto; }
</style>
</head>
<body>

<div class="header">
  <h1>HydroSense by agronex</h1>
  <p>Gateway & Tools Perangkat Kebun Hidroponik</p>
  <div style="margin-top:6px;">
    <span id="connBadge" class="badge badge-ap">Mode Jaringan Cadangan (AP)</span>
  </div>
</div>

<div class="nav-tabs">
  <button class="tab-btn active" onclick="openTab('tab-monitor', this)">Pemantauan & Kendali</button>
  <button class="tab-btn" onclick="openTab('tab-tools', this)">Tools Format JSON</button>
  <button class="tab-btn" onclick="openTab('tab-settings', this)">Pengaturan Jaringan</button>
</div>

<!-- Tab 1: Pemantauan & Kendali Cepat -->
<div id="tab-monitor" class="tab-content active">
  <div class="card">
    <div class="grid">
      <div class="metric">
        <div class="metric-title">Suhu Air</div>
        <div class="metric-val"><span id="temp">--</span> <span>°C</span></div>
      </div>
      <div class="metric">
        <div class="metric-title">Kepekatan Nutrisi</div>
        <div class="metric-val"><span id="tds">--</span> <span>PPM</span></div>
      </div>
      <div class="metric">
        <div class="metric-title">Status Pompa</div>
        <div class="metric-val" id="pump">--</div>
      </div>
      <div class="metric">
        <div class="metric-title">Mode Kerja</div>
        <div class="metric-val" id="mode">--</div>
      </div>
    </div>

    <button class="action-btn btn-on" onclick="callApi('/pump/on')">HIDUPKAN POMPA SIRKULASI (ON)</button>
    <button class="action-btn btn-off" onclick="callApi('/pump/off')">MATIKAN POMPA SIRKULASI (OFF)</button>
    <button class="action-btn btn-mode" onclick="callApi('/mode/toggle')">GANTI MODE KERJA (OTOMATIS / MANUAL)</button>
  </div>
</div>

<!-- Tab 2: Tools Pengirim Format JSON (NoSQL Modular) -->
<div id="tab-tools" class="tab-content">
  <div class="card">
    <h3 style="margin-top:0; font-size:15px; color:#0f172a; font-weight:800;">Tools Pengirim Format JSON (NoSQL Modular)</h3>
    <p style="font-size:11px; color:#64748b; margin-top:2px; margin-bottom:12px; line-height:1.4;">
      Fitur ini memungkinkan pengujian format JSON apa pun secara bebas. Data otomatis disimpan di database tanpa perlu mengubah tabel (konsep dokumen NoSQL).
    </p>

    <label>Pilih Contoh Paket Uji Cepat:</label>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:12px;">
      <button class="btn-preset" onclick="setPreset('standar')">1. Paket Standar (1 Pompa)</button>
      <button class="btn-preset" onclick="setPreset('lengkap')">2. Paket 5 Pompa + pH</button>
      <button class="btn-preset" onclick="setPreset('pantau')">3. Pos Pantau (Sensor Saja)</button>
      <button class="btn-preset" onclick="setPreset('live')">4. Ambil Nilai Sensor Saat Ini</button>
    </div>

    <label>Dokumen JSON yang Akan Dikirim:</label>
    <textarea id="jsonPayload" class="code-box" rows="9"></textarea>

    <button id="btnSendJson" class="action-btn btn-submit" onclick="sendJsonTool()">KIRIM FORMAT JSON KE SERVER SEKARANG</button>

    <div id="toolResultArea" style="display:none; margin-top:10px;">
      <label>Hasil Respon Server (Kontrol Otomatis Balasan):</label>
      <div id="toolResultBox" class="response-box">Menunggu hasil...</div>
    </div>
  </div>
</div>

<!-- Tab 3: Pengaturan Jaringan & Server -->
<div id="tab-settings" class="tab-content">
  <div class="card">
    <h3 style="margin-top:0; font-size:15px; color:#0f172a; font-weight:800;">Pengaturan Jaringan & Server Web</h3>
    <form action="/save-wifi" method="POST">
      <label>Kode Alat / Kunci API Instalasi:</label>
      <input type="text" name="code" value="alat1sumedang" placeholder="Contoh: alat1sumedang" required>

      <label>Nama WiFi Kebun (SSID):</label>
      <input type="text" name="ssid" placeholder="Nama WiFi" required>

      <label>Kata Sandi WiFi:</label>
      <input type="password" name="password" placeholder="Kata sandi WiFi">

      <label>Alamat Endpoint Server Laravel:</label>
      <input type="text" name="server" value="http://192.168.1.100:8000/api/sensor/alat1sumedang/data" required>

      <button type="submit" class="action-btn btn-submit">SIMPAN & SAMBUNGKAN</button>
    </form>
  </div>
</div>

<script>
let liveData = { temperature: 25.0, tds: 800.0, pump: false, auto: true };

function openTab(tabId, btn) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById(tabId).classList.add('active');
  btn.classList.add('active');
}

function setPreset(type) {
  let doc = {};
  if (type === 'standar') {
    doc = {
      device_code: "alat1sumedang",
      temperature: 25.5,
      tds: 820.0,
      voltage: 1.25,
      pump: true,
      auto: true,
      pumps: {
        pompa_sirkulasi: true
      }
    };
  } else if (type === 'lengkap') {
    doc = {
      device_code: "alat1sumedang",
      temperature: 26.2,
      tds: 910.0,
      ph: 6.35,
      voltage: 1.30,
      pumps: {
        pompa_sirkulasi: true,
        pompa_pupuk_a: false,
        pompa_pupuk_b: false,
        pompa_ph_up: false,
        pompa_ph_down: false
      }
    };
  } else if (type === 'pantau') {
    doc = {
      device_code: "alat1sumedang",
      temperature: 24.8,
      tds: 780.0,
      ph: 6.2,
      water_level: 85,
      humidity: 68
    };
  } else if (type === 'live') {
    doc = {
      device_code: "alat1sumedang",
      temperature: Number(liveData.temperature.toFixed(2)),
      tds: Number(liveData.tds.toFixed(1)),
      pump: liveData.pump,
      auto: liveData.auto,
      pumps: {
        pompa_sirkulasi: liveData.pump
      }
    };
  }
  document.getElementById('jsonPayload').value = JSON.stringify(doc, null, 2);
}

function sendJsonTool() {
  const jsonText = document.getElementById('jsonPayload').value.trim();
  if (!jsonText) {
    alert('Harap isi data JSON terlebih dahulu.');
    return;
  }
  try {
    JSON.parse(jsonText);
  } catch (e) {
    alert('Format JSON tidak valid: ' + e.message);
    return;
  }

  const btn = document.getElementById('btnSendJson');
  const resArea = document.getElementById('toolResultArea');
  const resBox = document.getElementById('toolResultBox');
  btn.disabled = true;
  btn.innerText = 'Mengirim data ke server...';
  resArea.style.display = 'block';
  resBox.innerText = 'Sedang mengirim format JSON ke server Laravel...';

  fetch('/tools/send-json', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: jsonText
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false;
    btn.innerText = 'KIRIM FORMAT JSON KE SERVER SEKARANG';
    resBox.innerText = JSON.stringify(data, null, 2);
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerText = 'KIRIM FORMAT JSON KE SERVER SEKARANG';
    resBox.innerText = 'Gagal mengirim: ' + err;
  });
}

function refreshData() {
  fetch('/data')
    .then(r => r.json())
    .then(d => {
      liveData = d;
      document.getElementById('temp').innerText = d.temperature.toFixed(1);
      document.getElementById('tds').innerText = d.tds.toFixed(0);
      document.getElementById('pump').innerText = d.pump ? 'MENYALA' : 'MATI';
      document.getElementById('pump').style.color = d.pump ? '#16a34a' : '#64748b';
      document.getElementById('mode').innerText = d.auto ? 'OTOMATIS' : 'MANUAL';
      
      const badge = document.getElementById('connBadge');
      if (d.wifi_connected) {
        badge.className = 'badge badge-online';
        badge.innerText = 'Online (Terhubung ke: ' + d.ssid + ')';
      } else {
        badge.className = 'badge badge-ap';
        badge.innerText = 'Mode Jaringan Cadangan (AP)';
      }
    }).catch(e => console.log(e));
}

function callApi(url) {
  fetch(url).then(() => refreshData());
}

setPreset('lengkap');
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
// TOOLS PENGIRIM FORMAT JSON MODULAR (NoSQL ARCHITECTURE)
// ============================================================
String sendModularTelemetry(String customJson = "") {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[API ERROR] WiFi belum terhubung, tidak dapat mengirim telemetry.");
    return "{\"status\":\"error\",\"message\":\"WiFi belum terhubung ke jaringan\"}";
  }

  HTTPClient http;
  http.begin(savedServerUrl);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");

  String requestBody;

  if (customJson.length() > 0) {
    // Gunakan payload custom langsung dari Tools Simulator
    requestBody = customJson;
  } else {
    // Siapkan Payload JSON Modular Otomatis
    StaticJsonDocument<512> doc;
    doc["device_code"] = savedDeviceCode;
    doc["temperature"] = serialized(String(temperature, 2));
    doc["tds"]         = serialized(String(tds, 2));
    // doc["ph"]       = 6.25; // Aktifkan jika instalasi memiliki sensor pH
    doc["voltage"]     = serialized(String(voltage, 2));
    doc["pump"]        = pumpStatus;
    doc["auto"]        = autoMode;

    // Daftar Kontrol Pompa (Bisa 1, 2, atau 5 pompa sesuai instalasi alat)
    JsonObject pumps = doc.createNestedObject("pumps");
    pumps["pompa_sirkulasi"] = pumpStatus;

    doc["ip_address"]  = WiFi.localIP().toString();
    doc["wifi_ssid"]   = WiFi.SSID();

    serializeJson(doc, requestBody);
  }

  int httpCode = http.POST(requestBody);
  String response = "";

  if (httpCode == HTTP_CODE_OK || httpCode == 201) {
    response = http.getString();
    
    // Parse Respon Kontrol dari Laravel
    StaticJsonDocument<512> resDoc;
    DeserializationError error = deserializeJson(resDoc, response);

    if (!error && resDoc["status"] == "success" && resDoc.containsKey("control")) {
      JsonObject control = resDoc["control"];
      
      bool serverAuto = control["auto"];
      float serverTarget = control["target_tds"];
      bool serverPump = control["pump"];

      // Update parameter dari Dashboard Web Laravel
      autoMode = serverAuto;
      targetTDS = serverTarget;

      // Cek apakah ada kontrol multi-pompa (NoSQL dynamic response)
      if (control.containsKey("pumps")) {
        JsonObject serverPumps = control["pumps"];
        if (serverPumps.containsKey("pompa_sirkulasi")) {
          bool state = serverPumps["pompa_sirkulasi"];
          if (!autoMode) {
            if (state && !pumpStatus) pumpON();
            else if (!state && pumpStatus) pumpOFF();
          }
        }
      } else if (!autoMode) {
        if (serverPump && !pumpStatus) {
          pumpON();
        } else if (!serverPump && pumpStatus) {
          pumpOFF();
        }
      }
      
      Serial.println("[API SYNC] Sukses! Sinkronisasi kontrol modular JSON aktif.");
    }
  } else {
    Serial.print("[API SYNC] HTTP Error Code: ");
    Serial.println(httpCode);
    response = "{\"status\":\"error\",\"http_code\":" + String(httpCode) + "}";
  }

  http.end();
  return response;
}

void syncWithLaravelServer() {
  sendModularTelemetry("");
}

void handleToolsSendJson() {
  if (server.hasArg("plain")) {
    String payload = server.arg("plain");
    String res = sendModularTelemetry(payload);
    server.send(200, "application/json", res);
  } else {
    server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Payload JSON kosong\"}");
  }
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
    if (server.hasArg("code") && server.arg("code").length() > 0) {
      savedDeviceCode = server.arg("code");
    }
    savedSSID = server.arg("ssid");
    savedPassword = server.arg("password");
    savedServerUrl = server.arg("server");

    // Simpan permanen ke Preferences NVS
    preferences.begin("hydro", false);
    preferences.putString("code", savedDeviceCode);
    preferences.putString("ssid", savedSSID);
    preferences.putString("pass", savedPassword);
    preferences.putString("server", savedServerUrl);
    preferences.end();

    String msg = "<!DOCTYPE html><html><body style='font-family:sans-serif; text-align:center; padding:40px; background:#f0fdf4;'>";
    msg += "<h2 style='color:#15803d;'>Pengaturan Berhasil Disimpan!</h2>";
    msg += "<p>Kode Alat: <b>" + savedDeviceCode + "</b></p>";
    msg += "<p>ESP32 sedang mencoba menyambungkan ke: <b>" + savedSSID + "</b></p>";
    msg += "<p>Tunggu 5 detik, lalu buka kembali browser.</p>";
    msg += "<a href='/' style='display:inline-block; padding:10px 20px; background:#15803d; color:white; border-radius:8px; text-decoration:none;'>Kembali ke Beranda</a>";
    msg += "</body></html>";
    server.send(200, "text/html", msg);

    Serial.println("[CONFIG] Kredensial WiFi & Kode Alat baru berhasil disimpan.");
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
  savedDeviceCode = preferences.getString("code", savedDeviceCode);
  savedSSID = preferences.getString("ssid", "");
  savedPassword = preferences.getString("pass", "");
  savedServerUrl = preferences.getString("server", savedServerUrl);
  preferences.end();

  Serial.print("[CONFIG] Kode Alat      : "); Serial.println(savedDeviceCode);
  Serial.print("[CONFIG] SSID Tersimpan  : "); Serial.println(savedSSID.length() > 0 ? savedSSID : "(Belum ada)");
  Serial.print("[CONFIG] API Endpoint    : "); Serial.println(savedServerUrl);

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
  server.on("/tools/send-json", HTTP_POST, handleToolsSendJson);

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

  // 4. Tools Serial: Kirim format JSON modular jika diketik di Serial Monitor
  if (Serial.available()) {
    String serialInput = Serial.readStringUntil('\n');
    serialInput.trim();
    if (serialInput.length() > 0 && serialInput.startsWith("{") && serialInput.endsWith("}")) {
      Serial.println("[TOOLS SERIAL] Mengirim format JSON modular ke server...");
      sendModularTelemetry(serialInput);
    }
  }
}
