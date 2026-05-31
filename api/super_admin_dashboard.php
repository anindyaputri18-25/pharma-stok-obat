<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users = $_COOKIE['users'] ?? '';
$role  = $role_saat_ini;

if ($role !== 'Super Admin') { header("Location: dashboard.php"); exit(); }

if (isset($_GET['set_apotek'])) {
    setcookie('sa_apotek', (int)$_GET['set_apotek'], time()+86400, '/');
    $_COOKIE['sa_apotek'] = (int)$_GET['set_apotek'];
    header("Location: super_admin_dashboard.php"); exit();
}
$sa_apotek_id = isset($_COOKIE['sa_apotek']) ? (int)$_COOKIE['sa_apotek'] : 0;
$sa_apotek    = $sa_apotek_id ? get_apotek($koneksi, $sa_apotek_id) : null;

if (isset($_POST['tambah_apotek'])) {
    $f=['nama_apotek','alamat','kota','provinsi','telp','wa_apotek','jam_buka'];
    $v=[]; foreach($f as $k) $v[$k]=mysqli_real_escape_string($koneksi,$_POST[$k]??'');
    $lat=(float)($_POST['lat']??0); $lng=(float)($_POST['lng']??0);
    mysqli_query($koneksi,"INSERT INTO apotek (nama_apotek,alamat,kota,provinsi,telp,wa_apotek,lat,lng,jam_buka) VALUES ('{$v['nama_apotek']}','{$v['alamat']}','{$v['kota']}','{$v['provinsi']}','{$v['telp']}','{$v['wa_apotek']}','$lat','$lng','{$v['jam_buka']}')");
    catat_log($koneksi,'Tambah Apotek',"Apotek: {$v['nama_apotek']}");
    header("Location: super_admin_dashboard.php?msg=tambah"); exit();
}
if (isset($_GET['hapus_apotek'])) {
    $aid=(int)$_GET['hapus_apotek'];
    mysqli_query($koneksi,"DELETE FROM apotek WHERE id='$aid'");
    header("Location: super_admin_dashboard.php?msg=hapus"); exit();
}
if (isset($_GET['toggle'])) {
    $aid=(int)$_GET['toggle'];
    $cur=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT status FROM apotek WHERE id='$aid'"))['status'];
    $new=($cur==='aktif')?'nonaktif':'aktif';
    mysqli_query($koneksi,"UPDATE apotek SET status='$new' WHERE id='$aid'");
    header("Location: super_admin_dashboard.php"); exit();
}

