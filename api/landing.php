<?php

include 'koneksi.php';

$filter_apotek = isset($_GET['apotek']) ? (int)$_GET['apotek'] : 0;
$cari          = isset($_GET['cari'])   ? mysqli_real_escape_string($koneksi, trim($_GET['cari'])) : '';

$cari_cond = $cari ? "AND m.nama_obat LIKE '%$cari%'" : "";
if ($filter_apotek > 0) {
    $sql_obat = "SELECT m.*, a.nama_apotek, a.wa_apotek, a.kota, a.provinsi,
                        IFNULL(h.harga_jual,0) AS harga_jual, IFNULL(h.satuan,'tablet') AS satuan
                 FROM medicines m
                 LEFT JOIN apotek a ON m.id_apotek=a.id
                 LEFT JOIN harga_obat h ON h.id_obat=m.id AND h.id_apotek=m.id_apotek
                 WHERE m.id_apotek='$filter_apotek' AND a.status='aktif' $cari_cond
                 ORDER BY m.nama_obat ASC";
} else {
    $sql_obat = "SELECT m.*, a.nama_apotek, a.wa_apotek, a.kota, a.provinsi,
                        IFNULL(h.harga_jual,0) AS harga_jual, IFNULL(h.satuan,'tablet') AS satuan
                 FROM medicines m
                 LEFT JOIN apotek a ON m.id_apotek=a.id
                 LEFT JOIN harga_obat h ON h.id_obat=m.id AND h.id_apotek=m.id_apotek
                 WHERE a.status='aktif' $cari_cond
                 ORDER BY a.nama_apotek ASC, m.nama_obat ASC";
}
$obat_result     = mysqli_query($koneksi, $sql_obat);
$apotek_list     = mysqli_query($koneksi, "SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");
$apotek_terpilih = null;
if ($filter_apotek > 0) {
    $apotek_terpilih = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT * FROM apotek WHERE id='$filter_apotek' AND status='aktif' LIMIT 1"));
}

$total_apotek  = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM apotek WHERE status='aktif'"))['c'];
$total_obat_ok = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) c FROM medicines m JOIN apotek a ON m.id_apotek=a.id WHERE m.jumlah>0 AND a.status='aktif'"))['c'];

// Ambil semua apotek dengan koordinat untuk peta
$apotek_peta = [];
$res = mysqli_query($koneksi,
    "SELECT id,nama_apotek,alamat,kota,provinsi,wa_apotek,jam_buka,lat,lng
     FROM apotek WHERE status='aktif' AND lat IS NOT NULL AND lat!=0 AND lng!=0");
while ($ap = mysqli_fetch_assoc($res)) $apotek_peta[] = $ap;
$apotek_json = json_encode($apotek_peta, JSON_UNESCAPED_UNICODE);

// Center peta: pakai apotek terpilih, atau rata-rata semua, atau Indonesia
$map_center_lat = -2.5489;
$map_center_lng = 118.0149;
$map_zoom       = 5;
if ($apotek_terpilih && !empty($apotek_terpilih['lat']) && $apotek_terpilih['lat'] != 0) {
    $map_center_lat = $apotek_terpilih['lat'];
    $map_center_lng = $apotek_terpilih['lng'];
    $map_zoom       = 15;
} elseif (count($apotek_peta) === 1) {
    $map_center_lat = $apotek_peta[0]['lat'];
    $map_center_lng = $apotek_peta[0]['lng'];
    $map_zoom       = 14;
} elseif (count($apotek_peta) > 1) {
    $map_zoom = 7;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Pharma Stock — Cek Stok Obat Seluruh Indonesia</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<!-- Leaflet.js CSS — OpenStreetMap, 100% gratis -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.12);}
