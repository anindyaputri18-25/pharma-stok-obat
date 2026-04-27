<?php
session_start();
include 'koneksi.php';
include 'autentikasi.php';

if (!isset($role)) {
    header("Location: login.php");
    exit();
}

// kalau bukan pending, redirect
if ($role != 'Pending') {

    if ($role == 'Kasir') {
        header("Location: kasir_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }

    exit();
}

$user_session = $_SESSION['users'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Menunggu Persetujuan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-md w-full bg-white p-10 rounded-[3rem] shadow-xl shadow-slate-200 border border-slate-100 text-center relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-orange-50 rounded-full"></div>
        
        <div class="relative">
            <div class="w-20 h-20 bg-orange-100 text-orange-500 rounded-3xl flex items-center justify-center mx-auto mb-6 text-3xl animate-bounce">
                <i class="fas fa-clock"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-800 uppercase mb-2">Akses Tertunda</h2>
            <p class="text-slate-500 mb-8 leading-relaxed italic text-sm">
                Halo <span class="font-bold text-blue-600">@<?php echo htmlspecialchars($user_session); ?></span>, akun Anda sedang menunggu verifikasi dari Admin. Halaman manajemen stok akan terbuka secara otomatis setelah disetujui.
            </p>
            
            <div class="space-y-3">
                <button onclick="window.location.reload()" class="block w-full py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition uppercase text-xs tracking-widest shadow-lg shadow-blue-100 mb-3">
                    <i class="fas fa-sync-alt mr-2"></i> Cek Status Terbaru
                </button>
                
                <a href="logout.php" class="block w-full py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-red-50 hover:text-red-600 transition uppercase text-xs tracking-widest">
                    Keluar & Cek Nanti
                </a>
            </div>
        </div>
    </div>
</body>
</html>