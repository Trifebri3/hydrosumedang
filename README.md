# HydroSense by agronex

Sistem IoT Cerdas untuk Monitoring dan Kontrol Nutrisi Hidroponik berbasis **ESP32**, **Laravel 12**, dan **MySQL**.

---

## Fitur Utama
1. **Monitoring Real-time & Modular NoSQL**:
   - Suhu Air (°C), TDS Nutrisi (ppm), Derajat Keasaman pH air, dan parameter sensor modular tambahan tanpa perlu migrasi database berulang kali.
   - Status kendali pompa multi-saluran (hingga banyak pompa sirkulasi/nutrisi).
   - Penyimpanan dokumen JSON mentah (NoSQL style payload) yang fleksibel.
2. **Manajemen Pengguna & Alat Terisolasi**:
   - Panel Manajemen Admin terpusat (`/admin`) untuk operasi CRUD lengkap Alat dan Pengguna.
   - Setiap alat wajib terikat pada 1 pengguna pemilik; pengguna lain tidak memiliki akses (HTTP 403 Forbidden).
3. **Kontrol Dua Arah**:
   - Kontrol saklar pompa manual atau multi-pompa via antarmuka web.
   - Pilihan Mode Kerja: **AUTO** dan **MANUAL**.
   - Pengaturan Target TDS dengan slider dan preset tanaman.
4. **Mekanisme Fallback WiFi Mandiri**:
   - Access Point darurat: `SMART-HYDROPONIC` (Sandi: `12345678`), Portal lokal `http://192.168.4.1`.
   - Alat uji format JSON & simulator modular bawaan pada portal lokal perangkat.
5. **Grafik & Riwayat Data**:
   - Visualisasi tren waktu nyata TDS & Suhu dengan Chart.js.
   - Riwayat data telemetri tersimpan otomatis di database.

---

## Pinout ESP32
- **TDS Sensor Analog:** GPIO 34
- **DS18B20 Data:** GPIO 4 (Pull-up resistor 4.7k ke 3.3V)
- **Relay IN:** GPIO 26 (Active LOW)

---

## Endpoint API Laravel
- `POST /api/sensor/data` : Pengiriman telemetri modular ESP32 & penerimaan status kontrol terbaru.
- `GET /api/sensor/latest` : Dashboard membaca data perangkat dan telemetri terakhir.
- `POST /api/sensor/control` : Perintah kontrol pompa, mode, dan target TDS dari web.
- `GET /api/sensor/history` : Riwayat pembacaan sensor untuk grafik tren.

---

## Instalasi & Menjalankan

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

