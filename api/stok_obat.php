<?php
/**
 * stok_obat.php (VERSI BARU)
 * Tambahan:
 * - Catat log setiap CRUD
 * - Kolom harga dari tabel harga_obat
 * - Navbar menambahkan Harga Obat & Log Aktivitas
 * - Filter per apotek untuk Super Admin
 * GANTI file stok_obat.php yang lama dengan file ini
 */
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? 'Guest';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek_data = $id_apotek ? get_apotek($koneksi, $id_apotek) : null;

// Super Admin & non-terikat bisa lihat semua
$filter_ap = ($role === 'Super Admin' && isset($_GET['apotek'])) ? (int)$_GET['apotek'] : $id_apotek;

// ===== TAMBAH OBAT =====
if (isset($_POST['tambah'])) {
    $nama  = mysqli_real_escape_string($koneksi, $_POST['nama_obat']);
    $kat   = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $qty   = (int)$_POST['jumlah'];
    $exp   = $_POST['expired_date'];
    $supp  = mysqli_real_escape_string($koneksi, $_POST['supplier']);
    $wa    = mysqli_real_escape_string($koneksi, $_POST['wa_supplier']);
    $ap    = $filter_ap ? "'$filter_ap'" : ($id_apotek ? "'$id_apotek'" : "NULL");

    $sql = "INSERT INTO medicines (nama_obat, kategori, jumlah, expired_date, supplier, wa_supplier, id_apotek)
            VALUES ('$nama','$kat','$qty','$exp','$supp','$wa',$ap)";
    if (mysqli_query($koneksi, $sql)) {
        catat_log($koneksi, 'Tambah Obat', "Nama: $nama, Qty: $qty, Supplier: $supp", $id_apotek);
        $pesan = 'success:Obat berhasil ditambahkan!';
    } else {
        $pesan = 'error:Gagal menambah obat.';
    }
}

// ===== HAPUS OBAT =====
if (isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    $nama_del = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT nama_obat FROM medicines WHERE id='$hid'"))['nama_obat'] ?? '-';
    mysqli_query($koneksi, "DELETE FROM medicines WHERE id='$hid'");
    catat_log($koneksi, 'Hapus Obat', "Nama: $nama_del, ID: $hid", $id_apotek);
    header("Location: stok_obat.php?msg=hapus");
    exit();
}
if (isset($_GET['msg']) && $_GET['msg']==='hapus') $pesan = 'success:Obat berhasil dihapus.';

// ===== QUERY OBAT =====
$where_apotek = '';
if ($filter_ap) {
    $where_apotek = "WHERE m.id_apotek = '$filter_ap'";
} elseif ($id_apotek) {
    $where_apotek = "WHERE m.id_apotek = '$id_apotek'";
}

$sql_obat = "
    SELECT m.*, 
           IFNULL(h.harga_jual,0) AS harga_jual,
           IFNULL(h.satuan,'tablet') AS satuan
    FROM medicines m
    LEFT JOIN harga_obat h ON h.id_obat = m.id AND h.id_apotek = m.id_apotek
    $where_apotek
    ORDER BY m.nama_obat ASC
";
$data_obat = mysqli_query($koneksi, $sql_obat);

$stok_aman    = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM medicines WHERE jumlah > 15" . ($id_apotek ? " AND id_apotek='$id_apotek'" : "")));
$stok_menipis = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM medicines WHERE jumlah > 0 AND jumlah <= 15" . ($id_apotek ? " AND id_apotek='$id_apotek'" : "")));
$stok_habis   = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM medicines WHERE jumlah <= 0" . ($id_apotek ? " AND id_apotek='$id_apotek'" : "")));

