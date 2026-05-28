<?php
/**
 * activity_log.php
 * Log semua aktivitas user di sistem
 * Akses: Admin (apotek sendiri), Super Admin (semua apotek)
 */
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? '';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);

if (!in_array($role, ['Admin', 'Super Admin'])) {
    echo "<script>alert('Akses Ditolak! Hanya Admin yang bisa melihat log.'); window.location='dashboard.php';</script>";
    exit();
}

// Filter tanggal & pencarian
$filter_date  = isset($_GET['tgl']) ? $_GET['tgl'] : '';
$filter_user  = isset($_GET['user']) ? mysqli_real_escape_string($koneksi, $_GET['user']) : '';
$filter_aksi  = isset($_GET['aksi']) ? mysqli_real_escape_string($koneksi, $_GET['aksi']) : '';

// Hapus log lama (opsional, tombol manual)
if (isset($_GET['bersihkan']) && $role === 'Super Admin') {
    $hari = (int)($_GET['bersihkan']);
    mysqli_query($koneksi, "DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL $hari DAY)");
    header("Location: activity_log.php?msg=bersih");
    exit();
}

// Build query berdasarkan role
$where = [];
if ($role === 'Admin' && $id_apotek) {
    $where[] = "al.id_apotek = '$id_apotek'";
}
if ($filter_date) {
    $d = mysqli_real_escape_string($koneksi, $filter_date);
    $where[] = "DATE(al.created_at) = '$d'";
}
if ($filter_user) {
    $where[] = "al.username LIKE '%$filter_user%'";
}
if ($filter_aksi) {
    $where[] = "al.aksi LIKE '%$filter_aksi%'";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql_log = "
    SELECT al.*, a.nama_apotek
    FROM activity_log al
    LEFT JOIN apotek a ON al.id_apotek = a.id
    $where_sql
    ORDER BY al.created_at DESC
    LIMIT 500
";
$log_result = mysqli_query($koneksi, $sql_log);
$total_log  = mysqli_num_rows($log_result);

// Stats
$total_hari_ini = mysqli_num_rows(mysqli_query($koneksi,
    "SELECT id FROM activity_log WHERE DATE(created_at)=CURDATE()" .
    ($role==='Admin' && $id_apotek ? " AND id_apotek='$id_apotek'" : "")));

// Catat bahwa admin membuka log
catat_log($koneksi, 'Buka Activity Log', "Filter: tgl=$filter_date, user=$filter_user", $id_apotek);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fe; font-size: 13px; }
        * { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .smooth-shadow { box-shadow: 0 10px 30px rgba(139,153,178,0.1); }
        .nav-text { font-size: 12px; letter-spacing: 0.02em; }
        .log-row:hover { background: rgba(239,246,255,0.7); }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50">
        <div class="mb-10 w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <i class="fas fa-pills text-lg"></i>
        </div>
        <nav class="flex flex-col gap-2 w-full px-4 font-bold h-full nav-text">
            <a href="<?php echo ($role==='Super Admin') ? 'super_admin_dashboard.php' : 'dashboard.php'; ?>" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Beranda</span>
            </a>
            <?php if ($role !== 'Super Admin'): ?>
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
            <a href="harga_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-tags w-5 text-center"></i><span class="hidden md:inline ml-3">Harga Obat</span>
            </a>
            <a href="laporan.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-file-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Laporan</span>
            </a>
            <a href="admin_users.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-users-cog w-5 text-center"></i><span class="hidden md:inline ml-3">User Management</span>
            </a>
            <?php endif; ?>
            <a href="activity_log.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                <i class="fas fa-history w-5 text-center"></i><span class="hidden md:inline ml-3">Log Aktivitas</span>
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
            <div>
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">System Audit Trail</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
                    Log <span class="text-blue-600">Aktivitas.</span>
                </h1>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($role === 'Super Admin'): ?>
                <a href="activity_log.php?bersihkan=30"
                   onclick="return confirm('Hapus semua log lebih dari 30 hari yang lalu?')"
                   class="bg-rose-50 text-rose-600 px-4 py-2 rounded-full font-black text-[9px] uppercase tracking-widest hover:bg-rose-500 hover:text-white transition shadow-sm">
                    <i class="fas fa-trash mr-1"></i> Bersihkan Log Lama
                </a>
                <?php endif; ?>
                <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
                    <div class="flex flex-col items-end px-3">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
                        <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 font-black text-sm border-2 border-white shadow-inner">
                        <?php echo strtoupper(substr($users,0,1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <?php if (isset($_GET['msg']) && $_GET['msg']==='bersih'): ?>
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-2xl text-xs font-bold flex items-center gap-2">
            <i class="fas fa-check-circle"></i> Log lama berhasil dibersihkan.
        </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded-2xl smooth-shadow border border-slate-50 text-center">
                <h4 class="text-2xl font-black text-blue-600"><?php echo $total_log; ?></h4>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Hasil Filter</p>
            </div>
            <div class="bg-white p-4 rounded-2xl smooth-shadow border border-slate-50 text-center">
                <h4 class="text-2xl font-black text-emerald-600"><?php echo $total_hari_ini; ?></h4>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Aktivitas Hari Ini</p>
            </div>
        </div>

        <!-- FILTER FORM -->
        <form method="GET" class="bg-white rounded-[2rem] smooth-shadow border border-slate-50 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Tanggal</label>
                    <input type="date" name="tgl" value="<?php echo $filter_date; ?>"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Username</label>
                    <input type="text" name="user" value="<?php echo htmlspecialchars($filter_user); ?>"
                        placeholder="Cari username..." 
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Aksi</label>
                    <input type="text" name="aksi" value="<?php echo htmlspecialchars($filter_aksi); ?>"
                        placeholder="Cari aksi..." 
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-slate-900 text-white p-3 rounded-xl text-[9px] font-black hover:bg-blue-600 transition shadow-lg uppercase tracking-widest">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="activity_log.php" class="flex-1 bg-slate-100 text-slate-500 p-3 rounded-xl text-[9px] font-black hover:bg-slate-200 transition text-center flex items-center justify-center uppercase tracking-widest">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <!-- TABEL LOG -->
        <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">User</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Role</th>
                            <?php if ($role === 'Super Admin'): ?>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Apotek</th>
                            <?php endif; ?>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Aksi</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Detail</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        // Warna badge per aksi
                        $aksi_warna = [
                            'login'   => 'bg-emerald-50 text-emerald-700',
                            'logout'  => 'bg-slate-100 text-slate-600',
                            'hapus'   => 'bg-rose-50 text-rose-600',
                            'tambah'  => 'bg-blue-50 text-blue-600',
                            'update'  => 'bg-amber-50 text-amber-700',
                            'buka'    => 'bg-indigo-50 text-indigo-600',
                        ];
                        if (mysqli_num_rows($log_result) > 0):
                            while ($log = mysqli_fetch_assoc($log_result)):
                                $aksi_lower = strtolower($log['aksi']);
                                $warna = 'bg-slate-100 text-slate-600';
                                foreach ($aksi_warna as $k => $v) {
                                    if (str_contains($aksi_lower, $k)) { $warna = $v; break; }
                                }
                        ?>
                        <tr class="log-row transition-colors">
                            <td class="p-4 whitespace-nowrap">
                                <div class="text-[10px] font-black text-slate-700"><?php echo date('d M Y', strtotime($log['created_at'])); ?></div>
                                <div class="text-[9px] text-slate-400 font-bold"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 font-black text-[10px]">
                                        <?php echo strtoupper(substr($log['username'],0,1)); ?>
                                    </div>
                                    <span class="font-black text-slate-800 text-xs"><?php echo htmlspecialchars($log['username']); ?></span>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-lg uppercase tracking-tighter">
                                    <?php echo htmlspecialchars($log['role']); ?>
                                </span>
                            </td>
                            <?php if ($role === 'Super Admin'): ?>
                            <td class="p-4 text-[10px] font-bold text-slate-500">
                                <?php echo htmlspecialchars($log['nama_apotek'] ?? '—'); ?>
                            </td>
                            <?php endif; ?>
                            <td class="p-4">
                                <span class="px-3 py-1 <?php echo $warna; ?> text-[9px] font-black rounded-full uppercase tracking-tighter">
                                    <?php echo htmlspecialchars($log['aksi']); ?>
                                </span>
                            </td>
                            <td class="p-4 text-[10px] text-slate-500 font-medium max-w-xs truncate" title="<?php echo htmlspecialchars($log['detail']); ?>">
                                <?php echo htmlspecialchars($log['detail'] ?: '—'); ?>
                            </td>
                            <td class="p-4 text-center text-[9px] font-bold text-slate-400 font-mono">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </td>
                        </tr>
                        <?php endwhile;
                        else: ?>
                        <tr>
                            <td colspan="7" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
                                Tidak ada log aktivitas yang ditemukan.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Audit Intelligence</p>
        </footer>
    </main>
</body>
</html>