<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HydroSense by agronex - Smart Hydroponic Monitoring & Control</title>
    <meta name="description" content="Sistem monitoring dan kontrol hidroponik cerdas ESP32 terintegrasi - HydroSense by agronex.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f8f5;
            color: #1e293b;
        }

        .pulse-online {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .pulse-pump {
            box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7);
            animation: pulse-blue 1.5s infinite;
        }

        @keyframes pulse-blue {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
            70% { box-shadow: 0 0 0 12px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 9999px;
        }
    </style>
</head>
<body class="min-h-screen pb-24 lg:pb-12 text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-5 right-5 z-50 transform transition-all duration-300 translate-y-[-120%] opacity-0 bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-700/60 max-w-md">
        <div id="toast-icon" class="text-emerald-400 font-bold text-lg">✓</div>
        <div class="text-sm font-medium" id="toast-message">Perintah berhasil dikirim</div>
    </div>

    <!-- Top Navigation / Brand Header -->
    <header class="sticky top-0 z-30 bg-white/85 backdrop-blur-md border-b border-emerald-100/80">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-1.5 font-extrabold text-base tracking-tight text-slate-800">
                        <span>denrawit</span>
                        <span class="text-emerald-500">✕</span>
                        <span class="text-emerald-700">agronex</span>
                    </div>
                    <div class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">HydroSense IoT</div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="toggleModal('guideModal')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-200/60 shadow-xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Panduan ESP32</span>
                </button>
                <div id="deviceBadgeStatus" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $device->isOnline() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    <span class="w-2 h-2 rounded-full {{ $device->isOnline() ? 'bg-emerald-500 pulse-online' : 'bg-amber-500' }}"></span>
                    <span id="deviceStatusText">{{ $device->isOnline() ? 'ONLINE' : 'OFFLINE' }}</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 pt-5 space-y-6">

        <!-- Welcome Banner -->
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                Halo, Petani <span class="text-emerald-600">Hebat!</span>
            </h1>
            <p class="text-sm sm:text-base text-slate-500 mt-1 font-medium">
                Mau mengecek kondisi nutrisi dan pompa hidroponik hari ini?
            </p>
        </div>

        <!-- 4 Quick Cards (Sesuai Referensi Gambar Pengguna) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <!-- 1. Kondisi Lahan -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-700">Kondisi Lahan</span>
                <span class="text-[11px] text-slate-400 mt-0.5">Greenhouse Sumedang</span>
            </div>

            <!-- 2. Kondisi Tanaman -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m14.14-14.14l-1.41 1.41"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-700">Kondisi Tanaman</span>
                <span class="text-[11px] text-emerald-600 font-medium mt-0.5" id="quickNutrisiText">Nutrisi Optimal</span>
            </div>

            <!-- 3. Smart Irrigation -->
            <a href="#control-section" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-700">Smart Irrigation</span>
                <span class="text-[11px] text-slate-400 mt-0.5" id="quickPumpStatus">Pompa: {{ $device->pump_status ? 'ON' : 'OFF' }}</span>
            </a>

            <!-- 4. Rencana Tindakan -->
            <div onclick="toggleModal('guideModal')" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col items-center text-center group">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-700">Rencana Tindakan</span>
                <span class="text-[11px] text-slate-400 mt-0.5">Lihat Panduan</span>
            </div>
        </div>

        <!-- Section: Lahan Saya / Dark Hero Card (Sesuai Referensi Gambar) -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Instalasi Hidroponik Saya</h2>
                <button onclick="toggleModal('deviceModal')" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                    <span>+ Info Perangkat</span>
                </button>
            </div>

            <!-- Card Gelap Hero (Sawah Cisewu Style) -->
            <div class="bg-[#172236] rounded-3xl p-5 sm:p-6 text-white shadow-xl shadow-slate-900/10 border border-slate-800 relative overflow-hidden">
                <!-- Background Glow -->
                <div class="absolute -right-16 -top-16 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white flex items-center gap-2">
                            <span>{{ $device->name }}</span>
                            <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-lg bg-slate-800 text-slate-300 border border-slate-700">
                                {{ $device->device_code }}
                            </span>
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-400 mt-0.5">
                            {{ $device->location }} • Selada & Pakcoy Hidroponik
                        </p>
                    </div>

                    <div id="heroBadge" class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-extrabold tracking-wider {{ $device->isOnline() ? 'bg-emerald-950/80 text-emerald-400 border border-emerald-600/40' : 'bg-amber-950/80 text-amber-400 border border-amber-600/40' }}">
                        <span class="w-2.5 h-2.5 rounded-full {{ $device->isOnline() ? 'bg-emerald-400 pulse-online' : 'bg-amber-400' }}"></span>
                        <span id="heroBadgeLabel">{{ $device->isOnline() ? 'SISTEM NORMAL' : 'PERHATIAN' }}</span>
                    </div>
                </div>

                <!-- Inner Banner Box -->
                <div class="bg-[#212f48] rounded-2xl p-4 border border-slate-700/60 text-slate-200">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="text-xs sm:text-sm">
                            <span class="font-bold text-white block mb-0.5">Status Hari Ini</span>
                            <p id="heroStatusDescription" class="text-slate-300 leading-relaxed">
                                {{ $device->isOnline() 
                                    ? 'ESP32 terhubung aktif ke server. Telemetri mengalir lancar dan pompa siap dikontrol otomatis atau manual.' 
                                    : 'Sensor offline atau ESP32 belum terhubung ke jaringan internet. Jika koneksi terputus, hubungkan ke WiFi lokal ESP32 untuk konfigurasi ulang.' }}
                            </p>
                            <div class="mt-2.5 pt-2.5 border-t border-slate-700/50 flex flex-wrap items-center gap-4 text-[11px] text-slate-400 font-mono">
                                <span>WiFi: <b id="heroWifiSSID" class="text-slate-200 font-sans">{{ $device->wifi_ssid ?? 'N/A' }}</b></span>
                                <span>IP: <b id="heroIpAddress" class="text-slate-200">{{ $device->ip_address ?? '-' }}</b></span>
                                <span>Update Terakhir: <b id="heroLastSeen" class="text-slate-200">{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum pernah' }}</b></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Grid Telemetri Sensor -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            
            <!-- Card 1: Suhu Air -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">🌡️ Suhu Air</span>
                    <span id="tempStatusBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700">Optimal</span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span id="telemetryTemp" class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        {{ number_format($device->temperature ?? 0, 1) }}
                    </span>
                    <span class="text-sm font-bold text-slate-400">°C</span>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Target Ideal</span>
                    <span class="font-semibold text-slate-700">22.0 - 28.0 °C</span>
                </div>
            </div>

            <!-- Card 2: Nilai TDS -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">💧 Nilai TDS</span>
                    <span id="tdsStatusBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700">Sesuai</span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span id="telemetryTds" class="text-3xl sm:text-4xl font-extrabold text-emerald-600 tracking-tight">
                        {{ number_format($device->tds ?? 0, 0) }}
                    </span>
                    <span class="text-sm font-bold text-slate-400">ppm</span>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Target TDS</span>
                    <span id="telemetryTargetTdsLabel" class="font-bold text-emerald-700">{{ number_format($device->target_tds, 0) }} ppm</span>
                </div>
            </div>

            <!-- Card 3: Tegangan Sensor -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">⚡ Tegangan ADC</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">Analog D34</span>
                </div>
                <div class="flex items-baseline gap-1 mt-1">
                    <span id="telemetryVoltage" class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        {{ number_format($device->voltage ?? 0, 2) }}
                    </span>
                    <span class="text-sm font-bold text-slate-400">V</span>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Rentang ADC</span>
                    <span class="font-semibold text-slate-700">0.0 - 3.3 V</span>
                </div>
            </div>

            <!-- Card 4: Pompa Air -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">🚿 Pompa Nutrisi</span>
                    <span id="pumpModeBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $device->auto_mode ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700' }}">
                        {{ $device->auto_mode ? 'AUTO' : 'MANUAL' }}
                    </span>
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span id="telemetryPumpStatus" class="text-3xl sm:text-4xl font-extrabold {{ $device->pump_status ? 'text-emerald-600' : 'text-slate-400' }} tracking-tight">
                        {{ $device->pump_status ? 'ON' : 'OFF' }}
                    </span>
                    <span id="pumpPulseDot" class="w-3 h-3 rounded-full {{ $device->pump_status ? 'bg-emerald-500 pulse-pump' : 'bg-slate-300' }}"></span>
                </div>
                <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Relay Pin</span>
                    <span class="font-semibold text-slate-700">GPIO 26</span>
                </div>
            </div>

        </div>

        <!-- Section: Kontrol Pompa & Target TDS (Interaktif via Web) -->
        <div id="control-section" class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm space-y-6">
            
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        Kontrol Sistem HydroSense
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Kontrol relay pompa dan mode kerja ESP32 secara instan melalui web ini.
                    </p>
                </div>

                <!-- Current Mode Indicator -->
                <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-200/80">
                    <button onclick="setMode(true)" id="btnSelectAuto" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs {{ $device->auto_mode ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                        Mode AUTO
                    </button>
                    <button onclick="setMode(false)" id="btnSelectManual" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs {{ ! $device->auto_mode ? 'bg-slate-800 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                        Mode MANUAL
                    </button>
                </div>
            </div>

            <!-- Tombol Kontrol Pompa Manual -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5">
                    1. Saklar Pompa Manual (Relay GPIO 26)
                </label>
                <div class="grid grid-cols-2 gap-3 sm:gap-4 max-w-md">
                    <button onclick="setPump(true)" id="btnPumpOn" class="flex items-center justify-center gap-2.5 px-5 py-3.5 rounded-2xl font-bold text-sm text-white bg-emerald-600 hover:bg-emerald-700 active:scale-95 transition shadow-lg shadow-emerald-600/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        HIDUPKAN POMPA (ON)
                    </button>

                    <button onclick="setPump(false)" id="btnPumpOff" class="flex items-center justify-center gap-2.5 px-5 py-3.5 rounded-2xl font-bold text-sm text-white bg-rose-600 hover:bg-rose-700 active:scale-95 transition shadow-lg shadow-rose-600/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        MATIKAN POMPA (OFF)
                    </button>
                </div>
                <p id="modeWarningText" class="text-xs text-amber-600 font-medium mt-2 flex items-center gap-1.5 {{ $device->auto_mode ? 'block' : 'hidden' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Dalam <b>Mode AUTO</b>, pompa dikontrol otomatis oleh ESP32 berdasarkan Target TDS. Menyalakan/mematikan manual akan beralih ke Mode MANUAL.</span>
                </p>
            </div>

            <!-- Target TDS & Presets -->
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        2. Target TDS Nutrisi Otomatis
                    </label>
                    <span class="text-xs font-bold text-emerald-600" id="sliderValueBadge">{{ number_format($device->target_tds, 0) }} ppm</span>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="w-full relative flex items-center">
                        <input type="range" id="targetTdsSlider" min="400" max="1800" step="25" value="{{ $device->target_tds }}" 
                            oninput="updateSliderLabel(this.value)"
                            class="w-full h-2.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-emerald-600">
                    </div>
                    <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
                        <div class="relative w-full sm:w-32">
                            <input type="number" id="targetTdsInput" value="{{ $device->target_tds }}" min="100" max="3000"
                                class="w-full px-3 py-2 text-sm font-bold rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-center">
                            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">ppm</span>
                        </div>
                        <button onclick="saveTargetTds()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition shrink-0">
                            Simpan Target
                        </button>
                    </div>
                </div>

                <!-- Presets Nutrisi Tanaman Populer -->
                <div class="mt-3.5 flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-400">Preset Nutrisi:</span>
                    <button onclick="applyPreset(700)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-100">
                        🥬 Selada (700 ppm)
                    </button>
                    <button onclick="applyPreset(900)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-100">
                        🥬 Pakcoy (900 ppm)
                    </button>
                    <button onclick="applyPreset(1100)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-100">
                        🌿 Bayam (1100 ppm)
                    </button>
                    <button onclick="applyPreset(1500)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-100">
                        🍅 Tomat (1500 ppm)
                    </button>
                </div>
            </div>

        </div>

        <!-- Section: Real-time Charts -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Grafik Real-time TDS & Suhu</h3>
                    <p class="text-xs text-slate-500">Memperbarui secara otomatis setiap pembacaan sensor baru diterima.</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-bold">
                    <div class="flex items-center gap-1.5 text-emerald-600">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span>TDS (ppm)</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-amber-500">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span>Suhu (°C)</span>
                    </div>
                </div>
            </div>

            <div class="h-64 sm:h-72 w-full">
                <canvas id="telemetryChart"></canvas>
            </div>
        </div>

        <!-- Section: Riwayat Data Telemetri -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Log Pembacaan Terakhir</h3>
                    <p class="text-xs text-slate-500">Data tersimpan di database MySQL <code class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">hydrosense_agronex</code>.</p>
                </div>
                <span class="text-xs font-mono font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-lg">15 Record Terakhir</span>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase font-bold tracking-wider">
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3">Suhu Air</th>
                            <th class="py-2.5 px-3">Nilai TDS</th>
                            <th class="py-2.5 px-3">Tegangan Sensor</th>
                            <th class="py-2.5 px-3">Status Pompa</th>
                            <th class="py-2.5 px-3">Mode</th>
                        </tr>
                    </thead>
                    <tbody id="telemetryTableBody" class="divide-y divide-slate-50 text-slate-700 font-medium">
                        @forelse($readings as $reading)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-2.5 px-3 font-mono text-slate-500">{{ $reading->created_at->format('H:i:s') }}</td>
                                <td class="py-2.5 px-3 font-bold text-slate-800">{{ number_format($reading->temperature, 2) }} °C</td>
                                <td class="py-2.5 px-3 font-bold text-emerald-600">{{ number_format($reading->tds, 0) }} ppm</td>
                                <td class="py-2.5 px-3 font-mono">{{ number_format($reading->voltage, 2) }} V</td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $reading->pump_status ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $reading->pump_status ? 'ON' : 'OFF' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $reading->auto_mode ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $reading->auto_mode ? 'AUTO' : 'MANUAL' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-400">Belum ada riwayat data telemetri. Menunggu data ESP32...</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Modal Panduan ESP32 & WiFi Fallback -->
    <div id="guideModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                        📶
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900">Panduan Koneksi & WiFi Fallback</h3>
                        <p class="text-xs text-slate-500">Cara kerja ESP32 dengan Laravel & Mode Offline</p>
                    </div>
                </div>
                <button onclick="toggleModal('guideModal')" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">✕</button>
            </div>

            <div class="space-y-4 text-xs sm:text-sm text-slate-600 leading-relaxed">
                <!-- Info 1 -->
                <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-emerald-900">
                    <span class="font-bold block mb-1">🚀 1. Endpoint API untuk ESP32:</span>
                    <p class="text-xs">ESP32 mengirim data sensor secara periodik melalui HTTP POST:</p>
                    <code class="block bg-white p-2 rounded-xl text-emerald-800 font-mono text-xs mt-1.5 border border-emerald-200">
                        POST http://&lt;IP_SERVER_LARAVEL&gt;:8000/api/sensor/data
                    </code>
                    <p class="text-xs mt-1 text-emerald-700">Setiap kali ESP32 mengirim data, Laravel langsung membalas dengan status pompa, mode auto, dan target TDS terbaru yang diatur dari web ini!</p>
                </div>

                <!-- Info 2: Fallback WiFi -->
                <div class="p-3.5 rounded-2xl bg-blue-50/70 border border-blue-100 text-blue-900">
                    <span class="font-bold block mb-1">📡 2. Mekanisme Fallback WiFi Mandiri (Offline Mode):</span>
                    <p class="text-xs">
                        Jika ESP32 tidak dapat terhubung ke WiFi atau internet terputus, ESP32 <b>secara otomatis memancarkan Access Point (WiFi) sendiri</b>:
                    </p>
                    <ul class="list-disc list-inside text-xs mt-2 space-y-1 font-medium">
                        <li>Nama WiFi (SSID): <b class="font-mono text-blue-800">SMART-HYDROPONIC</b></li>
                        <li>Password: <b class="font-mono text-blue-800">12345678</b></li>
                        <li>IP Web Lokal ESP32: <b class="font-mono text-blue-800">http://192.168.4.1</b></li>
                    </ul>
                    <p class="text-xs mt-2 text-blue-800">
                        Di web lokal ESP32 (<code class="bg-white px-1 py-0.5 rounded">192.168.4.1</code>), Anda dapat:
                        <br>• Menginput Nama & Password WiFi baru (tersimpan permanen di memori ESP32 Preferences).
                        <br>• Melakukan kontrol lokal pompa dan monitoring langsung tanpa butuh internet.
                    </p>
                </div>

                <!-- Info 3: Perintah Test Curl -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700">
                    <span class="font-bold block mb-1">💻 3. Test API dari Terminal (Simulasi ESP32):</span>
                    <pre class="bg-slate-900 text-emerald-400 p-2.5 rounded-xl font-mono text-[11px] overflow-x-auto">curl -X POST http://127.0.0.1:8000/api/sensor/data \
  -H "Content-Type: application/json" \
  -d '{"device_code":"HYDROSENSE-01","temperature":26.5,"tds":820,"voltage":1.65,"pump":false,"auto":true}'</pre>
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end">
                <button onclick="toggleModal('guideModal')" class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                    Mengerti & Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Info Perangkat -->
    <div id="deviceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <h3 class="text-base font-extrabold text-slate-900">Detail Perangkat ESP32</h3>
                <button onclick="toggleModal('deviceModal')" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
            </div>
            <div class="space-y-2.5 text-xs">
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-500 font-medium">Device Code</span>
                    <span class="font-mono font-bold text-slate-800">{{ $device->device_code }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-500 font-medium">Nama</span>
                    <span class="font-bold text-slate-800">{{ $device->name }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-500 font-medium">Lokasi</span>
                    <span class="font-bold text-slate-800">{{ $device->location }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-500 font-medium">IP Address ESP32</span>
                    <span class="font-mono font-bold text-slate-800" id="modalIp">{{ $device->ip_address ?? '-' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50">
                    <span class="text-slate-500 font-medium">WiFi Terhubung</span>
                    <span class="font-bold text-slate-800" id="modalWifi">{{ $device->wifi_ssid ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-500 font-medium">Target TDS Terpasang</span>
                    <span class="font-bold text-emerald-600" id="modalTarget">{{ number_format($device->target_tds, 0) }} ppm</span>
                </div>
            </div>
            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end">
                <button onclick="toggleModal('deviceModal')" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Floating Bottom Navigation Bar (Mobile Native Style dari Gambar User) -->
    <nav class="fixed bottom-0 inset-x-0 z-40 bg-white/90 backdrop-blur-md border-t border-slate-200/80 px-4 py-2 sm:hidden">
        <div class="flex items-center justify-around max-w-md mx-auto relative">
            
            <!-- Beranda -->
            <a href="#" class="flex flex-col items-center gap-1 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[10px] font-bold">Beranda</span>
            </a>

            <!-- Lahan -->
            <a href="#control-section" class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span class="text-[10px] font-semibold">Kontrol</span>
            </a>

            <!-- Floating Green Center Button (Pompa Quick Toggle) -->
            <button onclick="quickTogglePump()" class="w-12 h-12 -mt-6 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/40 border-4 border-white transition active:scale-95" title="Toggle Pompa">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </button>

            <!-- Grafik -->
            <a href="#telemetryChart" class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                <span class="text-[10px] font-semibold">Grafik</span>
            </a>

            <!-- Panduan -->
            <button onclick="toggleModal('guideModal')" class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <span class="text-[10px] font-semibold">WiFi</span>
            </button>

        </div>
    </nav>

    <!-- Client Script -->
    <script>
        const DEVICE_CODE = "{{ $device->device_code }}";
        let currentPump = {{ $device->pump_status ? 'true' : 'false' }};
        let currentAuto = {{ $device->auto_mode ? 'true' : 'false' }};
        let currentTargetTds = {{ $device->target_tds }};

        // Initialize Chart.js
        const initialLabels = [
            @foreach($historyPoints as $point)
                "{{ $point->created_at->format('H:i:s') }}",
            @endforeach
        ];

        const initialTdsData = [
            @foreach($historyPoints as $point)
                {{ $point->tds }},
            @endforeach
        ];

        const initialTempData = [
            @foreach($historyPoints as $point)
                {{ $point->temperature }},
            @endforeach
        ];

        const ctx = document.getElementById('telemetryChart').getContext('2d');
        const telemetryChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: initialLabels,
                datasets: [
                    {
                        label: 'TDS (ppm)',
                        data: initialTdsData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Suhu Air (°C)',
                        data: initialTempData,
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        tension: 0.3,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'TDS (ppm)', font: { size: 10 } },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Suhu (°C)', font: { size: 10 } },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });

        // Toast Helper
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const msg = document.getElementById('toast-message');

            msg.innerText = message;
            icon.innerText = isError ? '✕' : '✓';
            icon.className = isError ? 'text-rose-400 font-bold text-lg' : 'text-emerald-400 font-bold text-lg';

            toast.classList.remove('translate-y-[-120%]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.add('translate-y-[-120%]', 'opacity-0');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 3000);
        }

        // Toggle Modal
        function toggleModal(id) {
            const el = document.getElementById(id);
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }

        // Slider sync
        function updateSliderLabel(val) {
            document.getElementById('sliderValueBadge').innerText = `${val} ppm`;
            document.getElementById('targetTdsInput').value = val;
        }

        document.getElementById('targetTdsInput').addEventListener('input', function(e) {
            const val = e.target.value;
            if (val >= 400 && val <= 1800) {
                document.getElementById('targetTdsSlider').value = val;
                document.getElementById('sliderValueBadge').innerText = `${val} ppm`;
            }
        });

        function applyPreset(val) {
            document.getElementById('targetTdsSlider').value = val;
            document.getElementById('targetTdsInput').value = val;
            document.getElementById('sliderValueBadge').innerText = `${val} ppm`;
            saveTargetTds();
        }

        // Send Control Command to API
        async function sendControl(payload) {
            payload.device_code = DEVICE_CODE;
            try {
                const res = await fetch('/api/sensor/control', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Perintah berhasil diterapkan');
                    fetchLatestData();
                } else {
                    showToast(data.message || 'Gagal mengubah pengaturan', true);
                }
            } catch (err) {
                console.error(err);
                showToast('Koneksi ke server gagal', true);
            }
        }

        function setPump(state) {
            currentPump = state;
            // When user explicitly clicks pump ON/OFF, also disable auto mode to manual
            sendControl({ pump: state, auto: false });
        }

        function quickTogglePump() {
            setPump(!currentPump);
        }

        function setMode(isAuto) {
            currentAuto = isAuto;
            sendControl({ auto: isAuto });
        }

        function saveTargetTds() {
            const val = parseFloat(document.getElementById('targetTdsInput').value);
            if (!val || val < 100) {
                showToast('Masukkan target TDS yang valid', true);
                return;
            }
            sendControl({ target_tds: val });
        }

        // Periodic Fetch & Real-time Update
        async function fetchLatestData() {
            try {
                const res = await fetch(`/api/sensor/latest?device_code=${DEVICE_CODE}`);
                const data = await res.json();
                if (data.status === 'success' && data.device) {
                    const dev = data.device;
                    currentPump = dev.pump_status;
                    currentAuto = dev.auto_mode;
                    currentTargetTds = dev.target_tds;

                    // Update UI elements
                    document.getElementById('telemetryTemp').innerText = dev.temperature.toFixed(1);
                    document.getElementById('telemetryTds').innerText = dev.tds.toFixed(0);
                    document.getElementById('telemetryVoltage').innerText = dev.voltage.toFixed(2);
                    document.getElementById('telemetryPumpStatus').innerText = dev.pump_status ? 'ON' : 'OFF';
                    document.getElementById('quickPumpStatus').innerText = 'Pompa: ' + (dev.pump_status ? 'ON' : 'OFF');

                    if (dev.pump_status) {
                        document.getElementById('telemetryPumpStatus').className = 'text-3xl sm:text-4xl font-extrabold text-emerald-600 tracking-tight';
                        document.getElementById('pumpPulseDot').className = 'w-3 h-3 rounded-full bg-emerald-500 pulse-pump';
                    } else {
                        document.getElementById('telemetryPumpStatus').className = 'text-3xl sm:text-4xl font-extrabold text-slate-400 tracking-tight';
                        document.getElementById('pumpPulseDot').className = 'w-3 h-3 rounded-full bg-slate-300';
                    }

                    // Mode badge
                    const pumpModeBadge = document.getElementById('pumpModeBadge');
                    pumpModeBadge.innerText = dev.auto_mode ? 'AUTO' : 'MANUAL';
                    pumpModeBadge.className = `text-[10px] font-bold px-2 py-0.5 rounded-md ${dev.auto_mode ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700'}`;

                    // Auto/Manual Select buttons
                    const btnAuto = document.getElementById('btnSelectAuto');
                    const btnManual = document.getElementById('btnSelectManual');
                    if (dev.auto_mode) {
                        btnAuto.className = 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs bg-blue-600 text-white';
                        btnManual.className = 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs text-slate-600 hover:bg-slate-200';
                        document.getElementById('modeWarningText').classList.remove('hidden');
                    } else {
                        btnAuto.className = 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs text-slate-600 hover:bg-slate-200';
                        btnManual.className = 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs bg-slate-800 text-white';
                        document.getElementById('modeWarningText').classList.add('hidden');
                    }

                    // Target TDS
                    document.getElementById('telemetryTargetTdsLabel').innerText = `${dev.target_tds.toFixed(0)} ppm`;
                    document.getElementById('modalTarget').innerText = `${dev.target_tds.toFixed(0)} ppm`;

                    // Online/offline badge
                    const badge = document.getElementById('deviceBadgeStatus');
                    const heroBadge = document.getElementById('heroBadge');
                    const heroStatusDesc = document.getElementById('heroStatusDescription');

                    if (dev.is_online) {
                        badge.className = 'flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800';
                        badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 pulse-online"></span><span>ONLINE</span>';

                        heroBadge.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-extrabold tracking-wider bg-emerald-950/80 text-emerald-400 border border-emerald-600/40';
                        heroBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-emerald-400 pulse-online"></span><span>SISTEM NORMAL</span>';

                        heroStatusDesc.innerText = 'ESP32 terhubung aktif ke server. Telemetri mengalir lancar dan pompa siap dikontrol otomatis atau manual.';
                    } else {
                        badge.className = 'flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800';
                        badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-500"></span><span>OFFLINE</span>';

                        heroBadge.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-extrabold tracking-wider bg-amber-950/80 text-amber-400 border border-amber-600/40';
                        heroBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span><span>PERHATIAN</span>';

                        heroStatusDesc.innerText = 'Sensor offline atau ESP32 belum terhubung ke jaringan internet. Jika koneksi terputus, hubungkan ke WiFi lokal ESP32 untuk konfigurasi ulang.';
                    }

                    document.getElementById('heroWifiSSID').innerText = dev.wifi_ssid;
                    document.getElementById('heroIpAddress').innerText = dev.ip_address;
                    document.getElementById('heroLastSeen').innerText = dev.last_seen_formatted;
                    document.getElementById('modalWifi').innerText = dev.wifi_ssid;
                    document.getElementById('modalIp').innerText = dev.ip_address;
                }
            } catch (e) {
                console.error('Fetch error:', e);
            }
        }

        // Fetch History for Chart & Table
        async function fetchHistoryData() {
            try {
                const res = await fetch(`/api/sensor/history?device_code=${DEVICE_CODE}`);
                const data = await res.json();
                if (data.status === 'success' && data.history && data.history.length > 0) {
                    const times = data.history.map(item => item.time);
                    const tdsValues = data.history.map(item => item.tds);
                    const tempValues = data.history.map(item => item.temperature);

                    telemetryChart.data.labels = times;
                    telemetryChart.data.datasets[0].data = tdsValues;
                    telemetryChart.data.datasets[1].data = tempValues;
                    telemetryChart.update();

                    // Update Table
                    const tbody = document.getElementById('telemetryTableBody');
                    const reversedHistory = [...data.history].reverse().slice(0, 15);
                    tbody.innerHTML = reversedHistory.map(row => `
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-2.5 px-3 font-mono text-slate-500">${row.time}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-800">${row.temperature.toFixed(2)} °C</td>
                            <td class="py-2.5 px-3 font-bold text-emerald-600">${row.tds.toFixed(0)} ppm</td>
                            <td class="py-2.5 px-3 font-mono">${row.voltage.toFixed(2)} V</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${row.pump_status ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}">
                                    ${row.pump_status ? 'ON' : 'OFF'}
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${currentAuto ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600'}">
                                    ${currentAuto ? 'AUTO' : 'MANUAL'}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (e) {
                console.error('History fetch error:', e);
            }
        }

        // Run interval polling every 2.5 seconds
        setInterval(() => {
            fetchLatestData();
        }, 2500);

        setInterval(() => {
            fetchHistoryData();
        }, 5000);
    </script>
</body>
</html>
