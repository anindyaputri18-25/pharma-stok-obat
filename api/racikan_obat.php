<?php
session_start();
include 'koneksi.php';
include 'autentikasi.php'; 

if (!isset($_SESSION['users'])) {
    header("Location: login.php");
    exit();
}

$users = $_SESSION['users'];
$role = $role_saat_ini; 

// Proteksi Halaman: Hanya Admin dan Apoteker
$role_boleh = ['Admin', 'Apoteker'];
if (!in_array($role, $role_boleh)) {
    echo "<script>
            alert('Akses Ditolak! Menu Racikan hanya untuk Apoteker.');
            window.location='dashboard.php';
          </script>";
    exit();
}

// Hitung TOTAL RACIKAN secara Real-time
$query_count = mysqli_query($koneksi, "SELECT id_racikan FROM racikan");
$total_racikan_realtime = mysqli_num_rows($query_count);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Racikan Obat - Pharma Stock</title>
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
            <a href="dashboard.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-home w-5 text-center"></i> <span class="hidden md:inline ml-3">Beranda</span>
            </a>
            
            <a href="stok_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-box w-5 text-center"></i> <span class="hidden md:inline ml-3">Stok Obat</span>
            </a>

            <a href="racikan_obat.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                <i class="fas fa-mortar-pestle w-5 text-center"></i> <span class="hidden md:inline ml-3">Racikan Obat</span>
            </a>

            <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-chart-bar w-5 text-center"></i> <span class="hidden md:inline ml-3">Analisis</span>
            </a>

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
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="text-left">
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Pharmacist Compounding</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Daftar <span class="text-blue-600">Racikan.</span></h1>
            </div>
            
            <a href="tambah_racikan.php" class="relative z-[100] bg-slate-900 text-white px-6 py-3 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 shadow-xl shadow-slate-200 transition flex items-center gap-2 cursor-pointer">
                <i class="fas fa-plus"></i> Tambah Racikan Baru
            </a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-[2rem] border border-slate-50 smooth-shadow flex items-center gap-5 group hover:bg-blue-600 transition">
                <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 group-hover:bg-white/20 group-hover:text-white">
                    <i class="fas fa-mortar-pestle text-xl"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-[9px] font-black uppercase tracking-widest group-hover:text-blue-100">Total Racikan</p>
                    <h3 class="text-xl font-black text-slate-800 group-hover:text-white"><?php echo $total_racikan_realtime; ?> Resep</h3>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] smooth-shadow border border-slate-50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Racikan</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Komposisi Bahan</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Total Stok</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Tipe</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        $query = mysqli_query($koneksi, "SELECT * FROM racikan ORDER BY id_racikan DESC");
                        if(mysqli_num_rows($query) > 0) {
                            while($data = mysqli_fetch_array($query)) :
                                $id_r = $data['id_racikan'];
                                // Ambil komposisi bahan dalam satu string
                                $q_bahan = mysqli_query($koneksi, "SELECT m.nama_obat FROM racikan_detail rd JOIN medicines m ON rd.id_obat = m.id WHERE rd.id_racikan = '$id_r'");
                                $bahan_list = [];
                                while($b = mysqli_fetch_assoc($q_bahan)) { $bahan_list[] = $b['nama_obat']; }
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-5">
                                <div class="font-black text-slate-800 text-xs uppercase italic"><?php echo $data['nama_racikan']; ?></div>
                                <div class="text-[9px] text-blue-500 font-extrabold uppercase tracking-widest mt-0.5">ID: <?php echo $data['kode_racikan']; ?></div>
                            </td>
                            <td class="p-5">
                                <div class="text-[10px] font-bold text-slate-600">
                                    <?php echo !empty($bahan_list) ? implode(", ", $bahan_list) : "Tanpa Bahan"; ?>
                                </div>
                                <div class="text-[9px] text-slate-400 font-medium italic mt-0.5"><?php echo $data['keterangan']; ?></div>
                            </td>
                            <td class="p-5 text-center font-black text-slate-800 text-sm"><?php echo $data['stok_racikan']; ?></td>
                            <td class="p-5 text-center">
                                <span class="px-3 py-1 bg-purple-50 text-purple-600 rounded-full text-[9px] font-black border border-purple-100 uppercase tracking-tighter"><?php echo $data['tipe_racikan']; ?></span>
                            </td>
                            <td class="p-5 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="hapus_racikan.php?id=<?php echo $data['id_racikan']; ?>" onclick="return confirm('Hapus racikan?')" class="w-8 h-8 bg-slate-100 text-red-500 rounded-lg hover:bg-red-600 hover:text-white transition flex items-center justify-center">
                                        <i class="fas fa-trash text-[10px]"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; 
                        } else {
                            echo "<tr><td colspan='5' class='p-10 text-center text-slate-400 font-bold uppercase'>Belum ada data racikan</td></tr>";
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">
                &copy; 2026 Pharma Stock • Apothecary Division
            </p>
        </footer>
    </main>

</body>
</html>