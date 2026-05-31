<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? 'Guest';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

// Filter apotek (Super Admin bisa pilih)
$filter_ap = ($role === 'Super Admin' && isset($_GET['apotek'])) ? (int)$_GET['apotek'] : $id_apotek;

$can_edit = !in_array($role, ['Kasir']);

// TAMBAH OBAT
if ($can_edit && isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_obat']);
    $kat  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $qty  = (int)$_POST['jumlah'];
    $exp  = $_POST['expired_date'];
    $supp = mysqli_real_escape_string($koneksi, $_POST['supplier']);
    $wa   = mysqli_real_escape_string($koneksi, $_POST['wa_supplier']);
    $ap   = $filter_ap ? "'$filter_ap'" : 'NULL';

    if (mysqli_query($koneksi,
        "INSERT INTO medicines (nama_obat,kategori,jumlah,expired_date,supplier,wa_supplier,id_apotek)
         VALUES ('$nama','$kat','$qty','$exp','$supp','$wa',$ap)")) {
        catat_log($koneksi,'Tambah Obat',"Nama:$nama, Qty:$qty, Supplier:$supp",$id_apotek);
        $pesan = 'success:Obat berhasil ditambahkan!';
    } else {
        $pesan = 'error:Gagal menambah obat.';
    }
}

// HAPUS OBAT
if ($can_edit && isset($_GET['hapus'])) {
    $hid  = (int)$_GET['hapus'];
    $nm   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT nama_obat FROM medicines WHERE id='$hid'"))['nama_obat'] ?? '-';
    mysqli_query($koneksi,"DELETE FROM medicines WHERE id='$hid'");
    catat_log($koneksi,'Hapus Obat',"Nama:$nm",$id_apotek);
    header("Location: stok_obat.php?msg=hapus"); exit();
}
if (isset($_GET['msg']) && $_GET['msg']==='hapus') $pesan = 'success:Obat berhasil dihapus.';

// QUERY DATA
$w = $filter_ap ? "WHERE m.id_apotek='$filter_ap'" : ($id_apotek ? "WHERE m.id_apotek='$id_apotek'" : "");
$data_obat = mysqli_query($koneksi,
    "SELECT m.*, IFNULL(h.harga_jual,0) AS harga_jual, IFNULL(h.satuan,'tablet') AS satuan
     FROM medicines m
     LEFT JOIN harga_obat h ON h.id_obat=m.id AND h.id_apotek=m.id_apotek
     $w ORDER BY m.nama_obat ASC");

$wc = $id_apotek ? " AND id_apotek='$id_apotek'" : "";
$stok_aman    = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE jumlah>15$wc"))['c'];
$stok_menipis = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE jumlah>0 AND jumlah<=15$wc"))['c'];
$stok_habis   = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE jumlah<=0$wc"))['c'];

function wa_order_url($wa,$nama_obat,$jumlah,$nama_apotek='') {
    $clean = preg_replace('/[^0-9]/','', $wa);
    $msg   = "Halo, kami dari *$nama_apotek* ingin memesan stok:\n*Nama Obat:* $nama_obat\n*Stok Sisa:* $jumlah\nMohon konfirmasi ketersediaan 🙏";
    return "https://wa.me/$clean?text=".urlencode($msg);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Stok Obat - Pharma Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
.hero-bg{background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 50%,#0f172a 100%);}
.card-hover:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(29,78,216,0.15);}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.5s ease forwards;}
.fade-up-2{animation-delay:.1s;opacity:0;}
.fade-up-3{animation-delay:.2s;opacity:0;}
</style>
</head>
<body class="text-slate-800 flex min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

<!-- HEADER -->
<header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 fade-up">
  <div>
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">
      <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Inventory Management'); ?>
    </p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
      Stok <span class="text-blue-600">Obat.</span>
    </h1>
  </div>
  <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
    <div class="flex flex-col items-end px-3">
      <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
      <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
    </div>
    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-black text-sm border-2 border-white shadow-inner">
      <?php echo strtoupper(substr($users,0,1)); ?>
    </div>
  </div>
</header>

<!-- NOTIFIKASI -->
<?php if (isset($pesan)): [$t,$m]=explode(':',$pesan,2);
  $bg=$t==='success'?'bg-emerald-50 text-emerald-700 border-emerald-100':'bg-rose-50 text-rose-700 border-rose-100';
  $ic=$t==='success'?'fa-check-circle':'fa-exclamation-circle';
?>
<div class="mb-6 p-4 <?php echo $bg;?> border rounded-2xl text-xs font-bold flex items-center gap-2 fade-up">
  <i class="fas <?php echo $ic;?>"></i> <?php echo htmlspecialchars($m);?>
</div>
<?php endif;?>

<!-- STATS -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8 fade-up fade-up-2">
  <?php foreach([
    ['val'=>$stok_aman,   'label'=>'Stok Aman',   'icon'=>'fa-check-circle',       'c'=>'emerald'],
    ['val'=>$stok_menipis,'label'=>'Stok Menipis', 'icon'=>'fa-hourglass-half',     'c'=>'amber'],
    ['val'=>$stok_habis,  'label'=>'Out of Stock', 'icon'=>'fa-exclamation-triangle','c'=>'rose'],
  ] as $s): ?>
  <div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex items-center gap-5 group hover:bg-<?php echo $s['c'];?>-500 transition card-hover">
    <div class="w-12 h-12 bg-<?php echo $s['c'];?>-50 text-<?php echo $s['c'];?>-500 rounded-2xl flex items-center justify-center group-hover:bg-white/20 group-hover:text-white">
      <i class="fas <?php echo $s['icon'];?> text-xl"></i>
    </div>
    <div>
      <h3 class="text-2xl font-black text-slate-800 group-hover:text-white"><?php echo $s['val'];?></h3>
      <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest group-hover:text-white/80"><?php echo $s['label'];?></p>
    </div>
  </div>
  <?php endforeach;?>
</div>

<!-- FORM TAMBAH OBAT -->
<?php if ($can_edit): ?>
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-8 mb-8 fade-up fade-up-2">
  <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
    <span class="w-7 h-7 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center"><i class="fas fa-plus text-xs"></i></span>
    Tambah Obat Baru
  </h3>
  <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Obat *</label>
      <input type="text" name="nama_obat" required placeholder="Paracetamol 500mg"
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Kategori *</label>
      <select name="kategori" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
        <?php foreach(['Obat Bebas','Obat Bebas Terbatas','Obat Keras','Obat Tradisional'] as $k):?>
        <option><?php echo $k;?></option><?php endforeach;?>
      </select>
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Jumlah Stok *</label>
      <input type="number" name="jumlah" min="0" required placeholder="100"
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Tanggal Expired *</label>
      <input type="date" name="expired_date" required
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Supplier</label>
      <input type="text" name="supplier" placeholder="PT Kimia Farma"
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">WA Supplier (628xxx)</label>
      <div class="flex gap-2">
        <input type="text" name="wa_supplier" placeholder="6281234567890"
          class="flex-1 p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
        <button name="tambah" type="submit"
          class="bg-slate-900 text-white px-5 py-3 rounded-2xl text-[9px] font-black hover:bg-blue-600 transition shadow-lg active:scale-95 uppercase tracking-widest whitespace-nowrap">
          + Tambah
        </button>
      </div>
    </div>
  </form>
</div>
<?php endif;?>

<!-- TABEL OBAT -->
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up fade-up-3">
  <!-- Search bar -->
  <div class="p-5 border-b border-slate-50 flex items-center justify-between gap-4">
    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Daftar Inventaris</h3>
    <div class="relative">
      <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
      <input type="text" id="cariObat" placeholder="Cari nama obat..."
        class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-100 rounded-full text-xs font-bold outline-none focus:ring-2 focus:ring-blue-400 w-48 md:w-64 transition">
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-left">
      <thead class="bg-slate-50/50 border-b border-slate-100">
        <tr>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Kategori</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Jual</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Expired</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Supplier</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50" id="tbodyObat">
        <?php
        $has_data = false;
        while ($row = mysqli_fetch_assoc($data_obat)):
          $has_data = true;
          if ($row['jumlah'] <= 0)      { $badge='bg-rose-50 text-rose-600 border border-rose-100';       $label='Habis'; }
          elseif ($row['jumlah'] <= 15) { $badge='bg-amber-50 text-amber-600 border border-amber-100';    $label='Menipis'; }
          else                          { $badge='bg-emerald-50 text-emerald-600 border border-emerald-100'; $label='Aman'; }
          $harga_fmt = $row['harga_jual']>0 ? 'Rp '.number_format($row['harga_jual'],0,',','.') : '—';
          $exp_fmt   = !empty($row['expired_date']) ? date('d M Y',strtotime($row['expired_date'])) : '—';
          $nm_apotek = $apotek['nama_apotek'] ?? 'Apotek';
        ?>
        <tr class="hover:bg-blue-50/20 transition-colors obat-row">
          <td class="p-5">
            <div class="font-black text-slate-800 text-xs uppercase italic obat-nama"><?php echo htmlspecialchars($row['nama_obat']);?></div>
          </td>
          <td class="p-5 text-center">
            <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase tracking-tighter">
              <?php echo htmlspecialchars($row['kategori']);?>
            </span>
          </td>
          <td class="p-5 text-center font-black text-slate-700 text-xs"><?php echo $harga_fmt;?></td>
          <td class="p-5 text-center font-black text-slate-800 text-lg"><?php echo $row['jumlah'];?></td>
          <td class="p-5 text-center text-[10px] font-bold text-slate-500"><?php echo $exp_fmt;?></td>
          <td class="p-5 text-center">
            <span class="px-3 py-1 <?php echo $badge;?> text-[9px] font-black rounded-full uppercase tracking-tighter"><?php echo $label;?></span>
          </td>
          <td class="p-5">
            <div class="text-[10px] font-bold text-slate-600"><?php echo htmlspecialchars($row['supplier']?:'—');?></div>
            <?php if (!empty($row['wa_supplier'])): ?>
            <a href="<?php echo wa_order_url($row['wa_supplier'],$row['nama_obat'],$row['jumlah'],$nm_apotek);?>"
               target="_blank"
               class="inline-flex items-center gap-1 mt-1 text-emerald-500 hover:text-emerald-700 text-[9px] font-black uppercase tracking-wider">
              <i class="fab fa-whatsapp"></i> Order Stok
            </a>
            <?php endif;?>
          </td>
          <td class="p-5 text-center">
            <div class="flex items-center justify-center gap-2">
              <?php if ($can_edit): ?>
              <a href="edit_obat.php?id=<?php echo $row['id'];?>"
                 class="w-8 h-8 bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white rounded-xl flex items-center justify-center transition" title="Edit">
                <i class="fas fa-edit text-[10px]"></i>
              </a>
              <a href="stok_obat.php?hapus=<?php echo $row['id'];?>"
                 onclick="return confirm('Hapus obat <?php echo addslashes($row['nama_obat']);?>?')"
                 class="w-8 h-8 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl flex items-center justify-center transition" title="Hapus">
                <i class="fas fa-trash text-[10px]"></i>
              </a>
              <?php else: ?>
              <span class="text-[9px] text-slate-300 font-bold italic uppercase tracking-wider">Read Only</span>
              <?php endif;?>
            </div>
          </td>
        </tr>
        <?php endwhile;
        if (!$has_data): ?>
        <tr>
          <td colspan="8" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
            Belum ada data obat terdaftar.
          </td>
        </tr>
        <?php endif;?>
      </tbody>
    </table>
  </div>
</div>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Inventory Intelligence</p>
</footer>
</main>
<script>
document.getElementById('cariObat').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('.obat-row').forEach(row => {
    const nama = row.querySelector('.obat-nama').textContent.toLowerCase();
    row.style.display = nama.includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>