$apotek_list  =mysqli_query($koneksi,"SELECT * FROM apotek ORDER BY nama_apotek ASC");
$total_apotek =mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM apotek"))['c'];
$total_users  =mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM users WHERE role NOT IN ('Pending','Super Admin')"))['c'];
$total_obat   =mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines"))['c'];
$total_racikan=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM racikan"))['c'];
$msgs=['tambah'=>'Apotek berhasil ditambahkan!','hapus'=>'Apotek berhasil dihapus.'];
$pesan=isset($_GET['msg'])&&isset($msgs[$_GET['msg']])?'success:'.$msgs[$_GET['msg']]:'';
$provinsi_list=['Aceh','Sumatera Utara','Sumatera Barat','Riau','Kepulauan Riau','Jambi','Bengkulu','Sumatera Selatan','Kepulauan Bangka Belitung','Lampung','DKI Jakarta','Jawa Barat','Banten','Jawa Tengah','DI Yogyakarta','Jawa Timur','Bali','Nusa Tenggara Barat','Nusa Tenggara Timur','Kalimantan Barat','Kalimantan Tengah','Kalimantan Selatan','Kalimantan Timur','Kalimantan Utara','Sulawesi Utara','Gorontalo','Sulawesi Tengah','Sulawesi Barat','Sulawesi Selatan','Sulawesi Tenggara','Maluku','Maluku Utara','Papua Barat','Papua','Papua Selatan','Papua Tengah','Papua Pegunungan','Papua Barat Daya'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Super Admin - Pharma Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
.sa-hero{background:linear-gradient(135deg,#1e293b 0%,#334155 50%,#0f172a 100%);}
.card-hover:hover{transform:translateY(-3px);box-shadow:0 20px 40px rgba(139,153,178,0.18);}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.5s ease forwards;}
</style>
</head>
<body class="text-slate-800 flex min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

<header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6 fade-up">
  <div>
    <p class="text-amber-500 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">System Control Center</p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Super <span class="text-amber-500">Admin.</span></h1>
  </div>
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-100 smooth-shadow">
      <i class="fas fa-clinic-medical text-amber-500 ml-2 text-sm"></i>
      <select onchange="window.location='super_admin_dashboard.php?set_apotek='+this.value" class="text-xs font-black text-slate-700 bg-transparent outline-none pr-2 cursor-pointer min-w-[160px]">
        <option value="0" <?php echo !$sa_apotek_id?'selected':''; ?>>🌏 Semua Apotek</option>
        <?php mysqli_data_seek($apotek_list,0); while($ap=mysqli_fetch_assoc($apotek_list)): ?>
        <option value="<?php echo $ap['id']; ?>" <?php echo($sa_apotek_id==$ap['id'])?'selected':''; ?>><?php echo htmlspecialchars($ap['nama_apotek']); ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow">
      <div class="flex flex-col items-end px-3">
        <p class="text-[9px] text-amber-500 font-black uppercase tracking-widest">⭐ Super Admin</p>
        <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
      </div>
      <div class="w-10 h-10 bg-amber-400 rounded-full flex items-center justify-center text-slate-900 font-black text-sm border-2 border-white shadow-inner"><?php echo strtoupper(substr($users,0,1)); ?></div>
    </div>
  </div>
</header>

<?php if($pesan):[$t,$m]=explode(':',$pesan,2);?>
<div class="mb-6 p-4 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-2xl text-xs font-bold flex items-center gap-2 fade-up">
  <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($m);?>
</div>
<?php endif;?>

<?php if($sa_apotek):?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 flex items-center justify-between fade-up">
  <div class="flex items-center gap-3">
    <div class="w-8 h-8 bg-amber-400 rounded-xl flex items-center justify-center text-slate-900"><i class="fas fa-clinic-medical text-sm"></i></div>
    <div>
      <p class="text-[9px] font-black text-amber-600 uppercase tracking-widest">Filter Aktif</p>
      <p class="font-black text-slate-800 text-sm"><?php echo htmlspecialchars($sa_apotek['nama_apotek'].' — '.$sa_apotek['kota'].', '.$sa_apotek['provinsi']);?></p>
    </div>
  </div>
  <a href="super_admin_dashboard.php?set_apotek=0" class="text-[9px] font-black text-amber-600 hover:text-amber-800 uppercase tracking-widest flex items-center gap-1"><i class="fas fa-times"></i> Hapus Filter</a>
</div>
<?php endif;?>

<div class="sa-hero relative overflow-hidden p-10 rounded-[3rem] text-white mb-8 smooth-shadow fade-up">
  <div class="relative z-10">
    <span class="bg-white/10 border border-white/20 text-[9px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-4 inline-block">👑 Super Admin Control</span>
    <h2 class="text-3xl font-black italic mb-2">Halo, <?php echo htmlspecialchars($users);?>!</h2>
    <p class="text-slate-300 text-sm max-w-lg">Monitor dan kelola seluruh jaringan apotek Pharma Stock di seluruh Indonesia.</p>
  </div>
  <i class="fas fa-network-wired absolute -right-10 -bottom-10 text-[14rem] opacity-5"></i>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-10 fade-up">
<?php $stats=[['label'=>'Total Apotek','val'=>$total_apotek,'icon'=>'fa-clinic-medical','color'=>'amber'],['label'=>'Total User','val'=>$total_users,'icon'=>'fa-users','color'=>'blue'],['label'=>'Total Obat','val'=>$total_obat,'icon'=>'fa-pills','color'=>'emerald'],['label'=>'Total Racikan','val'=>$total_racikan,'icon'=>'fa-mortar-pestle','color'=>'purple']];
foreach($stats as $s):?>
<div class="bg-white p-6 rounded-[2rem] smooth-shadow border border-slate-50 flex flex-col items-center text-center card-hover">
  <div class="w-12 h-12 bg-<?php echo $s['color'];?>-50 text-<?php echo $s['color'];?>-600 rounded-2xl flex items-center justify-center mb-3"><i class="fas <?php echo $s['icon'];?> text-xl"></i></div>
  <h3 class="text-3xl font-black text-slate-800"><?php echo $s['val'];?></h3>
  <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1"><?php echo $s['label'];?></p>
</div>
<?php endforeach;?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-10 fade-up">
<?php $nav_items=[['href'=>'super_admin_users.php','icon'=>'fa-users-cog','label'=>'Semua User','sub'=>'Kelola & assign user ke apotek','color'=>'blue'],['href'=>'activity_log.php','icon'=>'fa-history','label'=>'Log Aktivitas','sub'=>'Audit trail seluruh apotek','color'=>'indigo'],['href'=>'landing.php','icon'=>'fa-globe','label'=>'Landing Page','sub'=>'Halaman publik pembeli','color'=>'emerald','target'=>'_blank'],['href'=>'super_admin_dashboard.php','icon'=>'fa-clinic-medical','label'=>'Kelola Apotek','sub'=>'Tambah, edit, nonaktifkan','color'=>'amber']];
foreach($nav_items as $n):?>
<a href="<?php echo $n['href'];?>" <?php echo isset($n['target'])?'target="'.$n['target'].'"':'';?>
   class="flex items-center justify-between bg-white p-7 rounded-[2rem] smooth-shadow border border-slate-50 hover:border-<?php echo $n['color'];?>-400 group card-hover">
  <div class="flex items-center gap-5">
    <div class="w-14 h-14 bg-<?php echo $n['color'];?>-50 text-<?php echo $n['color'];?>-600 rounded-2xl flex items-center justify-center group-hover:bg-<?php echo $n['color'];?>-600 group-hover:text-white"><i class="fas <?php echo $n['icon'];?> text-xl"></i></div>
    <div><h5 class="font-black text-slate-800 uppercase text-xs tracking-wide"><?php echo $n['label'];?></h5><p class="text-[10px] text-slate-400 font-medium"><?php echo $n['sub'];?></p></div>
  </div>
  <i class="fas fa-chevron-right text-slate-200 group-hover:text-<?php echo $n['color'];?>-500 group-hover:translate-x-1"></i>
</a>
<?php endforeach;?>
</div>

<div class="mb-6 flex items-center justify-between fade-up">
  <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest"><?php echo $sa_apotek?'Apotek Terpilih':'Semua Apotek Terdaftar';?></h2>
  <button onclick="document.getElementById('formTA').classList.toggle('hidden')" class="bg-amber-400 text-slate-900 px-5 py-2 rounded-full font-black text-[9px] uppercase tracking-widest hover:bg-amber-500 shadow-lg shadow-amber-100">
    <i class="fas fa-plus mr-1"></i> Tambah Apotek
  </button>
</div>

<div id="formTA" class="hidden bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-8 mb-8 fade-up">
  <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php $fields=[['n'=>'nama_apotek','l'=>'Nama Apotek *','p'=>'Apotek Sehat Farma','s'=>1],['n'=>'alamat','l'=>'Alamat Lengkap *','p'=>'Jl. Pahlawan No.12','s'=>2],['n'=>'kota','l'=>'Kota/Kabupaten *','p'=>'Jakarta Pusat','s'=>1],['n'=>'telp','l'=>'Telepon','p'=>'021-5551234','s'=>1],['n'=>'wa_apotek','l'=>'WA (628xxx)','p'=>'6281234567890','s'=>1],['n'=>'lat','l'=>'Latitude','p'=>'-6.2088','s'=>1,'t'=>'number'],['n'=>'lng','l'=>'Longitude','p'=>'106.8456','s'=>1,'t'=>'number'],['n'=>'jam_buka','l'=>'Jam Buka','p'=>'08:00 - 21:00','s'=>1]];
    foreach($fields as $f): $span=($f['s']??1)==2?'lg:col-span-2':''; $type=$f['t']??'text'; $step=$type==='number'?'step="0.0000001"':'';?>
    <div class="<?php echo $span;?>">
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block"><?php echo $f['l'];?></label>
      <input type="<?php echo $type;?>" name="<?php echo $f['n'];?>" <?php echo $step;?> placeholder="<?php echo $f['p'];?>"
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
    </div>
    <?php endforeach;?>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Provinsi *</label>
      <select name="provinsi" class="w-full p-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-amber-400 transition">
        <?php foreach($provinsi_list as $prov):?><option><?php echo $prov;?></option><?php endforeach;?>
      </select>
    </div>
    <div class="lg:col-span-3">
      <button name="tambah_apotek" type="submit" class="bg-amber-400 text-slate-900 px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-amber-500 shadow-lg shadow-amber-100 active:scale-95">
        <i class="fas fa-plus-circle mr-2"></i> Tambah Apotek
      </button>
    </div>
  </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 fade-up">
<?php mysqli_data_seek($apotek_list,0); while($ap=mysqli_fetch_assoc($apotek_list)):
  if($sa_apotek_id && $ap['id']!=$sa_apotek_id) continue;
  $staff=mysqli_query($koneksi,"SELECT username,role FROM users WHERE id_apotek='{$ap['id']}' AND role IN ('Admin','Manager Gudang','Apoteker','Kasir') ORDER BY role ASC");
  $jml_obat=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE id_apotek='{$ap['id']}'"))['c'];
  $stok_h=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM medicines WHERE id_apotek='{$ap['id']}' AND jumlah<=0"))['c'];
?>
<div class="bg-white rounded-[2rem] smooth-shadow border border-slate-50 p-6 card-hover">
  <div class="flex items-start justify-between mb-4">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600"><i class="fas fa-clinic-medical text-xl"></i></div>
      <div>
        <h3 class="font-black text-slate-900 text-sm uppercase italic"><?php echo htmlspecialchars($ap['nama_apotek']);?></h3>
        <p class="text-[9px] text-slate-400 font-bold uppercase"><?php echo htmlspecialchars($ap['kota'].', '.$ap['provinsi']);?></p>
      </div>
    </div>
    <a href="super_admin_dashboard.php?toggle=<?php echo $ap['id'];?>"
       class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider <?php echo $ap['status']==='aktif'?'bg-emerald-50 text-emerald-700 hover:bg-emerald-500 hover:text-white':'bg-slate-100 text-slate-500 hover:bg-blue-500 hover:text-white';?> transition">
      <?php echo $ap['status']==='aktif'?'✓ Aktif':'✗ Nonaktif';?>
    </a>
  </div>
  <div class="text-[10px] text-slate-500 font-bold space-y-1 mb-4">
    <p><i class="fas fa-map-marker-alt text-rose-400 mr-2"></i><?php echo htmlspecialchars($ap['alamat']);?></p>
    <p><i class="fas fa-clock text-blue-400 mr-2"></i><?php echo htmlspecialchars($ap['jam_buka']);?></p>
    <?php if($ap['wa_apotek']):?><p><i class="fab fa-whatsapp text-emerald-500 mr-2"></i><?php echo htmlspecialchars($ap['wa_apotek']);?></p><?php endif;?>
  </div>
  <div class="flex gap-3 mb-4">
    <div class="flex-1 bg-blue-50 rounded-xl p-3 text-center"><p class="text-lg font-black text-blue-600"><?php echo $jml_obat;?></p><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Jenis Obat</p></div>
    <div class="flex-1 bg-rose-50 rounded-xl p-3 text-center"><p class="text-lg font-black text-rose-500"><?php echo $stok_h;?></p><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Stok Habis</p></div>
  </div>
  <div class="border-t border-slate-50 pt-4 mb-4">
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Staff</p>
    <?php if(mysqli_num_rows($staff)>0): while($s=mysqli_fetch_assoc($staff)):
      $bc=match($s['role']){'Admin'=>'bg-rose-50 text-rose-600','Manager Gudang'=>'bg-amber-50 text-amber-700','Apoteker'=>'bg-purple-50 text-purple-600','Kasir'=>'bg-emerald-50 text-emerald-700',default=>'bg-slate-100 text-slate-500'};?>
    <div class="flex items-center justify-between py-1">
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 font-black text-[9px]"><?php echo strtoupper(substr($s['username'],0,1));?></div>
        <span class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($s['username']);?></span>
      </div>
      <span class="px-2 py-0.5 <?php echo $bc;?> text-[9px] font-black rounded-full uppercase"><?php echo htmlspecialchars($s['role']);?></span>
    </div>
    <?php endwhile; else:?><p class="text-[10px] text-slate-300 italic font-bold">Belum ada staff.</p><?php endif;?>
  </div>
  <div class="flex gap-2 pt-3 border-t border-slate-50">
    <?php if($ap['wa_apotek']):?>
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','', $ap['wa_apotek']);?>?text=Halo%20<?php echo urlencode($ap['nama_apotek']);?>%2C%20ini%20Super%20Admin%20Pharma%20Stock." target="_blank"
       class="flex-1 flex items-center justify-center gap-2 bg-emerald-50 text-emerald-600 py-2 rounded-xl font-black text-[9px] uppercase hover:bg-emerald-500 hover:text-white transition">
      <i class="fab fa-whatsapp"></i> Hubungi
    </a>
    <?php endif;?>
    <a href="super_admin_dashboard.php?hapus_apotek=<?php echo $ap['id'];?>"
       onclick="return confirm('Hapus apotek ini?')"
       class="flex-1 flex items-center justify-center gap-2 bg-rose-50 text-rose-500 py-2 rounded-xl font-black text-[9px] uppercase hover:bg-rose-500 hover:text-white transition">
      <i class="fas fa-trash"></i> Hapus
    </a>
  </div>
</div>
<?php endwhile;?>
</div>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Super Admin Control</p>
</footer>
</main>
</body>
</html>