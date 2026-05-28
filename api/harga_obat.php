<?php
/**
 * harga_obat.php
 * Kelola harga beli & jual obat per apotek
 * Akses: Admin, Manager Gudang
 */
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? '';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);

$role_boleh = ['Admin', 'Manager Gudang'];
if (!in_array($role, $role_boleh)) {
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>";
    exit();
}

// ===== PROSES SIMPAN / UPDATE HARGA =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_harga'])) {
    $id_obat    = (int)$_POST['id_obat'];
    $harga_beli = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_beli']);
    $harga_jual = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_jual']);
    $satuan     = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $ap_id      = ($role === 'Super Admin') ? (int)$_POST['id_apotek'] : (int)$id_apotek;

    // Upsert (INSERT or UPDATE)
    $sql = "INSERT INTO harga_obat (id_obat, id_apotek, harga_beli, harga_jual, satuan)
            VALUES ('$id_obat', '$ap_id', '$harga_beli', '$harga_jual', '$satuan')
            ON DUPLICATE KEY UPDATE
                harga_beli = '$harga_beli',
                harga_jual = '$harga_jual',
                satuan     = '$satuan'";

    if (mysqli_query($koneksi, $sql)) {
        $nama_obat_log = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT nama_obat FROM medicines WHERE id='$id_obat'"))['nama_obat'] ?? '-';
        catat_log($koneksi, 'Update Harga Obat', "Obat: $nama_obat_log | Beli: Rp$harga_beli | Jual: Rp$harga_jual", $id_apotek);
        $pesan = 'success:Harga berhasil disimpan!';
    } else {
        $pesan = 'error:Gagal menyimpan harga.';
    }
}

// ===== HAPUS HARGA =====
if (isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM harga_obat WHERE id='$hid'");
    catat_log($koneksi, 'Hapus Harga Obat', "ID harga: $hid", $id_apotek);
    header("Location: harga_obat.php?msg=hapus");
    exit();
}

// ===== QUERY DATA =====
if ($id_apotek) {
    $sql_harga = "
        SELECT h.*, m.nama_obat, m.kategori, a.nama_apotek
        FROM harga_obat h
        JOIN medicines m ON h.id_obat = m.id
        JOIN apotek a ON h.id_apotek = a.id
        WHERE h.id_apotek = '$id_apotek'
        ORDER BY m.nama_obat ASC
    ";
    $obat_list = mysqli_query($koneksi, "SELECT * FROM medicines WHERE id_apotek='$id_apotek' ORDER BY nama_obat ASC");
} else {
    // Super Admin: lihat semua
    $sql_harga = "
        SELECT h.*, m.nama_obat, m.kategori, a.nama_apotek
        FROM harga_obat h
        JOIN medicines m ON h.id_obat = m.id
        JOIN apotek a ON h.id_apotek = a.id
        ORDER BY a.nama_apotek ASC, m.nama_obat ASC
    ";
    $obat_list = mysqli_query($koneksi, "SELECT * FROM medicines ORDER BY nama_obat ASC");
}
$harga_result = mysqli_query($koneksi, $sql_harga);
$apotek_data  = $id_apotek ? get_apotek($koneksi, $id_apotek) : null;
$apotek_semua = mysqli_query($koneksi, "SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");

