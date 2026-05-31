<?php
include 'koneksi.php';
include 'autentikasi.php';

$users = $_COOKIE['users'];
$query_cek  = mysqli_query($koneksi,"SELECT role FROM users WHERE username='".mysqli_real_escape_string($koneksi,$users)."'");
$data_baru  = mysqli_fetch_assoc($query_cek);
$role_terbaru = $data_baru['role'];

if ($role_terbaru !== 'Pending') {
    setcookie('role', $role_terbaru, time()+86400, "/");
    header("Location: ".($role_terbaru==='Kasir'?'kasir_dashboard.php':($role_terbaru==='Super Admin'?'super_admin_dashboard.php':'dashboard.php')));
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Menunggu Persetujuan - Pharma Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.hero-bg{background:linear-gradient(135deg,#f59e0b 0%,#ea580c 50%,#0f172a 100%);}
@keyframes pulse-ring{0%{transform:scale(1);opacity:.5}100%{transform:scale(1.5);opacity:0}}
.pulse-ring{animation:pulse-ring 2s ease-out infinite;}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.6s ease forwards;}
@keyframes spin-slow{to{transform:rotate(360deg)}}
.spin-slow{animation:spin-slow 10s linear infinite;}
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
<div class="w-full max-w-md fade-up">

  <!-- LOGO -->
  <div class="text-center mb-8">
    <div class="inline-flex items-center gap-3">
      <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
        <i class="fas fa-pills text-lg"></i>
      </div>
      <div>
        <span class="font-black text-slate-900 text-lg uppercase tracking-tight">Pharma</span>
        <span class="font-black text-blue-600 text-lg uppercase tracking-tight">Stock</span>
      </div>
    </div>
  </div>

  <!-- CARD -->
  <div class="bg-white rounded-[3rem] shadow-2xl shadow-orange-100 border border-orange-100 overflow-hidden">

    <!-- HERO TOP -->
    <div class="hero-bg p-8 text-white text-center relative overflow-hidden">
      <div class="relative z-10">
        <!-- Ikon dengan pulse ring -->
        <div class="relative inline-block mb-4">
          <div class="absolute inset-0 rounded-full bg-orange-300 pulse-ring"></div>
          <div class="w-20 h-20 bg-white/20 backdrop-blur border-2 border-white/30 rounded-3xl flex items-center justify-center relative z-10">
            <i class="fas fa-clock text-4xl text-white"></i>
          </div>
        </div>
        <h2 class="text-2xl font-black uppercase tracking-tight mb-1">Akses Tertunda</h2>
        <p class="text-orange-100 text-xs font-medium">Menunggu verifikasi dari Admin</p>
      </div>
      <i class="fas fa-shield-alt absolute -right-6 -bottom-6 text-[8rem] opacity-10 spin-slow"></i>
    </div>

    <!-- CONTENT -->
    <div class="p-8 text-center">
      <p class="text-slate-600 text-sm leading-relaxed mb-6">
        Halo <span class="font-black text-blue-600">@<?php echo htmlspecialchars($users);?></span>,<br>
        akun Anda sedang menunggu verifikasi dari Admin apotek.<br>
        <span class="text-[11px] text-slate-400 mt-1 block">Halaman akan terbuka otomatis setelah disetujui.</span>
      </p>

      <!-- STATUS BADGE -->
      <div class="bg-amber-50 rounded-2xl p-4 mb-6 border border-amber-100 text-left">
        <p class="text-[9px] font-black text-amber-600 uppercase tracking-widest mb-2">Status Akun</p>
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>
          <span class="text-xs font-bold text-amber-700">Menunggu persetujuan Admin</span>
        </div>
      </div>

      <!-- LANGKAH -->
      <div class="bg-slate-50 rounded-2xl p-4 mb-6 text-left border border-slate-100">
        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Langkah Selanjutnya</p>
        <?php foreach([
          ['icon'=>'fa-envelope','text'=>'Hubungi Admin apotek Anda','color'=>'blue'],
          ['icon'=>'fa-clock',   'text'=>'Tunggu konfirmasi verifikasi','color'=>'amber'],
          ['icon'=>'fa-check',   'text'=>'Login ulang setelah disetujui','color'=>'emerald'],
        ] as $step):?>
        <div class="flex items-center gap-3 mb-2.5">
          <div class="w-7 h-7 bg-<?php echo $step['color'];?>-100 text-<?php echo $step['color'];?>-600 rounded-lg flex items-center justify-center shrink-0">
            <i class="fas <?php echo $step['icon'];?> text-[10px]"></i>
          </div>
          <span class="text-[11px] font-bold text-slate-600"><?php echo $step['text'];?></span>
        </div>
        <?php endforeach;?>
      </div>

      <!-- TOMBOL -->
      <div class="space-y-3">
        <button onclick="window.location.reload()"
          class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-100 transition active:scale-95 flex items-center justify-center gap-2">
          <i class="fas fa-sync-alt"></i> Cek Status Terbaru
        </button>
        <a href="logout.php"
          class="block w-full py-4 bg-slate-100 text-slate-500 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-rose-50 hover:text-rose-500 transition text-center">
          <i class="fas fa-sign-out-alt mr-1"></i> Keluar & Cek Nanti
        </a>
      </div>
    </div>
  </div>

  <p class="text-center text-slate-400 text-[10px] font-bold uppercase tracking-[0.3em] mt-8">
    &copy; 2026 Pharma Stock 💊
  </p>
</div>
</body>
</html>