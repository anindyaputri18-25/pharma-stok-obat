<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? '';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

// Kasir hanya bisa lihat, Admin & Manager Gudang bisa edit
$bisa_edit = in_array($role, ['Admin','Manager Gudang','Super Admin']);
if (!in_array($role, ['Admin','Manager Gudang','Kasir','Super Admin'])) {
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>"; exit();
}

// SIMPAN / UPDATE HARGA (hanya role yang bisa edit)
if ($bisa_edit && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['simpan_harga'])) {
    $id_obat    = (int)$_POST['id_obat'];
    $harga_beli = (float)str_replace(['.',',' ],['','.'], $_POST['harga_beli']);
    $harga_jual = (float)str_replace(['.',',' ],['','.'], $_POST['harga_jual']);
    $satuan     = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $ap_id      = $id_apotek ? (int)$id_apotek : 0;

    if ($ap_id > 0) {
        $sql = "INSERT INTO harga_obat (id_obat,id_apotek,harga_beli,harga_jual,satuan)
                VALUES ('$id_obat','$ap_id','$harga_beli','$harga_jual','$satuan')
                ON DUPLICATE KEY UPDATE harga_beli='$harga_beli',harga_jual='$harga_jual',satuan='$satuan'";
        if (mysqli_query($koneksi, $sql)) {
            $nm = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT nama_obat FROM medicines WHERE id='$id_obat'"))['nama_obat']??'-';
            catat_log($koneksi,'Update Harga Obat',"Obat: $nm | Jual: Rp$harga_jual",$id_apotek);
            $pesan = 'success:Harga berhasil disimpan!';
        } else { $pesan = 'error:Gagal menyimpan harga.'; }
    } else { $pesan = 'error:Apotek belum diset untuk akun ini.'; }
}

// HAPUS HARGA
if ($bisa_edit && isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    mysqli_query($koneksi,"DELETE FROM harga_obat WHERE id='$hid'");
    catat_log($koneksi,'Hapus Harga Obat',"ID: $hid",$id_apotek);
    header("Location: harga_obat.php?msg=hapus"); exit();
}
if (isset($_GET['msg']) && $_GET['msg']==='hapus') $pesan = 'success:Harga berhasil dihapus.';

