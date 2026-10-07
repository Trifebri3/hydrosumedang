/*
  =====================================================
          ESP32 RELAY TEST & CONTROL - GPIO D26
               (HydroSense by agronex)
  =====================================================

  LOGIKA RELAY (ACTIVE LOW):
  - LOW  (0V)   = RELAY ON  (Kumparan aktif / saklar terhubung)
  - HIGH (3.3V) = RELAY OFF (Kumparan lepas / saklar terputus)

  KEAMANAN BOOT (GLITCH PREVENTION):
  - Saat ESP32 pertama menyala atau restart, pin diberi sinyal
    HIGH terlebih dahulu SEBELUM diset ke mode OUTPUT.
    Hal ini mencegah hentakan sesaat (relay berderak/flicker)
    saat mikrokontroler baru menyala.

  PERINTAH SERIAL MONITOR (115200 BAUD):
  - ON     : Menyalakan relay (D26 = LOW)
  - OFF    : Mematikan relay (D26 = HIGH)
  - TOGGLE : Membalik status relay (ON <-> OFF)
  - STATUS : Melihat status pin GPIO & status relay
  - TEST   : Uji coba relay otomatis (ON 3 detik -> OFF)
  - HELP   : Menampilkan daftar perintah
  =====================================================
*/

// =====================================================
// KONFIGURASI PIN & LOGIKA
// =====================================================
#define RELAY_PIN 26

// Logika Active LOW
#define RELAY_ON_LEVEL  LOW   // 0V untuk mengaktifkan modul relay
#define RELAY_OFF_LEVEL HIGH  // 3.3V untuk mematikan modul relay

bool relayState = false;


// =====================================================
// FUNGSI RELAY ON
// =====================================================
void relayOn() {
  digitalWrite(RELAY_PIN, RELAY_ON_LEVEL);
  relayState = true;

  Serial.println();
  Serial.println(F(">>> RELAY ON (MENYALA)"));
  Serial.println(F("Pin D26 = LOW (0V)"));
}


// =====================================================
// FUNGSI RELAY OFF
// =====================================================
void relayOff() {
  digitalWrite(RELAY_PIN, RELAY_OFF_LEVEL);
  relayState = false;

  Serial.println();
  Serial.println(F(">>> RELAY OFF (MATI)"));
  Serial.println(F("Pin D26 = HIGH (3.3V)"));
}


// =====================================================
// STATUS RELAY & LOGIKA FISIK PIN
// =====================================================
void showStatus() {
  int pinLevel = digitalRead(RELAY_PIN);

  Serial.println();
  Serial.println(F("========================================="));
  Serial.println(F("           STATUS RELAY D26              "));
  Serial.println(F("========================================="));
  Serial.print(F("Pin GPIO       : D")); Serial.println(RELAY_PIN);
  Serial.print(F("Status Sistem  : ")); Serial.println(relayState ? F("ON (MENYALA)") : F("OFF (MATI)"));
  Serial.print(F("Logika Fisik   : "));
  if (pinLevel == LOW) {
    Serial.println(F("LOW (0V - Aktif / Terhubung)"));
  } else {
    Serial.println(F("HIGH (3.3V - Non-aktif / Terputus)"));
  }
  Serial.print(F("Validasi Logika: "));
  if ((relayState && pinLevel == LOW) || (!relayState && pinLevel == HIGH)) {
    Serial.println(F("SESUAI (Sinkron)"));
  } else {
    Serial.println(F("PERINGATAN: Status variabel & fisik tidak cocok!"));
  }
  Serial.println(F("========================================="));
}


// =====================================================
// UJI COBA OTOMATIS (TEST)
// =====================================================
void testRelay() {
  Serial.println();
  Serial.println(F("-----------------------------------------"));
  Serial.println(F("UJI COBA RELAY DIMULAI"));
  Serial.println(F("1. Menyalakan Relay (ON) selama 3 detik..."));
  relayOn();

  delay(3000);

  Serial.println();
  Serial.println(F("2. Mematikan Relay (OFF)..."));
  relayOff();

  delay(1000);

  Serial.println(F("UJI COBA RELAY SELESAI"));
  Serial.println(F("-----------------------------------------"));
}


// =====================================================
// MENU BANTUAN (HELP)
// =====================================================
void help() {
  Serial.println();
  Serial.println(F("========================================="));
  Serial.println(F("     PANDUAN PERINTAH RELAY SERIAL       "));
  Serial.println(F("========================================="));
  Serial.println(F("ON     : Menyalakan relay"));
  Serial.println(F("OFF    : Mematikan relay"));
  Serial.println(F("TOGGLE : Membalik status relay"));
  Serial.println(F("STATUS : Melihat kondisi fisik pin & relay"));
  Serial.println(F("TEST   : Uji siklus ON 3 detik lalu OFF"));
  Serial.println(F("HELP   : Menampilkan menu bantuan ini"));
  Serial.println(F("========================================="));
}


// =====================================================
// BACA PERINTAH DARI SERIAL MONITOR
// =====================================================
void readCommand() {
  if (Serial.available() == 0) {
    return;
  }

  String command = Serial.readStringUntil('\n');
  command.trim();

  // Abaikan baris kosong atau sekadar enter
  if (command.length() == 0) {
    return;
  }

  command.toUpperCase();

  if (command == "ON") {
    relayOn();
  }
  else if (command == "OFF") {
    relayOff();
  }
  else if (command == "STATUS") {
    showStatus();
  }
  else if (command == "TOGGLE") {
    if (relayState) {
      relayOff();
    } else {
      relayOn();
    }
  }
  else if (command == "TEST") {
    testRelay();
  }
  else if (command == "HELP") {
    help();
  }
  else {
    Serial.println();
    Serial.print(F("Perintah tidak dikenal: \""));
    Serial.print(command);
    Serial.println(F("\""));
    Serial.println(F("Ketik HELP untuk melihat daftar perintah yang tersedia."));
  }
}


// =====================================================
// SETUP
// =====================================================
void setup() {
  // Inisialisasi Serial Monitor
  Serial.begin(115200);
  delay(1000);

  // ===================================================
  // KEAMANAN BOOT (BOOT GLITCH SAFETY)
  // Untuk relay Active-LOW, output buffer harus diset ke
  // HIGH terlebih dahulu sebelum pin diset ke OUTPUT.
  // Ini mencegah relay tersentak menyala sesaat saat boot.
  // ===================================================
  digitalWrite(RELAY_PIN, RELAY_OFF_LEVEL);
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, RELAY_OFF_LEVEL);
  relayState = false;

  // Header Tampilan Awal
  Serial.println();
  Serial.println(F("========================================="));
  Serial.println(F("       SMART HYDROPONIC IoT ESP32        "));
  Serial.println(F("           UJI KONTROL RELAY D26         "));
  Serial.println(F("========================================="));
  Serial.println(F("Sistem Siap (ESP32 Ready)"));
  Serial.print(F("Pin Relay       : GPIO ")); Serial.println(RELAY_PIN);
  Serial.println(F("Logika Modul    : Active LOW (LOW=ON, HIGH=OFF)"));
  Serial.println(F("Status Awal     : MATI (OFF - 3.3V)"));
  Serial.println(F("-----------------------------------------"));
  Serial.println(F("Ketik perintah di Serial Monitor:"));
  Serial.println(F("ON  |  OFF  |  STATUS  |  TOGGLE  |  TEST  |  HELP"));
  Serial.println(F("=========================================\n"));
}


// =====================================================
// LOOP UTAMA
// =====================================================
void loop() {
  readCommand();
}
