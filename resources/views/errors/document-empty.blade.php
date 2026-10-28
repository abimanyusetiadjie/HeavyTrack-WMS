<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Jalan Belum Memiliki Barang - Mitra Abadi Nusantara</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 p-8 text-center">
        <!-- Warning Icon -->
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h1 class="text-xl font-bold text-slate-900 mb-2">
            Surat Jalan Belum Memiliki Rincian Barang
        </h1>

        <p class="text-slate-600 text-sm mb-6 leading-relaxed">
            Surat Jalan <span class="font-mono font-semibold text-slate-900 bg-slate-100 px-2 py-0.5 rounded">{{ $doNumber ?? 'SJ' }}</span> saat ini masih kosong (0 item). Faktur penjualan resmi hanya dapat diterbitkan untuk Surat Jalan yang sudah memiliki rincian barang.
        </p>

        <div class="space-y-3">
            @if(isset($editUrl))
            <a href="{{ $editUrl }}" class="w-full inline-flex items-center justify-center px-5 py-3 rounded-xl text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 shadow-md transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Isi Barang pada Surat Jalan Ini
            </a>
            @endif

            <a href="{{ url('/admin/delivery-orders') }}" class="w-full inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
                Kembali ke Daftar Surat Jalan
            </a>
        </div>
    </div>
</body>
</html>
