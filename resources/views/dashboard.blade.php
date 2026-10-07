<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $device->name }} - HydroSense by agronex</title>
    <meta name="description" content="Sistem monitoring dan kontrol hidroponik cerdas terpadu - HydroSense by agronex.">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

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
            -webkit-tap-highlight-color: transparent;
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
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-active 1.5s infinite;
        }

        @keyframes pulse-active {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 12px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 9999px;
        }
    </style>
</head>
<body class="min-h-screen pb-24 lg:pb-12 text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Notifikasi Pesan -->
    <div id="toast" class="fixed top-4 right-4 left-4 sm:left-auto sm:right-6 z-50 transform transition-all duration-300 translate-y-[-140%] opacity-0 bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-700/60 max-w-md mx-auto sm:mx-0">
        <div id="toast-icon" class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0">&#10003;</div>
        <div class="text-xs sm:text-sm font-medium" id="toast-message">Perintah berhasil diterapkan</div>
    </div>

    <!-- Header Atas -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-emerald-100/80">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-2.5 flex items-center justify-between">
            
            <!-- Logo Resmi denrawit x agronex -->
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                    <img src="{{ asset('logo.png') }}" alt="denrawit x agronex" class="h-8 sm:h-9 w-auto object-contain transition group-hover:opacity-95">
                    <div class="hidden sm:block border-l border-slate-200 pl-2.5">
                        <span class="text-[11px] font-extrabold text-emerald-700 uppercase tracking-wider block">HydroSense</span>
                    </div>
                </a>
            </div>

            <!-- Bagian Kanan Header -->
            <div class="flex items-center gap-2">
                <!-- Tombol Buka Buku Panduan -->
                <button onclick="openGuideModal('tab-monitor')" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 border border-slate-200/80 transition">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>Buku Panduan</span>
                </button>

                @auth
                    <div class="flex items-center gap-2 bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-200/80">
                        <div class="w-2 h-2 rounded-full bg-emerald-600"></div>
                        <span class="text-xs font-bold text-slate-700 max-w-[100px] sm:max-w-none truncate">
                            {{ auth()->user()->name }}
                        </span>
                        <a href="{{ route('logout') }}" title="Keluar dari akun" class="text-slate-400 hover:text-rose-600 transition text-xs font-bold pl-1">
                            Keluar
                        </a>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                        Masuk Akun
                    </a>
                @endauth

                <!-- Status Terhubung / Terputus -->
                <div id="deviceBadgeStatus" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-semibold {{ $device->isOnline() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    <span class="w-2 h-2 rounded-full {{ $device->isOnline() ? 'bg-emerald-500 pulse-online' : 'bg-amber-500' }}"></span>
                    <span id="deviceStatusText">{{ $device->isOnline() ? 'Terhubung' : 'Terputus' }}</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Kontainer Halaman Utama -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 pt-4 space-y-5">

        <!-- Pesan Sukses -->
        @if(session('success'))
            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs sm:text-sm text-emerald-800 font-semibold flex items-center justify-between shadow-xs">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold ml-2">Tutup</button>
            </div>
        @endif

        <!-- Sapaan Petani & Pemilih Instalasi -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
                    Halo, <span class="text-emerald-600">{{ auth()->check() ? auth()->user()->name : 'Petani Hebat' }}!</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5 font-medium">
                    Pantau nutrisi dan kelola sirkulasi air tanaman hidroponik Anda.
                </p>
            </div>

            <!-- Pilihan Instalasi Kebun -->
            <div class="bg-white p-1.5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-2 self-start sm:self-auto w-full sm:w-auto">
                <span class="text-[11px] font-bold text-slate-400 pl-2 shrink-0">Instalasi:</span>
                <select onchange="window.location.href='/?device=' + this.value" class="text-xs font-bold text-slate-800 bg-slate-50 px-3 py-1.5 rounded-xl border-none focus:ring-2 focus:ring-emerald-500 cursor-pointer w-full sm:w-auto">
                    @foreach($devices as $d)
                        <option value="{{ $d->device_code }}" {{ $d->id === $device->id ? 'selected' : '' }}>
                            {{ $d->name }} ({{ $d->location }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 4 Kotak Navigasi Cepat -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            
            <!-- 1. Kondisi Lahan -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col items-center text-center group">
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2.5 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-700">Kondisi Lahan</span>
                <span class="text-[11px] text-slate-400 mt-0.5 truncate max-w-[130px]">{{ $device->location }}</span>
            </div>

            <!-- 2. Kondisi Nutrisi Tanaman -->
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col items-center text-center group">
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mb-2.5 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m14.14-14.14l-1.41 1.41"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-700">Kondisi Tanaman</span>
                <span class="text-[11px] text-emerald-600 font-semibold mt-0.5">
                    {{ $device->has_tds ? 'Nutrisi Terpantau' : 'Pemantauan Aktif' }}
                </span>
            </div>

            <!-- 3. Sirkulasi Pompa Air / Status Alat -->
            @if($device->has_pump)
                <a href="#control-section" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col items-center text-center group">
                    <div class="w-11 h-11 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mb-2.5 group-hover:scale-105 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-700">Sirkulasi Pompa</span>
                    <span class="text-[11px] text-slate-400 mt-0.5" id="quickPumpStatus">Pompa: {{ $device->pump_status ? 'Menyala' : 'Mati' }}</span>
                </a>
            @else
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex flex-col items-center text-center">
                    <div class="w-11 h-11 rounded-2xl bg-slate-50 text-slate-600 flex items-center justify-center mb-2.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-700">Tipe Instalasi</span>
                    <span class="text-[11px] text-slate-400 mt-0.5">Pos Pantau Sensor</span>
                </div>
            @endif

            <!-- 4. Panduan & Bantuan -->
            <div onclick="openGuideModal('tab-monitor')" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col items-center text-center group">
                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-2.5 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-700">Panduan & Bantuan</span>
                <span class="text-[11px] text-slate-400 mt-0.5">Petunjuk Lengkap</span>
            </div>
        </div>

        <!-- Kartu Utama Instalasi (Warna Gelap Elegan) -->
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Instalasi Hidroponik</h2>
                    <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">
                        {{ $device->name }}
                    </span>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 font-semibold">
                        Kode: {{ $device->device_code }}
                    </span>
                </div>

                <!-- Aksi Khusus Admin -->
                @if(auth()->check() && auth()->user()->isAdmin())
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button onclick="toggleModal('editDeviceModal')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-700 text-white shadow-xs transition">
                            Atur Fitur Alat
                        </button>
                        <button onclick="toggleModal('createDeviceModal')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                            + Tambah Alat
                        </button>
                        <button onclick="toggleModal('createUserModal')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white shadow-xs transition">
                            + Akun Petani
                        </button>
                    </div>
                @endif
            </div>

            <!-- Kartu Status Gelap -->
            <div class="bg-[#172236] rounded-3xl p-5 sm:p-6 text-white shadow-xl shadow-slate-900/10 border border-slate-800 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
                                {{ $device->name }}
                            </h3>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                            <span>Lokasi: <b class="text-slate-200">{{ $device->location }}</b></span>
                            <span>•</span>
                            <span>Pengelola: <b class="text-slate-200">{{ $device->user?->name ?? 'Semua Petani' }}</b></span>
                        </p>
                    </div>

                    <div id="heroBadge" class="flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold tracking-wider {{ $device->isOnline() ? 'bg-emerald-950/80 text-emerald-400 border border-emerald-600/40' : 'bg-amber-950/80 text-amber-400 border border-amber-600/40' }}">
                        <span class="w-2.5 h-2.5 rounded-full {{ $device->isOnline() ? 'bg-emerald-400 pulse-online' : 'bg-amber-400' }}"></span>
                        <span id="heroBadgeLabel">{{ $device->isOnline() ? 'SISTEM NORMAL' : 'PERHATIAN' }}</span>
                    </div>
                </div>

                <!-- Fitur yang Terpasang pada Alat Ini -->
                <div class="mb-4 flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="text-slate-400 font-semibold mr-1">Fitur Terpasang:</span>
                    @if($device->has_tds)
                        <span class="px-2 py-0.5 rounded-md bg-emerald-900/60 border border-emerald-500/30 text-emerald-300 font-medium">Sensor Nutrisi (TDS)</span>
                    @endif
                    @if($device->has_temp)
                        <span class="px-2 py-0.5 rounded-md bg-amber-900/60 border border-amber-500/30 text-amber-300 font-medium">Sensor Suhu Air</span>
                    @endif
                    @if($device->has_pump)
                        <span class="px-2 py-0.5 rounded-md bg-blue-900/60 border border-blue-500/30 text-blue-300 font-medium">Pompa Sirkulasi</span>
                    @else
                        <span class="px-2 py-0.5 rounded-md bg-slate-800 border border-slate-700 text-slate-400 font-medium">Tanpa Pompa</span>
                    @endif
                    @if($device->has_auto_mode)
                        <span class="px-2 py-0.5 rounded-md bg-purple-900/60 border border-purple-500/30 text-purple-300 font-medium">Mode Otomatis</span>
                    @endif
                </div>

                <!-- Banner Informasi Status Hari Ini -->
                <div class="bg-[#212f48] rounded-2xl p-4 border border-slate-700/60 text-slate-200">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="text-xs sm:text-sm w-full">
                            <span class="font-bold text-white block mb-0.5">Kondisi Pemantauan</span>
                            <p id="heroStatusDescription" class="text-slate-300 leading-relaxed">
                                {{ $device->isOnline() 
                                    ? 'Sistem pemantauan aktif dan terhubung normal. Pengukuran sensor berjalan lancar.' 
                                    : 'Perangkat kebun belum tersambung ke jaringan. Jika koneksi terputus, sambungkan ke WiFi cadangan alat untuk menghubungkan kembali.' }}
                            </p>
                            <div class="mt-2.5 pt-2.5 border-t border-slate-700/50 flex flex-wrap items-center gap-4 text-[11px] text-slate-400">
                                <span>WiFi Kebun: <b id="heroWifiSSID" class="text-slate-200 font-semibold">{{ $device->wifi_ssid ?? 'Belum Terhubung' }}</b></span>
                                <span>Pembaruan Terakhir: <b id="heroLastSeen" class="text-slate-200 font-semibold">{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum ada data' }}</b></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kotak Parameter Sensor yang Aktif (Dinamis Sesuai Kustomisasi Alat) -->
        @php
            $featureCardsCount = ($device->has_temp ? 1 : 0) + ($device->has_tds ? 1 : 0) + (($device->has_auto_mode && $device->has_tds) ? 1 : 0) + ($device->has_pump ? 1 : 0);
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 {{ $featureCardsCount >= 4 ? 'lg:grid-cols-4' : ($featureCardsCount == 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-2') }} gap-3.5 sm:gap-4">
            
            <!-- Parameter 1: Suhu Air (Jika Aktif) -->
            @if($device->has_temp)
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Suhu Air</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700">Optimal</span>
                    </div>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span id="telemetryTemp" class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                            {{ number_format($device->temperature ?? 0, 1) }}
                        </span>
                        <span class="text-sm font-bold text-slate-400">°C</span>
                    </div>
                    <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Rentang Baik</span>
                        <span class="font-semibold text-slate-700">22.0 - 28.0 °C</span>
                    </div>
                </div>
            @endif

            <!-- Parameter 2: Kepekatan Nutrisi (Jika Aktif) -->
            @if($device->has_tds)
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Kepekatan Nutrisi</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700">Sesuai</span>
                    </div>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span id="telemetryTds" class="text-3xl sm:text-4xl font-extrabold text-emerald-600 tracking-tight">
                            {{ number_format($device->tds ?? 0, 0) }}
                        </span>
                        <span class="text-sm font-bold text-slate-400">PPM</span>
                    </div>
                    <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Target Nutrisi</span>
                        <span id="telemetryTargetTdsLabel" class="font-bold text-emerald-700">{{ number_format($device->target_tds, 0) }} PPM</span>
                    </div>
                </div>
            @endif

            <!-- Parameter 3: Target Kebutuhan Nutrisi (Jika Aktif) -->
            @if($device->has_auto_mode && $device->has_tds)
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Target Tanaman</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700">Dosis</span>
                    </div>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span id="telemetryTargetDisplay" class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                            {{ number_format($device->target_tds, 0) }}
                        </span>
                        <span class="text-sm font-bold text-slate-400">PPM</span>
                    </div>
                    <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Status Dosis</span>
                        <span class="font-semibold text-slate-700">Tercukupi</span>
                    </div>
                </div>
            @endif

            <!-- Parameter 4: Status Pompa Sirkulasi (Jika Aktif) -->
            @if($device->has_pump)
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pompa Sirkulasi</span>
                        <span id="pumpModeBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $device->auto_mode ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700' }}">
                            {{ $device->auto_mode ? 'Otomatis' : 'Manual' }}
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span id="telemetryPumpStatus" class="text-3xl sm:text-4xl font-extrabold {{ $device->pump_status ? 'text-emerald-600' : 'text-slate-400' }} tracking-tight">
                            {{ $device->pump_status ? 'Menyala' : 'Mati' }}
                        </span>
                        <span id="pumpPulseDot" class="w-3 h-3 rounded-full {{ $device->pump_status ? 'bg-emerald-500 pulse-pump' : 'bg-slate-300' }}"></span>
                    </div>
                    <div class="mt-3 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Sirkulasi Air</span>
                        <span class="font-semibold text-slate-700">{{ $device->pump_status ? 'Mengalir' : 'Berhenti' }}</span>
                    </div>
                </div>
            @endif

        </div>

        <!-- Section: Kendali Pompa & Target Nutrisi (Hanya Jika Alat Memiliki Pompa atau Target Nutrisi) -->
        @if($device->has_pump || ($device->has_tds && $device->has_auto_mode))
            <div id="control-section" class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm space-y-6">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            Pengaturan Kendali Kebun
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Atur pompa air dan sesuaikan dosis kebutuhan nutrisi tanaman Anda secara langsung.
                        </p>
                    </div>

                    <!-- Pemilih Mode Kerja (Jika Mendukung Mode Otomatis) -->
                    @if($device->has_auto_mode && $device->has_pump)
                        <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-200/80 self-start sm:self-auto">
                            <button onclick="setMode(true)" id="btnSelectAuto" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs {{ $device->auto_mode ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                                Mode Otomatis
                            </button>
                            <button onclick="setMode(false)" id="btnSelectManual" class="px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs {{ ! $device->auto_mode ? 'bg-slate-800 text-white' : 'text-slate-600 hover:bg-slate-200' }}">
                                Mode Manual
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Bagian 1: Saklar Pompa Air (Jika Terpasang Pompa) -->
                @if($device->has_pump)
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5">
                            1. Saklar Pompa Sirkulasi Air
                        </label>
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 max-w-md">
                            <button onclick="setPump(true)" id="btnPumpOn" class="flex items-center justify-center gap-2 px-4 sm:px-5 py-3.5 rounded-2xl font-bold text-xs sm:text-sm text-white bg-emerald-600 hover:bg-emerald-700 active:scale-95 transition shadow-lg shadow-emerald-600/20">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Nyalakan Pompa</span>
                            </button>

                            <button onclick="setPump(false)" id="btnPumpOff" class="flex items-center justify-center gap-2 px-4 sm:px-5 py-3.5 rounded-2xl font-bold text-xs sm:text-sm text-white bg-rose-600 hover:bg-rose-700 active:scale-95 transition shadow-lg shadow-rose-600/20">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span>Matikan Pompa</span>
                            </button>
                        </div>

                        @if($device->has_auto_mode)
                            <p id="modeWarningText" class="text-xs text-amber-700 font-medium mt-2.5 flex items-center gap-1.5 {{ $device->auto_mode ? 'block' : 'hidden' }}">
                                <span>Pada <b>Mode Otomatis</b>, pompa dikontrol mandiri oleh sistem. Menyalakan atau mematikan secara manual akan mengalihkan sistem ke <b>Mode Manual</b>.</span>
                            </p>
                        @endif
                    </div>
                @endif

                <!-- Bagian 2: Pengaturan Target Nutrisi Tanaman (Jika Mendukung Nutrisi) -->
                @if($device->has_tds)
                    <div class="pt-2 {{ $device->has_pump ? 'border-t border-slate-100' : '' }}">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                {{ $device->has_pump ? '2. Target Kepekatan Nutrisi' : '1. Target Kepekatan Nutrisi' }}
                            </label>
                            <span class="text-xs font-bold text-emerald-600" id="sliderValueBadge">{{ number_format($device->target_tds, 0) }} PPM</span>
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
                                    <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">PPM</span>
                                </div>
                                <button onclick="saveTargetTds()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition shrink-0">
                                    Simpan Target
                                </button>
                            </div>
                        </div>

                        <!-- Pilihan Cepat Kategori Tanaman -->
                        <div class="mt-3.5 flex flex-wrap items-center gap-2">
                            <span class="text-[11px] font-bold text-slate-400">Rekomendasi Tanaman:</span>
                            <button onclick="applyPreset(700)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition border border-emerald-100">
                                Selada (700 PPM)
                            </button>
                            <button onclick="applyPreset(900)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition border border-emerald-100">
                                Pakcoy (900 PPM)
                            </button>
                            <button onclick="applyPreset(1100)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition border border-emerald-100">
                                Bayam (1100 PPM)
                            </button>
                            <button onclick="applyPreset(1500)" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition border border-emerald-100">
                                Tomat (1500 PPM)
                            </button>
                        </div>
                    </div>
                @endif

            </div>
        @endif

        <!-- Section: Grafik Real-time -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Grafik Pemantauan Berkala</h3>
                    <p class="text-xs text-slate-500">Pembaruan data sensor berlangsung secara otomatis.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs font-bold">
                    @if($device->has_tds)
                        <div class="flex items-center gap-1.5 text-emerald-600">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span>Nutrisi (PPM)</span>
                        </div>
                    @endif
                    @if($device->has_temp)
                        <div class="flex items-center gap-1.5 text-amber-500">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span>Suhu (°C)</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="h-60 sm:h-72 w-full">
                <canvas id="telemetryChart"></canvas>
            </div>
        </div>

        <!-- Section: Riwayat Pembacaan -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Catatan Riwayat Pemantauan</h3>
                    <p class="text-xs text-slate-500">Daftar pengukuran berkala dari kebun Anda.</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">15 Data Terakhir</span>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase font-bold tracking-wider">
                            <th class="py-2.5 px-3">Waktu</th>
                            @if($device->has_temp)
                                <th class="py-2.5 px-3">Suhu Air</th>
                            @endif
                            @if($device->has_tds)
                                <th class="py-2.5 px-3">Kepekatan Nutrisi</th>
                            @endif
                            @if($device->has_pump)
                                <th class="py-2.5 px-3">Status Pompa</th>
                            @endif
                            @if($device->has_auto_mode)
                                <th class="py-2.5 px-3">Mode Kerja</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="telemetryTableBody" class="divide-y divide-slate-50 text-slate-700 font-medium">
                        @forelse($readings as $reading)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-2.5 px-3 font-mono text-slate-500">{{ $reading->created_at->format('H:i:s') }}</td>
                                @if($device->has_temp)
                                    <td class="py-2.5 px-3 font-bold text-slate-800">{{ number_format($reading->temperature, 1) }} °C</td>
                                @endif
                                @if($device->has_tds)
                                    <td class="py-2.5 px-3 font-bold text-emerald-600">{{ number_format($reading->tds, 0) }} PPM</td>
                                @endif
                                @if($device->has_pump)
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $reading->pump_status ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $reading->pump_status ? 'Menyala' : 'Mati' }}
                                        </span>
                                    </td>
                                @endif
                                @if($device->has_auto_mode)
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $reading->auto_mode ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $reading->auto_mode ? 'Otomatis' : 'Manual' }}
                                        </span>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">Belum ada catatan riwayat data untuk instalasi ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Modal Lengkap: Buku Panduan Penggunaan & Pengaturan (Super Ramah Petani & Admin) -->
    <div id="guideModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-5 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] flex flex-col">
            
            <!-- Judul Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3 shrink-0">
                <div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900">Buku Panduan HydroSense</h3>
                    <p class="text-xs text-slate-500">Petunjuk praktis penggunaan pemantauan dan kontrol kebun hidroponik</p>
                </div>
                <button onclick="toggleModal('guideModal')" class="text-slate-400 hover:text-slate-600 font-bold p-1 text-sm">Tutup</button>
            </div>

            <!-- Tab Navigasi Panduan -->
            <div class="flex items-center gap-1.5 border-b border-slate-100 pb-2 mb-4 shrink-0 overflow-x-auto custom-scrollbar">
                <button onclick="switchGuideTab('tab-monitor')" id="tabBtn-monitor" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white shrink-0">
                    Pemantauan & Kendali Pompa
                </button>
                <button onclick="switchGuideTab('tab-admin')" id="tabBtn-admin" class="px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 shrink-0">
                    Akun & Kustomisasi Alat
                </button>
                <button onclick="switchGuideTab('tab-wifi')" id="tabBtn-wifi" class="px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 shrink-0">
                    Sambungan WiFi Alat
                </button>
            </div>

            <!-- Konten Panduan (Scrollable) -->
            <div class="space-y-4 text-xs sm:text-sm text-slate-600 leading-relaxed overflow-y-auto custom-scrollbar pr-1 grow">
                
                <!-- Tab 1: Pemantauan & Kendali Pompa -->
                <div id="tabContent-monitor" class="space-y-3">
                    <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-100">
                        <span class="font-bold text-emerald-950 block mb-1">1. Membaca Data Nutrisi & Suhu:</span>
                        <p class="text-xs text-emerald-900 leading-relaxed">
                            Nilai <b>Kepekatan Nutrisi (PPM)</b> menunjukkan kadar kepekatan larutan makanan tanaman hidroponik Anda. Nilai <b>Suhu Air (°C)</b> menunjukkan suhu tandon nutrisi tanaman agar perakaran tetap segar dan tidak mudah busuk.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                        <span class="font-bold text-slate-900 block mb-1">2. Cara Menyalakan dan Mematikan Pompa via Web:</span>
                        <ul class="list-disc list-inside space-y-1 text-xs text-slate-700">
                            <li>Klik tombol hijau <b>Nyalakan Pompa</b> untuk menjalankan sirkulasi air kapan saja.</li>
                            <li>Klik tombol merah <b>Matikan Pompa</b> untuk menghentikan sirkulasi air jika sedang perbaikan atau pengurasan.</li>
                            <li>Pada layar HP, Anda juga dapat menekan <b>tombol bulat hijau di bagian tengah bawah</b> untuk saklar cepat.</li>
                        </ul>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-blue-50/70 border border-blue-100">
                        <span class="font-bold text-blue-950 block mb-1">3. Mode Otomatis vs Mode Manual:</span>
                        <p class="text-xs text-blue-900 leading-relaxed mb-1.5">
                            <b>Mode Otomatis:</b> Sistem secara mandiri menjaga sirkulasi dan kecukupan nutrisi tanaman berdasarkan target PPM yang Anda tentukan.
                        </p>
                        <p class="text-xs text-blue-900 leading-relaxed">
                            <b>Mode Manual:</b> Anda memiliki kendali penuh menyalakan atau mematikan pompa air tanpa campur tangan otomatis.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-100">
                        <span class="font-bold text-amber-950 block mb-1">4. Rekomendasi Target Nutrisi Tanaman:</span>
                        <p class="text-xs text-amber-900 leading-relaxed">
                            Gunakan tombol cepat rekomendasi tanaman pada panel pengaturan: Selada (700 PPM), Pakcoy (900 PPM), Bayam (1100 PPM), atau Tomat (1500 PPM).
                        </p>
                    </div>
                </div>

                <!-- Tab 2: Akun & Kustomisasi Alat (Untuk Admin & Petani) -->
                <div id="tabContent-admin" class="space-y-3 hidden">
                    <div class="p-3.5 rounded-2xl bg-purple-50/70 border border-purple-100">
                        <span class="font-bold text-purple-950 block mb-1">1. Sistem Akun Pengguna & Pembagian Instalasi:</span>
                        <p class="text-xs text-purple-900 leading-relaxed mb-1.5">
                            <b>Akun Petani:</b> Ketika login, petani hanya melihat dan mengendalikan instalasi kebun yang ditugaskan kepada mereka (misalnya Akun Sumedang mengelola unit Sumedang).
                        </p>
                        <p class="text-xs text-purple-900 leading-relaxed">
                            <b>Akun Administrator:</b> Dapat melihat semua instalasi kebun, menambahkan alat baru, membuat akun petani, dan mengatur fitur masing-masing alat.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-teal-50/70 border border-teal-100">
                        <span class="font-bold text-teal-950 block mb-1">2. Kustomisasi Fitur Tiap Alat (Sesuai Kebutuhan Lapangan):</span>
                        <p class="text-xs text-teal-900 leading-relaxed mb-1.5">
                            Setiap instalasi kebun memiliki kebutuhan berbeda. Administrator dapat mengatur kelengkapan fitur tiap alat melalui tombol <b>Atur Fitur Alat</b>:
                        </p>
                        <ul class="list-disc list-inside space-y-1 text-xs text-teal-900">
                            <li><b>Hanya Sensor Nutrisi (TDS):</b> Jika kebun hanya butuh pemantauan nutrisi tanpa pompa.</li>
                            <li><b>Multi Sensor (Nutrisi + Suhu):</b> Menampilkan nutrisi dan suhu secara bersamaan.</li>
                            <li><b>Dengan Saklar Pompa Air:</b> Menampilkan tombol kontrol saklar sirkulasi air.</li>
                            <li><b>Mode Otomatis Nutrisi:</b> Mengaktifkan pengaturan target dosis tanaman.</li>
                        </ul>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                        <span class="font-bold text-slate-900 block mb-1">3. Cara Menghubungkan Akun Petani ke Alat:</span>
                        <ol class="list-decimal list-inside space-y-1 text-xs text-slate-700">
                            <li>Klik tombol <b>+ Akun Petani</b> untuk membuat akun pemilik kebun baru.</li>
                            <li>Klik tombol <b>Atur Fitur Alat</b> pada instalasi yang ingin dihubungkan.</li>
                            <li>Pada pilihan <b>Akun Pemilik / Petani</b>, pilih nama petani yang bersangkutan.</li>
                            <li>Klik <b>Simpan Perubahan</b>. Petani tersebut kini dapat memantau alatnya langsung.</li>
                        </ol>
                    </div>
                </div>

                <!-- Tab 3: Sambungan WiFi Alat Baru -->
                <div id="tabContent-wifi" class="space-y-3 hidden">
                    <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-emerald-950">
                        <span class="font-bold block mb-1">Jika Sambungan Terputus atau Alat Baru:</span>
                        <p class="text-xs leading-relaxed">
                            Perangkat alat secara otomatis memancarkan jaringan WiFi sendiri bernama <b>SMART-HYDROPONIC</b> jika belum terhubung ke internet kebun.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700">
                        <span class="font-bold block mb-2 text-slate-900">Langkah Menghubungkan WiFi Mandiri:</span>
                        <ol class="list-decimal list-inside space-y-2 text-xs">
                            <li>Buka menu sambungan WiFi di HP Anda, pilih jaringan bernama <b>SMART-HYDROPONIC</b> (Kata sandi: <b>12345678</b>).</li>
                            <li>Buka peramban (browser Google Chrome atau Safari) di HP Anda, lalu ketik alamat: <b>192.168.4.1</b></li>
                            <li>Pilih atau masukkan nama WiFi kebun Anda beserta kata sandinya.</li>
                            <li>Masukkan kode alat kebun Anda (misal: <b>alat1sumedang</b>).</li>
                            <li>Klik tombol <b>Simpan & Sambungkan</b>. Alat akan otomatis terhubung ke internet dan halaman web ini akan langsung menampilkan data kebun Anda.</li>
                        </ol>
                    </div>
                </div>

            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end shrink-0">
                <button onclick="toggleModal('guideModal')" class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                    Mengerti & Tutup
                </button>
            </div>
        </div>
    </div>

    @if(auth()->check() && auth()->user()->isAdmin())
        <!-- Modal 2: Kustomisasi Fitur Alat yang Sedang Aktif (Admin Saja) -->
        <div id="editDeviceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Atur Fitur & Kepemilikan Alat</h3>
                        <p class="text-xs text-slate-500">Sesuaikan kemampuan sensor dan pompa untuk: <b>{{ $device->name }}</b></p>
                    </div>
                    <button onclick="toggleModal('editDeviceModal')" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>

                <form action="{{ route('admin.devices.update', $device->id) }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Instalasi / Kebun</label>
                        <input type="text" name="name" value="{{ $device->name }}" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-semibold">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Lokasi Kebun</label>
                            <input type="text" name="location" value="{{ $device->location }}" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Target Nutrisi Awal (PPM)</label>
                            <input type="number" name="target_tds" value="{{ $device->target_tds }}" min="100" max="3000" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Hubungkan ke Akun Petani</label>
                        <select name="user_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-semibold">
                            <option value="">-- Semua Petani / Terbuka --</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" {{ $device->user_id === $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-slate-400 mt-1 block">Petani yang dipilih hanya akan melihat alat ini di dasbor mereka.</span>
                    </div>

                    <!-- Kustomisasi Fitur Perangkat (Checkboxes) -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <span class="font-bold text-slate-900 block text-xs">Pilih Fitur yang Aktif pada Alat Ini:</span>
                        
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="has_tds" value="1" {{ $device->has_tds ? 'checked' : '' }}
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <span class="text-xs text-slate-700 font-semibold">Sensor Kepekatan Nutrisi (TDS / PPM)</span>
                        </label>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="has_temp" value="1" {{ $device->has_temp ? 'checked' : '' }}
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <span class="text-xs text-slate-700 font-semibold">Sensor Suhu Air (°C)</span>
                        </label>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="has_pump" value="1" {{ $device->has_pump ? 'checked' : '' }}
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <span class="text-xs text-slate-700 font-semibold">Saklar & Kendali Pompa Sirkulasi Air</span>
                        </label>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="has_auto_mode" value="1" {{ $device->has_auto_mode ? 'checked' : '' }}
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <span class="text-xs text-slate-700 font-semibold">Mode Otomatis Nutrisi Tanaman</span>
                        </label>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                        <input type="text" name="notes" value="{{ $device->notes }}" placeholder="Contoh: Rak NFT Selada Pipa 2.5 inch"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>

                    <div class="pt-3 flex justify-end gap-2">
                        <button type="button" onclick="toggleModal('editDeviceModal')" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold shadow-md shadow-teal-600/20">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 3: Tambah Instalasi Baru (Admin Saja) -->
        <div id="createDeviceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto custom-scrollbar">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Tambah Instalasi Baru</h3>
                        <p class="text-xs text-slate-500">Daftarkan alat baru untuk kebun atau greenhouse lain.</p>
                    </div>
                    <button onclick="toggleModal('createDeviceModal')" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>

                <form action="{{ route('admin.devices.store') }}" method="POST" class="space-y-3.5 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Instalasi / Kebun</label>
                        <input type="text" name="name" placeholder="Contoh: HydroSense Kebun Garut Unit 1" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-medium">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Unik Alat (Tanpa Spasi)</label>
                        <input type="text" name="device_code" placeholder="Contoh: alat1garut atau alat2sumedang" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-semibold">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Lokasi Kebun</label>
                            <input type="text" name="location" placeholder="Contoh: Cisewu, Garut" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Target Nutrisi Awal (PPM)</label>
                            <input type="number" name="target_tds" value="800" min="200" max="2500" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Akun Pemilik / Petani</label>
                        <select name="user_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-semibold">
                            <option value="">-- Pilih Akun Pemilik (Bisa Kosong) --</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pilihan Fitur Alat Baru -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                        <span class="font-bold text-slate-900 block text-xs">Fitur Terpasang:</span>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="has_tds" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs text-slate-700 font-medium">Sensor Nutrisi</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="has_temp" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs text-slate-700 font-medium">Sensor Suhu</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="has_pump" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs text-slate-700 font-medium">Pompa Sirkulasi</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="has_auto_mode" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs text-slate-700 font-medium">Mode Otomatis</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Keterangan / Catatan</label>
                        <input type="text" name="notes" placeholder="Contoh: Rak Selada NFT pipa 2.5 inch"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>

                    <div class="pt-3 flex justify-end gap-2">
                        <button type="button" onclick="toggleModal('createDeviceModal')" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md shadow-emerald-600/20">Simpan Instalasi</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 4: Tambah Akun Petani Baru (Admin Saja) -->
        <div id="createUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Tambah Akun Petani Baru</h3>
                        <p class="text-xs text-slate-500">Berikan akses login khusus untuk pemilik kebun.</p>
                    </div>
                    <button onclick="toggleModal('createUserModal')" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>

                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Petani</label>
                        <input type="text" name="name" placeholder="Contoh: Petani Hidroponik Garut" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-medium">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Pengguna (Username)</label>
                        <input type="text" name="username" placeholder="Contoh: petanigarut" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-semibold">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" placeholder="Contoh: garut@agronex.id" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kata Sandi</label>
                        <input type="password" name="password" placeholder="Minimal 6 karakter" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>

                    <div class="pt-3 flex justify-end gap-2">
                        <button type="button" onclick="toggleModal('createUserModal')" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold">Buat Akun</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Navigasi Bawah Khusus Layar Ponsel (Mobile Native Navigation) -->
    <nav class="fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/80 px-4 py-2 sm:hidden">
        <div class="flex items-center justify-around max-w-md mx-auto relative">
            <a href="#" class="flex flex-col items-center gap-1 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[10px] font-bold">Beranda</span>
            </a>

            @if($device->has_pump || $device->has_auto_mode)
                <a href="#control-section" class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span class="text-[10px] font-semibold">Kontrol</span>
                </a>
            @endif

            <!-- Tombol Tengah Cepat Saklar Pompa (Jika Terpasang Pompa) -->
            @if($device->has_pump)
                <button onclick="quickTogglePump()" class="w-12 h-12 -mt-6 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/40 border-4 border-white transition active:scale-95" title="Saklar Pompa">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </button>
            @endif

            <a href="#telemetryChart" class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                <span class="text-[10px] font-semibold">Grafik</span>
            </a>

            <button onclick="openGuideModal('tab-monitor')" class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-[10px] font-semibold">Panduan</span>
            </button>
        </div>
    </nav>

    <!-- Skrip Interaktif -->
    <script>
        const DEVICE_CODE = "{{ $device->device_code }}";
        const HAS_TEMP = {{ $device->has_temp ? 'true' : 'false' }};
        const HAS_TDS = {{ $device->has_tds ? 'true' : 'false' }};
        const HAS_PUMP = {{ $device->has_pump ? 'true' : 'false' }};
        const HAS_AUTO = {{ $device->has_auto_mode ? 'true' : 'false' }};

        let currentPump = {{ $device->pump_status ? 'true' : 'false' }};
        let currentAuto = {{ $device->auto_mode ? 'true' : 'false' }};
        let currentTargetTds = {{ $device->target_tds }};

        // Inisialisasi Grafik Chart.js
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

        const chartDatasets = [];
        if (HAS_TDS) {
            chartDatasets.push({
                label: 'Kepekatan Nutrisi (PPM)',
                data: initialTdsData,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.3,
                yAxisID: 'y',
            });
        }
        if (HAS_TEMP) {
            chartDatasets.push({
                label: 'Suhu Air (°C)',
                data: initialTempData,
                borderColor: '#f59e0b',
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [4, 4],
                tension: 0.3,
                yAxisID: 'y1',
            });
        }

        const ctx = document.getElementById('telemetryChart').getContext('2d');
        const telemetryChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: initialLabels,
                datasets: chartDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        type: 'linear',
                        display: HAS_TDS,
                        position: 'left',
                        title: { display: true, text: 'Nutrisi (PPM)', font: { size: 10 } },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    y1: {
                        type: 'linear',
                        display: HAS_TEMP,
                        position: 'right',
                        title: { display: true, text: 'Suhu (°C)', font: { size: 10 } },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const msg = document.getElementById('toast-message');

            msg.innerText = message;
            icon.innerText = isError ? '✕' : '✓';
            icon.className = isError 
                ? 'w-5 h-5 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-xs shrink-0' 
                : 'w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0';

            toast.classList.remove('translate-y-[-140%]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.add('translate-y-[-140%]', 'opacity-0');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 3000);
        }

        function toggleModal(id) {
            const el = document.getElementById(id);
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }

        function openGuideModal(tabId) {
            toggleModal('guideModal');
            if (tabId) {
                switchGuideTab(tabId);
            }
        }

        function switchGuideTab(tabId) {
            const tabs = ['tab-monitor', 'tab-admin', 'tab-wifi'];
            tabs.forEach(t => {
                const content = document.getElementById('tabContent-' + t.replace('tab-', ''));
                const btn = document.getElementById('tabBtn-' + t.replace('tab-', ''));
                if (t === tabId) {
                    content.classList.remove('hidden');
                    btn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white shrink-0';
                } else {
                    content.classList.add('hidden');
                    btn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 shrink-0';
                }
            });
        }

        function updateSliderLabel(val) {
            const badge = document.getElementById('sliderValueBadge');
            const input = document.getElementById('targetTdsInput');
            if (badge) badge.innerText = `${val} PPM`;
            if (input) input.value = val;
        }

        const targetTdsInputEl = document.getElementById('targetTdsInput');
        if (targetTdsInputEl) {
            targetTdsInputEl.addEventListener('input', function(e) {
                const val = e.target.value;
                if (val >= 400 && val <= 1800) {
                    const slider = document.getElementById('targetTdsSlider');
                    const badge = document.getElementById('sliderValueBadge');
                    if (slider) slider.value = val;
                    if (badge) badge.innerText = `${val} PPM`;
                }
            });
        }

        function applyPreset(val) {
            const slider = document.getElementById('targetTdsSlider');
            const input = document.getElementById('targetTdsInput');
            const badge = document.getElementById('sliderValueBadge');
            if (slider) slider.value = val;
            if (input) input.value = val;
            if (badge) badge.innerText = `${val} PPM`;
            saveTargetTds();
        }

        async function sendControl(payload) {
            try {
                const res = await fetch(`/api/sensor/${DEVICE_CODE}/control`, {
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
                    showToast('Pengaturan berhasil diperbarui');
                    fetchLatestData();
                } else {
                    showToast('Gagal memperbarui pengaturan', true);
                }
            } catch (err) {
                console.error(err);
                showToast('Koneksi ke server terputus', true);
            }
        }

        function setPump(state) {
            currentPump = state;
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
            const input = document.getElementById('targetTdsInput');
            if (!input) return;
            const val = parseFloat(input.value);
            if (!val || val < 100) {
                showToast('Masukkan target nutrisi yang sesuai', true);
                return;
            }
            sendControl({ target_tds: val });
        }

        async function fetchLatestData() {
            try {
                const res = await fetch(`/api/sensor/${DEVICE_CODE}/latest`);
                const data = await res.json();
                if (data.status === 'success' && data.device) {
                    const dev = data.device;
                    currentPump = dev.pump_status;
                    currentAuto = dev.auto_mode;
                    currentTargetTds = dev.target_tds;

                    const tempEl = document.getElementById('telemetryTemp');
                    if (tempEl) tempEl.innerText = dev.temperature.toFixed(1);

                    const tdsEl = document.getElementById('telemetryTds');
                    if (tdsEl) tdsEl.innerText = dev.tds.toFixed(0);

                    const targetDisp = document.getElementById('telemetryTargetDisplay');
                    if (targetDisp) targetDisp.innerText = dev.target_tds.toFixed(0);

                    const pumpStatusEl = document.getElementById('telemetryPumpStatus');
                    const quickPump = document.getElementById('quickPumpStatus');
                    const pumpPulse = document.getElementById('pumpPulseDot');

                    if (pumpStatusEl) pumpStatusEl.innerText = dev.pump_status ? 'Menyala' : 'Mati';
                    if (quickPump) quickPump.innerText = 'Pompa: ' + (dev.pump_status ? 'Menyala' : 'Mati');

                    if (dev.pump_status) {
                        if (pumpStatusEl) pumpStatusEl.className = 'text-3xl sm:text-4xl font-extrabold text-emerald-600 tracking-tight';
                        if (pumpPulse) pumpPulse.className = 'w-3 h-3 rounded-full bg-emerald-500 pulse-pump';
                    } else {
                        if (pumpStatusEl) pumpStatusEl.className = 'text-3xl sm:text-4xl font-extrabold text-slate-400 tracking-tight';
                        if (pumpPulse) pumpPulse.className = 'w-3 h-3 rounded-full bg-slate-300';
                    }

                    const pumpModeBadge = document.getElementById('pumpModeBadge');
                    if (pumpModeBadge) {
                        pumpModeBadge.innerText = dev.auto_mode ? 'Otomatis' : 'Manual';
                        pumpModeBadge.className = `text-[10px] font-bold px-2 py-0.5 rounded-md ${dev.auto_mode ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700'}`;
                    }

                    const btnAuto = document.getElementById('btnSelectAuto');
                    const btnManual = document.getElementById('btnSelectManual');
                    const modeWarn = document.getElementById('modeWarningText');
                    if (btnAuto && btnManual) {
                        if (dev.auto_mode) {
                            btnAuto.className = 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs bg-blue-600 text-white';
                            btnManual.className = 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs text-slate-600 hover:bg-slate-200';
                            if (modeWarn) modeWarn.classList.remove('hidden');
                        } else {
                            btnAuto.className = 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs text-slate-600 hover:bg-slate-200';
                            btnManual.className = 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-xs bg-slate-800 text-white';
                            if (modeWarn) modeWarn.classList.add('hidden');
                        }
                    }

                    const targetTdsLabel = document.getElementById('telemetryTargetTdsLabel');
                    if (targetTdsLabel) targetTdsLabel.innerText = `${dev.target_tds.toFixed(0)} PPM`;

                    const badge = document.getElementById('deviceBadgeStatus');
                    const heroBadge = document.getElementById('heroBadge');
                    const heroStatusDesc = document.getElementById('heroStatusDescription');

                    if (dev.is_online) {
                        badge.className = 'flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800';
                        badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 pulse-online"></span><span>Terhubung</span>';

                        heroBadge.className = 'flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold tracking-wider bg-emerald-950/80 text-emerald-400 border border-emerald-600/40';
                        heroBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-emerald-400 pulse-online"></span><span>SISTEM NORMAL</span>';

                        heroStatusDesc.innerText = 'Sistem pemantauan aktif dan terhubung normal. Pengukuran sensor berjalan lancar.';
                    } else {
                        badge.className = 'flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800';
                        badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-500"></span><span>Terputus</span>';

                        heroBadge.className = 'flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold tracking-wider bg-amber-950/80 text-amber-400 border border-amber-600/40';
                        heroBadge.innerHTML = '<span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span><span>PERHATIAN</span>';

                        heroStatusDesc.innerText = 'Perangkat kebun belum tersambung ke jaringan. Jika koneksi terputus, sambungkan ke WiFi cadangan alat untuk menghubungkan kembali.';
                    }

                    document.getElementById('heroWifiSSID').innerText = dev.wifi_ssid || 'Belum Terhubung';
                    document.getElementById('heroLastSeen').innerText = dev.last_seen_formatted;
                }
            } catch (e) {
                console.error('Fetch error:', e);
            }
        }

        async function fetchHistoryData() {
            try {
                const res = await fetch(`/api/sensor/${DEVICE_CODE}/history`);
                const data = await res.json();
                if (data.status === 'success' && data.history && data.history.length > 0) {
                    const times = data.history.map(item => item.time);
                    telemetryChart.data.labels = times;

                    let datasetIdx = 0;
                    if (HAS_TDS && telemetryChart.data.datasets[datasetIdx]) {
                        telemetryChart.data.datasets[datasetIdx].data = data.history.map(item => item.tds);
                        datasetIdx++;
                    }
                    if (HAS_TEMP && telemetryChart.data.datasets[datasetIdx]) {
                        telemetryChart.data.datasets[datasetIdx].data = data.history.map(item => item.temperature);
                    }
                    telemetryChart.update();

                    const tbody = document.getElementById('telemetryTableBody');
                    const reversedHistory = [...data.history].reverse().slice(0, 15);
                    tbody.innerHTML = reversedHistory.map(row => `
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-2.5 px-3 font-mono text-slate-500">${row.time}</td>
                            ${HAS_TEMP ? `<td class="py-2.5 px-3 font-bold text-slate-800">${row.temperature.toFixed(1)} °C</td>` : ''}
                            ${HAS_TDS ? `<td class="py-2.5 px-3 font-bold text-emerald-600">${row.tds.toFixed(0)} PPM</td>` : ''}
                            ${HAS_PUMP ? `
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${row.pump_status ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}">
                                        ${row.pump_status ? 'Menyala' : 'Mati'}
                                    </span>
                                </td>
                            ` : ''}
                            ${HAS_AUTO ? `
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${currentAuto ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600'}">
                                        ${currentAuto ? 'Otomatis' : 'Manual'}
                                    </span>
                                </td>
                            ` : ''}
                        </tr>
                    `).join('');
                }
            } catch (e) {
                console.error('History fetch error:', e);
            }
        }

        setInterval(() => { fetchLatestData(); }, 2500);
        setInterval(() => { fetchHistoryData(); }, 5000);
    </script>
</body>
</html>
