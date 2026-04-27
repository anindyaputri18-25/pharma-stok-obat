<?php
include 'koneksi.php';
include 'autentikasi.php'; //

// Ambil data dari Cookie agar konsisten
$users = $_COOKIE['users'] ?? 'Guest';
$role  = $role_saat_ini;

$today        = date('Y-m-d');
$warning_date = date('Y-m-d', strtotime('+30 days'));

if (isset($_POST['tambah_obat'])) {
    $exp  = $_POST['expired']; // Pastikan di form input name="expired"

    $query = "INSERT INTO medicines (nama_obat, kategori, jumlah, expired_date, supplier, wa_supplier) 
              VALUES ('$nama', '$kat', '$qty', '$exp', '$supp', '$wa')";

    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_obat']);
    $kat  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $qty  = (int)$_POST['jumlah'];
    $exp  = $_POST['expired'];
    $supp = mysqli_real_escape_string($koneksi, $_POST['supplier']);
    $wa   = mysqli_real_escape_string($koneksi, $_POST['wa_supplier']);

    $cek_nama = mysqli_query($koneksi, "SELECT id FROM medicines WHERE LOWER(nama_obat) = LOWER('$nama')");
    if (mysqli_num_rows($cek_nama) > 0) {
        echo "<script>alert('Gagal! Nama obat [$nama] sudah ada.'); window.location='stok_obat.php';</script>";
        exit();
    }

    $sql = "INSERT INTO medicines (nama_obat, kategori, jumlah, expired_date, supplier, wa_supplier)
            VALUES ('$nama', '$kat', '$qty', '$exp', '$supp', '$wa')";

    if (mysqli_query($koneksi, $sql)) {
        echo "<script>alert('Obat baru berhasil disimpan!'); window.location='stok_obat.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
    }
}

