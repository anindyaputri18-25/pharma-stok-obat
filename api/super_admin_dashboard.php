<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users = $_COOKIE['users'] ?? '';
$role  = $role_saat_ini;
if ($role !== 'Super Admin') { header("Location: dashboard.php"); exit(); }

// Apotek yang dipilih (dari klik card apotek)
$selected_apotek_id = isset($_GET['apotek']) ? (int)$_GET['apotek'] : 0;
$selected_apotek    = $selected_apotek_id ? get_apotek($koneksi, $selected_apotek_id) : null;

// Sub-halaman untuk apotek terpilih
$sub = isset($_GET['sub']) ? $_GET['sub'] : '';

// CRUD Apotek
if (isset($_POST['tambah_apotek'])) {
    $f = ['nama_apotek','alamat','kota','provinsi','telp','wa_apotek','jam_buka'];
    $v = []; foreach ($f as $k) $v[$k] = mysqli_real_escape_string($koneksi, $_POST[$k] ?? '');
    $lat = (float)($_POST['lat'] ?? 0); $lng = (float)($_POST['lng'] ?? 0);
    mysqli_query($koneksi,
        "INSERT INTO apotek (nama_apotek,alamat,kota,provinsi,telp,wa_apotek,lat,lng,jam_buka)
         VALUES ('{$v['nama_apotek']}','{$v['alamat']}','{$v['kota']}','{$v['provinsi']}',
                 '{$v['telp']}','{$v['wa_apotek']}','$lat','$lng','{$v['jam_buka']}')"
    );
    catat_log($koneksi,'Tambah Apotek',"Apotek: {$v['nama_apotek']}");
    header("Location: super_admin_dashboard.php?msg=tambah"); exit();
}
if (isset($_GET['hapus_apotek'])) {
    $aid = (int)$_GET['hapus_apotek'];
    mysqli_query($koneksi,"DELETE FROM apotek WHERE id='$aid'");
    header("Location: super_admin_dashboard.php?msg=hapus"); exit();
}
if (isset($_GET['toggle'])) {
    $aid = (int)$_GET['toggle'];
    $cur = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT status FROM apotek WHERE id='$aid'"))['status'];
    $new = ($cur === 'aktif') ? 'nonaktif' : 'aktif';
    mysqli_query($koneksi,"UPDATE apotek SET status='$new' WHERE id='$aid'");
    header("Location: super_admin_dashboard.php"); exit();
}

// Update role user (dari sub=users)
if (isset($_POST['update_user'])) {
    $uid     = (int)$_POST['id'];
    $nr      = mysqli_real_escape_string($koneksi, $_POST['role']);
    $na      = (int)$_POST['id_apotek'];
    $ap_val  = $na > 0 ? "'$na'" : "NULL";
    mysqli_query($koneksi,"UPDATE users SET role='$nr',id_apotek=$ap_val WHERE id='$uid'");
    catat_log($koneksi,'SA Update User',"ID:$uid, Role:$nr");
    header("Location: super_admin_dashboard.php?apotek=$selected_apotek_id&sub=users&msg=ok"); exit();
}
if (isset($_GET['hapus_user'])) {
    $uid = (int)$_GET['hapus_user'];
    mysqli_query($koneksi,"DELETE FROM users WHERE id='$uid'");
    header("Location: super_admin_dashboard.php?apotek=$selected_apotek_id&sub=users"); exit();
}
if (isset($_GET['hapus_obat'])) {
    $oid = (int)$_GET['hapus_obat'];
    mysqli_query($koneksi,"DELETE FROM medicines WHERE id='$oid'");
    header("Location: super_admin_dashboard.php?apotek=$selected_apotek_id&sub=stok"); exit();
}
if (isset($_GET['hapus_racikan_sa'])) {
    $rid = (int)$_GET['hapus_racikan_sa'];
    mysqli_query($koneksi,"DELETE FROM racikan_detail WHERE id_racikan='$rid'");
    mysqli_query($koneksi,"DELETE FROM racikan WHERE id_racikan='$rid'");
    header("Location: super_admin_dashboard.php?apotek=$selected_apotek_id&sub=racikan"); exit();
}

// Stats global
$apotek_list   = mysqli_query($koneksi,"SELECT * FROM apotek ORDER BY nama_apotek ASC");
$total_apotek  = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM apotek"))['c'];
$total_users   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM users WHERE role NOT IN ('Pending','Super Admin')"))['c'];
$total_obat    = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines"))['c'];
$total_racikan = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM racikan"))['c'];

$msgs = ['tambah'=>'Apotek berhasil ditambahkan!','hapus'=>'Berhasil dihapus.','ok'=>'Berhasil diperbarui!'];
$pesan = isset($_GET['msg']) && isset($msgs[$_GET['msg']]) ? 'success:'.$msgs[$_GET['msg']] : '';

