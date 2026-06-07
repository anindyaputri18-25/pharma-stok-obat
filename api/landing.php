<?php
include 'koneksi.php';

$filter_apotek = isset($_GET['apotek']) ? (int)$_GET['apotek'] : 0;
$cari          = isset($_GET['cari'])   ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';

$cari_cond = $cari ? "AND m.nama_obat LIKE '%$cari%'" : "";
if ($filter_apotek > 0) {
    $sql_obat = "SELECT m.*, a.nama_apotek, a.wa_apotek, a.kota, a.provinsi,
                        IFNULL(h.harga_jual,0) AS harga_jual, IFNULL(h.satuan,'tablet') AS satuan
                 FROM medicines m LEFT JOIN apotek a ON m.id_apotek=a.id
                 LEFT JOIN harga_obat h ON h.id_obat=m.id AND h.id_apotek=m.id_apotek
                 WHERE m.id_apotek='$filter_apotek' AND a.status='aktif' $cari_cond ORDER BY m.nama_obat ASC";
} else {
    $sql_obat = "SELECT m.*, a.nama_apotek, a.wa_apotek, a.kota, a.provinsi,
                        IFNULL(h.harga_jual,0) AS harga_jual, IFNULL(h.satuan,'tablet') AS satuan
                 FROM medicines m LEFT JOIN apotek a ON m.id_apotek=a.id
                 LEFT JOIN harga_obat h ON h.id_obat=m.id AND h.id_apotek=m.id_apotek
                 WHERE a.status='aktif' $cari_cond ORDER BY a.nama_apotek ASC, m.nama_obat ASC";
}
$obat_result     = mysqli_query($koneksi, $sql_obat);
$apotek_list     = mysqli_query($koneksi,"SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");
$apotek_terpilih = $filter_apotek ? mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM apotek WHERE id='$filter_apotek' AND status='aktif' LIMIT 1")) : null;
$total_apotek    = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM apotek WHERE status='aktif'"))['c'];
$total_obat_ok   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines m JOIN apotek a ON m.id_apotek=a.id WHERE m.jumlah>0 AND a.status='aktif'"))['c'];
$total_kategori  = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(DISTINCT kategori) c FROM medicines"))['c'];

// Apotek ber-koordinat untuk peta
$apotek_peta = [];
$rp = mysqli_query($koneksi,"SELECT id,nama_apotek,alamat,kota,provinsi,wa_apotek,jam_buka,lat,lng FROM apotek WHERE status='aktif' AND lat IS NOT NULL AND lat!=0");
while ($ap = mysqli_fetch_assoc($rp)) $apotek_peta[] = $ap;
$apotek_json = json_encode($apotek_peta, JSON_UNESCAPED_UNICODE);

// Contoh data preview (tidak bisa diklik — hanya tampilan)
$preview_obat  = mysqli_query($koneksi,"SELECT m.nama_obat,m.kategori,m.jumlah,a.nama_apotek FROM medicines m JOIN apotek a ON m.id_apotek=a.id WHERE a.status='aktif' ORDER BY RAND() LIMIT 5");
$preview_rows  = [];
while ($r = mysqli_fetch_assoc($preview_obat)) $preview_rows[] = $r;

