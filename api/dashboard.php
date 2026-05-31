<?php
session_start();
include_once 'koneksi.php';
include_once 'autentikasi.php';
include_once 'log_aktivitas.php';

if (!isset($role_saat_ini)) { header("Location: login.php"); exit(); }
if ($role_saat_ini === 'Pending')     { header("Location: pending.php"); exit(); }
if ($role_saat_ini === 'Kasir')       { header("Location: kasir_dashboard.php"); exit(); }
if ($role_saat_ini === 'Super Admin') { header("Location: super_admin_dashboard.php"); exit(); }

$users     = $_COOKIE['users'] ?? 'Guest';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

$ap_where  = $id_apotek ? "WHERE id_apotek='$id_apotek'" : "";
$stok_aman    = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines $ap_where" . ($ap_where ? " AND" : "WHERE") . " jumlah>15"))['c'];
$stok_menipis = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines $ap_where" . ($ap_where ? " AND" : "WHERE") . " jumlah>0 AND jumlah<=15"))['c'];
$stok_habis   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines $ap_where" . ($ap_where ? " AND" : "WHERE") . " jumlah<=0"))['c'];

$role_icon_map = [
    'Admin'          => ['fa-shield-alt',    'blue'],
    'Manager Gudang' => ['fa-warehouse',     'emerald'],
    'Apoteker'       => ['fa-mortar-pestle', 'purple'],
    'Kasir'          => ['fa-cash-register', 'orange'],
];
$ri = $role_icon_map[$role] ?? ['fa-user','slate'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
        *{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
        .smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
        .hero-bg{background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 50%,#0f172a 100%);}
        .card-hover:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(29,78,216,0.15);}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeUp 0.5s ease forwards;}
        .fade-up-1{animation-delay:0.1s;opacity:0;}
        .fade-up-2{animation-delay:0.2s;opacity:0;}
        .fade-up-3{animation-delay:0.3s;opacity:0;}

        aside ~ aside {
            display: none !important;
        }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

<?php include_once 'sidebar.php'; ?>

<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

    <header class="flex justify-between items-center mb-10 fade-up fade-up-1">
        <div>
            <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">
                <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Pharma Stock'); ?>
            </p>
            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none">
                Ringkasan <span class="text-blue-600 italic">Farmasi.</span>
            </h1>
        </div>
        <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow">
            <div class="flex flex-col items-end px-3">
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
                <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-black text-sm border-2 border-white shadow-inner">
                <?php echo strtoupper(substr($users,0,1)); ?>
            </div>
        </div>
    </header>

    <div class="hero-bg relative overflow-hidden p-10 md:p-12 rounded-[3rem] text-white mb-10 smooth-shadow fade-up fade-up-1">
        <div class="relative z-10">
            <span class="bg-white/20 border border-white/20 text-[9px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-4 inline-block backdrop-blur">
                ✅ Sistem Aktif
            </span>
            <h2 class="text-3xl md:text-4xl font-black italic mb-3 tracking-tight">Halo, <?php echo htmlspecialchars($users); ?>! 👋</h2>
            <p class="text-blue-100 text-sm font-medium max-w-lg opacity-90">Kelola stok obat dengan presisi tinggi dan analisis data real-time.</p>
        </div>
        <i class="fas fa-laptop-medical absolute -right-10 -bottom-10 text-[15rem] opacity-10 rotate-12"></i>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 fade-up fade-up-2">
        <?php
        $stats = [
            ['val'=>$stok_aman,    'label'=>'Stok Aman',   'icon'=>'fa-check-circle',        'bg_cls'=>'bg-emerald-50', 'text_cls'=>'text-emerald-500', 'h_cls'=>'group-hover:bg-emerald-500'],
            ['val'=>$stok_menipis, 'label'=>'Stok Menipis','icon'=>'fa-hourglass-half',     'bg_cls'=>'bg-amber-50',   'text_cls'=>'text-amber-500',   'h_cls'=>'group-hover:bg-amber-500'],
            ['val'=>$stok_habis,   'label'=>'Out of Stock', 'icon'=>'fa-exclamation-triangle','bg_cls'=>'bg-rose-50',    'text_cls'=>'text-rose-500',    'h_cls'=>'group-hover:bg-rose-500'],
        ];
        foreach($stats as $s): ?>
        <div class="card-hover bg-white p-8 rounded-[2.5rem] smooth-shadow border border-slate-50 flex flex-col items-center text-center group">
            <div class="w-16 h-16 <?php echo $s['bg_cls']; ?> <?php echo $s['text_cls']; ?> rounded-2xl flex items-center justify-center mb-4 <?php echo $s['h_cls']; ?> group-hover:text-white transition-all duration-200">
                <i class="fas <?php echo $s['icon']; ?> text-2xl"></i>
            </div>
            <h4 class="text-4xl font-black text-slate-800 mb-1"><?php echo $s['val']; ?></h4>
            <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest"><?php echo $s['label']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 fade-up fade-up-3">
        <?php
        $menu = [
            ['href'=>'stok_obat.php',    'icon'=>'fa-box-open',      'label'=>'Inventaris',    'sub'=>'Manajemen data obat.',           'color'=>'blue',   'roles'=>['Admin','Manager Gudang','Apoteker']],
            ['href'=>'analisis.php',     'icon'=>'fa-chart-line',    'label'=>'Analitik',      'sub'=>'Statistik data BPS.',            'color'=>'indigo', 'roles'=>['Admin','Manager Gudang','Apoteker']],
            ['href'=>'racikan_obat.php', 'icon'=>'fa-mortar-pestle', 'label'=>'Racikan Obat',  'sub'=>'Buat & kelola racikan.',         'color'=>'purple', 'roles'=>['Admin','Apoteker']],
            ['href'=>'harga_obat.php',   'icon'=>'fa-tags',          'label'=>'Harga Obat',    'sub'=>'Kelola harga beli & jual.',      'color'=>'orange', 'roles'=>['Admin','Manager Gudang']],
            ['href'=>'laporan.php',      'icon'=>'fa-file-alt',      'label'=>'Laporan',       'sub'=>'Rekap & cetak laporan.',         'color'=>'emerald','roles'=>['Admin','Manager Gudang']],
            ['href'=>'admin_users.php',  'icon'=>'fa-users-cog',     'label'=>'User Management','sub'=>'Kelola akun pengguna.',         'color'=>'slate',  'roles'=>['Admin']],
            ['href'=>'activity_log.php', 'icon'=>'fa-history',       'label'=>'Log Aktivitas', 'sub'=>'Audit trail semua kegiatan.',    'color'=>'rose',   'roles'=>['Admin']],
        ];
        foreach($menu as $m):
            if(!in_array($role, $m['roles'])) continue;
        ?>
        <a href="<?php echo $m['href']; ?>"
           class="flex items-center justify-between bg-white p-7 rounded-[2rem] smooth-shadow border border-slate-50 hover:border-<?php echo $m['color']; ?>-400 group card-hover">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-<?php echo $m['color']; ?>-50 text-<?php echo $m['color']; ?>-600 rounded-2xl flex items-center justify-center group-hover:bg-<?php echo $m['color']; ?>-600 group-hover:text-white">
                    <i class="fas <?php echo $m['icon']; ?> text-xl"></i>
                </div>
                <div>
                    <h5 class="font-black text-slate-800 uppercase text-xs tracking-wide"><?php echo $m['label']; ?></h5>
                    <p class="text-[10px] text-slate-400 font-medium"><?php echo $m['sub']; ?></p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-200 group-hover:text-<?php echo $m['color']; ?>-500 group-hover:translate-x-1"></i>
        </a>
        <?php endforeach; ?>
    </div>

    <footer class="mt-16 pb-6 text-center">
        <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
        <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">&copy; 2026 Pharma Stock</p>
    </footer>
</main>
</body>
</html>