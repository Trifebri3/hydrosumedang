<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Belum Ada Instalasi Terhubung - HydroSense by agronex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-['Plus_Jakarta_Sans',sans-serif] min-h-screen text-slate-800 flex flex-col justify-between p-4 sm:p-6 antialiased">
    
    <!-- Navbar Minimalis -->
    <header class="max-w-xl w-full mx-auto flex items-center justify-between pb-6 border-b border-slate-200">
        <div class="flex items-center gap-3">
            <img src="https://denrawit.agronex.id/logo.png" alt="Agronex Logo" class="h-8 w-auto">
            <div>
                <span class="text-sm font-extrabold text-slate-900 tracking-tight block">HydroSense</span>
                <span class="text-[10px] font-bold text-emerald-700 tracking-wider uppercase block">by agronex</span>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 transition">
                Keluar
            </button>
        </form>
    </header>

    <!-- Konten Utama -->
    <main class="max-w-md w-full mx-auto my-auto text-center py-10">
        <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight mb-2">
            Belum Ada Alat Terhubung
        </h1>

        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
            Akun <b>{{ $user->name }}</b> ({{ $user->email }}) telah aktif, namun belum ada instalasi alat kebun yang dikaitkan ke akun ini. Setiap alat wajib dihubungkan oleh Administrator sistem.
        </p>

        <div class="p-4 rounded-2xl bg-white border border-slate-200 text-left text-xs space-y-2 mb-6 shadow-sm">
            <span class="font-bold text-slate-900 block">Langkah Selanjutnya:</span>
            <p class="text-slate-600 leading-relaxed">
                1. Hubungi Administrator kebun Anda untuk mendaftarkan kode alat instalasi ke akun ini.
            </p>
            <p class="text-slate-600 leading-relaxed">
                2. Setelah Administrator menghubungkan alat kebun, segarkan halaman ini untuk memantau data sensor secara langsung.
            </p>
        </div>

        <div class="flex items-center justify-center gap-3">
            <button onclick="window.location.reload()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-emerald-600/20">
                Segarkan Halaman
            </button>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition">
                    Keluar Akun
                </button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="max-w-xl w-full mx-auto text-center pt-6 border-t border-slate-200 text-xs text-slate-400">
        HydroSense by agronex &bull; Platform Pemantauan Hidroponik Terpusat
    </footer>

</body>
</html>
