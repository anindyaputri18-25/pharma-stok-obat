<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? 'Guest';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);

if ($role === 'Kasir') { header("Location: stok_obat.php"); exit(); }
if (!isset($_GET['id'])) { header("Location: stok_obat.php"); exit(); }

$id    = (int)$_GET['id'];
$query = mysqli_query($koneksi,"SELECT * FROM medicines WHERE id='$id'");
$data  = mysqli_fetch_assoc($query);
if (!$data) { header("Location: stok_obat.php"); exit(); }

if (isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($koneksi,$_POST['nama_obat']);
    $kat  = mysqli_real_escape_string($koneksi,$_POST['kategori']);
    $qty  = (int)$_POST['jumlah'];
    $exp  = $_POST['expired_date'];
    $supp = mysqli_real_escape_string($koneksi,$_POST['supplier']);
    $wa   = mysqli_real_escape_string($koneksi,$_POST['wa_supplier']);
    $sql  = "UPDATE medicines SET nama_obat='$nama',kategori='$kat',jumlah='$qty',expired_date='$exp',supplier='$supp',wa_supplier='$wa' WHERE id='$id'";
    if (mysqli_query($koneksi,$sql)) {
        catat_log($koneksi,'Edit Obat',"ID:$id, Nama:$nama, Qty:$qty",$id_apotek);
        echo "<script>alert('Data berhasil diupdate!'); window.location='stok_obat.php';</script>";
    } else {
        echo "<script>alert('Gagal update data!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Edit Obat - Pharma Stock</title>
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
</style>
</head>
<body class="text-slate-800 flex min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[900px] mx-auto w-full">

<header class="flex justify-between items-center mb-8 fade-up">
  <div>
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Inventory Management</p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
      Edit <span class="text-blue-600">Obat.</span>
    </h1>
  </div>
  <a href="stok_obat.php"
     class="flex items-center gap-2 text-slate-500 font-black text-[10px] uppercase tracking-widest hover:text-blue-600 transition bg-white px-5 py-2.5 rounded-full smooth-shadow border border-slate-100">
    <i class="fas fa-arrow-left text-xs"></i> Kembali
  </a>
</header>

<!-- HERO -->
<div class="hero-bg relative overflow-hidden p-8 rounded-[2.5rem] text-white mb-8 smooth-shadow fade-up">
  <div class="flex items-center gap-5 relative z-10">
    <div class="w-14 h-14 bg-white/15 backdrop-blur border border-white/20 rounded-2xl flex items-center justify-center shrink-0">
      <i class="fas fa-pills text-2xl text-white"></i>
    </div>
    <div>
      <h2 class="text-xl font-black italic mb-1"><?php echo htmlspecialchars($data['nama_obat']);?></h2>
      <p class="text-blue-100 text-xs opacity-90">ID: #<?php echo $data['id'];?> • Perbarui informasi stok obat di bawah.</p>
    </div>
  </div>
  <i class="fas fa-edit absolute -right-8 -bottom-8 text-[10rem] opacity-10"></i>
</div>

<!-- FORM -->
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-10 fade-up">
  <form method="POST" class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="md:col-span-2">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Obat *</label>
        <input type="text" name="nama_obat" value="<?php echo htmlspecialchars($data['nama_obat']);?>" required
          class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm font-bold">
      </div>
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Kategori *</label>
        <div class="relative">
          <select name="kategori" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition appearance-none cursor-pointer text-sm font-bold">
            <?php foreach(['Obat Bebas','Obat Bebas Terbatas','Obat Keras','Obat Tradisional'] as $k):?>
            <option value="<?php echo $k;?>" <?php echo($data['kategori']===$k)?'selected':'';?>><?php echo $k;?></option>
            <?php endforeach;?>
          </select>
          <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none text-xs"></i>
        </div>
      </div>
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Jumlah Stok *</label>
        <input type="number" name="jumlah" value="<?php echo $data['jumlah'];?>" required min="0"
          class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm font-bold">
      </div>
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Supplier</label>
        <input type="text" name="supplier" value="<?php echo htmlspecialchars($data['supplier']);?>" placeholder="PT Kimia Farma"
          class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm font-bold">
      </div>
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">WhatsApp Supplier (628xxx)</label>
        <input type="text" name="wa_supplier" value="<?php echo htmlspecialchars($data['wa_supplier']);?>" placeholder="6281234567890"
          class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm font-bold">
      </div>
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Tanggal Kadaluarsa *</label>
        <input type="date" name="expired_date" value="<?php echo $data['expired_date'];?>" required
          class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm font-bold">
      </div>
    </div>

    <div class="flex items-center gap-4 pt-4 border-t border-slate-50">
      <a href="stok_obat.php"
         class="flex-1 text-center py-4 text-slate-400 font-bold hover:text-slate-600 transition uppercase text-[10px] tracking-widest rounded-2xl hover:bg-slate-50">
        <i class="fas fa-arrow-left mr-1"></i> Batal
      </a>
      <button name="update" type="submit"
        class="flex-1 bg-slate-900 text-white py-4 rounded-2xl font-black shadow-lg shadow-slate-200 hover:bg-blue-600 active:scale-95 transition uppercase text-[10px] tracking-widest">
        Simpan Perubahan <i class="fas fa-check-circle ml-1"></i>
      </button>
    </div>
  </form>
</div>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock</p>
</footer>
</main>
</body>
</html>