// Hapus obat
if (isset($_GET['hapus'])) {
    if ($role == 'Admin') {
        $id = (int)$_GET['hapus'];
        mysqli_query($koneksi, "DELETE FROM medicines WHERE id='$id'");
        header("Location: stok_obat.php");
        exit();
    } else {
        echo "<script>alert('Gagal! Hanya Admin yang boleh menghapus data.'); window.location='stok_obat.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Obat - Pharma Stock</title>
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
            <a href="<?php echo ($role == 'Kasir') ? 'kasir_dashboard.php' : 'dashboard.php'; ?>" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Beranda</span>
            </a>
            <a href="stok_obat.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                <i class="fas fa-box w-5 text-center"></i><span class="hidden md:inline ml-3">Stok Obat</span>
            </a>
            <?php if (in_array($role, ['Admin', 'Apoteker'])) : ?>
            <a href="racikan_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-mortar-pestle w-5 text-center"></i><span class="hidden md:inline ml-3">Racikan Obat</span>
            </a>
            <?php endif; ?>
            <?php if (in_array($role, ['Admin', 'Manager Gudang', 'Apoteker', 'Kasir'])) : ?>
            <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-chart-bar w-5 text-center"></i><span class="hidden md:inline ml-3">Analisis</span>
            </a>
            <?php endif; ?>
            <?php if (in_array($role, ['Admin', 'Manager Gudang'])) : ?>
            <a href="laporan.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-file-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Laporan</span>
            </a>
            <?php endif; ?>
            <?php if ($role == 'Admin') : ?>
            <a href="admin_users.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-users-cog w-5 text-center"></i><span class="hidden md:inline ml-3">User Management</span>
            </a>
            <?php endif; ?>
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
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Inventory Control</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Manajemen <span class="text-blue-600">Stok.</span></h1>
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

        <!-- Search bar -->
        <div class="bg-white p-4 rounded-2xl smooth-shadow border border-slate-50 mb-6 flex items-center gap-3">
            <i class="fas fa-search text-slate-300 ml-2"></i>
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari nama obat..." 
                class="flex-1 outline-none text-sm font-medium text-slate-700 bg-transparent placeholder:text-slate-300">
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            <!-- Form Tambah -->
            <div class="xl:col-span-1">
                <?php if ($role != 'Kasir') : ?>
                <div class="bg-white p-6 rounded-[2.5rem] smooth-shadow border border-slate-50">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xs">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h3 class="font-black text-slate-800 text-xs uppercase tracking-widest">Tambah Obat</h3>
                    </div>
                    <form method="POST" class="space-y-3">
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Nama Obat</label>
                            <input type="text" name="nama_obat" required placeholder="Nama obat..." class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none text-xs transition">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Kategori</label>
                            <select name="kategori" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none text-xs transition appearance-none">
                                <option value="Obat Bebas">Obat Bebas</option>
                                <option value="Obat Bebas Terbatas">Obat Bebas Terbatas</option>
                                <option value="Obat Keras">Obat Keras</option>
                                <option value="Obat Tradisional">Obat Tradisional</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Supplier</label>
                            <input type="text" name="supplier" required placeholder="Nama PT / Distributor" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none text-xs transition">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-2">WhatsApp (62...)</label>
                            <input type="text" name="wa_supplier" required placeholder="628123456789" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none text-xs transition">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Stok</label>
                                <input type="number" name="jumlah" required min="0" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none text-xs transition">
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Expired</label>
                                <input type="date" name="expired" required class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none text-[10px] transition">
                            </div>
                        </div>
                        <button name="tambah_obat" class="w-full bg-blue-600 text-white py-4 rounded-xl font-black hover:bg-blue-700 shadow-lg shadow-blue-100 transition active:scale-95 uppercase text-[10px] tracking-widest mt-2">
                            <i class="fas fa-plus mr-1"></i> Simpan Data
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div class="bg-white p-8 rounded-[2.5rem] smooth-shadow border border-slate-50 text-center">
                    <div class="w-16 h-16 bg-slate-50 text-slate-300 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lock text-2xl"></i>
                    </div>
                    <h3 class="font-black text-slate-800 uppercase tracking-tighter mb-2 text-sm">Akses Terbatas</h3>
                    <p class="text-slate-400 text-[10px] leading-relaxed font-medium italic">Role Kasir hanya diizinkan untuk melihat data stok.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tabel Stok -->
            <div class="xl:col-span-3 bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="obatTable">
                        <thead class="bg-slate-50/50 border-b border-slate-100">
                            <tr>
                                <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Produk</th>
                                <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Jumlah</th>
                                <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Expired</th>
                                <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php
                            $res = mysqli_query($koneksi, "SELECT * FROM medicines ORDER BY id DESC");
                            if (mysqli_num_rows($res) > 0):
                                while($row = mysqli_fetch_array($res)):
                                    $tgl_exp       = $row['expired_date'];
                                    $is_expired    = ($tgl_exp <= $today);
                                    $is_near_exp   = ($tgl_exp <= $warning_date);
                                    $is_low_stock  = ($row['jumlah'] <= 15);
                                    $is_out        = ($row['jumlah'] <= 0);
                            ?>
                            <tr class="group hover:bg-blue-50/30 transition">
                                <td class="p-6">
                                    <p class="font-black text-slate-800 text-sm tracking-tight name-target"><?php echo htmlspecialchars($row['nama_obat']); ?></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[8px] font-black text-blue-500 uppercase px-1.5 py-0.5 bg-blue-50 rounded-md"><?php echo htmlspecialchars($row['kategori']); ?></span>
                                        <span class="text-[8px] font-bold text-slate-300 uppercase italic">@<?php echo htmlspecialchars($row['supplier']); ?></span>
                                    </div>
                                </td>
                                <td class="p-6 text-center">
                                    <?php if ($is_out): ?>
                                        <span class="inline-block px-4 py-1.5 rounded-lg font-black text-xs bg-rose-100 text-rose-600">Habis</span>
                                    <?php elseif ($is_low_stock): ?>
                                        <span class="inline-block px-4 py-1.5 rounded-lg font-black text-xs bg-amber-100 text-amber-600"><?php echo $row['jumlah']; ?></span>
                                    <?php else: ?>
                                        <span class="inline-block px-4 py-1.5 rounded-lg font-black text-xs bg-slate-100 text-slate-700 group-hover:bg-blue-600 group-hover:text-white transition"><?php echo $row['jumlah']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-6 text-center">
                                    <span class="px-3 py-1.5 rounded-lg font-bold text-[9px] uppercase tracking-tighter
                                        <?php echo $is_expired ? 'bg-slate-900 text-white' : ($is_near_exp ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'); ?>">
                                        <?php echo date('d M Y', strtotime($tgl_exp)); ?>
                                    </span>
                                </td>
                                <td class="p-6 text-center">
                                    <div class="flex justify-center gap-2">
                                        <?php if ($role != 'Kasir') : ?>
                                            <a href="edit_obat.php?id=<?php echo $row['id']; ?>" class="w-8 h-8 flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition rounded-xl shadow-sm" title="Edit">
                                                <i class="fas fa-pen-nib text-[10px]"></i>
                                            </a>
                                            <?php if ($role == 'Admin') : ?>
                                            <a href="stok_obat.php?hapus=<?php echo $row['id']; ?>" onclick="return confirm('Hapus data obat ini?')" class="w-8 h-8 flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition rounded-xl shadow-sm" title="Hapus">
                                                <i class="fas fa-trash-alt text-[10px]"></i>
                                            </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-slate-300 text-[8px] font-black uppercase tracking-widest italic">View Only</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile;
                            else: ?>
                            <tr>
                                <td colspan="4" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">Belum ada data obat terdaftar.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Legenda status -->
        <div class="mt-6 flex flex-wrap gap-3 text-[10px] font-bold">
            <span class="flex items-center gap-1.5 text-slate-400"><span class="w-3 h-3 rounded bg-emerald-200 inline-block"></span> Stok Aman (&gt;15)</span>
            <span class="flex items-center gap-1.5 text-slate-400"><span class="w-3 h-3 rounded bg-amber-200 inline-block"></span> Stok Menipis (1–15)</span>
            <span class="flex items-center gap-1.5 text-slate-400"><span class="w-3 h-3 rounded bg-rose-200 inline-block"></span> Habis (0)</span>
            <span class="flex items-center gap-1.5 text-slate-400"><span class="w-3 h-3 rounded bg-amber-400 inline-block"></span> Exp &lt; 30 hari</span>
            <span class="flex items-center gap-1.5 text-slate-400"><span class="w-3 h-3 rounded bg-slate-800 inline-block"></span> Sudah Kadaluarsa</span>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">&copy; 2026 Pharma Stock</p>
        </footer>
    </main>

    <script>
        function filterTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows  = document.querySelectorAll("#obatTable tbody tr");
            rows.forEach(row => {
                let nameEl = row.querySelector(".name-target");
                if (nameEl) {
                    row.style.display = nameEl.innerText.toLowerCase().includes(input) ? "" : "none";
                }
            });
        }
    </script>
</body>
</html>