// QUERY DATA
$ap_cond = $id_apotek ? "WHERE h.id_apotek='$id_apotek'" : "";
$harga_result = mysqli_query($koneksi,
    "SELECT h.*, m.nama_obat, m.kategori, a.nama_apotek
     FROM harga_obat h
     JOIN medicines m ON h.id_obat=m.id
     JOIN apotek a ON h.id_apotek=a.id
     $ap_cond ORDER BY m.nama_obat ASC");

$obat_list = $id_apotek
    ? mysqli_query($koneksi,"SELECT * FROM medicines WHERE id_apotek='$id_apotek' ORDER BY nama_obat ASC")
    : mysqli_query($koneksi,"SELECT * FROM medicines ORDER BY nama_obat ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Harga Obat - Pharma Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
.hero-bg{background:linear-gradient(135deg,#ea580c 0%,#f59e0b 60%,#0f172a 100%);}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.5s ease forwards;}
</style>
</head>
<body class="text-slate-800 flex min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

<header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 fade-up">
  <div>
    <p class="text-orange-500 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">
      <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Price Management'); ?>
    </p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
      Harga <span class="text-orange-500">Obat.</span>
      <?php if ($role==='Kasir'): ?>
      <span class="text-sm font-bold text-slate-400 normal-case ml-2">— Mode Lihat</span>
      <?php endif; ?>
    </h1>
  </div>
  <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
    <div class="flex flex-col items-end px-3">
      <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
      <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
    </div>
    <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white font-black text-sm border-2 border-white shadow-inner">
      <?php echo strtoupper(substr($users,0,1)); ?>
    </div>
  </div>
</header>

<!-- HERO -->
<div class="hero-bg relative overflow-hidden p-10 rounded-[3rem] text-white mb-8 smooth-shadow fade-up">
  <div class="flex items-center gap-6 relative z-10">
    <div class="w-16 h-16 bg-white/15 backdrop-blur border border-white/20 rounded-3xl flex items-center justify-center shrink-0">
      <i class="fas fa-tags text-3xl text-white"></i>
    </div>
    <div>
      <h2 class="text-2xl font-black italic mb-1">
        <?php echo $bisa_edit ? 'Kelola Harga Beli & Jual' : 'Daftar Harga Obat'; ?>
      </h2>
      <p class="text-orange-100 text-xs font-medium opacity-90">
        <?php echo $bisa_edit
          ? 'Atur harga beli dari supplier dan harga jual ke pasien.'
          : 'Informasi harga obat untuk pelayanan pasien.'; ?>
      </p>
    </div>
  </div>
  <i class="fas fa-dollar-sign absolute -right-8 -bottom-8 text-[10rem] opacity-10"></i>
</div>

<!-- NOTIFIKASI -->
<?php if (isset($pesan)): [$t,$m]=explode(':',$pesan,2);
  $bg=$t==='success'?'bg-emerald-50 text-emerald-700 border-emerald-100':'bg-rose-50 text-rose-700 border-rose-100';
  $ic=$t==='success'?'fa-check-circle':'fa-exclamation-circle';
?>
<div class="mb-6 p-4 <?php echo $bg;?> border rounded-2xl text-xs font-bold flex items-center gap-2 fade-up">
  <i class="fas <?php echo $ic;?>"></i> <?php echo htmlspecialchars($m);?>
</div>
<?php endif;?>

<!-- FORM TAMBAH HARGA (hanya role bisa edit) -->
<?php if ($bisa_edit): ?>
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-8 mb-8 fade-up">
  <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
    <span class="w-7 h-7 bg-orange-100 text-orange-500 rounded-lg flex items-center justify-center">
      <i class="fas fa-plus text-xs"></i>
    </span>
    Tambah / Update Harga
  </h3>
  <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
    <div class="lg:col-span-2">
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Obat</label>
      <select name="id_obat" required class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-400 transition">
        <option value="">-- Pilih Obat --</option>
        <?php while($ob=mysqli_fetch_assoc($obat_list)):?>
        <option value="<?php echo $ob['id'];?>"><?php echo htmlspecialchars($ob['nama_obat']);?></option>
        <?php endwhile;?>
      </select>
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Harga Beli (Rp)</label>
      <input type="number" name="harga_beli" min="0" step="100" placeholder="0" required
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-400 transition">
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Harga Jual (Rp)</label>
      <input type="number" name="harga_jual" min="0" step="100" placeholder="0" required
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-400 transition">
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Satuan</label>
      <div class="flex gap-2">
        <select name="satuan" class="flex-1 p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-orange-400 transition">
          <?php foreach(['tablet','kapsul','botol','sachet','strip','tube','ampul'] as $s):?>
          <option><?php echo $s;?></option>
          <?php endforeach;?>
        </select>
        <button name="simpan_harga" type="submit"
          class="bg-slate-900 text-white px-4 py-3 rounded-2xl text-[9px] font-black hover:bg-orange-500 transition shadow-lg active:scale-95 uppercase whitespace-nowrap">
          Simpan
        </button>
      </div>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- TABEL HARGA -->
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
  <div class="overflow-x-auto">
    <table class="w-full text-left">
      <thead class="bg-slate-50/50 border-b border-slate-100">
        <tr>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Kategori</th>
          <?php if ($bisa_edit):?>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Beli</th>
          <?php endif;?>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga Jual</th>
          <?php if ($bisa_edit):?>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Margin</th>
          <?php endif;?>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Satuan</th>
          <?php if ($bisa_edit):?>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
          <?php endif;?>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php if (mysqli_num_rows($harga_result) > 0):
          while ($h=mysqli_fetch_assoc($harga_result)):
            $margin = $h['harga_jual'] - $h['harga_beli'];
            $pct    = $h['harga_beli']>0 ? round(($margin/$h['harga_beli'])*100,1) : 0;
            $mc     = $margin >= 0 ? 'text-emerald-600' : 'text-rose-600';
        ?>
        <tr class="hover:bg-orange-50/20 transition-colors">
          <td class="p-5">
            <div class="font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($h['nama_obat']);?></div>
          </td>
          <td class="p-5 text-center">
            <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase"><?php echo htmlspecialchars($h['kategori']);?></span>
          </td>
          <?php if ($bisa_edit):?>
          <td class="p-5 text-center font-bold text-slate-600 text-xs">Rp <?php echo number_format($h['harga_beli'],0,',','.');?></td>
          <?php endif;?>
          <td class="p-5 text-center font-black text-slate-800 text-sm">Rp <?php echo number_format($h['harga_jual'],0,',','.');?></td>
          <?php if ($bisa_edit):?>
          <td class="p-5 text-center font-black text-xs <?php echo $mc;?>">
            <?php echo ($margin>=0?'+':'').number_format($margin,0,',','.');?>
            <span class="text-[9px] font-bold opacity-70">(<?php echo $pct;?>%)</span>
          </td>
          <?php endif;?>
          <td class="p-5 text-center text-[10px] font-bold text-slate-500 uppercase"><?php echo htmlspecialchars($h['satuan']);?></td>
          <?php if ($bisa_edit):?>
          <td class="p-5 text-center">
            <a href="harga_obat.php?hapus=<?php echo $h['id'];?>"
               onclick="return confirm('Hapus data harga ini?')"
               class="w-8 h-8 bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl flex items-center justify-center mx-auto transition">
              <i class="fas fa-trash text-[10px]"></i>
            </a>
          </td>
          <?php endif;?>
        </tr>
        <?php endwhile; else:?>
        <tr>
          <td colspan="<?php echo $bisa_edit?7:3;?>" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
            <?php echo $bisa_edit ? 'Belum ada data harga. Tambahkan di atas.' : 'Belum ada data harga tersedia.';?>
          </td>
        </tr>
        <?php endif;?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($role==='Kasir'): ?>
<div class="mt-6 p-4 bg-blue-50 border border-blue-100 rounded-2xl flex items-center gap-3 text-xs font-bold text-blue-600 fade-up">
  <i class="fas fa-info-circle"></i>
  Sebagai Kasir, Anda dapat melihat daftar harga untuk keperluan pelayanan pasien. Perubahan harga hanya dapat dilakukan oleh Admin atau Manager Gudang.
</div>
<?php endif; ?>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Price Intelligence</p>
</footer>
</main>
</body>
</html>