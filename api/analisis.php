<?php
include 'koneksi.php';
include 'autentikasi.php'; // Ini akan otomatis mendefinisikan $role_saat_ini dan $users

$users = $_COOKIE['users']; // Ambil dari cookie
$role  = $role_saat_ini;    // Ambil dari variabel di autentikasi.php

$role_boleh = ['Admin', 'Manager Gudang'];
if (!in_array($role, $role_boleh)) {
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f7fe;
            font-size: 13px;
        }

        * {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .smooth-shadow {
            box-shadow: 0 10px 30px rgba(139, 153, 178, 0.1);
        }

        .nav-text {
            font-size: 12px;
            letter-spacing: 0.02em;
        }

        .chart-container {
            position: relative;
            filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.02));
        }

        /* CSS untuk merapikan tabel yang datang dari BPS */
        #bpsTableContent table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        #bpsTableContent th,
        #bpsTableContent td {
            border: 1px solid #e2e8f0;
            padding: 12px;
            text-align: center;
        }

        #bpsTableContent th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #1e293b;
        }
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

            <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
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
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Health Data Analysis</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Analisis <span class="text-blue-600">BPS.</span></h1>
            </div>

            <div class="flex items-center gap-4 w-full md:w-auto">

                <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
                    <div class="flex flex-col items-end px-3">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
                        <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 font-black text-sm border-2 border-white shadow-inner">
                        <?php echo strtoupper(substr($users, 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="bg-white p-8 rounded-[2.5rem] smooth-shadow border border-slate-50 mb-8">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-xs"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Persentase Penduduk yang Mempunyai Keluhan Kesehatan dan Penggunaan Obat</h3>
                        <p class="text-[9px] text-slate-400 font-bold uppercase mt-0.5 italic">Data Tahun 2009 - 2014</p>
                    </div>
                </div>
            </div>

            <div id="bpsTableContainer" class="mt-10 overflow-x-auto bg-white p-4 rounded-xl shadow-sm border border-slate-100 hidden">
                <h4 class="text-sm font-bold text-slate-800 mb-4 uppercase tracking-wider">Data Tabel dari BPS</h4>
                <div id="bpsTableContent" class="text-xs text-slate-600">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-[2rem] border border-slate-50 smooth-shadow relative overflow-hidden group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-100">
                        <i class="fas fa-hospital-users text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 uppercase tracking-tighter text-sm">Modern Care</h3>
                        <p class="text-[9px] text-blue-600 font-bold uppercase tracking-widest">Medical Method</p>
                    </div>
                </div>
                <p class="text-slate-400 text-[11px] leading-relaxed font-medium italic">Data menunjukkan persentase penduduk yang mengakses fasilitas medis profesional dan obat-obatan farmasi modern di wilayah Jawa.</p>
                <i class="fas fa-microscope absolute -bottom-4 -right-4 text-7xl text-slate-50 group-hover:text-blue-50 transition-colors"></i>
            </div>

            <div class="bg-white p-6 rounded-[2rem] border border-slate-50 smooth-shadow relative overflow-hidden group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-10 h-10 bg-emerald-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-100">
                        <i class="fas fa-leaf text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 uppercase tracking-tighter text-sm">Traditional Care</h3>
                        <p class="text-[9px] text-emerald-600 font-bold uppercase tracking-widest">Natural Method</p>
                    </div>
                </div>
                <p class="text-slate-400 text-[11px] leading-relaxed font-medium italic">Mencakup penggunaan kearifan lokal, jamu herbal, dan metode pengobatan tradisional yang masih kental dalam budaya masyarakat Jawa.</p>
                <i class="fas fa-mortar-pestle absolute -bottom-4 -right-4 text-7xl text-slate-50 group-hover:text-emerald-50 transition-colors"></i>
            </div>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">&copy; 2026 Pharma Stock • Analytical Intelligence</p>
        </footer>
    </main>

    <script>
        async function muatDataBPS() {
            const tableContainer = document.getElementById('bpsTableContainer');
            const tableContent = document.getElementById('bpsTableContent');

            try {
                // 1. Panggil API BPS
                const response = await fetch('api_bps.php');
                const result = await response.json();

                // 2. Cek apakah status OK
                if (result.status === "OK") {
                    let htmlTabel = result.data.table;

                    if (htmlTabel) {
                        // A. Trik untuk melakukan 'Decode' (menerjemahkan teks BPS kembali menjadi kode HTML asli)
                        const textarea = document.createElement("textarea");
                        textarea.innerHTML = htmlTabel;
                        let decodedHTML = textarea.value;

                        // B. Masukkan kode utuh tersebut ke dalam iframe agar tidak merusak desain web utama
                        // Kita replace tanda kutip (") menjadi (&quot;) agar tidak memotong tag srcdoc
                        const iframeHTML = `<iframe 
                            style="width: 100%; height: 550px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: white;" 
                            srcdoc="${decodedHTML.replace(/"/g, '&quot;')}">
                        </iframe>`;

                        // Munculkan ke layar
                        tableContent.innerHTML = iframeHTML;
                        tableContainer.classList.remove('hidden');
                        
                    } else {
                        tableContent.innerHTML = "<p class='text-red-500'>Data tabel tidak ditemukan dalam respon API.</p>";
                    }
                } else {
                    tableContent.innerHTML = "<p class='text-red-500'>API BPS mengembalikan error.</p>";
                }
            } catch (error) {
                console.error("Gagal memanggil API:", error);
                tableContent.innerHTML = "<p class='text-red-500'>Gagal terhubung ke server / API BPS.</p>";
            }
        }

        // Jalankan saat halaman siap
        window.onload = muatDataBPS;
    </script>
</body>

</html>