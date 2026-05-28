<?php
/**
 * super_admin_dashboard.php
 * Dashboard khusus Super Admin:
 * - Lihat semua apotek
 * - Lihat semua Admin & Manager Gudang per apotek
 * - Statistik gabungan seluruh apotek
 * - Tambah / Edit / Hapus apotek
 */
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users = $_COOKIE['users'] ?? '';
$role  = $role_saat_ini;

if ($role !== 'Super Admin') {
    header("Location: dashboard.php");
    exit();
}

// ===== TAMBAH APOTEK =====
if (isset($_POST['tambah_apotek'])) {
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama_apotek']);
    $alamat   = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $kota     = mysqli_real_escape_string($koneksi, $_POST['kota']);
    $provinsi = mysqli_real_escape_string($koneksi, $_POST['provinsi']);
    $telp     = mysqli_real_escape_string($koneksi, $_POST['telp']);
    $wa       = mysqli_real_escape_string($koneksi, $_POST['wa_apotek']);
    $lat      = (float)$_POST['lat'];
    $lng      = (float)$_POST['lng'];
    $jam      = mysqli_real_escape_string($koneksi, $_POST['jam_buka']);

    mysqli_query($koneksi,
        "INSERT INTO apotek (nama_apotek, alamat, kota, provinsi, telp, wa_apotek, lat, lng, jam_buka)
         VALUES ('$nama','$alamat','$kota','$provinsi','$telp','$wa','$lat','$lng','$jam')"
    );
    catat_log($koneksi, 'Tambah Apotek', "Apotek: $nama, Kota: $kota");
    $pesan = 'success:Apotek berhasil ditambahkan!';
}

// ===== HAPUS APOTEK =====
if (isset($_GET['hapus_apotek'])) {
    $aid = (int)$_GET['hapus_apotek'];
    mysqli_query($koneksi, "DELETE FROM apotek WHERE id='$aid'");
    catat_log($koneksi, 'Hapus Apotek', "ID Apotek: $aid");
    header("Location: super_admin_dashboard.php?msg=hapus");
    exit();
}

// ===== TOGGLE STATUS APOTEK =====
if (isset($_GET['toggle'])) {
    $aid = (int)$_GET['toggle'];
    $cur = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT status FROM apotek WHERE id='$aid'"))['status'];
    $new = ($cur === 'aktif') ? 'nonaktif' : 'aktif';
    mysqli_query($koneksi, "UPDATE apotek SET status='$new' WHERE id='$aid'");
    header("Location: super_admin_dashboard.php");
    exit();
}

// ===== DATA =====
$apotek_list = mysqli_query($koneksi, "SELECT * FROM apotek ORDER BY nama_apotek ASC");
$total_apotek  = mysqli_num_rows($apotek_list);
$total_users   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM users WHERE role NOT IN ('Pending','Super Admin')"))['c'];
$total_obat    = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines"))['c'];
$total_racikan = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM racikan"))['c'];