.hero-bg{background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 50%,#0f172a 100%);}
.navbar{backdrop-filter:blur(12px);background:rgba(255,255,255,0.96);}
.card-hover:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(29,78,216,0.15);}
.stok-aman   {background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.stok-menipis{background:#fef9c3;color:#854d0e;border:1px solid #fde68a;}
.stok-habis  {background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
.floating-btn{position:fixed;bottom:24px;right:24px;z-index:999;animation:bob 2s ease-in-out infinite;}
@keyframes bob{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.6s ease forwards;}
/* Map container */
#map{width:100%;height:420px;border-radius:1.5rem;z-index:1;}
/* Leaflet popup custom */
.leaflet-popup-content-wrapper{border-radius:1rem;box-shadow:0 10px 30px rgba(0,0,0,0.15);}
.leaflet-popup-content{font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;margin:12px 16px;}
.popup-apotek h4{font-weight:900;font-size:13px;color:#1e293b;margin-bottom:4px;text-transform:uppercase;}
.popup-apotek p{color:#64748b;margin:2px 0;font-size:11px;}
.popup-apotek a{display:inline-block;margin-top:8px;background:#22c55e;color:white;padding:6px 14px;border-radius:20px;font-weight:800;font-size:10px;text-transform:uppercase;letter-spacing:.05em;text-decoration:none;}
.popup-apotek a:hover{background:#16a34a;}
/* Marker custom */
.marker-apotek{
  background:#2563eb;border:3px solid white;border-radius:50% 50% 50% 0;
  width:32px;height:32px;transform:rotate(-45deg);
  box-shadow:0 4px 12px rgba(37,99,235,0.4);
}
.marker-apotek i{transform:rotate(45deg);display:block;text-align:center;line-height:26px;color:white;font-size:13px;}
</style>
</head>
<body class="text-slate-800 min-h-screen">

<!-- NAVBAR -->
<nav class="navbar sticky top-0 z-50 border-b border-slate-100 smooth-shadow">
  <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
        <i class="fas fa-pills text-sm"></i>
      </div>
      <div>
        <span class="font-black text-slate-900 text-sm uppercase">Pharma</span>
        <span class="font-black text-blue-600 text-sm uppercase">Stock</span>
      </div>
    </div>
    <div class="hidden md:flex items-center gap-6 text-xs font-bold text-slate-500">
      <a href="#apotek" class="hover:text-blue-600 transition uppercase tracking-widest">Apotek</a>
      <a href="#stok"   class="hover:text-blue-600 transition uppercase tracking-widest">Stok Obat</a>
      <a href="#maps"   class="hover:text-blue-600 transition uppercase tracking-widest">Lokasi</a>
    </div>
    <a href="login.php" class="bg-blue-600 text-white px-5 py-2 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100">
      <i class="fas fa-sign-in-alt mr-1.5"></i> Staff Login
    </a>
  </div>
</nav>

<!-- HERO -->
<section class="hero-bg py-20 px-6 text-white relative overflow-hidden">
  <div class="absolute inset-0 opacity-10">
    <div class="absolute top-10 right-20 w-64 h-64 bg-white rounded-full blur-3xl"></div>
    <div class="absolute bottom-10 left-10 w-48 h-48 bg-blue-300 rounded-full blur-2xl"></div>
  </div>
  <div class="max-w-4xl mx-auto text-center relative z-10 fade-up">
    <span class="inline-block bg-white/15 border border-white/20 text-[10px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-6 backdrop-blur">
      ✅ Cek Stok Tanpa Login — Seluruh Indonesia
    </span>
    <h1 class="text-4xl md:text-6xl font-black italic uppercase tracking-tighter mb-4 leading-tight">
      Temukan Obat<br><span class="text-blue-300">Terdekat Anda.</span>
    </h1>
    <p class="text-blue-100 text-sm md:text-base font-medium max-w-xl mx-auto mb-10 leading-relaxed">
      Cek ketersediaan obat & harga dari Sabang sampai Merauke, lalu hubungi apotek via WhatsApp.
    </p>
    <form method="GET" action="landing.php#stok" class="flex flex-col md:flex-row gap-3 max-w-2xl mx-auto">
      <input type="text" name="cari" value="<?php echo htmlspecialchars($cari);?>"
        placeholder="🔍  Cari nama obat..."
        class="flex-1 px-6 py-4 rounded-2xl bg-white text-slate-800 font-bold text-sm outline-none placeholder:text-slate-400">
      <select name="apotek" onchange="this.form.submit()"
        class="px-6 py-4 rounded-2xl bg-white/90 text-slate-700 font-bold text-sm outline-none cursor-pointer">
        <option value="0">🏥 Semua Apotek</option>
        <?php mysqli_data_seek($apotek_list,0); while($ap=mysqli_fetch_assoc($apotek_list)):?>
        <option value="<?php echo $ap['id'];?>" <?php echo($filter_apotek==$ap['id'])?'selected':'';?>>
          <?php echo htmlspecialchars($ap['nama_apotek'].' — '.$ap['kota']);?>
        </option>
        <?php endwhile;?>
      </select>
      <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-4 rounded-2xl font-black text-sm uppercase tracking-widest transition shadow-lg">
        Cari
      </button>
    </form>
  </div>
</section>

<!-- STATS BAR -->
<section class="bg-white border-b border-slate-100 py-6">
  <div class="max-w-7xl mx-auto px-6 grid grid-cols-3 gap-4 text-center">
    <div>
      <p class="text-2xl font-black text-blue-600"><?php echo $total_apotek;?></p>
      <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Apotek Aktif</p>
    </div>
    <div>
      <p class="text-2xl font-black text-emerald-600"><?php echo $total_obat_ok;?></p>
      <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Obat Tersedia</p>
    </div>
    <div>
      <p class="text-2xl font-black text-indigo-600">24/7</p>
      <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Info Real-Time</p>
    </div>
  </div>
</section>

<!-- DAFTAR APOTEK CARDS -->
<section id="apotek" class="max-w-7xl mx-auto px-6 py-14 fade-up">
  <div class="mb-8">
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Jaringan Apotek</p>
    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">Apotek <span class="text-blue-600">Terdekat.</span></h2>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php
    $apotek_list2 = mysqli_query($koneksi,"SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");
    while ($ap = mysqli_fetch_assoc($apotek_list2)):
      $jml = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE id_apotek='{$ap['id']}' AND jumlah>0"))['c'];
      $is_active = ($filter_apotek == $ap['id']);
    ?>
    <div class="card-hover bg-white rounded-[2rem] smooth-shadow border-2 p-6 cursor-pointer <?php echo $is_active?'border-blue-500 ring-2 ring-blue-100':'border-slate-50';?>"
         onclick="filterApotek(<?php echo $ap['id'];?>)">
      <div class="flex items-start justify-between mb-4">
        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
          <i class="fas fa-clinic-medical text-xl"></i>
        </div>
        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[9px] font-black uppercase tracking-wider rounded-full border border-emerald-100">Aktif</span>
      </div>
      <h3 class="font-black text-slate-900 text-sm uppercase italic mb-1"><?php echo htmlspecialchars($ap['nama_apotek']);?></h3>
      <p class="text-[10px] text-slate-400 font-bold mb-1">
        <i class="fas fa-map-marker-alt text-rose-400 mr-1"></i>
        <?php echo htmlspecialchars($ap['kota'].', '.$ap['provinsi']);?>
      </p>
      <p class="text-[10px] text-slate-400 font-bold mb-3 leading-relaxed">
        <i class="fas fa-road text-slate-300 mr-1"></i>
        <?php echo htmlspecialchars($ap['alamat']);?>
      </p>
      <div class="flex items-center justify-between mb-4">
        <span class="text-[10px] font-black text-slate-500">
          <i class="fas fa-pills text-blue-400 mr-1"></i><?php echo $jml;?> obat tersedia
        </span>
        <span class="text-[10px] font-bold text-slate-400">
          <i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars($ap['jam_buka']);?>
        </span>
      </div>
      <div class="flex gap-2">
        <?php if (!empty($ap['wa_apotek'])): ?>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $ap['wa_apotek']);?>?text=Halo%20<?php echo urlencode($ap['nama_apotek']);?>%2C%20saya%20ingin%20menanyakan%20ketersediaan%20obat."
           target="_blank" onclick="event.stopPropagation()"
           class="flex-1 flex items-center justify-center gap-2 bg-emerald-500 text-white py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-emerald-600 transition">
          <i class="fab fa-whatsapp"></i> Chat WA
        </a>
        <?php endif;?>
        <?php if (!empty($ap['lat']) && $ap['lat'] != 0): ?>
        <a href="#maps" onclick="event.stopPropagation(); terbangKePeta(<?php echo $ap['lat'];?>,<?php echo $ap['lng'];?>,<?php echo $ap['id'];?>)"
           class="flex-1 flex items-center justify-center gap-2 bg-blue-50 text-blue-600 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-blue-600 hover:text-white transition">
          <i class="fas fa-map-marker-alt"></i> Lihat Peta
        </a>
        <?php else: ?>
        <span class="flex-1 flex items-center justify-center gap-2 bg-slate-50 text-slate-300 py-2 rounded-xl font-black text-[9px] uppercase">
          <i class="fas fa-map-marker-alt"></i> No GPS
        </span>
        <?php endif;?>
      </div>
    </div>
    <?php endwhile;?>
  </div>
</section>

<!-- PETA OPENSTREETMAP -->
<section id="maps" class="max-w-7xl mx-auto px-6 pb-10 fade-up">
  <div class="mb-6">
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Navigasi</p>
    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">
      Lokasi <span class="text-blue-600">Apotek.</span>
    </h2>
  </div>
  <div class="bg-white rounded-[2rem] smooth-shadow border border-slate-100 p-5 overflow-hidden">
    <!-- Tombol aksi -->
    <div class="flex flex-wrap gap-3 mb-4">
      <button onclick="cariLokasiSaya()"
        class="flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100">
        <i class="fas fa-crosshairs" id="iconCari"></i>
        <span id="labelCari">Apotek Terdekat dari Saya</span>
      </button>
      <button onclick="resetPeta()"
        class="flex items-center gap-2 bg-slate-100 text-slate-600 px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition">
        <i class="fas fa-globe"></i> Lihat Semua
      </button>
      <?php if (count($apotek_peta) === 0): ?>
      <div class="flex items-center gap-2 bg-amber-50 text-amber-700 px-4 py-2.5 rounded-xl text-[10px] font-bold border border-amber-100">
        <i class="fas fa-info-circle"></i>
        Isi kolom Latitude & Longitude apotek di Super Admin agar muncul di peta.
      </div>
      <?php endif;?>
    </div>

    <!-- LEAFLET MAP -->
    <div id="map"></div>

    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-3 text-center italic flex items-center justify-center gap-2">
      <img src="https://www.openstreetmap.org/assets/osm_logo-eb1a3a4c5e0ab0c97e54e5adbd7b4f46ff1b9da8a5c8f95ca1d0ecd5c45a7d28.svg"
           alt="OSM" class="h-4 opacity-50" onerror="this.style.display='none'">
      Peta oleh OpenStreetMap — Gratis tanpa API key
    </p>
  </div>
</section>

<!-- TABEL STOK OBAT -->
<section id="stok" class="max-w-7xl mx-auto px-6 pb-20 fade-up">
  <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Inventaris Publik</p>
      <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">
        Stok <span class="text-blue-600">Obat.</span>
        <?php if ($cari): ?>
        <span class="text-sm font-bold text-slate-400 normal-case ml-2">— "<?php echo htmlspecialchars($cari);?>"</span>
        <?php elseif ($apotek_terpilih): ?>
        <span class="text-sm font-bold text-slate-400 normal-case ml-2">— <?php echo htmlspecialchars($apotek_terpilih['nama_apotek']);?></span>
        <?php endif;?>
      </h2>
    </div>
    <div class="flex gap-3">
      <input type="text" id="tableSearch" placeholder="🔍 Filter obat..."
        class="px-5 py-3 rounded-xl bg-white border border-slate-200 text-sm font-bold outline-none w-full md:w-56 smooth-shadow focus:ring-2 focus:ring-blue-400 transition">
      <?php if ($filter_apotek || $cari): ?>
      <a href="landing.php" class="px-5 py-3 rounded-xl bg-slate-100 text-slate-500 font-black text-xs uppercase tracking-widest hover:bg-rose-50 hover:text-rose-500 transition whitespace-nowrap">
        Reset
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
            $has_data = true;
            if ($row['jumlah'] <= 0)      { $badge='stok-habis';    $label='Habis'; }
            elseif ($row['jumlah'] <= 15) { $badge='stok-menipis';  $label='Menipis'; }
            else                          { $badge='stok-aman';     $label='Tersedia'; }
            $harga_fmt = $row['harga_jual']>0
                ? 'Rp '.number_format($row['harga_jual'],0,',','.').' / '.$row['satuan']
                : '—';
            $wa_msg = "Halo%20".urlencode($row['nama_apotek']??'')
                    . "%2C%20apakah%20obat%20*".urlencode($row['nama_obat'])
                    . "*%20masih%20tersedia%3F%20Berapa%20harganya%3F";
          ?>
          <tr class="hover:bg-blue-50/20 transition-colors obat-row"
              data-nama="<?php echo strtolower($row['nama_obat']);?>"
              data-apotek="<?php echo strtolower($row['nama_apotek']??'');?>">
            <td class="p-5 font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($row['nama_obat']);?></td>
            <td class="p-5">
              <div class="text-[10px] font-bold text-slate-600"><?php echo htmlspecialchars($row['nama_apotek']??'—');?></div>
              <div class="text-[9px] text-slate-400"><?php echo htmlspecialchars(($row['kota']??'').', '.($row['provinsi']??''));?></div>
            </td>
            <td class="p-5 text-center">
              <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase"><?php echo htmlspecialchars($row['kategori']);?></span>
            </td>
            <td class="p-5 text-center font-black text-slate-700 text-xs"><?php echo $harga_fmt;?></td>
            <td class="p-5 text-center font-black text-slate-800 text-sm"><?php echo $row['jumlah'];?></td>
            <td class="p-5 text-center">
              <span class="<?php echo $badge;?> px-3 py-1 text-[9px] font-black rounded-full uppercase tracking-tighter"><?php echo $label;?></span>
            </td>
            <td class="p-5 text-center">
              <?php if (!empty($row['wa_apotek'])): ?>
              <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $row['wa_apotek']);?>?text=<?php echo $wa_msg;?>"
                 target="_blank"
                 class="inline-flex items-center gap-1.5 bg-emerald-500 text-white px-4 py-1.5 rounded-full text-[9px] font-black hover:bg-emerald-600 transition uppercase tracking-widest shadow-sm">
                <i class="fab fa-whatsapp"></i> Tanya
              </a>
              <?php else: ?>
              <span class="text-[9px] text-slate-300 font-bold italic">—</span>
              <?php endif;?>
            </td>
          </tr>
          <?php endwhile;?>
          <?php if (!$has_data): ?>
          <tr>
            <td colspan="7" class="p-16 text-center text-slate-400 font-bold italic text-[10px] uppercase tracking-widest">
              <?php echo $cari ? "Obat \"".htmlspecialchars($cari)."\" tidak ditemukan." : "Belum ada data stok.";?>
            </td>
          </tr>
          <?php endif;?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="bg-slate-900 text-white py-12 px-6">
  <div class="max-w-7xl mx-auto text-center">
    <div class="flex items-center justify-center gap-3 mb-4">
      <div class="w-8 h-8 bg-blue-600 rounded-xl flex items-center justify-center">
        <i class="fas fa-pills text-sm"></i>
      </div>
      <span class="font-black text-xl uppercase tracking-tight">Pharma<span class="text-blue-400">Stock</span></span>
    </div>
    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-2">
      &copy; 2026 PharmaStock • Informasi Stok Obat Apotek Seluruh Indonesia
    </p>
    <p class="text-slate-600 text-[10px] mb-4">Peta menggunakan OpenStreetMap © Kontributor OSM</p>
    <a href="login.php" class="text-blue-400 text-xs font-bold hover:text-blue-300 transition uppercase tracking-widest">
      <i class="fas fa-sign-in-alt mr-1"></i> Login Staff / Admin
    </a>
  </div>
</footer>

<!-- FLOATING WA -->
<div class="floating-btn">
  <a href="https://wa.me/" target="_blank"
     class="w-14 h-14 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full flex items-center justify-center shadow-2xl shadow-emerald-300 transition">
    <i class="fab fa-whatsapp text-2xl"></i>
  </a>
</div>

<script>
// ============================================================
// DATA APOTEK DARI DATABASE (untuk marker peta)
// ============================================================
const apotekData  = <?php echo $apotek_json; ?>;
const centerLat   = <?php echo $map_center_lat; ?>;
const centerLng   = <?php echo $map_center_lng; ?>;
const centerZoom  = <?php echo $map_zoom; ?>;

// ============================================================
// INISIALISASI LEAFLET MAP (OpenStreetMap - GRATIS)
// ============================================================
const map = L.map('map', { zoomControl: true }).setView([centerLat, centerLng], centerZoom);

// Tile layer OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors',
    maxZoom: 19
}).addTo(map);

// Icon custom untuk marker apotek
const apotekIcon = L.divIcon({
    className: '',
    html: '<div class="marker-apotek"><i class="fas fa-clinic-medical"></i></div>',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -36]
});

// Tambahkan marker untuk setiap apotek
const markers = {};
apotekData.forEach(ap => {
    if (!ap.lat || !ap.lng) return;

    const waBtn = ap.wa_apotek
        ? `<a href="https://wa.me/${ap.wa_apotek.replace(/[^0-9]/g,'')}?text=Halo%20${encodeURIComponent(ap.nama_apotek)}%2C%20saya%20ingin%20menanyakan%20ketersediaan%20obat." target="_blank">
             <i class="fab fa-whatsapp"></i> Chat WhatsApp
           </a>`
        : '';

    const popup = `
        <div class="popup-apotek">
          <h4>${ap.nama_apotek}</h4>
          <p><i class="fas fa-map-marker-alt" style="color:#ef4444;margin-right:4px"></i>${ap.alamat}, ${ap.kota}</p>
          <p><i class="fas fa-map-pin" style="color:#8b5cf6;margin-right:4px"></i>${ap.provinsi}</p>
          <p><i class="fas fa-clock" style="color:#3b82f6;margin-right:4px"></i>${ap.jam_buka}</p>
          ${waBtn}
        </div>`;

    const marker = L.marker([parseFloat(ap.lat), parseFloat(ap.lng)], { icon: apotekIcon })
        .addTo(map)
        .bindPopup(popup, { maxWidth: 260 });

    markers[ap.id] = marker;
});

// ============================================================
// FUNGSI TERBANG KE APOTEK TERTENTU (dari klik card)
// ============================================================
function terbangKePeta(lat, lng, id) {
    map.flyTo([lat, lng], 16, { animate: true, duration: 1.2 });
    if (markers[id]) setTimeout(() => markers[id].openPopup(), 1300);
    document.getElementById('maps').scrollIntoView({ behavior: 'smooth' });
}

// ============================================================
// CARI APOTEK TERDEKAT DARI LOKASI PENGGUNA
// ============================================================
function cariLokasiSaya() {
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung geolokasi.'); return;
    }
    const icon  = document.getElementById('iconCari');
    const label = document.getElementById('labelCari');
    icon.className  = 'fas fa-spinner fa-spin';
    label.textContent = 'Mendeteksi lokasi...';

    navigator.geolocation.getCurrentPosition(
        pos => {
            icon.className  = 'fas fa-crosshairs';
            label.textContent = 'Apotek Terdekat dari Saya';

            const { latitude: lat, longitude: lng } = pos.coords;

            // Marker lokasi pengguna
            const userIcon = L.divIcon({
                className: '',
                html: '<div style="width:16px;height:16px;background:#3b82f6;border:3px solid white;border-radius:50%;box-shadow:0 0 0 6px rgba(59,130,246,0.3)"></div>',
                iconSize: [16, 16], iconAnchor: [8, 8]
            });
            L.marker([lat, lng], { icon: userIcon })
             .addTo(map)
             .bindPopup('<b style="font-family:Plus Jakarta Sans">📍 Lokasi Anda</b>')
             .openPopup();

            // Hitung apotek terdekat
            let nearest = null, minDist = Infinity;
            apotekData.forEach(ap => {
                if (!ap.lat || !ap.lng) return;
                const d = Math.sqrt(Math.pow(ap.lat - lat, 2) + Math.pow(ap.lng - lng, 2));
                if (d < minDist) { minDist = d; nearest = ap; }
            });

            // Zoom ke area yang mencakup user + semua apotek
            if (apotekData.length > 0) {
                const bounds = L.latLngBounds([[lat, lng]]);
                apotekData.forEach(ap => { if(ap.lat&&ap.lng) bounds.extend([ap.lat, ap.lng]); });
                map.flyToBounds(bounds.pad(0.2), { animate: true, duration: 1.5 });
            } else {
                map.flyTo([lat, lng], 13, { animate: true, duration: 1.2 });
            }

            if (nearest) {
                setTimeout(() => {
                    if (markers[nearest.id]) markers[nearest.id].openPopup();
                }, 2000);
            }

            document.getElementById('maps').scrollIntoView({ behavior: 'smooth' });
        },
        err => {
            icon.className  = 'fas fa-crosshairs';
            label.textContent = 'Apotek Terdekat dari Saya';
            const msg = {1:'Izin lokasi ditolak.',2:'Lokasi tidak tersedia.',3:'Waktu habis.'};
            alert((msg[err.code]||'Gagal.') + '\nAktifkan izin lokasi di browser Anda.');
        },
        { timeout: 10000, maximumAge: 300000 }
    );
}

// ============================================================
// RESET PETA KE TAMPILAN INDONESIA
// ============================================================
function resetPeta() {
    if (apotekData.length > 1) {
        const bounds = L.latLngBounds(apotekData.filter(a=>a.lat&&a.lng).map(a=>[a.lat,a.lng]));
        if (bounds.isValid()) { map.flyToBounds(bounds.pad(0.3), {duration:1.2}); return; }
    }
    map.flyTo([-2.5489, 118.0149], 5, { animate: true, duration: 1.2 });
}

// ============================================================
// FILTER APOTEK (klik card)
// ============================================================
function filterApotek(id) {
    const cari = document.getElementById('tableSearch')?.value || '';
    window.location.href = `landing.php?apotek=${id}&cari=${encodeURIComponent(cari)}#stok`;
}

// ============================================================
// SEARCH REAL-TIME DI TABEL
// ============================================================
document.getElementById('tableSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.obat-row').forEach(row => {
        row.style.display = (row.dataset.nama + ' ' + row.dataset.apotek).includes(q) ? '' : 'none';
    });
});

// Jika ada apotek terpilih dengan koordinat, buka popup-nya
<?php if ($apotek_terpilih && !empty($apotek_terpilih['lat']) && $apotek_terpilih['lat'] != 0): ?>
window.addEventListener('load', () => {
    setTimeout(() => {
        const m = markers[<?php echo $apotek_terpilih['id'];?>];
        if (m) m.openPopup();
    }, 800);
});
<?php endif;?>
</script>
</body>
</html>