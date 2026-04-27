<?php
session_start();
include 'koneksi.php';
include 'autentikasi.php';

// Gunakan variabel yang didefinisikan di autentikasi.php
if (!isset($role_saat_ini)) {
    header("Location: login.php");
    exit();
}

// Redirect jika role tidak sesuai
if ($role_saat_ini == 'Pending') {
    header("Location: pending.php");
    exit();
}

if ($role_saat_ini == 'Kasir') {
    header("Location: kasir_dashboard.php");
    exit();
}

$users = $_SESSION['users'];
$role = $role_saat_ini;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f4f7fe;
            font-size: 13px; /* Ukuran font global lebih kecil */
        }

        * { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .smooth-shadow {
            box-shadow: 0 10px 30px rgba(139, 153, 178, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(139, 153, 178, 0.2);
        }

        /* Nav link lebih ringkas */
        .nav-text { font-size: 12px; letter-spacing: 0.02em; }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50">
        <div class="mb-10 w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <i class="fas fa-pills text-lg"></i>
        </div>
        
        <nav class="flex flex-col gap-2 w-full px-4 font-bold h-full nav-text">
            <a href="<?php echo ($role == 'Kasir') ? 'kasir_dashboard.php' : 'dashboard.php'; ?>" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100">
                <i class="fas fa-home w-5 text-center"></i> <span class="hidden md:inline ml-3">Beranda</span>
            </a>
            
            <?php if ($role != 'Pending') : ?>
                <a href="stok_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                    <i class="fas fa-box w-5 text-center"></i> <span class="hidden md:inline ml-3">Stok Obat</span>
                </a>

                <?php if (in_array($role, ['Admin', 'Apoteker'])) : ?>
                <a href="racikan_obat.php" class="flex items-center justify-center md:justify-start p-3 <?php echo (basename($_SERVER['PHP_SELF']) == 'racikan_obat.php') ? 'bg-blue-600 text-white shadow-xl shadow-blue-100' : 'text-slate-400 hover:bg-slate-50 hover:text-blue-600'; ?> rounded-xl transition">
                    <i class="fas fa-mortar-pestle w-5 text-center"></i> 
                    <span class="hidden md:inline ml-3">Racikan Obat</span>
                </a>
            <?php endif; ?>

                <?php if (in_array($role, ['Admin', 'Manager Gudang', 'Apoteker', 'Kasir'])) : ?>
                <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                    <i class="fas fa-chart-bar w-5 text-center"></i> <span class="hidden md:inline ml-3">Analisis</span>
                </a>
                <?php endif; ?>
                
                <?php if (in_array($role, ['Admin', 'Manager Gudang'])) : ?>
                    <a href="laporan.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                        <i class="fas fa-file-alt w-5 text-center"></i> <span class="hidden md:inline ml-3">Laporan</span>
                    </a>
                <?php endif; ?>

                <?php if ($role == 'Admin') : ?>
                    <a href="admin_users.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                        <i class="fas fa-users-cog w-5 text-center"></i> <span class="hidden md:inline ml-3">User Management</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-auto flex flex-col gap-2">
                <a href="profil.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                    <i class="fas fa-users w-5 text-center"></i> <span class="hidden md:inline ml-3">Profil</span>
                </a>
                
                <a href="logout.php" class="flex items-center justify-center md:justify-start p-3 text-red-500 hover:bg-red-50 rounded-xl transition">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i> <span class="hidden md:inline ml-3">Keluar</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">
        <header class="flex justify-between items-center mb-10 print:hidden">
            <div class="text-left">
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Management System</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none">Ringkasan <span class="text-blue-600 italic">Farmasi.</span></h1>
            </div>
            
            <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow">
                <div class="flex flex-col items-end px-3">
                    <p class="text-[9px] text-slate-400 font-bold uppercase">User Active</p>
                    <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
                </div>
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 font-black text-sm border-2 border-white shadow-inner">
                    <?php echo strtoupper(substr($users, 0, 1)); ?>
                </div>
            </div>
        </header>

        <?php if ($role == 'Pending') : ?>
            <div class="glass-card p-12 rounded-[3rem] text-center max-w-xl mx-auto mt-10">
                <div class="w-20 h-20 bg-orange-100 text-orange-600 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-3xl animate-bounce">
                    <i class="fas fa-users-clock"></i>
                </div>
                <h2 class="text-xl font-black text-slate-800 uppercase mb-2">Menunggu Aktivasi</h2>
                <p class="text-slate-500 text-sm font-medium leading-relaxed italic">Halo <?php echo htmlspecialchars($users); ?>, Admin sedang meninjau akunmu.</p>
            </div>
        <?php else : ?>
            
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-700 p-10 md:p-12 rounded-[3rem] text-white mb-10 shadow-xl">
                <div class="relative z-10">
                    <span class="bg-white/20 text-[9px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-4 inline-block border border-white/20">System Aktif</span>
                    <h2 class="text-3xl md:text-4xl font-black italic mb-3 tracking-tight">Kesehatan adalah <span class="text-white/70">Aset Berharga.</span></h2>
                    <p class="text-blue-100 text-sm font-medium max-w-lg opacity-90">Kelola stok obat dengan presisi tinggi dan analisis data *real-time*.</p>
                </div>
                <i class="fas fa-laptop-medical absolute -right-10 -bottom-10 text-[15rem] opacity-10 rotate-12"></i>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="stat-card glass-card p-8 rounded-[2.5rem] flex flex-col items-center text-center group transition-all">
                    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <h4 class="text-4xl font-black text-slate-800 mb-1"><?php echo $stok_aman; ?></h4>
                    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Kondisi Aman</p>
                </div>

                <div class="stat-card glass-card p-8 rounded-[2.5rem] flex flex-col items-center text-center group transition-all">
                    <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-amber-500 group-hover:text-white transition-all">
                        <i class="fas fa-hourglass-half text-2xl"></i>
                    </div>
                    <h4 class="text-4xl font-black text-slate-800 mb-1"><?php echo $stok_menipis; ?></h4>
                    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Stok Menipis</p>
                </div>

                <div class="stat-card glass-card p-8 rounded-[2.5rem] flex flex-col items-center text-center group transition-all">
                    <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-rose-600 group-hover:text-white transition-all">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <h4 class="text-4xl font-black text-slate-800 mb-1"><?php echo $stok_habis; ?></h4>
                    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Out of Stock</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="stok_obat.php" class="flex items-center justify-between bg-white p-8 rounded-[2.5rem] smooth-shadow border border-slate-50 hover:border-blue-500 group transition-all">
                    <div class="flex items-center gap-6">
                        <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-all">
                            <i class="fas fa-box-open text-xl"></i>
                        </div>
                        <div>
                            <h5 class="font-black text-slate-800 uppercase text-xs tracking-wide">Inventaris</h5>
                            <p class="text-[10px] text-slate-400 font-medium">Manajemen data obat.</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-slate-200 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"></i>
                </a>

                <?php if (in_array($role, ['Admin', 'Manager Gudang', 'Apoteker', 'Kasir'])) : ?>
                <a href="analisis.php" class="flex items-center justify-between bg-white p-8 rounded-[2.5rem] smooth-shadow border border-slate-50 hover:border-indigo-500 group transition-all">
                    <div class="flex items-center gap-6">
                        <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <div>
                            <h5 class="font-black text-slate-800 uppercase text-xs tracking-wide">Analitik</h5>
                            <p class="text-[10px] text-slate-400 font-medium">Statistik data BPS.</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-slate-200 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all"></i>
                </a>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">&copy; 2026 Pharma Stock</p>
        </footer>
    </main>
</body>
</html>
