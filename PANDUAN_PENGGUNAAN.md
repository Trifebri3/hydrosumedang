# Panduan Penggunaan dan Pengelolaan HydroSense by agronex

Dokumen panduan resmi sistem pemantauan dan kendali kebun hidroponik cerdas terpadu HydroSense. Disusun menggunakan bahasa yang mudah dipahami oleh seluruh pengelola kebun, petani mitra, dan administrator.

---

## 1. Panduan Akun dan Hak Akses

Platform HydroSense menggunakan sistem multi-kebun terpusat. Anda hanya memerlukan satu alamat web untuk mengelola seluruh instalasi kebun di berbagai wilayah.

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

## 4. Kustomisasi Fitur Tiap Alat (Khusus Administrator)

Setiap instalasi kebun memiliki spesifikasi lapangan yang berbeda. Administrator dapat mengkustomisasi fitur apa saja yang aktif pada masing-masing alat:

### Skenario Lapangan yang Didukung:
1. **Pos Pantau Nutrisi Mandiri:**
   Alat hanya memiliki sensor nutrisi (TDS) tanpa sensor suhu dan tanpa pompa air.
2. **Pos Multi-Sensor:**
   Alat membaca kepekatan nutrisi dan suhu air sekaligus tanpa saklar pompa.
3. **Instalasi Lengkap:**
   Alat membaca sensor nutrisi, sensor suhu, saklar pompa air, dan mode otomatis.

### Langkah Mengatur Fitur dan Menghubungkan Akun:
1. Masuk menggunakan akun **Administrator**.
2. Pilih instalasi kebun yang ingin diatur dari daftar pemilih di bagian atas.
3. Klik tombol **Atur Fitur Alat** di samping judul instalasi.
4. Pada jendela pengaturan:
   - Ubah nama instalasi dan lokasi jika diperlukan.
   - Pada menu **Hubungkan ke Akun Petani**, pilih nama akun petani yang berhak mengelola kebun tersebut.
   - Pada bagian **Pilih Fitur yang Aktif pada Alat Ini**, centang atau hapus centang sesuai kebutuhan:
     - [ ] Sensor Kepekatan Nutrisi (TDS / PPM)
     - [ ] Sensor Suhu Air (°C)
     - [ ] Saklar & Kendali Pompa Sirkulasi Air
     - [ ] Mode Otomatis Nutrisi Tanaman
5. Klik **Simpan Perubahan**.
6. Tampilan halaman dasbor petani yang bersangkutan akan langsung menyesuaikan secara dinamis:
   - Jika pompa dimatikan pada pengaturan alat, tombol saklar pompa akan disembunyikan.
   - Jika sensor suhu dimatikan, kolom dan grafik suhu akan disembunyikan agar tampilan tetap rapi.

---

## 5. Menambah Instalasi Kebun Baru Tanpa Membuat Web Baru

Jika Anda memasang unit baru di kebun lain (misalnya Unit Garut atau Unit Subang):
1. Masuk sebagai **Administrator**.
2. Klik tombol **+ Tambah Alat**.
3. Isi:
   - Nama Instalasi (contoh: HydroSense Kebun Garut Unit 1)
   - Kode Unik Alat (contoh: `alat1garut` atau `alat2sumedang`)
   - Lokasi Kebun (contoh: Cisewu, Garut)
   - Target Nutrisi Awal
   - Akun Pemilik / Petani
   - Centang fitur yang terpasang pada alat baru tersebut.
4. Klik **Simpan Instalasi**.
5. Alat baru langsung aktif di sistem tanpa perlu mengubah kode web.

---

## 6. Bantuan Sambungan WiFi Mandiri (Offline Mode)

Jika perangkat alat di kebun belum tersambung ke internet atau kata sandi WiFi kebun diganti:

1. Perangkat kebun secara otomatis akan memancarkan WiFi darurat sendiri bernama **SMART-HYDROPONIC**.
2. Buka menu pengaturan WiFi di HP atau laptop Anda, lalu sambungkan ke WiFi **SMART-HYDROPONIC** (Kata sandi: **12345678**).
3. Buka peramban (browser Google Chrome atau Safari), lalu ketik alamat: **192.168.4.1**
4. Pada halaman yang muncul:
   - Masukkan Nama WiFi kebun dan kata sandinya.
   - Masukkan Kode Alat (misal: `alat1sumedang` atau kode alat baru yang sudah dibuat).
   - Klik **Simpan & Sambungkan**.
5. Alat akan otomatis terhubung ke internet dan data kebun Anda akan langsung tampil di halaman web HydroSense.