// WA order stok: pesan otomatis ke supplier
function wa_order_url($wa, $nama_obat, $jumlah, $nama_apotek = '') {
    $wa_clean = preg_replace('/[^0-9]/', '', $wa);
    $pesan = "Halo, kami dari *$nama_apotek* ingin memesan stok obat:\n\n"
           . "*Nama Obat:* $nama_obat\n"
           . "*Stok Saat Ini:* $jumlah\n"
           . "*Jumlah Pesan:* ... (mohon konfirmasi ketersediaan)\n\n"
           . "Terima kasih 🙏";
    return "https://wa.me/$wa_clean?text=" . urlencode($pesan);
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
        * { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .smooth-shadow { box-shadow: 0 10px 30px rgba(139,153,178,0.1); }
        .nav-text { font-size: 12px; letter-spacing: 0.02em; }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50">
        <div class="mb-10 w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <i class="fas fa-pills text-lg"></i>
        </div>
        <nav class="flex flex-col gap-2 w-full px-4 font-bold h-full nav-text">
            <a href="<?php echo ($role=='Kasir') ? 'kasir_dashboard.php' : (($role=='Super Admin') ? 'super_admin_dashboard.php' : 'dashboard.php'); ?>"
               class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Beranda</span>
            </a>
            <a href="stok_obat.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                <i class="fas fa-box w-5 text-center"></i><span class="hidden md:inline ml-3">Stok Obat</span>
            </a>
            <?php if (in_array($role, ['Admin','Apoteker'])): ?>
            <a href="racikan_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-mortar-pestle w-5 text-center"></i><span class="hidden md:inline ml-3">Racikan Obat</span>
            </a>
            <?php endif; ?>
            <?php if (in_array($role, ['Admin','Manager Gudang','Apoteker','Kasir'])): ?>
            <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-chart-bar w-5 text-center"></i><span class="hidden md:inline ml-3">Analisis</span>
            </a>
            <?php endif; ?>
            <?php if (in_array($role, ['Admin','Manager Gudang'])): ?>
            <!-- NAVBAR BARU: HARGA OBAT -->
            <a href="harga_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-tags w-5 text-center"></i><span class="hidden md:inline ml-3">Harga Obat</span>
            </a>
            <a href="laporan.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-file-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Laporan</span>
            </a>
            <?php endif; ?>
            <?php if ($role === 'Admin'): ?>
            <a href="admin_users.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-users-cog w-5 text-center"></i><span class="hidden md:inline ml-3">User Management</span>
            </a>
            <!-- LOG AKTIVITAS (Admin & Super Admin) -->
            <a href="activity_log.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-history w-5 text-center"></i><span class="hidden md:inline ml-3">Log Aktivitas</span>
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
            <div>
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Inventory Management</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
                    Stok <span class="text-blue-600">Obat.</span>
                    <?php if ($apotek_data): ?>
                    <span class="text-sm font-bold text-slate-400 normal-case ml-2">— <?php echo htmlspecialchars($apotek_data['nama_apotek']); ?></span>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
                <div class="flex flex-col items-end px-3">
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
                    <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
                </div>
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 font-black text-sm border-2 border-white shadow-inner">
                    <?php echo strtoupper(substr($users,0,1)); ?>
                </div>
            </div>
        </header>

        <!-- NOTIFIKASI -->
        <?php if (isset($pesan)):
            [$t,$m] = explode(':', $pesan, 2);
            $bg  = $t==='success' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100';
            $ico = $t==='success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        ?>
        <div class="mb-6 p-4 <?php echo $bg; ?> border rounded-2xl text-xs font-bold flex items-center gap-2">
            <i class="fas <?php echo $ico; ?>"></i> <?php echo htmlspecialchars($m); ?>
        </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex items-center gap-4 group hover:bg-emerald-500 transition">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center group-hover:bg-white/20 group-hover:text-white">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-800 group-hover:text-white"><?php echo $stok_aman; ?></h3>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest group-hover:text-white/80">Stok Aman</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex items-center gap-4 group hover:bg-amber-500 transition">
                <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center group-hover:bg-white/20 group-hover:text-white">
                    <i class="fas fa-hourglass-half text-xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-800 group-hover:text-white"><?php echo $stok_menipis; ?></h3>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest group-hover:text-white/80">Stok Menipis</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex items-center gap-4 group hover:bg-rose-500 transition">
                <div class="w-12 h-12 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center group-hover:bg-white/20 group-hover:text-white">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-800 group-hover:text-white"><?php echo $stok_habis; ?></h3>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest group-hover:text-white/80">Out of Stock</p>
                </div>
            </div>
        </div>

        <!-- FORM TAMBAH OBAT (hanya non-Kasir) -->
        <?php if ($role !== 'Kasir'): ?>
        <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-8 mb-8">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-plus text-xs"></i>
                </span>
                Tambah Obat Baru
            </h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Obat *</label>
                    <input type="text" name="nama_obat" required placeholder="Paracetamol 500mg"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Kategori *</label>
                    <select name="kategori" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option>Obat Bebas</option>
                        <option>Obat Bebas Terbatas</option>
                        <option>Obat Keras</option>
                        <option>Obat Tradisional</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Jumlah Stok *</label>
                    <input type="number" name="jumlah" min="0" required placeholder="100"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Tanggal Expired *</label>
                    <input type="date" name="expired_date" required
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Supplier</label>
                    <input type="text" name="supplier" placeholder="PT Kimia Farma"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">WA Supplier (628xxx)</label>
                    <div class="flex gap-2">
                        <input type="text" name="wa_supplier" placeholder="6281234567890"
                            class="flex-1 p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <button name="tambah" type="submit"
                            class="bg-slate-900 text-white px-5 py-3 rounded-2xl text-[9px] font-black hover:bg-blue-600 transition shadow-lg active:scale-95 uppercase">
                            Tambah
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL OBAT -->
        <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Kategori</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Jual</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Expired</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Supplier</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (mysqli_num_rows($data_obat) > 0):
                            while ($row = mysqli_fetch_assoc($data_obat)):
                                if ($row['jumlah'] <= 0) {
                                    $badge = 'bg-rose-50 text-rose-600 border border-rose-100'; $label = 'Habis';
                                } elseif ($row['jumlah'] <= 15) {
                                    $badge = 'bg-amber-50 text-amber-600 border border-amber-100'; $label = 'Menipis';
                                } else {
                                    $badge = 'bg-emerald-50 text-emerald-600 border border-emerald-100'; $label = 'Aman';
                                }
                                $harga_fmt = ($row['harga_jual'] > 0)
                                    ? 'Rp ' . number_format($row['harga_jual'], 0, ',', '.')
                                    : '—';
                                $nama_apotek_row = $apotek_data['nama_apotek'] ?? 'Apotek';
                        ?>
                        <tr class="hover:bg-blue-50/20 transition-colors">
                            <td class="p-5">
                                <div class="font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($row['nama_obat']); ?></div>
                            </td>
                            <td class="p-5 text-center">
                                <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase tracking-tighter">
                                    <?php echo htmlspecialchars($row['kategori']); ?>
                                </span>
                            </td>
                            <td class="p-5 text-center font-black text-slate-700 text-xs"><?php echo $harga_fmt; ?></td>
                            <td class="p-5 text-center font-black text-slate-800 text-sm"><?php echo $row['jumlah']; ?></td>
                            <td class="p-5 text-center text-[10px] font-bold text-slate-500">
                                <?php echo !empty($row['expired_date']) ? date('d M Y', strtotime($row['expired_date'])) : '—'; ?>
                            </td>
                            <td class="p-5 text-center">
                                <span class="px-3 py-1 <?php echo $badge; ?> text-[9px] font-black rounded-full uppercase tracking-tighter">
                                    <?php echo $label; ?>
                                </span>
                            </td>
                            <td class="p-5">
                                <div class="text-[10px] font-bold text-slate-600"><?php echo htmlspecialchars($row['supplier'] ?: '—'); ?></div>
                                <?php if (!empty($row['wa_supplier'])): ?>
                                <!-- WA ORDER STOK OTOMATIS -->
                                <a href="<?php echo wa_order_url($row['wa_supplier'], $row['nama_obat'], $row['jumlah'], $nama_apotek_row); ?>"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 mt-1 text-emerald-500 hover:text-emerald-700 text-[9px] font-black uppercase tracking-wider transition">
                                    <i class="fab fa-whatsapp"></i> Order Stok
                                </a>
                                <?php endif; ?>
                            </td>
                            <td class="p-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if ($role !== 'Kasir'): ?>
                                    <a href="edit_obat.php?id=<?php echo $row['id']; ?>"
                                       class="w-8 h-8 bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white rounded-xl flex items-center justify-center transition"
                                       title="Edit">
                                        <i class="fas fa-edit text-[10px]"></i>
                                    </a>
                                    <a href="stok_obat.php?hapus=<?php echo $row['id']; ?>"
                                       onclick="return confirm('Hapus obat <?php echo addslashes($row['nama_obat']); ?>?')"
                                       class="w-8 h-8 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl flex items-center justify-center transition"
                                       title="Hapus">
                                        <i class="fas fa-trash text-[10px]"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-[9px] text-slate-300 font-bold italic">Read Only</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile;
                        else: ?>
                        <tr>
                            <td colspan="8" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
                                Belum ada data obat terdaftar.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Inventory Intelligence</p>
        </footer>
    </main>
</body>
</html>