if (isset($_GET['msg']) && $_GET['msg']==='hapus') $pesan = 'success:Apotek berhasil dihapus.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fe; font-size: 13px; }
        * { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .smooth-shadow { box-shadow: 0 10px 30px rgba(139,153,178,0.1); }
        .nav-text { font-size: 12px; letter-spacing: 0.02em; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(139,153,178,0.18); }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <!-- SIDEBAR SUPER ADMIN -->
    <aside class="w-20 md:w-64 bg-gradient-to-b from-slate-900 to-slate-800 border-r border-slate-700 flex flex-col items-center py-8 sticky top-0 h-screen z-50">
        <div class="mb-10 w-10 h-10 bg-amber-400 rounded-xl flex items-center justify-center text-slate-900 shadow-lg shadow-amber-200">
            <i class="fas fa-crown text-lg"></i>
        </div>
        <nav class="flex flex-col gap-2 w-full px-4 font-bold h-full nav-text">
            <a href="super_admin_dashboard.php" class="flex items-center justify-center md:justify-start p-3 bg-amber-400 text-slate-900 rounded-xl shadow-xl transition">
                <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Dashboard</span>
            </a>
            <a href="activity_log.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-700 hover:text-white rounded-xl transition">
                <i class="fas fa-history w-5 text-center"></i><span class="hidden md:inline ml-3">Log Aktivitas</span>
            </a>
            <a href="super_admin_users.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-700 hover:text-white rounded-xl transition">
                <i class="fas fa-users-cog w-5 text-center"></i><span class="hidden md:inline ml-3">Semua User</span>
            </a>
            <a href="landing.php" target="_blank" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-700 hover:text-white rounded-xl transition">
                <i class="fas fa-globe w-5 text-center"></i><span class="hidden md:inline ml-3">Lihat Landing Page</span>
            </a>
            <div class="mt-auto flex flex-col gap-2">
                <a href="profil.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-700 hover:text-white rounded-xl transition">
                    <i class="fas fa-user w-5 text-center"></i><span class="hidden md:inline ml-3">Profil</span>
                </a>
                <a href="logout.php" class="flex items-center justify-center md:justify-start p-3 text-red-400 hover:bg-red-900/30 rounded-xl transition">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Keluar</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">
        <!-- HEADER -->
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div>
                <p class="text-amber-500 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">System Control Center</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
                    Super <span class="text-amber-500">Admin.</span>
                </h1>
            </div>
            <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
                <div class="flex flex-col items-end px-3">
                    <p class="text-[9px] text-amber-500 font-black uppercase tracking-widest">⭐ Super Admin</p>
                    <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
                </div>
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-black text-sm border-2 border-white shadow-inner">
                    <?php echo strtoupper(substr($users,0,1)); ?>
                </div>
            </div>
        </header>

        <?php if (isset($pesan)):
            [$t,$m] = explode(':', $pesan, 2);
            $bg = $t==='success' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100';
        ?>
        <div class="mb-6 p-4 <?php echo $bg; ?> border rounded-2xl text-xs font-bold flex items-center gap-2">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($m); ?>
        </div>
        <?php endif; ?>

        <!-- STATS GLOBAL -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-10">
            <?php
            $stats = [
                ['label'=>'Total Apotek',  'val'=>$total_apotek,  'icon'=>'fa-clinic-medical', 'color'=>'amber'],
                ['label'=>'Total User',    'val'=>$total_users,   'icon'=>'fa-users',           'color'=>'blue'],
                ['label'=>'Total Obat',    'val'=>$total_obat,    'icon'=>'fa-pills',           'color'=>'emerald'],
                ['label'=>'Total Racikan', 'val'=>$total_racikan, 'icon'=>'fa-mortar-pestle',   'color'=>'purple'],
            ];
            foreach ($stats as $s): ?>
            <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex flex-col items-center text-center card-hover">
                <div class="w-12 h-12 bg-<?php echo $s['color']; ?>-50 text-<?php echo $s['color']; ?>-600 rounded-2xl flex items-center justify-center mb-3">
                    <i class="fas <?php echo $s['icon']; ?> text-xl"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-800"><?php echo $s['val']; ?></h3>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1"><?php echo $s['label']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TAMBAH APOTEK BARU -->
        <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-8 mb-8">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                <span class="w-7 h-7 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-plus text-xs"></i>
                </span>
                Tambah Apotek Baru
            </h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Apotek *</label>
                    <input type="text" name="nama_apotek" required placeholder="Apotek Sehat Farma"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div class="lg:col-span-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Alamat Lengkap *</label>
                    <input type="text" name="alamat" required placeholder="Jl. Pahlawan No. 12"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Kota *</label>
                    <input type="text" name="kota" required placeholder="Tulungagung"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Provinsi</label>
                    <input type="text" name="provinsi" value="Jawa Timur" placeholder="Jawa Timur"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">No. Telepon</label>
                    <input type="text" name="telp" placeholder="0355-123456"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">WA Apotek (628xxx)</label>
                    <input type="text" name="wa_apotek" placeholder="6281234567890"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Latitude (GPS)</label>
                    <input type="number" name="lat" step="0.0000001" placeholder="-8.0653"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Longitude (GPS)</label>
                    <input type="number" name="lng" step="0.0000001" placeholder="111.9039"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Jam Buka</label>
                    <input type="text" name="jam_buka" value="08:00 - 21:00" placeholder="08:00 - 21:00"
                        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                <div class="lg:col-span-3">
                    <button name="tambah_apotek" type="submit"
                        class="bg-amber-400 text-slate-900 px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-amber-500 transition shadow-lg shadow-amber-100 active:scale-95">
                        <i class="fas fa-plus-circle mr-2"></i> Tambah Apotek
                    </button>
                </div>
            </form>
        </div>

        <!-- DAFTAR APOTEK -->
        <div class="mb-6">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Semua Apotek Terdaftar</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php
            mysqli_data_seek($apotek_list, 0);
            while ($ap = mysqli_fetch_assoc($apotek_list)):
                // Ambil admin & manager gudang apotek ini
                $staff = mysqli_query($koneksi,
                    "SELECT username, role FROM users
                     WHERE id_apotek='{$ap['id']}' AND role IN ('Admin','Manager Gudang','Apoteker','Kasir')
                     ORDER BY role ASC");
                $jml_obat = mysqli_fetch_assoc(mysqli_query($koneksi,
                    "SELECT COUNT(*) c FROM medicines WHERE id_apotek='{$ap['id']}'"))['c'];
                $stok_habis = mysqli_fetch_assoc(mysqli_query($koneksi,
                    "SELECT COUNT(*) c FROM medicines WHERE id_apotek='{$ap['id']}' AND jumlah<=0"))['c'];
            ?>
            <div class="bg-white rounded-[2rem] smooth-shadow border border-slate-50 p-6 card-hover">
                <!-- Header apotek -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600">
                            <i class="fas fa-clinic-medical text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-900 text-sm uppercase italic"><?php echo htmlspecialchars($ap['nama_apotek']); ?></h3>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">ID: #<?php echo $ap['id']; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="super_admin_dashboard.php?toggle=<?php echo $ap['id']; ?>"
                           class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $ap['status']==='aktif' ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-500 hover:text-white' : 'bg-slate-100 text-slate-500 hover:bg-blue-500 hover:text-white'; ?> transition">
                            <?php echo $ap['status']==='aktif' ? '✓ Aktif' : '✗ Nonaktif'; ?>
                        </a>
                    </div>
                </div>

                <!-- Info Apotek -->
                <div class="text-[10px] text-slate-500 font-bold space-y-1 mb-4 pl-1">
                    <p><i class="fas fa-map-marker-alt text-rose-400 w-4"></i> <?php echo htmlspecialchars($ap['alamat']); ?>, <?php echo htmlspecialchars($ap['kota']); ?></p>
                    <p><i class="fas fa-clock text-blue-400 w-4"></i> <?php echo htmlspecialchars($ap['jam_buka']); ?></p>
                    <?php if ($ap['wa_apotek']): ?>
                    <p><i class="fab fa-whatsapp text-emerald-500 w-4"></i> <?php echo htmlspecialchars($ap['wa_apotek']); ?></p>
                    <?php endif; ?>
                    <?php if ($ap['lat'] && $ap['lng']): ?>
                    <p><i class="fas fa-map-pin text-indigo-400 w-4"></i> <?php echo $ap['lat']; ?>, <?php echo $ap['lng']; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Stats obat -->
                <div class="flex gap-3 mb-4">
                    <div class="flex-1 bg-blue-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-blue-600"><?php echo $jml_obat; ?></p>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Jenis Obat</p>
                    </div>
                    <div class="flex-1 bg-rose-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-black text-rose-500"><?php echo $stok_habis; ?></p>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Stok Habis</p>
                    </div>
                </div>

                <!-- Daftar Staff -->
                <div class="border-t border-slate-50 pt-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Staff Terdaftar</p>
                    <?php if (mysqli_num_rows($staff) > 0):
                        while ($s = mysqli_fetch_assoc($staff)):
                            $badge_color = match($s['role']) {
                                'Admin'          => 'bg-rose-50 text-rose-600',
                                'Manager Gudang' => 'bg-amber-50 text-amber-700',
                                'Apoteker'       => 'bg-purple-50 text-purple-600',
                                'Kasir'          => 'bg-emerald-50 text-emerald-700',
                                default          => 'bg-slate-100 text-slate-500',
                            };
                    ?>
                    <div class="flex items-center justify-between py-1.5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 font-black text-[9px]">
                                <?php echo strtoupper(substr($s['username'],0,1)); ?>
                            </div>
                            <span class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($s['username']); ?></span>
                        </div>
                        <span class="px-2 py-0.5 <?php echo $badge_color; ?> text-[9px] font-black rounded-full uppercase tracking-tighter">
                            <?php echo htmlspecialchars($s['role']); ?>
                        </span>
                    </div>
                    <?php endwhile;
                    else: ?>
                    <p class="text-[10px] text-slate-300 italic font-bold">Belum ada staff terdaftar.</p>
                    <?php endif; ?>
                </div>

                <!-- Aksi -->
                <div class="flex gap-2 mt-4 pt-4 border-t border-slate-50">
                    <?php if ($ap['wa_apotek']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','',$ap['wa_apotek']); ?>?text=Halo%20<?php echo urlencode($ap['nama_apotek']); ?>%2C%20ini%20pesan%20dari%20Super%20Admin%20Pharma%20Stock."
                       target="_blank"
                       class="flex-1 flex items-center justify-center gap-2 bg-emerald-50 text-emerald-600 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-emerald-500 hover:text-white transition">
                        <i class="fab fa-whatsapp"></i> Hubungi
                    </a>
                    <?php endif; ?>
                    <a href="super_admin_dashboard.php?hapus_apotek=<?php echo $ap['id']; ?>"
                       onclick="return confirm('HAPUS apotek <?php echo addslashes($ap['nama_apotek']); ?> secara permanen?\nSemua data terkait akan tetap ada tapi apotek dihapus.')"
                       class="flex-1 flex items-center justify-center gap-2 bg-rose-50 text-rose-600 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-rose-500 hover:text-white transition">
                        <i class="fas fa-trash"></i> Hapus
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Super Admin Control</p>
        </footer>
    </main>
</body>
</html>