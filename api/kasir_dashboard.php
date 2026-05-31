<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

if (!isset($role_saat_ini) || $role_saat_ini !== 'Kasir') {
    header("Location: dashboard.php"); exit();
}
$users     = $_COOKIE['users'];
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

$aw = $id_apotek ? "WHERE id_apotek='$id_apotek'" : "";
$and = $id_apotek ? " AND " : " WHERE ";
$stok_aman    = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines $aw{$and}jumlah>15"))['c'];
$stok_menipis = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines $aw{$and}jumlah>0 AND jumlah<=15"))['c'];
$stok_habis   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines $aw{$and}jumlah<=0"))['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir Dashboard - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
        *{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
        .smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
        .hero-bg{background:linear-gradient(135deg,#ea580c 0%,#dc2626 50%,#0f172a 100%);}
        .card-hover:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(29,78,216,0.15);}
        @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeUp 0.5s ease forwards;}
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

    <!-- HEADER -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 fade-up">
        <div>
            <p class="text-orange-500 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">
                <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Pharma Stock'); ?>
            </p>
            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
                Kasir <span class="text-orange-500">Panel.</span>
            </h1>
        </div>
        <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
            <div class="flex flex-col items-end px-3">
                <p class="text-[9px] text-orange-500 font-bold uppercase tracking-widest">Kasir</p>
                <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
            </div>
            <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white font-black text-sm border-2 border-white shadow-inner">
                <?php echo strtoupper(substr($users,0,1)); ?>
            </div>
        </div>
    </header>

    <!-- HERO -->
    <div class="hero-bg relative overflow-hidden p-10 rounded-[3rem] text-white mb-10 smooth-shadow fade-up">
        <div class="relative z-10">
            <span class="bg-white/20 backdrop-blur border border-white/20 text-[9px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-4 inline-block">
                🏪 Kasir On-Duty
            </span>
            <h2 class="text-3xl md:text-4xl font-black italic mb-3 tracking-tight">
                Halo, <?php echo htmlspecialchars($users); ?>! 👋
            </h2>
            <p class="text-orange-100 font-medium max-w-xl opacity-90 text-sm leading-relaxed">
                Pantau ketersediaan stok obat dan harga secara real-time sebelum memberikan informasi kepada pasien.
            </p>
        </div>
        <i class="fas fa-cash-register absolute -right-10 -bottom-10 text-[15rem] opacity-10 rotate-12"></i>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 fade-up">
        <?php
        $stats = [
            ['val'=>$stok_aman,    'label'=>'Stok Aman',   'icon'=>'fa-check-double',       'c'=>'emerald'],
            ['val'=>$stok_menipis, 'label'=>'Stok Menipis','icon'=>'fa-exclamation-triangle','c'=>'amber'],
            ['val'=>$stok_habis,   'label'=>'Obat Kosong', 'icon'=>'fa-times-circle',        'c'=>'rose'],
        ];
        foreach($stats as $s): ?>
        <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex flex-col items-center text-center group hover:bg-<?php echo $s['c']; ?>-500 card-hover">
            <div class="w-12 h-12 bg-<?php echo $s['c']; ?>-50 text-<?php echo $s['c']; ?>-600 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-white/20 group-hover:text-white">
                <i class="fas <?php echo $s['icon']; ?> text-lg"></i>
            </div>
            <h4 class="text-3xl font-black text-slate-800 mb-1 group-hover:text-white"><?php echo $s['val']; ?></h4>
            <p class="text-slate-400 font-bold text-[9px] uppercase tracking-widest group-hover:text-white/80"><?php echo $s['label']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- QUICK ACCESS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 fade-up">
        <?php
        $menu = [
            ['href'=>'stok_obat.php',  'icon'=>'fa-search',    'label'=>'Cek Inventaris','sub'=>'Cari ketersediaan obat & rak', 'color'=>'blue'],
            ['href'=>'harga_obat.php', 'icon'=>'fa-tags',      'label'=>'Harga Obat',    'sub'=>'Lihat daftar harga obat',     'color'=>'orange'],
            ['href'=>'analisis.php',   'icon'=>'fa-chart-line','label'=>'Analisis Data', 'sub'=>'Lihat tren kesehatan BPS',    'color'=>'indigo'],
        ];
        foreach($menu as $m): ?>
        <a href="<?php echo $m['href']; ?>"
           class="bg-white p-7 rounded-[2rem] smooth-shadow border border-slate-50 flex items-center justify-between group hover:border-<?php echo $m['color']; ?>-400 card-hover">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-<?php echo $m['color']; ?>-50 text-<?php echo $m['color']; ?>-600 rounded-2xl flex items-center justify-center group-hover:scale-110">
                    <i class="fas <?php echo $m['icon']; ?> text-xl"></i>
                </div>
                <div>
                    <h5 class="font-black text-slate-800 uppercase text-xs tracking-wide mb-1 italic"><?php echo $m['label']; ?></h5>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter"><?php echo $m['sub']; ?></p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-slate-200 group-hover:text-<?php echo $m['color']; ?>-500 group-hover:translate-x-2"></i>
        </a>
        <?php endforeach; ?>
    </div>

    <footer class="mt-20 pb-6 text-center">
        <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
        <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">&copy; 2026 Pharma Stock • Cashier Intelligence</p>
    </footer>
</main>
</body>
</html>