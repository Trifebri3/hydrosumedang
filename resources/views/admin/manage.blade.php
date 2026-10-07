<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Manajemen Sistem - HydroSense by agronex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .pulse-online { animation: pulseGreen 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulseGreen { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }
    </style>
</head>
<body class="bg-slate-50 font-['Plus_Jakarta_Sans',sans-serif] text-slate-800 min-h-screen flex flex-col antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Navigation Bar (Fokus Manajemen Admin) -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            
            <!-- Brand & Judul Halaman -->
            <div class="flex items-center gap-3">
                <img src="https://denrawit.agronex.id/logo.png" alt="Agronex Logo" class="h-9 w-auto">
                <div class="leading-tight">
                    <span class="text-base font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5">
                        HydroSense
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800">Admin</span>
                    </span>
                    <span class="text-[11px] font-bold text-emerald-700 tracking-wider uppercase block">Panel Manajemen Terpusat</span>
                </div>
            </div>

            <!-- Tombol Navigasi Utama -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition flex items-center gap-1.5 shadow-xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Pantau Kebun (Live)</span>
                </a>

                <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                <div class="hidden md:flex items-center gap-2 text-xs font-semibold text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>{{ auth()->user()->name }}</span>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-2 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 transition shadow-xs">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 grow space-y-6">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold p-1">✕</button>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs sm:text-sm font-semibold space-y-1 shadow-xs">
                @if(session('error'))
                    <p>{{ session('error') }}</p>
                @endif
                @foreach($errors->all() as $err)
                    <p>&bull; {{ $err }}</p>
                @endforeach
            </div>
        @endif

        <!-- Header Ringkasan Statistik Sistem -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
                <span class="text-slate-400 text-[11px] font-bold uppercase tracking-wider block mb-1">Total Instalasi Alat</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ $devices->count() }}</span>
                    <span class="text-xs font-semibold text-slate-500">Unit</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
                <span class="text-slate-400 text-[11px] font-bold uppercase tracking-wider block mb-1">Status Online</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ $onlineCount }}</span>
                    <span class="text-xs font-semibold text-slate-500">Tersambung</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
                <span class="text-slate-400 text-[11px] font-bold uppercase tracking-wider block mb-1">Akun Pengguna</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ $users->count() }}</span>
                    <span class="text-xs font-semibold text-slate-500">Petani / Admin</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
                <span class="text-slate-400 text-[11px] font-bold uppercase tracking-wider block mb-1">Rekaman NoSQL</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-extrabold text-indigo-600">{{ number_format($totalReadings, 0, ',', '.') }}</span>
                    <span class="text-xs font-semibold text-slate-500">Data</span>
                </div>
            </div>
        </div>

        <!-- Tab Navigasi Menu Manajemen -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1">
                    <button onclick="switchTab('tab-devices')" id="tabBtn-devices" 
                        class="px-4 py-2 rounded-xl text-xs font-bold transition bg-emerald-600 text-white shadow-xs">
                        Manajemen Alat Kebun ({{ $devices->count() }})
                    </button>
                    <button onclick="switchTab('tab-users')" id="tabBtn-users" 
                        class="px-4 py-2 rounded-xl text-xs font-bold transition text-slate-600 hover:bg-slate-100">
                        Manajemen Pengguna ({{ $users->count() }})
                    </button>
                    <button onclick="switchTab('tab-nosql')" id="tabBtn-nosql" 
                        class="px-4 py-2 rounded-xl text-xs font-bold transition text-slate-600 hover:bg-slate-100">
                        Uji JSON & NoSQL Inspector
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button onclick="openModal('createDeviceModal')" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Alat Baru</span>
                    </button>
                    <button onclick="openModal('createUserModal')" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        <span>Tambah Pengguna</span>
                    </button>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 1: MANAJEMEN ALAT KEBUN (CRUD LENGKAP) -->
            <!-- ======================================================== -->
            <div id="section-devices" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900">Daftar Instalasi Alat Kebun</h2>
                        <p class="text-xs text-slate-500">Setiap alat wajib terikat ke 1 pengguna untuk privasi dan kontrol aman.</p>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar border border-slate-100 rounded-2xl">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                                <th class="py-3 px-4">Nama Alat & Kode</th>
                                <th class="py-3 px-4">Pemilik Petani (User)</th>
                                <th class="py-3 px-4">Lokasi Kebun</th>
                                <th class="py-3 px-4">Status & Koneksi</th>
                                <th class="py-3 px-4">Sensor Terpasang</th>
                                <th class="py-3 px-4">Pompa</th>
                                <th class="py-3 px-4 text-right">Aksi Manajemen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @forelse($devices as $d)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 text-sm">{{ $d->name }}</div>
                                        <div class="font-mono text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <span>Kode API:</span>
                                            <span class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-800 font-semibold">{{ $d->device_code }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($d->user)
                                            <div class="font-bold text-slate-800">{{ $d->user->name }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $d->user->email }}</div>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 font-bold text-[10px]">Belum Terikat</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600">
                                        {{ $d->location }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $d->isOnline() ? 'bg-emerald-500 pulse-online' : 'bg-slate-300' }}"></span>
                                            <span class="font-bold text-[11px] {{ $d->isOnline() ? 'text-emerald-700' : 'text-slate-500' }}">
                                                {{ $d->isOnline() ? 'Online' : 'Offline' }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            {{ $d->last_seen_at ? $d->last_seen_at->diffForHumans() : 'Belum pernah konek' }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex flex-wrap gap-1">
                                            @if($d->has_tds)
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 text-[10px] font-bold">TDS</span>
                                            @endif
                                            @if($d->has_temp)
                                                <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 text-[10px] font-bold">Suhu</span>
                                            @endif
                                            @if($d->hasPh())
                                                <span class="px-1.5 py-0.5 rounded bg-violet-50 text-violet-800 text-[10px] font-bold">pH</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @php $pList = $d->getPumpsList(); @endphp
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ count($pList) > 0 ? 'bg-blue-50 text-blue-800' : 'bg-slate-100 text-slate-500' }}">
                                            {{ count($pList) }} Pompa
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('dashboard', ['device' => $d->device_code]) }}" 
                                                class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold transition text-[11px]">
                                                Pantau
                                            </a>
                                            <button type="button" onclick='openEditDeviceModal(@json($d))'
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold transition text-[11px]">
                                                Atur
                                            </button>
                                            <button type="button" onclick='openSimulateModal(@json($d))'
                                                class="px-2.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold transition text-[11px]">
                                                Uji JSON
                                            </button>
                                            <form action="{{ route('admin.devices.destroy', $d->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus instalasi ini? Seluruh data riwayat telemetri akan terhapus.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold transition text-[11px]">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400">
                                        Belum ada alat yang didaftarkan. Klik tombol <b>+ Tambah Alat Baru</b> di atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: MANAJEMEN PENGGUNA & PETANI (CRUD LENGKAP) -->
            <!-- ======================================================== -->
            <div id="section-users" class="space-y-4 hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900">Daftar Akun Pengguna & Petani</h2>
                        <p class="text-xs text-slate-500">Kelola kredensial akun petani dan hak akses masing-masing kebun.</p>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar border border-slate-100 rounded-2xl">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                                <th class="py-3 px-4">Nama Petani</th>
                                <th class="py-3 px-4">Username & Email</th>
                                <th class="py-3 px-4">Peran (Role)</th>
                                <th class="py-3 px-4">Alat Terikat</th>
                                <th class="py-3 px-4">Tanggal Dibuat</th>
                                <th class="py-3 px-4 text-right">Aksi Manajemen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @forelse($users as $u)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">
                                        {{ $u->name }}
                                        @if($u->id === auth()->id())
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 ml-1">Akun Anda</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-slate-600">
                                        <div><b>@</b>{{ $u->username }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $u->email }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $u->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $u->role === 'admin' ? 'Administrator' : 'Petani Pengguna' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-bold text-slate-800">{{ $u->devices_count }}</span>
                                        <span class="text-slate-500">Unit Kebun</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500">
                                        {{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" onclick='openEditUserModal(@json($u))'
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold transition text-[11px]">
                                                Ubah Akun
                                            </button>
                                            @if($u->id !== auth()->id())
                                                <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold transition text-[11px]">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">
                                        Belum ada pengguna.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 3: TOOLS UJI FORMAT JSON & NoSQL INSPECTOR -->
            <!-- ======================================================== -->
            <div id="section-nosql" class="space-y-4 hidden">
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900">Uji Format JSON & NoSQL Inspector</h2>
                    <p class="text-xs text-slate-500">Simulasikan pengiriman data telemetri modular untuk menguji auto-discovery sensor dan multi-pompa.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Kolom Kiri: Form Simulasi -->
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <label class="block font-bold text-slate-800 text-xs">Pilih Instalasi Target Uji:</label>
                        <select id="simTargetDevice" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold bg-white" onchange="updateSimTarget()">
                            @foreach($devices as $d)
                                <option value="{{ $d->id }}" data-code="{{ $d->device_code }}" data-payload="{{ json_encode($d->last_payload) }}">
                                    {{ $d->name }} ({{ $d->device_code }})
                                </option>
                            @endforeach
                        </select>

                        <div class="flex items-center gap-1.5 overflow-x-auto py-1">
                            <button type="button" onclick="setSimPreset('standar')" class="px-2.5 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-[11px]">Standar 1 Pompa</button>
                            <button type="button" onclick="setSimPreset('lengkap')" class="px-2.5 py-1 rounded-lg bg-violet-100 hover:bg-violet-200 text-violet-800 font-bold text-[11px]">5 Pompa + pH</button>
                            <button type="button" onclick="setSimPreset('pantau')" class="px-2.5 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-800 font-bold text-[11px]">Pos Pantau</button>
                        </div>

                        <form id="simForm" action="" method="POST" class="space-y-3">
                            @csrf
                            <label class="block font-bold text-slate-800 text-xs">Payload JSON yang Dikirim:</label>
                            <textarea id="simPayloadJson" name="payload_json" rows="10" required
                                class="w-full p-3 font-mono text-xs rounded-xl border border-slate-700 bg-slate-900 text-emerald-400"></textarea>

                            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-indigo-600/20">
                                Kirim Simulasi & Simpan Dokumen NoSQL
                            </button>
                        </form>
                    </div>

                    <!-- Kolom Kanan: Panduan & Dokumen Tersimpan -->
                    <div class="p-5 rounded-2xl bg-white border border-slate-200 space-y-4">
                        <div class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-950 text-xs leading-relaxed">
                            <span class="font-bold block mb-1">Prinsip Dokumen NoSQL Modular:</span>
                            Alat IoT Anda bebas menambahkan kunci sensor baru apa pun (misal: <code>water_level</code>, <code>humidity</code>, <code>ec</code>, <code>lux</code>) atau daftar pompa baru di dalam objek <code>pumps</code>. Server langsung mengenali dan menyimpannya ke database tanpa migrasi baru.
                        </div>

                        <div>
                            <span class="block font-bold text-slate-800 text-xs mb-1">Dokumen NoSQL Terakhir di Database Alat Ini:</span>
                            <pre id="simLastPayloadPreview" class="p-3 bg-slate-900 text-teal-300 font-mono text-[11px] rounded-xl overflow-x-auto max-h-56 custom-scrollbar whitespace-pre-wrap"></pre>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 border-t border-slate-200 text-center text-xs text-slate-400">
        HydroSense by agronex &bull; Manajemen Perangkat Hidroponik Terpusat &bull; Dilindungi Hak Cipta
    </footer>

    <!-- ======================================================== -->
    <!-- MODAL: TAMBAH ALAT BARU (WAJIB TERIKAT KE 1 USER) -->
    <!-- ======================================================== -->
    <div id="createDeviceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Pendaftaran Alat Kebun Baru</h3>
                    <p class="text-xs text-slate-500">Alat baru wajib terikat ke 1 pengguna petani terdaftar.</p>
                </div>
                <button onclick="closeModal('createDeviceModal')" class="text-slate-400 hover:text-slate-600 font-bold p-1">✕</button>
            </div>

            <form action="{{ route('admin.devices.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Instalasi Kebun</label>
                    <input type="text" name="name" placeholder="Contoh: HydroSense Greenhouse 2" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Alat / Kunci API</label>
                        <input type="text" name="device_code" placeholder="Contoh: alat2lembang" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Lokasi Kebun</label>
                        <input type="text" name="location" placeholder="Contoh: Blok B Cisewu" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>
                </div>

                <!-- WAJIB PILIH PEMILIK PETANI -->
                <div class="p-3.5 rounded-2xl bg-amber-50/80 border border-amber-200">
                    <label class="block font-extrabold text-amber-950 mb-1">Wajib Hubungkan ke 1 Akun Petani:</label>
                    <select name="user_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-amber-300 bg-white focus:ring-2 focus:ring-emerald-500 text-xs font-bold text-slate-800">
                        <option value="">-- Pilih Petani Pemilik Alat --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">
                                {{ $u->name }} ({{ $u->email }}) [{{ $u->role }}]
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-amber-800 mt-1 block">Hanya akun terpilih yang dapat memantau dan mengontrol alat ini.</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Target Nutrisi Awal (PPM)</label>
                        <input type="number" name="target_tds" value="800" min="100" max="3000" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                        <input type="text" name="notes" placeholder="Opsional"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>
                </div>

                <!-- Sensor Checkboxes -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <span class="font-bold text-slate-900 block text-xs">Fitur Sensor yang Aktif:</span>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_tds" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Sensor Kepekatan Nutrisi (TDS / PPM)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_temp" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Sensor Suhu Air (°C)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_ph" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Sensor Derajat Keasaman (pH Air)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_pump" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Saklar Pompa Relai</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_auto_mode" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Mode Otomatis Nutrisi</span>
                    </label>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Daftar Pompa Terpasang (Pisahkan Koma)</label>
                    <input type="text" name="pump_names" value="Pompa Sirkulasi" placeholder="Pompa Sirkulasi, Pompa Pupuk A, Pompa Pupuk B"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('createDeviceModal')" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md shadow-emerald-600/20">
                        Simpan & Daftarkan Alat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: EDIT ALAT (UPDATE LENGKAP) -->
    <!-- ======================================================== -->
    <div id="editDeviceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Perbarui Fitur & Kepemilikan Alat</h3>
                    <p class="text-xs text-slate-500" id="editDeviceSubTitle">Sesuaikan setelan instalasi.</p>
                </div>
                <button onclick="closeModal('editDeviceModal')" class="text-slate-400 hover:text-slate-600 font-bold p-1">✕</button>
            </div>

            <form id="editDeviceForm" action="" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Instalasi Kebun</label>
                    <input type="text" id="editDeviceName" name="name" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Alat / Kunci API</label>
                        <input type="text" id="editDeviceCode" name="device_code" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Lokasi Kebun</label>
                        <input type="text" id="editDeviceLocation" name="location" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-amber-50/80 border border-amber-200">
                    <label class="block font-extrabold text-amber-950 mb-1">Pemilik Petani (User Terikat):</label>
                    <select id="editDeviceUserId" name="user_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-amber-300 bg-white focus:ring-2 focus:ring-emerald-500 text-xs font-bold text-slate-800">
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">
                                {{ $u->name }} ({{ $u->email }}) [{{ $u->role }}]
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Target Nutrisi (PPM)</label>
                        <input type="number" id="editDeviceTargetTds" name="target_tds" min="100" max="3000" step="any" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                        <input type="text" id="editDeviceNotes" name="notes"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                    </div>
                </div>

                <!-- Sensor Checkboxes -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <span class="font-bold text-slate-900 block text-xs">Pilih Fitur yang Aktif:</span>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="editDeviceHasTds" name="has_tds" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Sensor Kepekatan Nutrisi (TDS / PPM)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="editDeviceHasTemp" name="has_temp" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Sensor Suhu Air (°C)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="editDeviceHasPh" name="has_ph" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Sensor Derajat Keasaman (pH Air)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="editDeviceHasPump" name="has_pump" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Saklar Pompa Relai</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="editDeviceHasAuto" name="has_auto_mode" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-semibold text-slate-700">Mode Otomatis Nutrisi</span>
                    </label>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Daftar Pompa Terpasang (Pisahkan Koma)</label>
                    <input type="text" id="editDevicePumpNames" name="pump_names" placeholder="Contoh: Pompa Sirkulasi, Pompa Pupuk A, Pompa Pupuk B"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('editDeviceModal')" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md shadow-emerald-600/20">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: TAMBAH AKUN PENGGUNA BARU (CRUD) -->
    <!-- ======================================================== -->
    <div id="createUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Tambah Akun Pengguna Baru</h3>
                    <p class="text-xs text-slate-500">Buat akun untuk petani atau admin tambahan.</p>
                </div>
                <button onclick="closeModal('createUserModal')" class="text-slate-400 hover:text-slate-600 font-bold p-1">✕</button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" placeholder="Contoh: Budi Santoso" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Username Masuk</label>
                        <input type="text" name="username" placeholder="budi123" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Peran Akun (Role)</label>
                        <select name="role" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-semibold">
                            <option value="user" selected>Petani Pengguna</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat Email</label>
                    <input type="email" name="email" placeholder="budi@agronex.id" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kata Sandi Masuk (Min. 6 Karakter)</label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" required minlength="6"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('createUserModal')" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold shadow-md shadow-slate-900/20">
                        Buat Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: EDIT AKUN PENGGUNA (CRUD) -->
    <!-- ======================================================== -->
    <div id="editUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Perbarui Akun Pengguna</h3>
                    <p class="text-xs text-slate-500" id="editUserSubTitle">Ubah profil dan kredensial pengguna.</p>
                </div>
                <button onclick="closeModal('editUserModal')" class="text-slate-400 hover:text-slate-600 font-bold p-1">✕</button>
            </div>

            <form id="editUserForm" action="" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="editUserName" name="name" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Username Masuk</label>
                        <input type="text" id="editUserUsername" name="username" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Peran Akun (Role)</label>
                        <select id="editUserRole" name="role" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs font-semibold">
                            <option value="user">Petani Pengguna</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat Email</label>
                    <input type="email" id="editUserEmail" name="email" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Ganti Kata Sandi (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" placeholder="Biarkan kosong jika tetap" minlength="6"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('editUserModal')" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold shadow-md shadow-slate-900/20">
                        Perbarui Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Logika Tab & Modal -->
    <script>
        function switchTab(tabId) {
            ['tab-devices', 'tab-users', 'tab-nosql'].forEach(id => {
                const btn = document.getElementById('tabBtn-' + id.replace('tab-', ''));
                if (btn) {
                    btn.className = 'px-4 py-2 rounded-xl text-xs font-bold transition text-slate-600 hover:bg-slate-100';
                }
            });
            ['section-devices', 'section-users', 'section-nosql'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });

            const activeBtn = document.getElementById('tabBtn-' + tabId.replace('tab-', ''));
            const activeSec = document.getElementById('section-' + tabId.replace('tab-', ''));
            if (activeBtn) {
                activeBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold transition bg-emerald-600 text-white shadow-xs';
            }
            if (activeSec) {
                activeSec.classList.remove('hidden');
            }
        }

        function openModal(modalId) {
            const el = document.getElementById(modalId);
            if (el) el.classList.remove('hidden');
        }

        function closeModal(modalId) {
            const el = document.getElementById(modalId);
            if (el) el.classList.add('hidden');
        }

        function openEditDeviceModal(device) {
            document.getElementById('editDeviceForm').action = `/admin/devices/${device.id}`;
            document.getElementById('editDeviceSubTitle').innerText = `Kode: ${device.device_code}`;
            document.getElementById('editDeviceName').value = device.name;
            document.getElementById('editDeviceCode').value = device.device_code;
            document.getElementById('editDeviceLocation').value = device.location;
            document.getElementById('editDeviceUserId').value = device.user_id || '';
            document.getElementById('editDeviceTargetTds').value = device.target_tds;
            document.getElementById('editDeviceNotes').value = device.notes || '';

            document.getElementById('editDeviceHasTds').checked = !!device.has_tds;
            document.getElementById('editDeviceHasTemp').checked = !!device.has_temp;
            document.getElementById('editDeviceHasPh').checked = !!device.has_ph;
            document.getElementById('editDeviceHasPump').checked = !!device.has_pump;
            document.getElementById('editDeviceHasAuto').checked = !!device.has_auto_mode;

            let pumpNames = [];
            if (device.has_pump && device.pump_controls && Array.isArray(device.pump_controls)) {
                pumpNames = device.pump_controls.map(p => p.name || p.key);
            }
            document.getElementById('editDevicePumpNames').value = pumpNames.join(', ');

            openModal('editDeviceModal');
        }

        function openEditUserModal(user) {
            document.getElementById('editUserForm').action = `/admin/users/${user.id}`;
            document.getElementById('editUserSubTitle').innerText = `@${user.username} (${user.email})`;
            document.getElementById('editUserName').value = user.name;
            document.getElementById('editUserUsername').value = user.username;
            document.getElementById('editUserEmail').value = user.email;
            document.getElementById('editUserRole').value = user.role || 'user';
            openModal('editUserModal');
        }

        function updateSimTarget() {
            const sel = document.getElementById('simTargetDevice');
            if (!sel) return;
            const opt = sel.options[sel.selectedIndex];
            const devId = opt.value;
            const form = document.getElementById('simForm');
            if (form) form.action = `/admin/devices/${devId}/simulate`;

            const raw = opt.getAttribute('data-payload');
            const preview = document.getElementById('simLastPayloadPreview');
            if (preview) {
                try {
                    const parsed = JSON.parse(raw);
                    preview.innerText = JSON.stringify(parsed, null, 2);
                } catch(e) {
                    preview.innerText = raw || 'Belum ada data rekaman payload tersimpan.';
                }
            }
        }

        function setSimPreset(type) {
            const ta = document.getElementById('simPayloadJson');
            if (!ta) return;
            let doc = {};
            if (type === 'standar') {
                doc = {
                    temperature: 25.5,
                    tds: 820.0,
                    voltage: 1.25,
                    pumps: {
                        pompa_sirkulasi: true
                    }
                };
            } else if (type === 'lengkap') {
                doc = {
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
                    temperature: 24.8,
                    tds: 780.0,
                    ph: 6.2,
                    water_level: 85,
                    humidity: 68
                };
            }
            ta.value = JSON.stringify(doc, null, 2);
        }

        function openSimulateModal(device) {
            switchTab('tab-nosql');
            const sel = document.getElementById('simTargetDevice');
            if (sel) {
                sel.value = device.id;
                updateSimTarget();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateSimTarget();
            setSimPreset('lengkap');
        });
    </script>
</body>
</html>
