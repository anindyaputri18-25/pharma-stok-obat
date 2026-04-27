<?php
session_start();
include 'koneksi.php';
include 'autentikasi.php'; 

$users = $_SESSION['users'];
$role = $role_saat_ini; 

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$users'");
$data_user = mysqli_fetch_array($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f4f7fe;
            font-size: 13px;
        }

        * { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .smooth-shadow {
            box-shadow: 0 10px 30px rgba(139, 153, 178, 0.1);
        }

        .nav-text { font-size: 12px; letter-spacing: 0.02em; }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50">
        <div class="mb-10 w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <i class="fas fa-pills text-lg"></i>
        </div>
        
        <nav class="flex flex-col gap-2 w-full px-4 font-bold h-full nav-text">
            <a href="<?php echo ($role == 'Kasir') ? 'kasir_dashboard.php' : 'dashboard.php'; ?>" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-home w-5 text-center"></i> <span class="hidden md:inline ml-3">Beranda</span>
            </a>
            
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

            <div class="mt-auto flex flex-col gap-2">
                <a href="profil.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                    <i class="fas fa-users w-5 text-center"></i> <span class="hidden md:inline ml-3">Profil</span>
                </a>

                <a href="logout.php" class="flex items-center justify-center md:justify-start p-3 text-red-500 hover:bg-red-50 rounded-xl transition">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i> <span class="hidden md:inline ml-3">Keluar</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="text-left">
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">User Identity</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Profil <span class="text-blue-600">Saya.</span></h1>
            </div>
        </header>

        <div class="flex justify-center items-center py-4">
            <div class="w-full max-w-2xl bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden relative">
                <div class="h-32 bg-gradient-to-br from-blue-600 to-indigo-700 relative">
                    <i class="fas fa-dna absolute right-10 top-5 text-white/10 text-8xl -rotate-12"></i>
                </div>
                
                <div class="px-10 pb-12 -mt-16 text-center relative z-10">
                    <div class="inline-flex p-2 bg-white rounded-[2rem] shadow-xl mb-6">
                        <div class="w-28 h-28 bg-slate-50 rounded-[1.8rem] flex items-center justify-center border-4 border-white overflow-hidden shadow-inner">
                            <i class="fas fa-users-circle text-slate-200 text-6xl"></i>
                        </div>
                    </div>

                    <h2 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">@<?php echo htmlspecialchars($users); ?></h2>
                    <div class="flex justify-center">
                        <span class="bg-blue-50 text-blue-600 font-black text-[9px] px-5 py-2 rounded-full uppercase tracking-widest mt-3 inline-block border border-blue-100">
                            Pharma Stock Member 💊
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-10">
                        <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100 text-left hover:bg-white hover:smooth-shadow transition-all group">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-hover:text-blue-600">Account Status</p>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center group-hover:scale-110">
                                    <i class="fas fa-shield-alt text-sm"></i>
                                </div>
                                <p class="text-slate-800 font-black text-lg tracking-tighter italic">AKTIF</p>
                            </div>
                        </div>
                        
                        <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100 text-left hover:bg-white hover:smooth-shadow transition-all group">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-hover:text-indigo-600">Access Level</p>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center group-hover:scale-110">
                                    <i class="fas fa-id-badge text-sm"></i>
                                </div>
                                <p class="text-slate-800 font-black text-lg tracking-tighter italic uppercase"><?php echo htmlspecialchars($role); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 p-6 bg-slate-900 rounded-[2rem] text-center relative overflow-hidden group">
                        <p class="text-[11px] text-slate-300 font-bold italic leading-relaxed relative z-10 px-4">
                            "Menjaga ketersediaan stok obat adalah menjaga kesehatan banyak orang. Teruslah berkontribusi dengan integritas, <span class="text-blue-400 uppercase"><?php echo htmlspecialchars($users); ?></span>!"
                        </p>
                        <i class="fas fa-quote-right absolute -right-2 -bottom-2 text-5xl text-white/5 transition-transform group-hover:scale-110"></i>
                    </div>

                    <div class="mt-10 flex flex-col md:flex-row justify-center gap-3">
                        <a href="<?php echo ($role == 'Kasir') ? 'kasir_dashboard.php' : 'dashboard.php'; ?>" class="px-8 py-3.5 bg-white border border-slate-200 text-slate-700 rounded-xl font-black text-[10px] uppercase tracking-widest hover:border-blue-600 hover:text-blue-600 smooth-shadow transition active:scale-95 flex items-center justify-center gap-2">
                            <i class="fas fa-chevron-left"></i> Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-10 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">Pharma Stock v1.0 • Core Intelligence Identity</p>
        </footer>
    </main>
</body>
</html>