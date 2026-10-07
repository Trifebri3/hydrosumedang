# 🌱 HydroSense by agronex

Sistem IoT Cerdas untuk Monitoring dan Kontrol Nutrisi Hidroponik berbasis **ESP32**, **Laravel 12**, dan **MySQL**.

---

## ✨ Fitur Utama
1. **Monitoring Real-time**:
   - Suhu Air (°C) menggunakan sensor waterproof DS18B20.
   - Nilai TDS Nutrisi (ppm) menggunakan analog TDS Sensor dengan kompensasi suhu otomatis.
   - Tegangan Sensor (V) pada pin ADC ESP32.
   - Status Relay Pompa Nutrisi (ON / OFF).
2. **Kontrol Dua Arah (Bidirectional Control)**:
   - Kontrol Pompa Manual (ON / OFF) langsung dari Dashboard Web.
   - Pilihan Mode Kerja: **AUTO** (pompa aktif otomatis jika TDS < target) dan **MANUAL**.
   - Pengaturan Target TDS (400 - 1800 ppm) dengan slider & preset tanaman (Selada, Pakcoy, Bayam, Tomat).
3. **Mekanisme Fallback WiFi Mandiri (Offline Mode)**:
   - Jika ESP32 tidak terhubung ke WiFi atau internet putus, ESP32 otomatis menyalakan Access Point sendiri:
     - **SSID:** `SMART-HYDROPONIC`
     - **Password:** `12345678`
     - **IP Web Portal:** `http://192.168.4.1`
   - Melalui web portal lokal tersebut, pengguna dapat mengganti SSID & Password WiFi serta URL Server tanpa perlu memprogram ulang ESP32. Kredensial tersimpan permanen di memori Flash NVS (`Preferences`).
4. **Grafik & Riwayat Data**:
   - Visualisasi tren waktu nyata TDS & Suhu dengan Chart.js.
   - Tabel histori pembacaan sensor tersimpan di database MySQL.

---

## 📌 Pinout ESP32
- **TDS Sensor Analog:** GPIO 34
- **DS18B20 Data:** GPIO 4 (Pull-up resistor 4.7kΩ ke 3.3V)
- **Relay IN:** GPIO 26 (Active LOW)

---

## 🚀 Endpoint API Laravel
- `POST /api/sensor/data` : ESP32 mengirim telemetri & menerima status kontrol terbaru.
- `GET /api/sensor/latest` : Dashboard mengambil data perangkat dan telemetri terakhir.
- `POST /api/sensor/control` : Perintah kontrol pompa, mode, dan target TDS dari web.
- `GET /api/sensor/history` : Riwayat pembacaan sensor untuk grafik tren.

---

## 🛠️ Instalasi & Menjalankan

### 1. Web Server Laravel
```bash
composer install
npm install
npm run build
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```
Buka dashboard web di: `http://localhost:8000`

### 2. Firmware ESP32
Buka file `arduino/HydroSense_ESP32.ino` di Arduino IDE, pastikan library terpasang:
- `OneWire`
- `DallasTemperature`
- `ArduinoJson` (v6 atau v7)

Upload ke board ESP32 Anda.