$provinsi_list = ['Aceh','Sumatera Utara','Sumatera Barat','Riau','Kepulauan Riau','Jambi','Bengkulu',
    'Sumatera Selatan','Kepulauan Bangka Belitung','Lampung','DKI Jakarta','Jawa Barat','Banten',
    'Jawa Tengah','DI Yogyakarta','Jawa Timur','Bali','Nusa Tenggara Barat','Nusa Tenggara Timur',
    'Kalimantan Barat','Kalimantan Tengah','Kalimantan Selatan','Kalimantan Timur','Kalimantan Utara',
    'Sulawesi Utara','Gorontalo','Sulawesi Tengah','Sulawesi Barat','Sulawesi Selatan','Sulawesi Tenggara',
    'Maluku','Maluku Utara','Papua Barat','Papua','Papua Selatan','Papua Tengah','Papua Pegunungan','Papua Barat Daya'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Super Admin - Pharma Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
.hero-bg{background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 50%,#0f172a 100%);}
.card-hover:hover{transform:translateY(-3px);box-shadow:0 20px 40px rgba(139,153,178,0.18);}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.4s ease forwards;}
.sub-nav-active{background:#2563eb;color:white;border-radius:.75rem;box-shadow:0 4px 14px rgba(37,99,235,.3);}
.sub-nav-item{color:#64748b;}
.sub-nav-item:hover{background:#f1f5f9;color:#2563eb;border-radius:.75rem;}
</style>
</head>
<body class="text-slate-800 flex min-h-screen">

<!-- SIDEBAR PUTIH STANDAR (sama dengan role lain) -->
<aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50 smooth-shadow">
    <div class="mb-10 w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
        <i class="fas fa-pills text-lg"></i>
    </div>
    <nav class="flex flex-col gap-1.5 w-full px-4 font-bold h-full text-[12px]">
        <!-- Dashboard -->
        <a href="super_admin_dashboard.php"
           class="flex items-center justify-center md:justify-start p-3 rounded-xl transition <?php echo (!$selected_apotek_id && !$sub)?'bg-blue-600 text-white shadow-xl shadow-blue-100':'text-slate-400 hover:bg-slate-50 hover:text-blue-600';?>">
            <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Dashboard</span>
        </a>
        <!-- Semua Apotek -->
        <a href="super_admin_dashboard.php#apotek-list"
           class="flex items-center justify-center md:justify-start p-3 rounded-xl transition <?php echo ($selected_apotek_id)?'text-blue-600 bg-blue-50':'text-slate-400 hover:bg-slate-50 hover:text-blue-600';?>">
            <i class="fas fa-clinic-medical w-5 text-center"></i><span class="hidden md:inline ml-3">Semua Apotek</span>
        </a>

        <!-- SUB-NAVBAR MUNCUL JIKA ADA APOTEK TERPILIH -->
        <?php if ($selected_apotek_id && $selected_apotek): ?>
        <div class="ml-2 md:ml-4 mt-1 mb-1 border-l-2 border-blue-100 pl-2 flex flex-col gap-1">
            <p class="hidden md:block text-[8px] font-black text-blue-400 uppercase tracking-widest px-2 py-1 truncate">
                📍 <?php echo htmlspecialchars($selected_apotek['nama_apotek']); ?>
            </p>
            <?php
            $sub_menus = [
                ['sub'=>'stok',    'icon'=>'fa-box',           'label'=>'Stok Obat'],
                ['sub'=>'racikan', 'icon'=>'fa-mortar-pestle', 'label'=>'Racikan Obat'],
                ['sub'=>'harga',   'icon'=>'fa-tags',          'label'=>'Harga Obat'],
                ['sub'=>'laporan', 'icon'=>'fa-file-alt',      'label'=>'Laporan'],
                ['sub'=>'users',   'icon'=>'fa-users-cog',     'label'=>'User Management'],
                ['sub'=>'log',     'icon'=>'fa-history',       'label'=>'Log Aktivitas'],
            ];
            foreach ($sub_menus as $sm):
                $active = ($sub === $sm['sub']);
            ?>
            <a href="super_admin_dashboard.php?apotek=<?php echo $selected_apotek_id;?>&sub=<?php echo $sm['sub'];?>"
               class="flex items-center justify-center md:justify-start p-2.5 rounded-xl font-bold text-[11px] transition <?php echo $active ? 'sub-nav-active' : 'sub-nav-item';?>">
                <i class="fas <?php echo $sm['icon'];?> w-4 text-center text-xs"></i>
                <span class="hidden md:inline ml-2"><?php echo $sm['label'];?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Log Global -->
        <a href="activity_log.php"
           class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
            <i class="fas fa-history w-5 text-center"></i><span class="hidden md:inline ml-3">Log Aktivitas</span>
        </a>
        <!-- Semua User -->
        <a href="super_admin_users.php"
           class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
            <i class="fas fa-users-cog w-5 text-center"></i><span class="hidden md:inline ml-3">Semua User</span>
        </a>
        <!-- Landing Page -->
        <a href="landing.php" target="_blank"
           class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
            <i class="fas fa-globe w-5 text-center"></i><span class="hidden md:inline ml-3">Landing Page</span>
        </a>

        <div class="mt-auto flex flex-col gap-1.5">
            <a href="profil.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-user w-5 text-center"></i><span class="hidden md:inline ml-3">Profil</span>
            </a>
            <a href="logout.php" class="flex items-center justify-center md:justify-start p-3 text-red-400 hover:bg-red-50 rounded-xl transition">
                <i class="fas fa-sign-out-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Keluar</span>
            </a>
        </div>
    </nav>
</aside>

<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

<!-- HEADER -->
<header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 fade-up">
    <div>
        <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">
            <?php echo $selected_apotek ? htmlspecialchars($selected_apotek['nama_apotek']) : 'System Control Center'; ?>
        </p>
        <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
            <?php if ($selected_apotek && $sub): ?>
                <?php $sub_labels=['stok'=>'Stok Obat','racikan'=>'Racikan','harga'=>'Harga Obat','laporan'=>'Laporan','users'=>'User Management','log'=>'Log Aktivitas'];?>
                <span class="text-blue-600"><?php echo $sub_labels[$sub]??ucfirst($sub);?></span>
            <?php elseif ($selected_apotek): ?>
                Detail <span class="text-blue-600">Apotek.</span>
            <?php else: ?>
                Super <span class="text-blue-600">Admin.</span>
            <?php endif; ?>
        </h1>
    </div>
    <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
        <div class="flex flex-col items-end px-3">
            <p class="text-[9px] text-blue-600 font-black uppercase tracking-widest">⭐ Super Admin</p>
            <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
        </div>
        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-black text-sm border-2 border-white shadow-inner">
            <?php echo strtoupper(substr($users,0,1)); ?>
        </div>
    </div>
</header>

<?php if ($pesan): [$t,$m] = explode(':',$pesan,2); ?>
<div class="mb-6 p-4 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-2xl text-xs font-bold flex items-center gap-2 fade-up">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($m); ?>
</div>
<?php endif; ?>

<?php
// ================================================================
// TAMPILAN: BREADCRUMB jika ada apotek terpilih
// ================================================================
if ($selected_apotek):
?>
<div class="flex items-center gap-2 mb-6 text-xs font-bold fade-up">
    <a href="super_admin_dashboard.php" class="text-blue-500 hover:text-blue-700 flex items-center gap-1">
        <i class="fas fa-home text-[10px]"></i> Dashboard
    </a>
    <i class="fas fa-chevron-right text-slate-300 text-[9px]"></i>
    <a href="super_admin_dashboard.php?apotek=<?php echo $selected_apotek_id;?>" class="text-blue-500 hover:text-blue-700">
        <?php echo htmlspecialchars($selected_apotek['nama_apotek']); ?>
    </a>
    <?php if ($sub): $sub_labels=['stok'=>'Stok Obat','racikan'=>'Racikan','harga'=>'Harga Obat','laporan'=>'Laporan','users'=>'User Management','log'=>'Log Aktivitas']; ?>
    <i class="fas fa-chevron-right text-slate-300 text-[9px]"></i>
    <span class="text-slate-500"><?php echo $sub_labels[$sub]??ucfirst($sub); ?></span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
// ================================================================
// KONTEN BERDASARKAN STATE
// ================================================================

// ─── STATE 1: DASHBOARD UTAMA (tidak ada apotek dipilih) ───────
if (!$selected_apotek_id):
?>
<!-- HERO BANNER -->
<div class="hero-bg relative overflow-hidden p-10 rounded-[3rem] text-white mb-8 smooth-shadow fade-up">
    <div class="relative z-10">
        <span class="bg-white/15 border border-white/20 text-[9px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-4 inline-block">
            👑 Super Admin Control
        </span>
        <h2 class="text-3xl font-black italic mb-2">Halo, <?php echo htmlspecialchars($users); ?>!</h2>
        <p class="text-blue-100 text-sm max-w-lg">Monitor dan kelola seluruh jaringan apotek Pharma Stock di seluruh Indonesia.</p>
    </div>
    <i class="fas fa-network-wired absolute -right-10 -bottom-10 text-[14rem] opacity-10 rotate-12"></i>
</div>

<!-- STATS GLOBAL -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-10 fade-up">
<?php foreach([
    ['label'=>'Total Apotek', 'val'=>$total_apotek,  'icon'=>'fa-clinic-medical','c'=>'blue'],
    ['label'=>'Total User',   'val'=>$total_users,   'icon'=>'fa-users',          'c'=>'emerald'],
    ['label'=>'Total Obat',   'val'=>$total_obat,    'icon'=>'fa-pills',          'c'=>'purple'],
    ['label'=>'Total Racikan','val'=>$total_racikan, 'icon'=>'fa-mortar-pestle',  'c'=>'amber'],
] as $s): ?>
<div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex flex-col items-center text-center card-hover">
    <div class="w-12 h-12 bg-<?php echo $s['c'];?>-50 text-<?php echo $s['c'];?>-600 rounded-2xl flex items-center justify-center mb-3">
        <i class="fas <?php echo $s['icon'];?> text-xl"></i>
    </div>
    <h3 class="text-3xl font-black text-slate-800"><?php echo $s['val']; ?></h3>
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1"><?php echo $s['label']; ?></p>
</div>
<?php endforeach; ?>
</div>

<!-- DAFTAR APOTEK -->
<div id="apotek-list" class="mb-6 flex items-center justify-between fade-up">
    <div>
        <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-1">Klik apotek untuk mengelola</p>
        <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Semua Apotek Terdaftar</h2>
    </div>
    <button onclick="document.getElementById('formTA').classList.toggle('hidden')"
        class="bg-blue-600 text-white px-5 py-2.5 rounded-full font-black text-[9px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center gap-2">
        <i class="fas fa-plus"></i> Tambah Apotek
    </button>
</div>

<!-- FORM TAMBAH APOTEK -->
<div id="formTA" class="hidden bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-8 mb-8 fade-up">
    <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest mb-6 flex items-center gap-2">
        <span class="w-7 h-7 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center"><i class="fas fa-plus text-xs"></i></span>
        Tambah Apotek Baru
    </h3>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php $fields=[
            ['n'=>'nama_apotek','l'=>'Nama Apotek *',    'p'=>'Apotek Sehat Farma', 'span'=>1],
            ['n'=>'alamat',     'l'=>'Alamat Lengkap *', 'p'=>'Jl. Pahlawan No.12', 'span'=>2],
            ['n'=>'kota',       'l'=>'Kota/Kabupaten *', 'p'=>'Jakarta Pusat',       'span'=>1],
            ['n'=>'telp',       'l'=>'Telepon',          'p'=>'021-5551234',         'span'=>1],
            ['n'=>'wa_apotek',  'l'=>'WA (628xxx)',      'p'=>'6281234567890',       'span'=>1],
            ['n'=>'lat',        'l'=>'Latitude (GPS)',   'p'=>'-6.2088',             'span'=>1,'t'=>'number'],
            ['n'=>'lng',        'l'=>'Longitude (GPS)',  'p'=>'106.8456',            'span'=>1,'t'=>'number'],
            ['n'=>'jam_buka',   'l'=>'Jam Buka',         'p'=>'08:00 - 21:00',       'span'=>1],
        ]; foreach($fields as $f):
            $span = ($f['span']??1)==2?'lg:col-span-2':'';
            $type = $f['t']??'text'; $step = $type==='number'?'step="0.0000001"':'';
        ?>
        <div class="<?php echo $span;?>">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block"><?php echo $f['l'];?></label>
            <input type="<?php echo $type;?>" name="<?php echo $f['n'];?>" <?php echo $step;?> placeholder="<?php echo $f['p'];?>"
                class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
        </div>
        <?php endforeach; ?>
        <div>
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Provinsi *</label>
            <select name="provinsi" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
                <?php foreach($provinsi_list as $p): ?><option><?php echo $p;?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="lg:col-span-3">
            <button name="tambah_apotek" type="submit"
                class="bg-blue-600 text-white px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100 active:scale-95">
                <i class="fas fa-plus-circle mr-2"></i> Simpan Apotek
            </button>
        </div>
    </form>
</div>

<!-- GRID APOTEK — KLIK UNTUK MASUK KE DETAIL -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 fade-up">
<?php
mysqli_data_seek($apotek_list,0);
while ($ap = mysqli_fetch_assoc($apotek_list)):
    $staff      = mysqli_query($koneksi,"SELECT username,role FROM users WHERE id_apotek='{$ap['id']}' AND role IN ('Admin','Manager Gudang','Apoteker','Kasir') ORDER BY role ASC");
    $jml_obat   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE id_apotek='{$ap['id']}'"))['c'];
    $stok_habis = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE id_apotek='{$ap['id']}' AND jumlah<=0"))['c'];
    $jml_user   = mysqli_num_rows($staff);
?>
<div class="bg-white rounded-[2rem] smooth-shadow border-2 border-slate-50 hover:border-blue-400 p-6 card-hover cursor-pointer group"
     onclick="window.location='super_admin_dashboard.php?apotek=<?php echo $ap['id'];?>'">
    <div class="flex items-start justify-between mb-4">
        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
            <i class="fas fa-clinic-medical text-xl"></i>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 text-[9px] font-black uppercase rounded-full <?php echo $ap['status']==='aktif'?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-400';?>">
                <?php echo $ap['status']==='aktif'?'● Aktif':'○ Nonaktif';?>
            </span>
        </div>
    </div>
    <h3 class="font-black text-slate-900 text-sm uppercase italic mb-1 group-hover:text-blue-600"><?php echo htmlspecialchars($ap['nama_apotek']);?></h3>
    <p class="text-[10px] text-slate-400 font-bold mb-1"><i class="fas fa-map-marker-alt text-rose-400 mr-1"></i><?php echo htmlspecialchars($ap['kota'].', '.$ap['provinsi']);?></p>
    <p class="text-[10px] text-slate-400 font-bold mb-4"><i class="fas fa-clock text-blue-300 mr-1"></i><?php echo htmlspecialchars($ap['jam_buka']);?></p>
    <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="bg-blue-50 rounded-xl p-2 text-center"><p class="text-base font-black text-blue-600"><?php echo $jml_obat;?></p><p class="text-[8px] font-black text-slate-400 uppercase">Obat</p></div>
        <div class="bg-rose-50 rounded-xl p-2 text-center"><p class="text-base font-black text-rose-500"><?php echo $stok_habis;?></p><p class="text-[8px] font-black text-slate-400 uppercase">Habis</p></div>
        <div class="bg-emerald-50 rounded-xl p-2 text-center"><p class="text-base font-black text-emerald-600"><?php echo $jml_user;?></p><p class="text-[8px] font-black text-slate-400 uppercase">Staff</p></div>
    </div>
    <div class="flex items-center justify-between pt-3 border-t border-slate-50">
        <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest flex items-center gap-1">
            Kelola Apotek <i class="fas fa-chevron-right text-[8px]"></i>
        </span>
        <div class="flex gap-2" onclick="event.stopPropagation()">
            <a href="super_admin_dashboard.php?toggle=<?php echo $ap['id'];?>"
               class="w-7 h-7 flex items-center justify-center bg-slate-50 text-slate-400 hover:bg-amber-400 hover:text-white rounded-lg transition text-[10px]" title="Toggle Status">
                <i class="fas fa-power-off"></i>
            </a>
            <a href="super_admin_dashboard.php?hapus_apotek=<?php echo $ap['id'];?>"
               onclick="return confirm('Hapus apotek <?php echo addslashes($ap['nama_apotek']);?>?')"
               class="w-7 h-7 flex items-center justify-center bg-slate-50 text-rose-400 hover:bg-rose-500 hover:text-white rounded-lg transition text-[10px]" title="Hapus">
                <i class="fas fa-trash"></i>
            </a>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>

<?php
// ─── STATE 2: DETAIL APOTEK (belum pilih sub) ──────────────────
elseif ($selected_apotek_id && !$sub):
    $staff_list = mysqli_query($koneksi,"SELECT u.*,a.nama_apotek FROM users u LEFT JOIN apotek a ON u.id_apotek=a.id WHERE u.id_apotek='$selected_apotek_id' ORDER BY u.role ASC");
    $jml_obat   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE id_apotek='$selected_apotek_id'"))['c'];
    $stok_h     = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE id_apotek='$selected_apotek_id' AND jumlah<=0"))['c'];
    $jml_r      = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM racikan WHERE id_apotek='$selected_apotek_id'"))['c'];
?>
<!-- INFO APOTEK -->
<div class="hero-bg relative overflow-hidden p-10 rounded-[3rem] text-white mb-8 smooth-shadow fade-up">
    <div class="flex items-center gap-6 relative z-10">
        <div class="w-20 h-20 bg-white/15 backdrop-blur border-2 border-white/20 rounded-3xl flex items-center justify-center shrink-0">
            <i class="fas fa-clinic-medical text-4xl text-white"></i>
        </div>
        <div>
            <h2 class="text-2xl font-black italic mb-1"><?php echo htmlspecialchars($selected_apotek['nama_apotek']);?></h2>
            <p class="text-blue-100 text-xs"><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($selected_apotek['alamat'].', '.$selected_apotek['kota'].', '.$selected_apotek['provinsi']);?></p>
            <p class="text-blue-100 text-xs mt-1"><i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars($selected_apotek['jam_buka']);?></p>
            <?php if($selected_apotek['wa_apotek']): ?>
            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $selected_apotek['wa_apotek']);?>" target="_blank"
               class="inline-flex items-center gap-2 mt-3 bg-white/20 hover:bg-white/30 text-white text-[10px] font-black px-4 py-2 rounded-full uppercase tracking-widest transition">
                <i class="fab fa-whatsapp"></i> Hubungi WA
            </a>
            <?php endif; ?>
        </div>
    </div>
    <i class="fas fa-pills absolute -right-10 -bottom-10 text-[14rem] opacity-10"></i>
</div>

<!-- STATS APOTEK -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 fade-up">
<?php foreach([
    ['v'=>$jml_obat,'l'=>'Jenis Obat',  'i'=>'fa-pills',          'c'=>'blue'],
    ['v'=>$stok_h,  'l'=>'Stok Habis',  'i'=>'fa-exclamation-circle','c'=>'rose'],
    ['v'=>$jml_r,   'l'=>'Racikan',     'i'=>'fa-mortar-pestle',  'c'=>'purple'],
    ['v'=>mysqli_num_rows($staff_list),'l'=>'Staff','i'=>'fa-users','c'=>'emerald'],
] as $s):?>
<div class="bg-white p-5 rounded-2xl smooth-shadow border border-slate-50 text-center">
    <div class="w-10 h-10 bg-<?php echo $s['c'];?>-50 text-<?php echo $s['c'];?>-600 rounded-xl flex items-center justify-center mx-auto mb-2">
        <i class="fas <?php echo $s['i'];?>"></i>
    </div>
    <h4 class="text-2xl font-black text-slate-800"><?php echo $s['v'];?></h4>
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-0.5"><?php echo $s['l'];?></p>
</div>
<?php endforeach;?>
</div>

<!-- AKSES CEPAT KE SUB-HALAMAN -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 fade-up">
<?php $nav_sub=[
    ['sub'=>'stok',    'icon'=>'fa-box',           'label'=>'Stok Obat',       'color'=>'blue'],
    ['sub'=>'racikan', 'icon'=>'fa-mortar-pestle', 'label'=>'Racikan Obat',    'color'=>'purple'],
    ['sub'=>'harga',   'icon'=>'fa-tags',          'label'=>'Harga Obat',      'color'=>'orange'],
    ['sub'=>'laporan', 'icon'=>'fa-file-alt',      'label'=>'Laporan',         'color'=>'emerald'],
    ['sub'=>'users',   'icon'=>'fa-users-cog',     'label'=>'User Management', 'color'=>'indigo'],
    ['sub'=>'log',     'icon'=>'fa-history',       'label'=>'Log Aktivitas',   'color'=>'slate'],
];
foreach ($nav_sub as $n):?>
<a href="super_admin_dashboard.php?apotek=<?php echo $selected_apotek_id;?>&sub=<?php echo $n['sub'];?>"
   class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 hover:border-<?php echo $n['color'];?>-400 group card-hover flex items-center gap-4">
    <div class="w-12 h-12 bg-<?php echo $n['color'];?>-50 text-<?php echo $n['color'];?>-600 rounded-2xl flex items-center justify-center group-hover:bg-<?php echo $n['color'];?>-600 group-hover:text-white transition">
        <i class="fas <?php echo $n['icon'];?> text-xl"></i>
    </div>
    <div>
        <h5 class="font-black text-slate-800 text-xs uppercase"><?php echo $n['label'];?></h5>
        <i class="fas fa-chevron-right text-slate-200 group-hover:text-<?php echo $n['color'];?>-400 text-[10px]"></i>
    </div>
</a>
<?php endforeach;?>
</div>

<?php
// ─── STATE 3: SUB = STOK ─────────────────────────────────────
elseif ($sub === 'stok'):
    $obat_list = mysqli_query($koneksi,
        "SELECT m.*, IFNULL(h.harga_jual,0) harga_jual FROM medicines m
         LEFT JOIN harga_obat h ON h.id_obat=m.id AND h.id_apotek=m.id_apotek
         WHERE m.id_apotek='$selected_apotek_id' ORDER BY m.nama_obat ASC");
?>
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/60 border-b border-slate-100">
                <tr>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Kategori</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Jual</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Expired</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php if(mysqli_num_rows($obat_list)>0): while($o=mysqli_fetch_assoc($obat_list)):
                if($o['jumlah']<=0){$badge='bg-rose-50 text-rose-600';$lbl='Habis';}
                elseif($o['jumlah']<=15){$badge='bg-amber-50 text-amber-600';$lbl='Menipis';}
                else{$badge='bg-emerald-50 text-emerald-600';$lbl='Aman';}
                $hf=$o['harga_jual']>0?'Rp '.number_format($o['harga_jual'],0,',','.'):'—';
                $ef=!empty($o['expired_date'])?date('d M Y',strtotime($o['expired_date'])):'—';
            ?>
            <tr class="hover:bg-blue-50/20 transition-colors">
                <td class="p-5 font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($o['nama_obat']);?></td>
                <td class="p-5 text-center"><span class="px-2 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase"><?php echo htmlspecialchars($o['kategori']);?></span></td>
                <td class="p-5 text-center font-bold text-slate-700 text-xs"><?php echo $hf;?></td>
                <td class="p-5 text-center font-black text-slate-800 text-lg"><?php echo $o['jumlah'];?></td>
                <td class="p-5 text-center text-[10px] font-bold text-slate-500"><?php echo $ef;?></td>
                <td class="p-5 text-center"><span class="px-2 py-1 <?php echo $badge;?> text-[9px] font-black rounded-full uppercase border"><?php echo $lbl;?></span></td>
                <td class="p-5 text-center">
                    <a href="super_admin_dashboard.php?apotek=<?php echo $selected_apotek_id;?>&sub=stok&hapus_obat=<?php echo $o['id'];?>"
                       onclick="return confirm('Hapus obat ini?')"
                       class="w-7 h-7 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-lg flex items-center justify-center mx-auto transition">
                        <i class="fas fa-trash text-[10px]"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; else:?>
            <tr><td colspan="7" class="p-16 text-center text-slate-400 font-bold italic text-[10px] uppercase">Belum ada data stok.</td></tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</div>

<?php
// ─── STATE 4: SUB = RACIKAN ───────────────────────────────────
elseif ($sub === 'racikan'):
    $racikan_list = mysqli_query($koneksi,"SELECT * FROM racikan WHERE id_apotek='$selected_apotek_id' ORDER BY id_racikan DESC");
?>
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/60 border-b border-slate-100">
                <tr>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Racikan</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Komposisi</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Tipe</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Hapus</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php if(mysqli_num_rows($racikan_list)>0): while($r=mysqli_fetch_assoc($racikan_list)):
                $qb=mysqli_query($koneksi,"SELECT m.nama_obat,rd.jumlah_digunakan FROM racikan_detail rd JOIN medicines m ON rd.id_obat=m.id WHERE rd.id_racikan='{$r['id_racikan']}'");
                $bl=[];while($b=mysqli_fetch_assoc($qb))$bl[]=$b['nama_obat'].'('.$b['jumlah_digunakan'].')';
            ?>
            <tr class="hover:bg-purple-50/20 transition-colors">
                <td class="p-5">
                    <div class="font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($r['nama_racikan']);?></div>
                    <div class="text-[9px] text-purple-500 font-bold"><?php echo htmlspecialchars($r['kode_racikan']);?></div>
                </td>
                <td class="p-5 text-[10px] font-bold text-slate-600"><?php echo implode(', ',$bl)?:'—';?></td>
                <td class="p-5 text-center font-black text-slate-800"><?php echo $r['stok_racikan'];?></td>
                <td class="p-5 text-center"><span class="px-2 py-1 bg-purple-50 text-purple-600 text-[9px] font-black rounded-full uppercase"><?php echo htmlspecialchars($r['tipe_racikan']);?></span></td>
                <td class="p-5 text-center">
                    <a href="super_admin_dashboard.php?apotek=<?php echo $selected_apotek_id;?>&sub=racikan&hapus_racikan_sa=<?php echo $r['id_racikan'];?>"
                       onclick="return confirm('Hapus racikan ini?')"
                       class="w-7 h-7 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-lg flex items-center justify-center mx-auto transition">
                        <i class="fas fa-trash text-[10px]"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; else:?>
            <tr><td colspan="5" class="p-16 text-center text-slate-400 font-bold italic text-[10px] uppercase">Belum ada racikan.</td></tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</div>

<?php
// ─── STATE 5: SUB = HARGA ─────────────────────────────────────
elseif ($sub === 'harga'):
    $harga_list = mysqli_query($koneksi,"SELECT h.*,m.nama_obat,m.kategori FROM harga_obat h JOIN medicines m ON h.id_obat=m.id WHERE h.id_apotek='$selected_apotek_id' ORDER BY m.nama_obat ASC");
?>
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/60 border-b border-slate-100">
                <tr>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Beli</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Jual</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Margin</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Satuan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php if(mysqli_num_rows($harga_list)>0): while($h=mysqli_fetch_assoc($harga_list)):
                $mg=$h['harga_jual']-$h['harga_beli'];$pct=$h['harga_beli']>0?round($mg/$h['harga_beli']*100,1):0;
            ?>
            <tr class="hover:bg-orange-50/20 transition-colors">
                <td class="p-5 font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($h['nama_obat']);?></td>
                <td class="p-5 text-center font-bold text-slate-600 text-xs">Rp <?php echo number_format($h['harga_beli'],0,',','.');?></td>
                <td class="p-5 text-center font-black text-slate-800 text-sm">Rp <?php echo number_format($h['harga_jual'],0,',','.');?></td>
                <td class="p-5 text-center font-black text-xs <?php echo $mg>=0?'text-emerald-600':'text-rose-600';?>">
                    <?php echo ($mg>=0?'+':'').number_format($mg,0,',','.');?> <span class="text-[9px] opacity-70">(<?php echo $pct;?>%)</span>
                </td>
                <td class="p-5 text-center text-[10px] font-bold text-slate-500 uppercase"><?php echo htmlspecialchars($h['satuan']);?></td>
            </tr>
            <?php endwhile; else:?>
            <tr><td colspan="5" class="p-16 text-center text-slate-400 font-bold italic text-[10px] uppercase">Belum ada data harga.</td></tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</div>

<?php
// ─── STATE 6: SUB = LAPORAN ───────────────────────────────────
elseif ($sub === 'laporan'):
    $lap_data = mysqli_query($koneksi,"SELECT m.*,IFNULL(h.harga_jual,0) harga_jual FROM medicines m LEFT JOIN harga_obat h ON h.id_obat=m.id AND h.id_apotek=m.id_apotek WHERE m.id_apotek='$selected_apotek_id' ORDER BY m.nama_obat ASC");
?>
<div class="flex justify-end mb-4 fade-up">
    <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-2.5 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition flex items-center gap-2 shadow-lg">
        <i class="fas fa-print"></i> Cetak
    </button>
</div>
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/60 border-b border-slate-100">
                <tr>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Supplier</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Jual</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php while($r=mysqli_fetch_assoc($lap_data)):
                if($r['jumlah']<=0){$b='bg-rose-50 text-rose-600';$l='Habis';}
                elseif($r['jumlah']<=15){$b='bg-amber-50 text-amber-600';$l='Menipis';}
                else{$b='bg-emerald-50 text-emerald-600';$l='Aman';}
            ?>
            <tr class="hover:bg-slate-50/50">
                <td class="p-5 font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($r['nama_obat']);?></td>
                <td class="p-5 text-[10px] font-bold text-slate-500"><?php echo htmlspecialchars($r['supplier']?:'—');?></td>
                <td class="p-5 text-center font-black text-xs"><?php echo $r['harga_jual']>0?'Rp '.number_format($r['harga_jual'],0,',','.'):'—';?></td>
                <td class="p-5 text-center font-black text-slate-800"><?php echo $r['jumlah'];?></td>
                <td class="p-5 text-center"><span class="px-2 py-1 <?php echo $b;?> text-[9px] font-black rounded-full uppercase border"><?php echo $l;?></span></td>
            </tr>
            <?php endwhile;?>
            </tbody>
        </table>
    </div>
</div>

<?php
// ─── STATE 7: SUB = USERS ─────────────────────────────────────
elseif ($sub === 'users'):
    $user_list  = mysqli_query($koneksi,"SELECT * FROM users WHERE id_apotek='$selected_apotek_id' AND role!='Super Admin' ORDER BY role ASC");
    $apotek_all = mysqli_query($koneksi,"SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");
?>
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/60 border-b border-slate-100">
                <tr>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">User</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Role</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Ubah Role & Apotek</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Hapus</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php if(mysqli_num_rows($user_list)>0): while($u=mysqli_fetch_assoc($user_list)):
                $rc=match($u['role']){'Admin'=>'bg-rose-50 text-rose-600','Manager Gudang'=>'bg-amber-50 text-amber-700','Apoteker'=>'bg-purple-50 text-purple-600','Kasir'=>'bg-emerald-50 text-emerald-700','Pending'=>'bg-orange-50 text-orange-600',default=>'bg-slate-100 text-slate-500'};
            ?>
            <tr class="hover:bg-blue-50/20 transition-colors">
                <td class="p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center font-black text-blue-600"><?php echo strtoupper(substr($u['username'],0,1));?></div>
                        <div>
                            <p class="font-black text-slate-800 text-sm"><?php echo htmlspecialchars($u['username']);?></p>
                            <p class="text-[8px] text-slate-300 font-bold uppercase">ID #<?php echo $u['id'];?></p>
                        </div>
                    </div>
                </td>
                <td class="p-5 text-center"><span class="px-3 py-1 <?php echo $rc;?> text-[9px] font-black rounded-xl uppercase"><?php echo htmlspecialchars($u['role']);?></span></td>
                <td class="p-5">
                    <form method="POST" class="flex items-center gap-2 flex-wrap">
                        <input type="hidden" name="id" value="<?php echo $u['id'];?>">
                        <select name="id_apotek" class="p-2 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-bold outline-none focus:ring-2 focus:ring-blue-400 min-w-[140px]">
                            <option value="0">— Tanpa Apotek —</option>
                            <?php mysqli_data_seek($apotek_all,0); while($ap=mysqli_fetch_assoc($apotek_all)):?>
                            <option value="<?php echo $ap['id'];?>" <?php echo($u['id_apotek']==$ap['id'])?'selected':'';?>><?php echo htmlspecialchars($ap['nama_apotek']);?></option>
                            <?php endwhile;?>
                        </select>
                        <select name="role" class="p-2 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-bold outline-none focus:ring-2 focus:ring-blue-400 min-w-[120px]">
                            <?php foreach(['Pending','Kasir','Apoteker','Manager Gudang','Admin'] as $r):?>
                            <option value="<?php echo $r;?>" <?php echo($u['role']===$r)?'selected':'';?>><?php echo $r;?></option>
                            <?php endforeach;?>
                        </select>
                        <button name="update_user" class="bg-slate-900 text-white px-3 py-2 rounded-xl text-[9px] font-black hover:bg-blue-600 transition uppercase tracking-widest">Simpan</button>
                    </form>
                </td>
                <td class="p-5 text-center">
                    <a href="super_admin_dashboard.php?apotek=<?php echo $selected_apotek_id;?>&sub=users&hapus_user=<?php echo $u['id'];?>"
                       onclick="return confirm('Hapus user ini?')"
                       class="w-8 h-8 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl flex items-center justify-center mx-auto transition">
                        <i class="fas fa-trash text-[10px]"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; else:?>
            <tr><td colspan="4" class="p-16 text-center text-slate-400 font-bold italic text-[10px] uppercase">Belum ada user di apotek ini.</td></tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</div>

<?php
// ─── STATE 8: SUB = LOG ───────────────────────────────────────
elseif ($sub === 'log'):
    $log_list = mysqli_query($koneksi,"SELECT * FROM activity_log WHERE id_apotek='$selected_apotek_id' ORDER BY created_at DESC LIMIT 200");
    $aksi_warna=['login'=>'bg-emerald-50 text-emerald-700','logout'=>'bg-slate-100 text-slate-600','hapus'=>'bg-rose-50 text-rose-600','tambah'=>'bg-blue-50 text-blue-600','update'=>'bg-amber-50 text-amber-700','edit'=>'bg-amber-50 text-amber-700','buka'=>'bg-indigo-50 text-indigo-600'];
?>
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/60 border-b border-slate-100">
                <tr>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">User</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Aksi</th>
                    <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php if(mysqli_num_rows($log_list)>0): while($lg=mysqli_fetch_assoc($log_list)):
                $al=strtolower($lg['aksi']);$w='bg-slate-100 text-slate-600';
                foreach($aksi_warna as $k=>$v){if(str_contains($al,$k)){$w=$v;break;}}
            ?>
            <tr class="hover:bg-blue-50/20 transition-colors">
                <td class="p-4 whitespace-nowrap">
                    <div class="text-[10px] font-black text-slate-700"><?php echo date('d M Y',strtotime($lg['created_at']));?></div>
                    <div class="text-[9px] text-slate-400 font-bold"><?php echo date('H:i:s',strtotime($lg['created_at']));?></div>
                </td>
                <td class="p-4">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-black text-[10px]"><?php echo strtoupper(substr($lg['username'],0,1));?></div>
                        <div>
                            <p class="font-black text-slate-800 text-xs"><?php echo htmlspecialchars($lg['username']);?></p>
                            <p class="text-[9px] text-slate-400"><?php echo htmlspecialchars($lg['role']);?></p>
                        </div>
                    </div>
                </td>
                <td class="p-4"><span class="px-3 py-1 <?php echo $w;?> text-[9px] font-black rounded-full uppercase"><?php echo htmlspecialchars($lg['aksi']);?></span></td>
                <td class="p-4 text-[10px] text-slate-500 font-medium max-w-xs truncate"><?php echo htmlspecialchars($lg['detail']?:'—');?></td>
            </tr>
            <?php endwhile; else:?>
            <tr><td colspan="4" class="p-16 text-center text-slate-400 font-bold italic text-[10px] uppercase">Belum ada log untuk apotek ini.</td></tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<footer class="mt-16 pb-6 text-center">
    <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
    <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Super Admin Control</p>
</footer>
</main>
</body>
</html>