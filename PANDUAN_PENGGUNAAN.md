# Panduan Penggunaan dan Pengelolaan HydroSense by agronex

Dokumen panduan resmi sistem pemantauan dan kendali kebun hidroponik cerdas terpadu HydroSense. Disusun menggunakan bahasa yang mudah dipahami oleh seluruh pengelola kebun, petani mitra, dan administrator.

---

## 1. Panduan Akun dan Hak Akses

Platform HydroSense menggunakan sistem multi-kebun terpusat yang bersifat privat (tertutup). Tidak ada tampilan publik bebas—siapa pun wajib masuk akun terlebih dahulu untuk mengakses data pemantauan dan kontrol kebun.

### Tipe Akun:
1. **Akun Petani / Pemilik Kebun:**
   - Dikhususkan untuk petani atau penanggung jawab lahan di lokasi tertentu (contoh: Petani Sumedang).
   - Saat masuk, petani hanya melihat data dan mengontrol alat pada instalasi kebun miliknya sendiri.
   - Akun bawaan kebun Sumedang:
     - Nama Pengguna: `usersumedang` atau Email: `sumedang@agronex.id`
     - Kata Sandi: `password123`

2. **Akun Administrator:**
   - Memiliki kendali penuh atas seluruh instalasi kebun di seluruh daerah.
   - Dapat menambah instalasi baru, membuat akun petani baru, menghubungkan akun ke alat tertentu, dan menyesuaikan fitur tiap alat.
   - Akun bawaan Administrator:
     - Nama Pengguna: `admin` atau Email: `admin@agronex.id`
     - Kata Sandi: `admin123`

---

## 2. Cara Pemantauan (Monitoring) Kebun

Setelah masuk ke halaman utama, Anda akan melihat data langsung dari kebun hidroponik:

1. **Status Sambungan Alat:**
   - Tanda hijau bertuliskan **Terhubung** atau **Sistem Normal** menandakan alat di kebun aktif mengirimkan data berkala.
   - Tanda oranye bertuliskan **Terputus** menandakan alat belum tersambung ke jaringan WiFi internet.

2. **Kepekatan Nutrisi (PPM):**
   - Menampilkan kepekatan larutan nutrisi tanaman. Angka ini memastikan tanaman mendapatkan asupan pupuk yang tepat sesuai fase pertumbuhannya.

3. **Suhu Air (°C):**
   - Menampilkan derajat suhu air pada tandon nutrisi. Rentang yang disarankan adalah 22.0 hingga 28.0 °C agar akar tanaman tetap segar dan mampu menyerap nutrisi dengan optimal.

4. **Grafik dan Riwayat Berkala:**
   - Menampilkan pergerakan naik-turun nutrisi dan suhu secara langsung dalam bentuk grafik garis dan tabel riwayat pencatatan.

---

## 3. Cara Menyalakan dan Mematikan Pompa Sirkulasi via Web

Platform menyediakan dua cara pengoperasian pompa sirkulasi air:

### A. Kendali Langsung via Tombol Web:
- **Nyalakan Pompa:** Klik tombol hijau bertuliskan **Nyalakan Pompa**. Pompa di kebun akan segera menyala mengalirkan nutrisi ke talang tanaman.
- **Matikan Pompa:** Klik tombol merah bertuliskan **Matikan Pompa**. Pompa akan berhenti seketika (berguna saat pengurasan tandon atau perawatan pipa).
- **Tombol Cepat di Layar Ponsel:** Pada tampilan ponsel pintar, Anda dapat menekan tombol bulat hijau di bagian tengah bawah layar untuk saklar cepat.

### B. Mode Otomatis vs Mode Manual:
- **Mode Otomatis:**
  Pompa dikontrol mandiri oleh sistem berdasarkan target nutrisi yang Anda tentukan. Jika nutrisi belum mencapai batas yang disyaratkan, sistem akan mengalirkan larutan secara mandiri.
- **Mode Manual:**
  Petani menentukan sendiri kapan pompa harus menyala atau mati tanpa intervensi otomatis sistem.
- **Catatan:** Jika sistem sedang berada pada Mode Otomatis, lalu Anda menekan tombol saklar pompa secara manual, sistem akan otomatis beralih ke Mode Manual demi keamanan.

### C. Menentukan Target Kebutuhan Nutrisi Tanaman:
- Geser tuas pengatur atau ketik angka target nutrisi (PPM) yang Anda inginkan, lalu klik **Simpan Target**.
- Tersedia tombol rekomendasi tanaman siap pakai:
  - Selada: 700 PPM
  - Pakcoy: 900 PPM
  - Bayam: 1100 PPM
  - Tomat: 1500 PPM

