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

$role_boleh = ['Admin', 'Manager Gudang'];
if (!in_array($role, $role_boleh)) {
    echo "<script>
            alert('Akses Ditolak! Anda tidak memiliki izin untuk melihat laporan.');
            window.location='dashboard.php';
          </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Pharma Stock</title>
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

        @media print {
            aside, nav, .print-hidden { display: none !important; }
            body { background: white !important; font-size: 10pt; }
            main { padding: 0 !important; margin: 0 !important; }
            .container { max-width: 100% !important; width: 100% !important; }
            .no-print-shadow { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
            .rounded-custom { border-radius: 0 !important; }
        }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50 print-hidden">
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

            <?php if (in_array($role, ['Admin', 'Apoteker'])) : ?>
                <a href="racikan_obat.php" class="flex items-center justify-center md:justify-start p-3 <?php echo (basename($_SERVER['PHP_SELF']) == 'racikan_obat.php') ? 'bg-blue-600 text-white shadow-xl shadow-blue-100' : 'text-slate-400 hover:bg-slate-50 hover:text-blue-600'; ?> rounded-xl transition">
                    <i class="fas fa-mortar-pestle w-5 text-center"></i> 
                    <span class="hidden md:inline ml-3">Racikan Obat</span>
                </a>
            <?php endif; ?>

            <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-chart-bar w-5 text-center"></i> <span class="hidden md:inline ml-3">Analisis</span>
            </a>

            <a href="laporan.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                <i class="fas fa-file-alt w-5 text-center"></i> <span class="hidden md:inline ml-3">Laporan</span>
            </a>

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
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 print-hidden">
            <div class="text-left">
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Inventory Summary</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Rekap <span class="text-blue-600">Laporan.</span></h1>
            </div>
            
            <div class="flex items-center gap-4">
                <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-2.5 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 shadow-xl shadow-slate-200 transition flex items-center gap-2">
                    <i class="fas fa-print"></i> Cetak Dokumen
                </button>
            </div>
        </header>

        <div class="bg-white p-10 rounded-[2.5rem] smooth-shadow border border-slate-50 mb-8 text-center relative overflow-hidden no-print-shadow rounded-custom">
            <div class="relative z-10">
                <div class="inline-block p-4 bg-blue-50 rounded-2xl mb-4 print-hidden">
                    <i class="fas fa-file-medical text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">Laporan Inventaris & Supplier</h2>
                <p class="text-slate-400 mt-2 font-bold text-[10px] uppercase tracking-[0.2em]">Data Per Tanggal: <span class="text-slate-800 underline decoration-blue-200 decoration-4 italic"><?php echo date('d F Y'); ?></span></p>
            </div>
            <i class="fas fa-pills absolute -right-10 -bottom-10 text-9xl text-slate-50 rotate-12 opacity-50"></i>
        </div>

        <div class="bg-white rounded-[2rem] smooth-shadow border border-slate-50 overflow-hidden no-print-shadow rounded-custom">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Produk & Kategori</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Detail Supplier</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center print-hidden">Order Stok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        $data = mysqli_query($koneksi, "SELECT * FROM medicines ORDER BY nama_obat ASC");
                        if(mysqli_num_rows($data) > 0):
                            while($row = mysqli_fetch_array($data)):
                                if ($row['jumlah'] <= 0) {
                                    $status_badge = '<span class="px-3 py-1 bg-rose-50 text-rose-600 rounded-full text-[9px] font-black border border-rose-100 uppercase tracking-tighter">Habis</span>';
                                } elseif ($row['jumlah'] <= 15) {
                                    $status_badge = '<span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-full text-[9px] font-black border border-amber-100 uppercase tracking-tighter">Menipis</span>';
                                } else {
                                    $status_badge = '<span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[9px] font-black border border-emerald-100 uppercase tracking-tighter">Aman</span>';
                                }

                                $nama_supplier = !empty($row['supplier']) ? htmlspecialchars($row['supplier']) : "N/A";
                                $wa_supplier = !empty($row['wa_supplier']) ? htmlspecialchars($row['wa_supplier']) : "-";
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-5">
                                <div class="font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($row['nama_obat']); ?></div>
                                <div class="text-[9px] text-blue-500 font-extrabold uppercase tracking-widest mt-0.5"><?php echo htmlspecialchars($row['kategori']); ?></div>
                            </td>
                            <td class="p-5">
                                <div class="text-[11px] font-bold text-slate-600"><?php echo $nama_supplier; ?></div>
                                <div class="text-[9px] text-slate-400 font-medium tracking-tight mt-0.5"><?php echo $wa_supplier; ?></div>
                            </td>
                            <td class="p-5 text-center">
                                <span class="font-black text-slate-800 text-sm"><?php echo $row['jumlah']; ?></span>
                            </td>
                            <td class="p-5 text-center">
                                <?php echo $status_badge; ?>
                            </td>
                            <td class="p-5 text-center print-hidden">
                                <?php if($wa_supplier != "-"): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $wa_supplier); ?>?text=Halo%20<?php echo urlencode($nama_supplier); ?>%2C%20kami%20ingin%20memesan%20stok%20obat%20<?php echo urlencode($row['nama_obat']); ?>%20karena%20stok%20saat%20ini%20tersisa%20<?php echo $row['jumlah']; ?>." 
                                   target="_blank" 
                                   class="inline-flex items-center gap-2 bg-emerald-500 text-white px-4 py-1.5 rounded-full text-[9px] font-black hover:bg-emerald-600 hover:scale-105 transition shadow-lg shadow-emerald-100 uppercase tracking-widest">
                                     <i class="fab fa-whatsapp text-xs"></i> Order
                                </a>
                                <?php else: ?>
                                    <span class="text-[9px] text-slate-300 font-bold italic uppercase tracking-widest">No Contact</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">Belum ada data inventaris terdaftar.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] print:text-slate-800 italic">
                &copy; 2026 Pharma Stock • Validated Inventory Report • Confidential
            </p>
        </footer>
    </main>

</body>
</html>