if (isset($_GET['msg']) && $_GET['msg'] === 'hapus') $pesan = 'success:Harga berhasil dihapus.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harga Obat - Pharma Stock</title>
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
            <a href="<?php echo ($role=='Kasir') ? 'kasir_dashboard.php' : 'dashboard.php'; ?>" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Beranda</span>
            </a>
            <a href="stok_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-box w-5 text-center"></i><span class="hidden md:inline ml-3">Stok Obat</span>
            </a>
            <?php if (in_array($role, ['Admin','Apoteker'])): ?>
            <a href="racikan_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-mortar-pestle w-5 text-center"></i><span class="hidden md:inline ml-3">Racikan Obat</span>
            </a>
            <?php endif; ?>
            <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-chart-bar w-5 text-center"></i><span class="hidden md:inline ml-3">Analisis</span>
            </a>
            <!-- NAVBAR BARU: HARGA OBAT -->
            <a href="harga_obat.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                <i class="fas fa-tags w-5 text-center"></i><span class="hidden md:inline ml-3">Harga Obat</span>
            </a>
            <?php if (in_array($role, ['Admin','Manager Gudang'])): ?>
            <a href="laporan.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-file-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Laporan</span>
            </a>
            <?php endif; ?>
            <?php if ($role === 'Admin'): ?>
            <a href="admin_users.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-users-cog w-5 text-center"></i><span class="hidden md:inline ml-3">User Management</span>
            </a>
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
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Price Management</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
                    Harga <span class="text-blue-600">Obat.</span>
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
            [$tipe, $msg] = explode(':', $pesan, 2);
            $bg  = $tipe === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100';
            $ico = $tipe === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        ?>
        <div class="mb-6 p-4 <?php echo $bg; ?> border rounded-2xl text-xs font-bold flex items-center gap-2">
            <i class="fas <?php echo $ico; ?>"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <!-- FORM TAMBAH / EDIT HARGA -->
        <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-8 mb-8">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-plus text-xs"></i>
                </span>
                Tambah / Update Harga
            </h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <!-- Pilih Obat -->
                <div class="lg:col-span-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Obat</label>
                    <select name="id_obat" required class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">-- Pilih Obat --</option>
                        <?php while ($ob = mysqli_fetch_assoc($obat_list)): ?>
                        <option value="<?php echo $ob['id']; ?>"><?php echo htmlspecialchars($ob['nama_obat']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <!-- Harga Beli -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" min="0" step="100" placeholder="0" required
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <!-- Harga Jual -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Harga Jual (Rp)</label>
                    <input type="number" name="harga_jual" min="0" step="100" placeholder="0" required
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <!-- Satuan + Submit -->
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Satuan</label>
                    <div class="flex gap-2">
                        <select name="satuan" class="flex-1 p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                            <option value="tablet">Tablet</option>
                            <option value="kapsul">Kapsul</option>
                            <option value="botol">Botol</option>
                            <option value="sachet">Sachet</option>
                            <option value="strip">Strip</option>
                            <option value="tube">Tube</option>
                            <option value="ampul">Ampul</option>
                        </select>
                        <button name="simpan_harga" type="submit"
                            class="bg-slate-900 text-white px-5 py-3 rounded-2xl text-[9px] font-black hover:bg-blue-600 transition shadow-lg shadow-slate-100 active:scale-95 uppercase whitespace-nowrap">
                            Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TABEL HARGA -->
        <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
                            <?php if (!$id_apotek): ?>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Apotek</th>
                            <?php endif; ?>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Kategori</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Beli</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Jual</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Margin</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Satuan</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (mysqli_num_rows($harga_result) > 0):
                            while ($h = mysqli_fetch_assoc($harga_result)):
                                $margin = $h['harga_jual'] - $h['harga_beli'];
                                $margin_pct = ($h['harga_beli'] > 0) ? round(($margin / $h['harga_beli']) * 100, 1) : 0;
                                $margin_color = $margin >= 0 ? 'text-emerald-600' : 'text-rose-600';
                        ?>
                        <tr class="hover:bg-blue-50/20 transition-colors">
                            <td class="p-5">
                                <div class="font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($h['nama_obat']); ?></div>
                            </td>
                            <?php if (!$id_apotek): ?>
                            <td class="p-5 text-[10px] font-bold text-slate-500"><?php echo htmlspecialchars($h['nama_apotek']); ?></td>
                            <?php endif; ?>
                            <td class="p-5 text-center">
                                <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase">
                                    <?php echo htmlspecialchars($h['kategori']); ?>
                                </span>
                            </td>
                            <td class="p-5 text-center font-bold text-slate-700 text-xs">
                                Rp <?php echo number_format($h['harga_beli'],0,',','.'); ?>
                            </td>
                            <td class="p-5 text-center font-black text-slate-800 text-xs">
                                Rp <?php echo number_format($h['harga_jual'],0,',','.'); ?>
                            </td>
                            <td class="p-5 text-center font-black text-xs <?php echo $margin_color; ?>">
                                <?php echo ($margin >= 0 ? '+' : ''); echo number_format($margin,0,',','.'); ?>
                                <span class="text-[9px] font-bold opacity-70">(<?php echo $margin_pct; ?>%)</span>
                            </td>
                            <td class="p-5 text-center text-[10px] font-bold text-slate-500 uppercase"><?php echo htmlspecialchars($h['satuan']); ?></td>
                            <td class="p-5 text-center">
                                <a href="harga_obat.php?hapus=<?php echo $h['id']; ?>"
                                   onclick="return confirm('Hapus data harga ini?')"
                                   class="w-8 h-8 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl flex items-center justify-center mx-auto transition">
                                    <i class="fas fa-trash text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile;
                        else: ?>
                        <tr>
                            <td colspan="8" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
                                Belum ada data harga. Tambahkan harga obat di atas.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Price Intelligence</p>
        </footer>
    </main>
</body>
</html>