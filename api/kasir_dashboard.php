<?php
session_start();
include 'koneksi.php';
include 'autentikasi.php';

// FIX: $role_saat_ini dari autentikasi.php, bukan $role
if (!isset($role_saat_ini) || $role_saat_ini != 'Kasir') {
    header("Location: dashboard.php");
    exit();
}

$users = $_SESSION['users'];
$role  = $role_saat_ini;

// Statistik stok
$stok_aman    = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM medicines WHERE jumlah > 15"));
$stok_menipis = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM medicines WHERE jumlah > 0 AND jumlah <= 15"));
$stok_habis   = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM medicines WHERE jumlah <= 0"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Dashboard - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fe; font-size: 13px; }
        * { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .smooth-shadow { box-shadow: 0 10px 30px rgba(139,153,178,0.1); }
        .nav-text { font-size: 12px; letter-spacing: 0.02em; }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50">
        <div class="mb-10 w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <i class="fas fa-pills text-lg"></i>
        </div>
        <nav class="flex flex-col gap-2 w-full px-4 font-bold h-full nav-text">
            <a href="kasir_dashboard.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Beranda</span>
            </a>
            <a href="stok_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-box w-5 text-center"></i><span class="hidden md:inline ml-3">Stok Obat</span>
            </a>
            <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-chart-bar w-5 text-center"></i><span class="hidden md:inline ml-3">Analisis</span>
            </a>
            <div class="mt-auto flex flex-col gap-2">
                <a href="profil.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                    <i class="fas fa-user w-5 text-center"></i><span class="hidden md:inline ml-3">Profil</span>
                </a>
                <a href="logout.php" class="flex items-center justify-center md:justify-start p-3 text-red-500 hover:bg-red-50 rounded-xl transition">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Keluar</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="text-left">
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Service Management</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Kasir <span class="text-blue-600">Panel.</span></h1>
            </div>
            <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
                <div class="flex flex-col items-end px-3">
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
                    <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
                </div>
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 font-black text-sm border-2 border-white shadow-inner">
                    <?php echo strtoupper(substr($users, 0, 1)); ?>
                </div>
            </div>
        </header>

        <div class="group relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-700 p-10 rounded-[2.5rem] text-white mb-10 smooth-shadow transition-transform hover:scale-[1.005]">
            <div class="relative z-10">
                <span class="bg-white/20 backdrop-blur-md text-white text-[9px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-6 inline-block">Kasir On-Duty</span>
                <h2 class="text-4xl font-black italic mb-3 tracking-tight">Halo, <?php echo htmlspecialchars($users); ?>! 👋</h2>
                <p class="text-blue-100 font-medium max-w-xl opacity-90 text-sm leading-relaxed">Pantau ketersediaan stok obat secara real-time sebelum memberikan informasi kepada pasien.</p>
            </div>
            <i class="fas fa-cash-register absolute -right-10 -bottom-10 text-[15rem] opacity-10 rotate-12 transition-transform group-hover:scale-110"></i>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex flex-col items-center text-center group hover:bg-emerald-500 transition-all duration-500">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-white/20 group-hover:text-white transition-all">
                    <i class="fas fa-check-double text-lg"></i>
                </div>
                <h4 class="text-3xl font-black text-slate-800 mb-1 group-hover:text-white"><?php echo $stok_aman; ?></h4>
                <p class="text-slate-400 font-bold text-[9px] uppercase tracking-widest group-hover:text-white/80">Stok Aman</p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex flex-col items-center text-center group hover:bg-amber-500 transition-all duration-500">
                <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-white/20 group-hover:text-white transition-all">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </div>
                <h4 class="text-3xl font-black text-slate-800 mb-1 group-hover:text-white"><?php echo $stok_menipis; ?></h4>
                <p class="text-slate-400 font-bold text-[9px] uppercase tracking-widest group-hover:text-white/80">Stok Menipis</p>
            </div>
            <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex flex-col items-center text-center group hover:bg-rose-500 transition-all duration-500">
                <div class="w-12 h-12 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-white/20 group-hover:text-white transition-all">
                    <i class="fas fa-times-circle text-lg"></i>
                </div>
                <h4 class="text-3xl font-black text-slate-800 mb-1 group-hover:text-white"><?php echo $stok_habis; ?></h4>
                <p class="text-slate-400 font-bold text-[9px] uppercase tracking-widest group-hover:text-white/80">Obat Kosong</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="stok_obat.php" class="bg-white p-8 rounded-[2rem] smooth-shadow border border-slate-50 flex items-center justify-between group hover:border-blue-500 transition-all duration-300">
                <div class="flex items-center gap-6">
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-all">
                        <i class="fas fa-search text-xl"></i>
                    </div>
                    <div>
                        <h5 class="font-black text-slate-800 uppercase text-xs tracking-widest mb-1 italic">Cek Inventaris</h5>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Cari ketersediaan obat & rak</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-slate-200 group-hover:text-blue-500 group-hover:translate-x-2 transition-all"></i>
            </a>
            <a href="analisis.php" class="bg-white p-8 rounded-[2rem] smooth-shadow border border-slate-50 flex items-center justify-between group hover:border-indigo-500 transition-all duration-300">
                <div class="flex items-center gap-6">
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-all">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div>
                        <h5 class="font-black text-slate-800 uppercase text-xs tracking-widest mb-1 italic">Analisis Data</h5>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Lihat tren kesehatan BPS</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-slate-200 group-hover:text-indigo-500 group-hover:translate-x-2 transition-all"></i>
            </a>
        </div>

        <footer class="mt-20 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">&copy; 2026 Pharma Stock • Cashier Intelligence System</p>
        </footer>
    </main>
</body>
</html>