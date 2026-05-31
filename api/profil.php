<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? '';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

$query     = mysqli_query($koneksi, "SELECT * FROM users WHERE username='".mysqli_real_escape_string($koneksi,$users)."'");
$data_user = mysqli_fetch_assoc($query);

// Ikon & warna per role
$role_config = [
    'Super Admin'    => ['icon'=>'fa-crown',         'bg'=>'bg-amber-400',    'text'=>'text-slate-900', 'label'=>'Super Administrator', 'ring'=>'ring-amber-300'],
    'Admin'          => ['icon'=>'fa-shield-alt',    'bg'=>'bg-blue-600',     'text'=>'text-white',      'label'=>'Administrator',       'ring'=>'ring-blue-300'],
    'Manager Gudang' => ['icon'=>'fa-warehouse',     'bg'=>'bg-emerald-500',  'text'=>'text-white',      'label'=>'Manager Gudang',      'ring'=>'ring-emerald-300'],
    'Apoteker'       => ['icon'=>'fa-mortar-pestle', 'bg'=>'bg-purple-600',   'text'=>'text-white',      'label'=>'Apoteker',            'ring'=>'ring-purple-300'],
    'Kasir'          => ['icon'=>'fa-cash-register', 'bg'=>'bg-orange-500',   'text'=>'text-white',      'label'=>'Kasir',               'ring'=>'ring-orange-300'],
    'Pending'        => ['icon'=>'fa-clock',         'bg'=>'bg-slate-400',    'text'=>'text-white',      'label'=>'Menunggu Verifikasi', 'ring'=>'ring-slate-300'],
];
$rc = $role_config[$role] ?? $role_config['Pending'];
$home_url = match($role) {
    'Kasir'       => 'kasir_dashboard.php',
    'Super Admin' => 'super_admin_dashboard.php',
    default       => 'dashboard.php',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
        *{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
        .smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
        .hero-bg{background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 50%,#0f172a 100%);}
        @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeUp 0.5s ease forwards;}
        @keyframes spin-slow{to{transform:rotate(360deg)}}
        .spin-slow{animation:spin-slow 8s linear infinite;}
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

    <!-- HEADER -->
    <header class="flex justify-between items-center mb-10 fade-up">
        <div>
            <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">User Identity</p>
            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
                Profil <span class="text-blue-600">Saya.</span>
            </h1>
        </div>
        <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow">
            <div class="flex flex-col items-end px-3">
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
                <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
            </div>
            <div class="w-10 h-10 <?php echo $rc['bg']; ?> rounded-full flex items-center justify-center <?php echo $rc['text']; ?> font-black text-sm border-2 border-white shadow-inner">
                <?php echo strtoupper(substr($users,0,1)); ?>
            </div>
        </div>
    </header>

    <div class="max-w-2xl mx-auto fade-up">
        <div class="bg-white rounded-[3rem] smooth-shadow border border-slate-50 overflow-hidden">

            <!-- HERO CARD -->
            <div class="hero-bg h-40 relative overflow-hidden">
                <i class="fas fa-dna absolute right-10 top-4 text-white/10 text-8xl -rotate-12"></i>
                <div class="absolute inset-0 flex items-end p-8">
                    <p class="text-blue-200 text-[10px] font-black uppercase tracking-[0.3em]">
                        <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Pharma Stock'); ?>
                    </p>
                </div>
            </div>

            <!-- AVATAR & INFO -->
            <div class="px-10 pb-10 -mt-14 text-center relative z-10">
                <!-- Avatar dengan ikon role -->
                <div class="inline-flex p-2 bg-white rounded-[2rem] shadow-2xl mb-5 ring-4 <?php echo $rc['ring']; ?>">
                    <div class="w-24 h-24 <?php echo $rc['bg']; ?> rounded-[1.8rem] flex items-center justify-center <?php echo $rc['text']; ?> relative overflow-hidden">
                        <i class="fas <?php echo $rc['icon']; ?> text-4xl relative z-10"></i>
                        <i class="fas fa-circle absolute -right-4 -bottom-4 text-white/10 text-7xl spin-slow"></i>
                    </div>
                </div>

                <h2 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">
                    @<?php echo htmlspecialchars($users); ?>
                </h2>
                <div class="flex justify-center mt-2 mb-6">
                    <span class="<?php echo $rc['bg']; ?> <?php echo $rc['text']; ?> font-black text-[9px] px-5 py-2 rounded-full uppercase tracking-widest inline-flex items-center gap-2">
                        <i class="fas <?php echo $rc['icon']; ?> text-xs"></i>
                        <?php echo $rc['label']; ?>
                    </span>
                </div>

                <!-- INFO GRID -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 text-left hover:bg-white hover:smooth-shadow group">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-hover:text-blue-600">Status Akun</p>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-shield-check text-sm"></i>
                            </div>
                            <p class="text-slate-800 font-black text-base italic">AKTIF</p>
                        </div>
                    </div>
                    <div class="p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 text-left hover:bg-white hover:smooth-shadow group">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-hover:text-indigo-600">Access Level</p>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 <?php echo $rc['bg']; ?> <?php echo $rc['text']; ?> rounded-xl flex items-center justify-center">
                                <i class="fas <?php echo $rc['icon']; ?> text-sm"></i>
                            </div>
                            <p class="text-slate-800 font-black text-base italic uppercase tracking-tighter"><?php echo $role; ?></p>
                        </div>
                    </div>
                    <?php if ($apotek): ?>
                    <div class="col-span-2 p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 text-left hover:bg-white hover:smooth-shadow group">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-hover:text-emerald-600">Apotek</p>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clinic-medical text-sm"></i>
                            </div>
                            <div>
                                <p class="text-slate-800 font-black text-sm italic"><?php echo htmlspecialchars($apotek['nama_apotek']); ?></p>
                                <p class="text-[9px] text-slate-400 font-bold"><?php echo htmlspecialchars($apotek['kota'].', '.$apotek['provinsi']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- QUOTE -->
                <div class="bg-slate-900 rounded-[1.5rem] p-6 text-center relative overflow-hidden mb-6">
                    <p class="text-[11px] text-slate-300 font-bold italic leading-relaxed relative z-10">
                        "Menjaga ketersediaan stok obat adalah menjaga kesehatan banyak orang.
                        Teruslah berkontribusi dengan integritas,
                        <span class="text-blue-400 uppercase"><?php echo htmlspecialchars($users); ?></span>!"
                    </p>
                    <i class="fas fa-quote-right absolute -right-2 -bottom-2 text-5xl text-white/5"></i>
                </div>

                <a href="<?php echo $home_url; ?>"
                   class="inline-flex items-center gap-2 px-8 py-3.5 bg-white border-2 border-slate-200 text-slate-700 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:border-blue-500 hover:text-blue-600 smooth-shadow">
                    <i class="fas fa-chevron-left text-xs"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <footer class="mt-10 pb-6 text-center">
        <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
        <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">Pharma Stock v2.0 • Identity</p>
    </footer>
</main>
</body>
</html>