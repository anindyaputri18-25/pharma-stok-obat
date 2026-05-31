<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? '';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

if (!in_array($role, ['Admin','Manager Gudang'])) {
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>"; exit();
}

$ap_where = $id_apotek ? "WHERE m.id_apotek='$id_apotek'" : "";
$data = mysqli_query($koneksi,
    "SELECT m.*, IFNULL(h.harga_jual,0) AS harga_jual, IFNULL(h.satuan,'tablet') AS satuan
     FROM medicines m
     LEFT JOIN harga_obat h ON h.id_obat=m.id AND h.id_apotek=m.id_apotek
     $ap_where ORDER BY m.nama_obat ASC");

// WA order otomatis
function wa_order($wa, $nama_obat, $jumlah, $nama_apotek='') {
    $clean = preg_replace('/[^0-9]/','', $wa);
    $msg = "Halo, kami dari *$nama_apotek* ingin memesan:\n*Nama Obat:* $nama_obat\n*Stok Sisa:* $jumlah\nMohon konfirmasi ketersediaan 🙏";
    return "https://wa.me/$clean?text=".urlencode($msg);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Laporan - Pharma Stock</title>
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
@media print {
  aside,.print-hidden{display:none!important;}
  body{background:white!important;}
  main{padding:0!important;}
  .no-print-shadow{box-shadow:none!important;border:1px solid #e2e8f0!important;}
}
</style>
</head>
<body class="text-slate-800 flex min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

<header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 print-hidden fade-up">
  <div>
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">
      <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Inventory Summary'); ?>
    </p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Rekap <span class="text-blue-600">Laporan.</span></h1>
  </div>
  <div class="flex items-center gap-3 print-hidden">
    <button onclick="window.print()"
      class="bg-slate-900 text-white px-6 py-2.5 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 shadow-xl shadow-slate-200 transition flex items-center gap-2">
      <i class="fas fa-print"></i> Cetak Dokumen
    </button>
    <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
      <div class="flex flex-col items-end px-3">
        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
        <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
      </div>
      <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-black text-sm border-2 border-white shadow-inner">
        <?php echo strtoupper(substr($users,0,1)); ?>
      </div>
    </div>
  </div>
</header>

<!-- HERO LAPORAN -->
<div class="hero-bg relative overflow-hidden p-10 rounded-[3rem] text-white mb-8 smooth-shadow no-print-shadow fade-up">
  <div class="relative z-10 flex items-center gap-6">
    <div class="w-16 h-16 bg-white/15 backdrop-blur border border-white/20 rounded-3xl flex items-center justify-center shrink-0">
      <i class="fas fa-file-medical text-3xl text-white"></i>
    </div>
    <div>
      <h2 class="text-2xl font-black italic mb-1">Laporan Inventaris & Supplier</h2>
      <p class="text-blue-100 text-xs font-medium opacity-90">
        <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Semua Apotek'); ?> •
        Data Per: <span class="font-black"><?php echo date('d F Y'); ?></span>
      </p>
    </div>
  </div>
  <i class="fas fa-pills absolute -right-10 -bottom-10 text-9xl opacity-10 rotate-12"></i>
</div>

<!-- TABEL LAPORAN -->
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden no-print-shadow fade-up">
  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-50/50 border-b border-slate-100">
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Produk & Kategori</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Supplier</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Jual</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Expired</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center print-hidden">Order WA</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php
        if (mysqli_num_rows($data) > 0):
          while ($row = mysqli_fetch_assoc($data)):
            if ($row['jumlah'] <= 0)      { $badge = 'bg-rose-50 text-rose-600 border border-rose-100';     $label = 'Habis'; }
            elseif ($row['jumlah'] <= 15) { $badge = 'bg-amber-50 text-amber-600 border border-amber-100';  $label = 'Menipis'; }
            else                          { $badge = 'bg-emerald-50 text-emerald-600 border border-emerald-100'; $label = 'Aman'; }
            $supplier   = htmlspecialchars($row['supplier'] ?: 'N/A');
            $wa         = htmlspecialchars($row['wa_supplier'] ?: '');
            $harga_fmt  = $row['harga_jual'] > 0 ? 'Rp '.number_format($row['harga_jual'],0,',','.') : '—';
            $exp_fmt    = !empty($row['expired_date']) ? date('d M Y', strtotime($row['expired_date'])) : '—';
            $nm_apotek  = $apotek['nama_apotek'] ?? 'Apotek';
        ?>
        <tr class="hover:bg-blue-50/20 transition-colors">
          <td class="p-5">
            <div class="font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($row['nama_obat']); ?></div>
            <div class="text-[9px] text-blue-500 font-extrabold uppercase tracking-widest mt-0.5"><?php echo htmlspecialchars($row['kategori']); ?></div>
          </td>
          <td class="p-5">
            <div class="text-[11px] font-bold text-slate-600"><?php echo $supplier; ?></div>
            <?php if($wa): ?>
            <div class="text-[9px] text-slate-400 font-medium mt-0.5"><?php echo $wa; ?></div>
            <?php endif; ?>
          </td>
          <td class="p-5 text-center font-black text-slate-700 text-xs"><?php echo $harga_fmt; ?></td>
          <td class="p-5 text-center font-black text-slate-800 text-sm"><?php echo $row['jumlah']; ?></td>
          <td class="p-5 text-center text-[10px] font-bold text-slate-500"><?php echo $exp_fmt; ?></td>
          <td class="p-5 text-center">
            <span class="px-3 py-1 <?php echo $badge; ?> text-[9px] font-black rounded-full uppercase tracking-tighter"><?php echo $label; ?></span>
          </td>
          <td class="p-5 text-center print-hidden">
            <?php if ($wa): ?>
            <a href="<?php echo wa_order($wa, $row['nama_obat'], $row['jumlah'], $nm_apotek); ?>"
               target="_blank"
               class="inline-flex items-center gap-2 bg-emerald-500 text-white px-4 py-1.5 rounded-full text-[9px] font-black hover:bg-emerald-600 hover:scale-105 transition shadow-sm shadow-emerald-100 uppercase tracking-widest">
              <i class="fab fa-whatsapp text-xs"></i> Order
            </a>
            <?php else: ?>
            <span class="text-[9px] text-slate-300 font-bold italic uppercase">No Contact</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; else: ?>
        <tr>
          <td colspan="7" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
            Belum ada data inventaris.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">
    &copy; 2026 Pharma Stock • Validated Inventory Report • Confidential
  </p>
</footer>
</main>
</body>
</html>