$map_lat  = -2.5489; $map_lng = 118.0149; $map_zoom = 5;
if ($apotek_terpilih && !empty($apotek_terpilih['lat']) && $apotek_terpilih['lat']!=0) {
    $map_lat=$apotek_terpilih['lat']; $map_lng=$apotek_terpilih['lng']; $map_zoom=15;
} elseif (count($apotek_peta)===1) { $map_lat=$apotek_peta[0]['lat']; $map_lng=$apotek_peta[0]['lng']; $map_zoom=14; }
elseif (count($apotek_peta)>1) { $map_zoom=7; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Pharma Stock — Cek Stok Obat Seluruh Indonesia</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.12);}
.hero-bg{background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 40%,#4338ca 100%);}
.feature-bg{background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);}
.navbar{backdrop-filter:blur(16px);background:rgba(255,255,255,0.97);}
.card-hover:hover{transform:translateY(-6px);box-shadow:0 24px 48px rgba(29,78,216,0.18);}
.stok-aman   {background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.stok-menipis{background:#fef9c3;color:#854d0e;border:1px solid #fde68a;}
.stok-habis  {background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
.floating-btn{position:fixed;bottom:24px;right:24px;z-index:999;animation:bob 2.5s ease-in-out infinite;}
@keyframes bob{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.7s ease forwards;}
@keyframes float{0%,100%{transform:translateY(0) rotate(-2deg)}50%{transform:translateY(-12px) rotate(2deg)}}
.float-anim{animation:float 4s ease-in-out infinite;}
#map{width:100%;height:420px;border-radius:1.5rem;z-index:1;}
.leaflet-popup-content-wrapper{border-radius:1rem;box-shadow:0 10px 30px rgba(0,0,0,0.12);}
.leaflet-popup-content{font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;margin:12px 16px;}
.popup-apotek h4{font-weight:900;font-size:13px;color:#1e293b;margin-bottom:4px;text-transform:uppercase;}
.popup-apotek p{color:#64748b;margin:2px 0;font-size:11px;}
.popup-apotek a{display:inline-flex;align-items:center;gap:6px;margin-top:8px;background:#22c55e;color:white;padding:6px 14px;border-radius:20px;font-weight:800;font-size:10px;text-transform:uppercase;text-decoration:none;}
.preview-blur{filter:blur(2px);pointer-events:none;user-select:none;}
.preview-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,transparent 0%,rgba(248,250,252,0.95) 70%);border-radius:inherit;display:flex;align-items:flex-end;padding:1.5rem;}
.hero-particle{position:absolute;border-radius:50%;background:rgba(255,255,255,0.08);}
.gradient-text{background:linear-gradient(135deg,#60a5fa,#a78bfa,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
</style>
</head>
<body class="text-slate-800 min-h-screen">

<!-- ===== NAVBAR ===== -->
<nav class="navbar sticky top-0 z-50 border-b border-slate-100 smooth-shadow">
  <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
        <i class="fas fa-pills text-sm"></i>
      </div>
      <div>
        <span class="font-black text-slate-900 text-sm uppercase tracking-tight">Pharma</span>
        <span class="font-black text-blue-600 text-sm uppercase tracking-tight">Stock</span>
      </div>
    </div>
    <div class="hidden md:flex items-center gap-6 text-xs font-bold text-slate-500">
      <a href="#fitur"  class="hover:text-blue-600 transition uppercase tracking-widest">Fitur</a>
      <a href="#apotek" class="hover:text-blue-600 transition uppercase tracking-widest">Apotek</a>
      <a href="#stok"   class="hover:text-blue-600 transition uppercase tracking-widest">Cek Stok</a>
      <a href="#maps"   class="hover:text-blue-600 transition uppercase tracking-widest">Peta</a>
    </div>
    <a href="login.php" class="bg-blue-600 text-white px-5 py-2.5 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center gap-2">
      <i class="fas fa-sign-in-alt"></i> Login Staff
    </a>
  </div>
</nav>

<!-- ===== HERO ===== -->
<section class="hero-bg min-h-[90vh] flex items-center px-6 py-24 text-white relative overflow-hidden">
  <!-- Particles dekoratif -->
  <div class="hero-particle w-96 h-96 top-10 right-10 opacity-30" style="animation:bob 6s ease-in-out infinite"></div>
  <div class="hero-particle w-64 h-64 bottom-20 left-20 opacity-20" style="animation:bob 8s ease-in-out infinite reverse"></div>
  <div class="hero-particle w-32 h-32 top-1/2 left-1/3 opacity-10" style="animation:bob 5s ease-in-out infinite"></div>

  <div class="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-2 gap-16 items-center relative z-10">
    <!-- KIRI: Teks -->
    <div class="fade-up">
      <div class="flex items-center gap-2 mb-6">
        <span class="bg-blue-500/30 border border-blue-400/30 text-blue-200 text-[10px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest backdrop-blur">
          ✅ Tanpa Login — Seluruh Indonesia
        </span>
      </div>
      <h1 class="text-5xl md:text-6xl font-black italic uppercase tracking-tighter leading-none mb-6">
        Cek Stok<br>
        <span class="gradient-text">Obat Online.</span>
      </h1>
      <p class="text-slate-300 text-base font-medium leading-relaxed mb-10 max-w-lg">
        Temukan ketersediaan obat dan harga di apotek terdekat Anda dari <strong class="text-white">Sabang sampai Merauke</strong> — real-time, gratis, dan tanpa perlu login.
      </p>
      <!-- SEARCH UTAMA -->
      <form method="GET" action="landing.php#stok" class="flex flex-col sm:flex-row gap-3 max-w-xl">
        <div class="flex-1 flex items-center gap-3 bg-white/10 backdrop-blur border border-white/20 rounded-2xl px-5 py-4 focus-within:bg-white/20 transition">
          <i class="fas fa-search text-white/60 text-sm shrink-0"></i>
          <input type="text" name="cari" value="<?php echo htmlspecialchars($cari);?>"
            placeholder="Cari nama obat, misal: Paracetamol..."
            class="flex-1 bg-transparent text-white placeholder:text-white/50 font-bold text-sm outline-none">
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-400 text-white px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-widest transition shadow-lg shrink-0">
          Cari
        </button>
      </form>
      <p class="text-slate-500 text-xs mt-4 font-bold">
        <i class="fas fa-shield-alt mr-1 text-blue-400"></i>
        Data diperbarui real-time dari sistem apotek terdaftar
      </p>
    </div>

    <!-- KANAN: PREVIEW CARD (tidak bisa diklik) -->
    <div class="relative hidden lg:block fade-up float-anim">
      <div class="bg-white rounded-[2rem] smooth-shadow p-6 relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest">Inventaris Publik</p>
            <h3 class="text-lg font-black text-slate-900 italic">Stok <span class="text-blue-600">Obat.</span></h3>
          </div>
          <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[9px] font-black rounded-full border border-emerald-100 uppercase">● Live</span>
        </div>
        <!-- Preview tabel blur -->
        <div class="relative">
          <div class="preview-blur">
            <div class="space-y-2">
              <?php
              $demo = [
                ['Amoxicillin 500mg','Obat Keras','Apotek Sehat',120,'Rp 3.500','aman'],
                ['Paracetamol 500mg','Obat Bebas','Apotek Medika',45,'Rp 1.200','aman'],
                ['Vitamin C 1000mg','Obat Bebas','Apotek Prima',8,'Rp 5.000','menipis'],
                ['Antasida Tablet','Obat Bebas','Apotek Jaya',0,'Rp 2.100','habis'],
                ['Cetirizine 10mg','Obat Keras','Apotek Husada',33,'Rp 4.500','aman'],
              ];
              foreach($demo as $d):
                $bg=$d[5]==='aman'?'stok-aman':($d[5]==='menipis'?'stok-menipis':'stok-habis');
              ?>
              <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                <div>
                  <p class="font-black text-slate-800 text-xs uppercase"><?php echo $d[0];?></p>
                  <p class="text-[9px] text-slate-400 font-bold"><?php echo $d[2];?></p>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-xs font-black text-slate-700"><?php echo $d[4];?></span>
                  <span class="px-2 py-0.5 <?php echo $bg;?> text-[9px] font-black rounded-full uppercase"><?php echo ucfirst($d[5]);?></span>
                </div>
              </div>
              <?php endforeach;?>
            </div>
          </div>
          <!-- Overlay gradient + CTA -->
          <div class="preview-overlay">
            <div class="w-full text-center pb-2">
              <p class="text-sm font-black text-slate-700 mb-1">Lihat data lengkap di bawah</p>
              <a href="#stok" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg">
                Cek Stok Sekarang <i class="fas fa-arrow-down text-xs"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
      <!-- Dekorasi card kecil -->
      <div class="absolute -bottom-6 -right-6 bg-emerald-400 rounded-2xl p-4 shadow-xl text-white">
        <p class="text-2xl font-black"><?php echo $total_apotek;?>+</p>
        <p class="text-[10px] font-black uppercase">Apotek</p>
      </div>
      <div class="absolute -top-6 -left-6 bg-purple-500 rounded-2xl p-4 shadow-xl text-white">
        <p class="text-2xl font-black"><?php echo $total_obat_ok;?></p>
        <p class="text-[10px] font-black uppercase">Obat Tersedia</p>
      </div>
    </div>
  </div>
</section>

<!-- ===== STATS BAR ===== -->
<section class="bg-white border-b border-slate-100 py-8">
  <div class="max-w-7xl mx-auto px-6 grid grid-cols-3 md:grid-cols-3 gap-6 text-center">
    <?php foreach([
      ['val'=>$total_apotek, 'label'=>'Apotek Aktif',   'icon'=>'fa-clinic-medical','c'=>'blue'],
      ['val'=>$total_obat_ok,'label'=>'Obat Tersedia',  'icon'=>'fa-pills',          'c'=>'emerald'],
      ['val'=>$total_kategori,'label'=>'Kategori Obat', 'icon'=>'fa-tags',           'c'=>'purple'],
    ] as $s):?>
    <div class="flex flex-col items-center gap-2">
      <div class="w-12 h-12 bg-<?php echo $s['c'];?>-50 text-<?php echo $s['c'];?>-600 rounded-2xl flex items-center justify-center">
        <i class="fas <?php echo $s['icon'];?> text-lg"></i>
      </div>
      <p class="text-3xl font-black text-slate-900"><?php echo $s['val'];?></p>
      <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo $s['label'];?></p>
    </div>
    <?php endforeach;?>
  </div>
</section>

<!-- ===== FITUR HIGHLIGHT ===== -->
<section id="fitur" class="max-w-7xl mx-auto px-6 py-20 fade-up">
  <div class="text-center mb-14">
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-2">Kenapa Pharma Stock?</p>
    <h2 class="text-3xl font-black text-slate-900 uppercase tracking-tighter italic">
      Semua yang Anda <span class="text-blue-600">Butuhkan.</span>
    </h2>
    <p class="text-slate-400 text-sm font-medium mt-3 max-w-xl mx-auto">Platform informasi obat terlengkap, dari cek stok hingga menemukan apotek terdekat.</p>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php $fitur=[
      ['icon'=>'fa-search','title'=>'Cek Stok Real-Time','desc'=>'Lihat ketersediaan obat dan harga terbaru dari semua apotek terdaftar tanpa perlu login.','color'=>'blue','bg'=>'bg-blue-50'],
      ['icon'=>'fa-map-marker-alt','title'=>'Temukan Apotek Terdekat','desc'=>'Gunakan GPS perangkat Anda untuk menemukan apotek terdekat dengan peta interaktif OpenStreetMap.','color'=>'emerald','bg'=>'bg-emerald-50'],
      ['icon'=>'fab fa-whatsapp','title'=>'Tanya Langsung via WA','desc'=>'Hubungi apotek langsung via WhatsApp dengan satu klik untuk konfirmasi stok dan harga.','color'=>'green','bg'=>'bg-green-50'],
      ['icon'=>'fa-tags','title'=>'Transparansi Harga','desc'=>'Bandingkan harga obat antar apotek secara terbuka untuk mendapatkan pilihan terbaik.','color'=>'orange','bg'=>'bg-orange-50'],
      ['icon'=>'fa-shield-alt','title'=>'Informasi Terpercaya','desc'=>'Data bersumber langsung dari sistem internal apotek yang terverifikasi, bukan dari pihak ketiga.','color'=>'indigo','bg'=>'bg-indigo-50'],
      ['icon'=>'fa-mobile-alt','title'=>'Akses di Mana Saja','desc'=>'Responsif di semua perangkat — ponsel, tablet, maupun desktop, kapan saja dan di mana saja.','color'=>'purple','bg'=>'bg-purple-50'],
    ];
    foreach($fitur as $f):?>
    <div class="bg-white p-7 rounded-[2rem] smooth-shadow border border-slate-50 card-hover">
      <div class="w-14 h-14 <?php echo $f['bg'];?> text-<?php echo $f['color'];?>-600 rounded-2xl flex items-center justify-center mb-5">
        <i class="<?php echo (str_starts_with($f['icon'],'fab')?$f['icon']:'fas '.$f['icon']);?> text-2xl"></i>
      </div>
      <h3 class="font-black text-slate-800 text-sm uppercase tracking-tight mb-2"><?php echo $f['title'];?></h3>
      <p class="text-slate-400 text-[11px] font-medium leading-relaxed"><?php echo $f['desc'];?></p>
    </div>
    <?php endforeach;?>
  </div>
</section>

<!-- ===== PREVIEW DASHBOARD (tampilan saja, tidak bisa klik) ===== -->
<section class="bg-slate-900 py-20 px-6 relative overflow-hidden">
  <div class="absolute inset-0 opacity-5">
    <div class="absolute top-10 left-10 w-64 h-64 bg-blue-400 rounded-full blur-3xl"></div>
    <div class="absolute bottom-10 right-10 w-48 h-48 bg-purple-400 rounded-full blur-3xl"></div>
  </div>
  <div class="max-w-7xl mx-auto relative z-10">
    <div class="text-center mb-12">
      <p class="text-blue-400 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-2">Gambaran Sistem</p>
      <h2 class="text-3xl font-black text-white uppercase tracking-tighter italic">
        Tampilan <span class="text-blue-400">Dashboard.</span>
      </h2>
      <p class="text-slate-400 text-sm font-medium mt-3">Begini tampilan sistem yang digunakan staff apotek kami.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <!-- Preview Stok -->
      <div class="bg-white/5 border border-white/10 rounded-[2rem] p-5 backdrop-blur">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 bg-blue-500/30 text-blue-300 rounded-xl flex items-center justify-center"><i class="fas fa-box text-sm"></i></div>
          <div><p class="text-white font-black text-xs uppercase">Stok Obat</p><p class="text-slate-500 text-[9px]">Manajemen inventaris</p></div>
        </div>
        <div class="space-y-2 preview-blur select-none pointer-events-none">
          <?php foreach(['Paracetamol 500mg','Amoxicillin 500mg','Vitamin C','Antasida'] as $i=>$nm):
            $qty=[120,45,8,0][$i]; $c=['emerald','emerald','amber','rose'][$i]; $l=['Aman','Aman','Menipis','Habis'][$i];?>
          <div class="flex items-center justify-between bg-white/10 p-2.5 rounded-xl">
            <span class="text-white text-[10px] font-bold"><?php echo $nm;?></span>
            <span class="text-[9px] font-black px-2 py-0.5 bg-<?php echo $c;?>-500/30 text-<?php echo $c;?>-300 rounded-full"><?php echo $l;?></span>
          </div>
          <?php endforeach;?>
        </div>
        <p class="text-center text-slate-500 text-[9px] font-bold mt-3 italic">Hanya tampilan — tidak bisa diklik</p>
      </div>
      <!-- Preview Analisis -->
      <div class="bg-white/5 border border-white/10 rounded-[2rem] p-5 backdrop-blur">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 bg-purple-500/30 text-purple-300 rounded-xl flex items-center justify-center"><i class="fas fa-chart-bar text-sm"></i></div>
          <div><p class="text-white font-black text-xs uppercase">Analisis</p><p class="text-slate-500 text-[9px]">Data BPS Kesehatan</p></div>
        </div>
        <div class="preview-blur select-none pointer-events-none">
          <div class="grid grid-cols-2 gap-2 mb-3">
            <?php foreach([['Obat Modern','78%','blue'],['Tradisional','42%','emerald'],['Puskesmas','65%','purple'],['Klinik','53%','amber']] as $d):?>
            <div class="bg-white/10 p-3 rounded-xl text-center">
              <p class="text-xl font-black text-<?php echo $d[2];?>-300"><?php echo $d[1];?></p>
              <p class="text-[9px] text-slate-400 font-bold"><?php echo $d[0];?></p>
            </div>
            <?php endforeach;?>
          </div>
          <div class="bg-white/5 rounded-xl p-3 flex items-end gap-1 h-16">
            <?php foreach([40,60,80,55,90,70,85] as $h):?>
            <div class="flex-1 bg-blue-400/60 rounded-t" style="height:<?php echo $h;?>%"></div>
            <?php endforeach;?>
          </div>
        </div>
        <p class="text-center text-slate-500 text-[9px] font-bold mt-3 italic">Hanya tampilan — tidak bisa diklik</p>
      </div>
      <!-- Preview Laporan -->
      <div class="bg-white/5 border border-white/10 rounded-[2rem] p-5 backdrop-blur">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-8 h-8 bg-emerald-500/30 text-emerald-300 rounded-xl flex items-center justify-center"><i class="fas fa-file-alt text-sm"></i></div>
          <div><p class="text-white font-black text-xs uppercase">Laporan</p><p class="text-slate-500 text-[9px]">Cetak & ekspor data</p></div>
        </div>
        <div class="space-y-2 preview-blur select-none pointer-events-none">
          <?php foreach([['Stok Aman','120 item','emerald'],['Stok Menipis','18 item','amber'],['Out of Stock','5 item','rose'],['Total Nilai','Rp 12.4M','blue']] as $l):?>
          <div class="flex items-center justify-between bg-white/10 p-2.5 rounded-xl">
            <span class="text-slate-300 text-[10px] font-bold"><?php echo $l[0];?></span>
            <span class="text-<?php echo $l[2];?>-300 text-[10px] font-black"><?php echo $l[1];?></span>
          </div>
          <?php endforeach;?>
        </div>
        <p class="text-center text-slate-500 text-[9px] font-bold mt-3 italic">Hanya tampilan — tidak bisa diklik</p>
      </div>
    </div>
    <p class="text-center text-slate-500 text-xs font-bold mt-8 italic">
      <i class="fas fa-lock mr-1"></i> Fitur lengkap hanya untuk staff apotek yang sudah login
    </p>
  </div>
</section>

<!-- ===== DAFTAR APOTEK ===== -->
<section id="apotek" class="max-w-7xl mx-auto px-6 py-16 fade-up">
  <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Jaringan Kami</p>
      <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">Apotek <span class="text-blue-600">Terdaftar.</span></h2>
      <p class="text-slate-400 text-xs font-medium mt-1">Klik apotek untuk melihat stok khusus apotek tersebut</p>
    </div>
    <!-- Filter search apotek -->
    <div class="relative">
      <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
      <input type="text" id="cariApotek" placeholder="Cari apotek atau kota..."
        class="pl-9 pr-4 py-3 bg-white border border-slate-200 rounded-full text-xs font-bold outline-none focus:ring-2 focus:ring-blue-400 w-64 smooth-shadow transition">
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="apotekGrid">
    <?php
    $apotek_list2 = mysqli_query($koneksi,"SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");
    while ($ap = mysqli_fetch_assoc($apotek_list2)):
      $jml = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE id_apotek='{$ap['id']}' AND jumlah>0"))['c'];
      $is_active = ($filter_apotek == $ap['id']);
    ?>
    <div class="apotek-card card-hover bg-white rounded-[2rem] smooth-shadow border-2 p-6 cursor-pointer transition <?php echo $is_active?'border-blue-500 ring-2 ring-blue-100':'border-slate-50 hover:border-blue-300';?>"
         data-nama="<?php echo strtolower($ap['nama_apotek'].' '.$ap['kota'].' '.$ap['provinsi']);?>"
         onclick="filterApotek(<?php echo $ap['id'];?>)">
      <div class="flex items-start justify-between mb-5">
        <div class="w-14 h-14 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl flex items-center justify-center text-blue-600 group-hover:scale-110 transition">
          <i class="fas fa-clinic-medical text-2xl"></i>
        </div>
        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[9px] font-black uppercase tracking-wider rounded-full border border-emerald-100 flex items-center gap-1">
          <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Aktif
        </span>
      </div>
      <h3 class="font-black text-slate-900 text-sm uppercase italic mb-2"><?php echo htmlspecialchars($ap['nama_apotek']);?></h3>
      <p class="text-[10px] text-slate-400 font-bold mb-1 flex items-center gap-1.5">
        <i class="fas fa-map-marker-alt text-rose-400"></i><?php echo htmlspecialchars($ap['kota'].', '.$ap['provinsi']);?>
      </p>
      <p class="text-[10px] text-slate-400 font-medium mb-4 line-clamp-1">
        <i class="fas fa-road text-slate-300 mr-1.5"></i><?php echo htmlspecialchars($ap['alamat']);?>
      </p>
      <div class="flex items-center justify-between pt-3 border-t border-slate-50">
        <div class="flex items-center gap-2">
          <span class="text-[10px] font-black text-slate-600 flex items-center gap-1">
            <i class="fas fa-pills text-blue-400"></i><?php echo $jml;?> obat
          </span>
          <span class="text-slate-200">|</span>
          <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
            <i class="fas fa-clock text-slate-300"></i><?php echo htmlspecialchars($ap['jam_buka']);?>
          </span>
        </div>
      </div>
      <div class="flex gap-2 mt-3">
        <?php if (!empty($ap['wa_apotek'])): ?>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $ap['wa_apotek']);?>?text=Halo%20<?php echo urlencode($ap['nama_apotek']);?>%2C%20saya%20ingin%20menanyakan%20ketersediaan%20obat."
           target="_blank" onclick="event.stopPropagation()"
           class="flex-1 flex items-center justify-center gap-1.5 bg-emerald-50 text-emerald-600 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-emerald-500 hover:text-white transition border border-emerald-100">
          <i class="fab fa-whatsapp"></i> Chat WA
        </a>
        <?php endif;?>
        <?php if (!empty($ap['lat']) && $ap['lat']!=0): ?>
        <a href="#maps" onclick="event.stopPropagation(); terbangKePeta(<?php echo $ap['lat'];?>,<?php echo $ap['lng'];?>,<?php echo $ap['id'];?>)"
           class="flex-1 flex items-center justify-center gap-1.5 bg-blue-50 text-blue-600 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-blue-600 hover:text-white transition border border-blue-100">
          <i class="fas fa-map-marker-alt"></i> Peta
        </a>
        <?php else: ?>
        <span class="flex-1 flex items-center justify-center gap-1.5 bg-slate-50 text-slate-300 py-2 rounded-xl font-black text-[9px] uppercase border border-slate-100">
          <i class="fas fa-map-marker-alt"></i> No GPS
        </span>
        <?php endif;?>
      </div>
      <?php if($is_active): ?>
      <div class="mt-3 text-center">
        <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest"><i class="fas fa-check-circle mr-1"></i> Menampilkan stok apotek ini</span>
      </div>
      <?php endif;?>
    </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- ===== PETA OPENSTREETMAP ===== -->
<section id="maps" class="max-w-7xl mx-auto px-6 pb-12 fade-up">
  <div class="mb-6">
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Navigasi</p>
    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">Lokasi <span class="text-blue-600">Apotek.</span></h2>
    <p class="text-slate-400 text-xs font-medium mt-1">Peta menggunakan OpenStreetMap — 100% gratis, tanpa API key</p>
  </div>
  <div class="bg-white rounded-[2rem] smooth-shadow border border-slate-100 p-5 overflow-hidden">
    <div class="flex flex-wrap gap-3 mb-4">
      <button onclick="cariLokasiSaya()" class="flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100">
        <i class="fas fa-crosshairs" id="iconCari"></i>
        <span id="labelCari">Apotek Terdekat dari Saya</span>
      </button>
      <button onclick="resetPeta()" class="flex items-center gap-2 bg-slate-100 text-slate-600 px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition">
        <i class="fas fa-globe"></i> Lihat Semua
      </button>
    </div>
    <div id="map"></div>
    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-3 text-center italic">
      <i class="fas fa-info-circle mr-1"></i>Klik marker apotek di peta untuk info lengkap & kontak WA
    </p>
  </div>
</section>

<!-- ===== TABEL STOK OBAT ===== -->
<section id="stok" class="max-w-7xl mx-auto px-6 pb-20 fade-up">
  <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Inventaris Publik</p>
      <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">
        Cek Stok <span class="text-blue-600">Obat.</span>
        <?php if($cari): ?>
        <span class="text-sm font-bold text-slate-400 normal-case ml-2">— "<?php echo htmlspecialchars($cari);?>"</span>
        <?php elseif($apotek_terpilih): ?>
        <span class="text-sm font-bold text-slate-400 normal-case ml-2">— <?php echo htmlspecialchars($apotek_terpilih['nama_apotek']);?></span>
        <?php endif;?>
      </h2>
    </div>
    <div class="flex gap-3">
      <div class="relative">
        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
        <input type="text" id="tableSearch" placeholder="Filter obat..."
          class="pl-9 pr-4 py-3 rounded-xl bg-white border border-slate-200 text-sm font-bold outline-none w-full md:w-52 smooth-shadow focus:ring-2 focus:ring-blue-400 transition">
      </div>
      <?php if($filter_apotek||$cari): ?>
      <a href="landing.php#stok" class="px-5 py-3 rounded-xl bg-slate-100 text-slate-500 font-black text-xs uppercase tracking-widest hover:bg-rose-50 hover:text-rose-500 transition whitespace-nowrap flex items-center gap-2">
        <i class="fas fa-times"></i> Reset
      </a>
      <?php endif;?>
    </div>
  </div>
  <div class="bg-white rounded-[2rem] smooth-shadow border border-slate-50 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead class="bg-slate-50/60 border-b border-slate-100">
          <tr>
            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Apotek</th>
            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Kategori</th>
            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga</th>
            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Tanya WA</th>
          </tr>
        </thead>
        <tbody id="bodyTabel" class="divide-y divide-slate-50">
          <?php
          $has_data = false;
          while ($row = mysqli_fetch_assoc($obat_result)):
            $has_data=true;
            if($row['jumlah']<=0){$badge='stok-habis';$label='Habis';}
            elseif($row['jumlah']<=15){$badge='stok-menipis';$label='Menipis';}
            else{$badge='stok-aman';$label='Tersedia';}
            $hf=$row['harga_jual']>0?'Rp '.number_format($row['harga_jual'],0,',','.').' / '.$row['satuan']:'—';
            $wm="Halo%20".urlencode($row['nama_apotek']??'')
               ."%2C%20apakah%20obat%20*".urlencode($row['nama_obat'])
               ."*%20masih%20tersedia%3F%20Berapa%20harganya%3F";
          ?>
          <tr class="hover:bg-blue-50/20 transition-colors obat-row"
              data-nama="<?php echo strtolower($row['nama_obat']);?>"
              data-apotek="<?php echo strtolower($row['nama_apotek']??'');?>">
            <td class="p-5 font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($row['nama_obat']);?></td>
            <td class="p-5">
              <div class="text-[10px] font-bold text-slate-600"><?php echo htmlspecialchars($row['nama_apotek']??'—');?></div>
              <div class="text-[9px] text-slate-400"><?php echo htmlspecialchars(($row['kota']??'').', '.($row['provinsi']??''));?></div>
            </td>
            <td class="p-5 text-center"><span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase"><?php echo htmlspecialchars($row['kategori']);?></span></td>
            <td class="p-5 text-center font-black text-slate-700 text-xs"><?php echo $hf;?></td>
            <td class="p-5 text-center font-black text-slate-800 text-sm"><?php echo $row['jumlah'];?></td>
            <td class="p-5 text-center"><span class="<?php echo $badge;?> px-3 py-1 text-[9px] font-black rounded-full uppercase tracking-tighter"><?php echo $label;?></span></td>
            <td class="p-5 text-center">
              <?php if(!empty($row['wa_apotek'])): ?>
              <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $row['wa_apotek']);?>?text=<?php echo $wm;?>"
                 target="_blank"
                 class="inline-flex items-center gap-1.5 bg-emerald-500 text-white px-4 py-1.5 rounded-full text-[9px] font-black hover:bg-emerald-600 transition uppercase tracking-widest shadow-sm">
                <i class="fab fa-whatsapp"></i> Tanya
              </a>
              <?php else: ?><span class="text-[9px] text-slate-300 font-bold italic">—</span><?php endif;?>
            </td>
          </tr>
          <?php endwhile; if(!$has_data): ?>
          <tr><td colspan="7" class="p-16 text-center text-slate-400 font-bold italic text-[10px] uppercase tracking-widest">
            <?php echo $cari?"Obat \"".htmlspecialchars($cari)."\" tidak ditemukan.":'Belum ada data stok.';?>
          </td></tr>
          <?php endif;?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="bg-slate-900 text-white py-16 px-6">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
      <div>
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-900">
            <i class="fas fa-pills text-sm text-white"></i>
          </div>
          <div>
            <span class="font-black text-xl uppercase tracking-tight">Pharma</span>
            <span class="font-black text-xl text-blue-400 uppercase tracking-tight">Stock</span>
          </div>
        </div>
        <p class="text-slate-400 text-xs font-medium leading-relaxed">Platform informasi stok obat real-time untuk apotek di seluruh Indonesia.</p>
      </div>
      <div>
        <p class="font-black text-white text-xs uppercase tracking-widest mb-4">Navigasi Cepat</p>
        <div class="flex flex-col gap-2">
          <?php foreach(['#fitur'=>'Fitur','#apotek'=>'Apotek','#stok'=>'Cek Stok','#maps'=>'Peta'] as $h=>$l):?>
          <a href="<?php echo $h;?>" class="text-slate-400 text-xs font-bold hover:text-blue-400 transition"><?php echo $l;?></a>
          <?php endforeach;?>
        </div>
      </div>
      <div>
        <p class="font-black text-white text-xs uppercase tracking-widest mb-4">Untuk Staff Apotek</p>
        <p class="text-slate-400 text-xs font-medium mb-4 leading-relaxed">Akses dashboard lengkap untuk mengelola stok, harga, racikan, dan laporan apotek Anda.</p>
        <a href="login.php" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-500 transition">
          <i class="fas fa-sign-in-alt"></i> Login Staff
        </a>
      </div>
    </div>
    <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
      <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">&copy; 2026 PharmaStock • Seluruh Indonesia</p>
      <p class="text-slate-600 text-[10px]">Peta © <a href="https://www.openstreetmap.org/copyright" target="_blank" class="hover:text-slate-400 transition">OpenStreetMap</a> contributors</p>
    </div>
  </div>
</footer>

<!-- FLOATING WA -->
<div class="floating-btn">
  <a href="https://wa.me/" target="_blank"
     class="w-14 h-14 bg-emerald-500 hover:bg-emerald-400 text-white rounded-full flex items-center justify-center shadow-2xl shadow-emerald-900 transition">
    <i class="fab fa-whatsapp text-2xl"></i>
  </a>
</div>

<script>
const apotekData = <?php echo $apotek_json; ?>;
const map = L.map('map',{zoomControl:true}).setView([<?php echo $map_lat;?>,<?php echo $map_lng;?>],<?php echo $map_zoom;?>);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors',maxZoom:19
}).addTo(map);

const apotekIcon = L.divIcon({
    className:'',
    html:`<div style="width:36px;height:36px;background:linear-gradient(135deg,#2563eb,#4338ca);border:3px solid white;border-radius:50% 50% 50% 0;transform:rotate(-45deg);box-shadow:0 4px 14px rgba(37,99,235,.4);display:flex;align-items:center;justify-content:center;">
           <i class="fas fa-clinic-medical" style="transform:rotate(45deg);color:white;font-size:13px;"></i>
         </div>`,
    iconSize:[36,36],iconAnchor:[18,36],popupAnchor:[0,-40]
});

const markers={};
apotekData.forEach(ap=>{
    if(!ap.lat||!ap.lng)return;
    const wa=ap.wa_apotek?`<a href="https://wa.me/${ap.wa_apotek.replace(/[^0-9]/g,'')}?text=Halo%20${encodeURIComponent(ap.nama_apotek)}%2C%20saya%20ingin%20menanyakan%20ketersediaan%20obat." target="_blank"><i class="fab fa-whatsapp"></i> Chat WhatsApp</a>`:'';
    const popup=`<div class="popup-apotek">
      <h4>${ap.nama_apotek}</h4>
      <p><i class="fas fa-map-marker-alt" style="color:#ef4444;margin-right:4px"></i>${ap.alamat}, ${ap.kota}</p>
      <p><i class="fas fa-map-pin" style="color:#8b5cf6;margin-right:4px"></i>${ap.provinsi}</p>
      <p><i class="fas fa-clock" style="color:#3b82f6;margin-right:4px"></i>${ap.jam_buka}</p>
      ${wa}</div>`;
    markers[ap.id]=L.marker([parseFloat(ap.lat),parseFloat(ap.lng)],{icon:apotekIcon}).addTo(map).bindPopup(popup,{maxWidth:260});
});

function terbangKePeta(lat,lng,id){
    map.flyTo([lat,lng],16,{animate:true,duration:1.2});
    if(markers[id])setTimeout(()=>markers[id].openPopup(),1300);
    document.getElementById('maps').scrollIntoView({behavior:'smooth'});
}
function resetPeta(){
    if(apotekData.length>1){
        const b=L.latLngBounds(apotekData.filter(a=>a.lat&&a.lng).map(a=>[a.lat,a.lng]));
        if(b.isValid()){map.flyToBounds(b.pad(0.3),{duration:1.2});return;}
    }
    map.flyTo([-2.5489,118.0149],5,{animate:true,duration:1.2});
}
function cariLokasiSaya(){
    if(!navigator.geolocation){alert('Browser tidak mendukung geolokasi.');return;}
    const ic=document.getElementById('iconCari'),lb=document.getElementById('labelCari');
    ic.className='fas fa-spinner fa-spin';lb.textContent='Mendeteksi lokasi...';
    navigator.geolocation.getCurrentPosition(pos=>{
        ic.className='fas fa-crosshairs';lb.textContent='Apotek Terdekat dari Saya';
        const{latitude:lat,longitude:lng}=pos.coords;
        const ui=L.divIcon({className:'',html:`<div style="width:16px;height:16px;background:#3b82f6;border:3px solid white;border-radius:50%;box-shadow:0 0 0 8px rgba(59,130,246,.25)"></div>`,iconSize:[16,16],iconAnchor:[8,8]});
        L.marker([lat,lng],{icon:ui}).addTo(map).bindPopup('<b style="font-family:Plus Jakarta Sans">📍 Lokasi Anda</b>').openPopup();
        let nearest=null,minD=Infinity;
        apotekData.forEach(ap=>{if(!ap.lat||!ap.lng)return;const d=Math.hypot(ap.lat-lat,ap.lng-lng);if(d<minD){minD=d;nearest=ap;}});
        if(apotekData.length>0){
            const b=L.latLngBounds([[lat,lng]]);
            apotekData.forEach(ap=>{if(ap.lat&&ap.lng)b.extend([ap.lat,ap.lng]);});
            map.flyToBounds(b.pad(0.2),{animate:true,duration:1.5});
        }else{map.flyTo([lat,lng],13,{animate:true,duration:1.2});}
        if(nearest)setTimeout(()=>{if(markers[nearest.id])markers[nearest.id].openPopup();},2000);
        document.getElementById('maps').scrollIntoView({behavior:'smooth'});
    },err=>{
        ic.className='fas fa-crosshairs';lb.textContent='Apotek Terdekat dari Saya';
        const m={1:'Izin lokasi ditolak.',2:'Lokasi tidak tersedia.',3:'Waktu habis.'};
        alert(m[err.code]||'Gagal mendapatkan lokasi.');
    },{timeout:10000,maximumAge:300000});
}
function filterApotek(id){
    const cari=document.getElementById('tableSearch')?.value||'';
    window.location.href=`landing.php?apotek=${id}&cari=${encodeURIComponent(cari)}#stok`;
}
// Search real-time tabel
document.getElementById('tableSearch').addEventListener('input',function(){
    const q=this.value.toLowerCase().trim();
    document.querySelectorAll('.obat-row').forEach(r=>{r.style.display=(r.dataset.nama+' '+r.dataset.apotek).includes(q)?'':'none';});
});
// Search apotek cards
document.getElementById('cariApotek').addEventListener('input',function(){
    const q=this.value.toLowerCase().trim();
    document.querySelectorAll('.apotek-card').forEach(c=>{c.style.display=c.dataset.nama.includes(q)?'':'none';});
});
// Buka popup jika ada apotek terpilih
<?php if($apotek_terpilih&&!empty($apotek_terpilih['lat'])&&$apotek_terpilih['lat']!=0):?>
window.addEventListener('load',()=>{setTimeout(()=>{const m=markers[<?php echo $apotek_terpilih['id'];?>];if(m)m.openPopup();},800);});
<?php endif;?>
</script>
</body>
</html>