<?php
session_start();
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$role      = $role_saat_ini;
$users     = $_COOKIE['users'];
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

if (!in_array($role, ['Admin','Apoteker'])) {
    header("Location: dashboard.php"); exit();
}

$ap_where  = $id_apotek ? "WHERE id_apotek='$id_apotek'" : "";
$query_obat = mysqli_query($koneksi, "SELECT * FROM medicines $ap_where ORDER BY nama_obat ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tambah Racikan - Pharma Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
.hero-bg{background:linear-gradient(135deg,#7c3aed 0%,#4338ca 50%,#0f172a 100%);}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.5s ease forwards;}
</style>
</head>
<body class="text-slate-800 flex min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1200px] mx-auto w-full">

<header class="flex justify-between items-center mb-8 fade-up">
  <div>
    <p class="text-purple-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">
      <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Pharmacist Compounding'); ?>
    </p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
      Buat <span class="text-purple-600">Racikan Baru.</span>
    </h1>
  </div>
  <a href="racikan_obat.php"
     class="flex items-center gap-2 text-slate-500 font-black text-[10px] uppercase tracking-widest hover:text-purple-600 transition bg-white px-5 py-2.5 rounded-full smooth-shadow border border-slate-100">
    <i class="fas fa-arrow-left text-xs"></i> Kembali
  </a>
</header>

<!-- HERO -->
<div class="hero-bg relative overflow-hidden p-8 rounded-[2.5rem] text-white mb-8 smooth-shadow fade-up">
  <div class="flex items-center gap-5 relative z-10">
    <div class="w-14 h-14 bg-white/15 backdrop-blur border border-white/20 rounded-2xl flex items-center justify-center shrink-0">
      <i class="fas fa-plus text-2xl text-white"></i>
    </div>
    <div>
      <h2 class="text-xl font-black italic mb-1">Formula Racikan Baru</h2>
      <p class="text-purple-100 text-xs opacity-90">Pilih bahan baku, tentukan takaran, dan simpan formula racikan.</p>
    </div>
  </div>
  <i class="fas fa-flask absolute -right-8 -bottom-8 text-[8rem] opacity-10"></i>
</div>

<!-- FORM -->
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-10 fade-up">
  <form action="proses_tambah_racikan.php" method="POST">

    <!-- INFO DASAR -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Nama Racikan *</label>
        <input type="text" name="nama_racikan" required placeholder="Contoh: Puyer Flu Anak"
          class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-purple-500 focus:bg-white transition">
      </div>
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Tipe Racikan *</label>
        <select name="tipe_racikan"
          class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-purple-500 transition">
          <option value="Puyer">Puyer</option>
          <option value="Sirup">Sirup</option>
          <option value="Salep">Salep</option>
          <option value="Kapsul">Kapsul</option>
        </select>
      </div>
    </div>

    <!-- PILIH BAHAN BAKU -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pilih Bahan Baku</label>
        <div class="relative">
          <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
          <input type="text" id="cariObat" placeholder="Cari nama obat..."
            class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-100 rounded-full text-xs font-bold focus:ring-2 focus:ring-purple-400 outline-none w-56">
        </div>
      </div>

      <div class="bg-slate-50 rounded-2xl border border-slate-100 p-4 max-h-80 overflow-y-auto" id="kontainerObat">
        <div class="space-y-2">
          <?php
          $has_obat = false;
          while ($obat = mysqli_fetch_assoc($query_obat)):
            $has_obat = true;
          ?>
          <div class="item-obat flex items-center justify-between bg-white p-3.5 rounded-xl shadow-sm border border-slate-50 hover:border-purple-200 transition"
               data-nama="<?php echo strtolower($obat['nama_obat']); ?>">
            <div class="flex items-center gap-3">
              <input type="checkbox" name="obat_dipilih[]" value="<?php echo $obat['id']; ?>"
                     id="check-<?php echo $obat['id']; ?>" class="hidden-checkbox w-4 h-4 text-purple-600 rounded">
              <div>
                <span class="text-xs font-black text-slate-700"><?php echo htmlspecialchars($obat['nama_obat']); ?></span>
                <span class="ml-2 text-[9px] font-bold text-slate-400 uppercase"><?php echo htmlspecialchars($obat['kategori']); ?></span>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-[10px] font-black text-slate-400">
                Stok: <span class="<?php echo $obat['jumlah']<=15?'text-rose-500':'text-emerald-600'; ?>"><?php echo $obat['jumlah']; ?></span>
              </span>
              <div class="flex items-center bg-slate-100 rounded-xl p-1 gap-1">
                <span class="text-[9px] text-slate-400 font-bold px-2">Pakai:</span>
                <input type="number" name="jumlah_pakai[<?php echo $obat['id']; ?>]"
                       data-nama-obat="<?php echo htmlspecialchars($obat['nama_obat']); ?>"
                       oninput="updateKeterangan(this, <?php echo $obat['id']; ?>)"
                       placeholder="0" min="0" max="<?php echo $obat['jumlah']; ?>"
                       class="w-14 bg-white border border-slate-200 rounded-lg text-center text-xs font-black focus:ring-2 focus:ring-purple-400 outline-none p-1">
              </div>
            </div>
          </div>
          <?php endwhile; ?>
          <?php if (!$has_obat): ?>
          <div class="text-center py-8 text-slate-400 font-bold text-[10px] uppercase italic">
            Belum ada data obat. <a href="stok_obat.php" class="text-purple-500 underline">Tambah obat dulu.</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- STOK & KETERANGAN -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Total Stok Hasil Racikan *</label>
        <input type="number" name="stok_racikan" required placeholder="Misal: 10" min="1"
          class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-purple-500 focus:bg-white transition">
      </div>
      <div>
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Keterangan (Otomatis Terisi)</label>
        <textarea name="keterangan" id="keteranganOtomatis" rows="3"
          placeholder="Pilih bahan obat untuk mengisi otomatis..."
          class="w-full bg-slate-50 border border-slate-100 rounded-2xl p-4 text-xs italic font-medium text-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:bg-white transition resize-none"></textarea>
      </div>
    </div>

    <!-- SUBMIT -->
    <button type="submit"
      class="w-full bg-purple-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-purple-700 shadow-lg shadow-purple-100 transition active:scale-[0.98]">
      <i class="fas fa-check-circle mr-2"></i> Simpan & Proses Racikan
    </button>
  </form>
</div>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Apothecary Division</p>
</footer>
</main>

<script>
const inputCari = document.getElementById('cariObat');
const itemsObat = document.querySelectorAll('.item-obat');

inputCari.addEventListener('input', function() {
  const q = this.value.toLowerCase();
  itemsObat.forEach(item => {
    item.style.display = item.dataset.nama.includes(q) ? 'flex' : 'none';
  });
});

function updateKeterangan(input, id) {
  const checkbox = document.getElementById('check-' + id);
  checkbox.checked = input.value > 0;

  let list = [];
  document.querySelectorAll('input[name^="jumlah_pakai"]').forEach(inp => {
    if (inp.value > 0) list.push(inp.dataset.namaObat + ' (' + inp.value + ')');
  });
  document.getElementById('keteranganOtomatis').value =
    list.length > 0 ? 'Komposisi: ' + list.join(', ') + '.' : '';
}
</script>
</body>
</html>