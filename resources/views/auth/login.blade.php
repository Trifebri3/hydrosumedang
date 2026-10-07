<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - HydroSense by agronex</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f0fdf4;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 selection:bg-emerald-500 selection:text-white">

    <div class="max-w-md w-full bg-white rounded-3xl p-6 sm:p-8 shadow-2xl shadow-emerald-950/10 border border-emerald-100">
        
        <!-- Logo Branding -->
        <div class="text-center mb-6">
            <img src="{{ asset('logo.png') }}" alt="denrawit x agronex" class="h-10 sm:h-12 w-auto object-contain mx-auto mb-3">
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">HydroSense Platform</h1>
            <p class="text-xs text-slate-500 mt-1">Sistem Pemantauan dan Kontrol Hidroponik Terpadu</p>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-semibold text-center">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Form Login -->
        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Email atau Nama Pengguna</label>
                <input type="text" name="login" value="{{ old('login') }}" placeholder="Masukkan email atau nama pengguna" required autofocus
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">Kata Sandi</label>
                <input type="password" name="password" placeholder="Masukkan kata sandi" required
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-sm font-medium">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-medium">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Ingat Saya</span>
                </label>
            </div>

            <button type="submit" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-600/25 transition">
                Masuk ke Dashboard
            </button>
        </form>

        <!-- Pilihan Masuk Cepat -->
        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2.5">Akses Akun Cepat:</span>
            
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('quick-login', 'user') }}" class="p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-200 text-left transition block group">
                    <div class="text-[11px] font-bold text-slate-800 group-hover:text-emerald-700">Akun Sumedang</div>
                    <div class="text-[10px] text-slate-400">Khusus kebun Sumedang</div>
                </a>

                <a href="{{ route('quick-login', 'admin') }}" class="p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-left transition block group">
                    <div class="text-[11px] font-bold text-slate-800">Administrator</div>
                    <div class="text-[10px] text-slate-400">Kelola semua instalasi</div>
                </a>
            </div>

            <div class="mt-4">
                <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-emerald-600 hover:underline">
                    Kembali ke Halaman Utama
                </a>
            </div>
        </div>

    </div>

</body>
</html>