---

## 4. Panel Manajemen Terpusat Administrator (/admin)

Untuk memudahkan pemantauan dan administrasi, sistem memisahkan tampilan monitoring kebun dengan **Panel Manajemen** khusus Administrator.

### Akses Panel Manajemen:
1. Masuk menggunakan akun **Administrator** (`admin@agronex.id`).
2. Klik tombol **Panel Manajemen** di sudut kanan atas layar (atau kunjungi URL `/admin`).
3. Pada panel ini terdapat tiga tab utama:
   - **Daftar Alat (CRUD Alat):** Tambah alat baru, ubah konfigurasi & fitur, hubungkan ke akun petani, atau hapus alat beserta data telemetrinya.
   - **Daftar Pengguna (CRUD Pengguna):** Buat akun petani/pengelola baru, perbarui nama/email/role/kata sandi, atau hapus akun pengguna (akun admin yang sedang login dilindungi dari penghapusan mandiri).
   - **Simulator JSON & Modular NoSQL:** Menguji pengiriman payload JSON dinamis ke alat tanpa memerlukan perangkat fisik.

---

## 5. Aturan Pengikatan Alat & Keamanan Akses (Isolasi Pengguna)

Sistem menerapkan aturan keamanan multi-tenant yang ketat:
1. **Wajib Terikat ke 1 Pengguna:**
   Setiap alat yang dibuat **wajib dipilihkan 1 akun pemilik**. Tidak ada alat liar atau mengambang tanpa pemilik.
2. **Isolasi Akses Total:**
   - Petani hanya dapat melihat alat dan riwayat grafik kebun yang terikat pada akun miliknya.
   - Petani tidak dapat mengakses atau memanipulasi alat milik petani lain. Upaya membuka alat lain akan ditolak sistem dengan respon `403 Forbidden` (Akses Ditolak).
   - Jika petani belum memiliki instalasi kebun yang terhubung, sistem akan menampilkan halaman ramah yang mengarahkan petani untuk menghubungi Administrator.
3. **Administrator:**
   Memiliki hak untuk melihat seluruh instalasi di lapangan serta mengalihkan kepemilikan alat ke petani lain kapan pun dibutuhkan melalui formulir Edit Alat.

---

## 6. Format Data Modular NoSQL (Dukungan Sensor Tambahan & Pompa Multi-Saluran)

Sistem menggunakan konsep dokumen NoSQL JSON yang fleksibel:
1. **Sensor & Pompa Fleksibel:**
   Alat dapat mengirimkan data parameter apa saja (misal: sensor pH, DO, intensitas cahaya, kelembaban, serta status hingga 5 pompa relay pupuk) dalam satu dokumen JSON.
2. **Tanpa Perlu Migrasi Database Berulang:**
   Data JSON mentah disimpan seutuhnya di kolom dokumen database (`raw_payload` dan `last_payload`). Kolom-kolom parameter baru langsung tampil secara dinamis pada kartu dashboard dan inspektur JSON.
3. **Contoh Format JSON:**
```json
{
  "device": "alat1sumedang",
  "tds": 845.0,
  "temp": 24.5,
  "ph": 6.8,
  "voltage": 1.95,
  "pump": "OFF",
  "mode": "AUTO",
  "target_tds": 850,
  "pumps": {
    "pompa_sirkulasi": "OFF",
    "pompa_pupuk_a": "OFF",
    "pompa_pupuk_b": "OFF"
  },
  "sensors": {
    "kelembaban": 72.0,
    "lux": 1540
  }
}
```

---

## 7. Bantuan Sambungan WiFi Mandiri & Tools Firmware ESP32

Jika perangkat alat di kebun belum tersambung ke internet atau kata sandi WiFi kebun diganti:

1. Perangkat kebun secara otomatis akan memancarkan WiFi darurat sendiri bernama **SMART-HYDROPONIC**.
2. Sambungkan ponsel atau laptop ke WiFi **SMART-HYDROPONIC** (Kata sandi: **12345678**).
3. Buka peramban di alamat: **http://192.168.4.1**
4. Pada portal lokal terdapat menu:
   - **Pemantauan:** Memantau nilai sensor langsung dari alat secara offline.
   - **Tools JSON (Modular):** Simulator untuk menguji format JSON modular (preset Standar, Tambah Sensor pH, dan Multi-Pompa Pupuk) langsung ke server.
   - **Pengaturan:** Mengganti SSID WiFi, sandi WiFi, dan URL API server.
5. Klik **Simpan & Sambungkan**. Alat akan segera terhubung ke server HydroSense dan data langsung tersaji pada dashboard web.

