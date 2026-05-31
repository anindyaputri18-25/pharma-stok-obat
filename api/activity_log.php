<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? '';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);

if (!in_array($role, ['Admin','Super Admin'])) {
    echo "<script>alert('Akses Ditolak! Hanya Admin & Super Admin.'); window.location='dashboard.php';</script>"; exit();
}

// BERSIHKAN LOG LAMA (Super Admin only)
if (isset($_GET['bersihkan']) && $role === 'Super Admin') {
    $hari = (int)$_GET['bersihkan'];
    mysqli_query($koneksi,"DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL $hari DAY)");
    header("Location: activity_log.php?msg=bersih"); exit();
}

// FILTER
$f_date = isset($_GET['tgl'])  ? mysqli_real_escape_string($koneksi,$_GET['tgl'])  : '';
$f_user = isset($_GET['user']) ? mysqli_real_escape_string($koneksi,$_GET['user']) : '';
$f_aksi = isset($_GET['aksi']) ? mysqli_real_escape_string($koneksi,$_GET['aksi']) : '';

$where = [];
if ($role === 'Admin' && $id_apotek) $where[] = "al.id_apotek='$id_apotek'";
if ($f_date) $where[] = "DATE(al.created_at)='$f_date'";
if ($f_user) $where[] = "al.username LIKE '%$f_user%'";
if ($f_aksi) $where[] = "al.aksi LIKE '%$f_aksi%'";
$wsql  = $where ? 'WHERE '.implode(' AND ',$where) : '';

$log_result   = mysqli_query($koneksi,
    "SELECT al.*,a.nama_apotek FROM activity_log al
     LEFT JOIN apotek a ON al.id_apotek=a.id
     $wsql ORDER BY al.created_at DESC LIMIT 500");
$total_log    = mysqli_num_rows($log_result);
$today_cond   = "WHERE DATE(created_at)=CURDATE()".($role==='Admin'&&$id_apotek?" AND id_apotek='$id_apotek'":'');
$total_hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM activity_log $today_cond"))['c'];

$aksi_warna = [
    'login'=>'bg-emerald-50 text-emerald-700','logout'=>'bg-slate-100 text-slate-600',
    'hapus'=>'bg-rose-50 text-rose-600',       'tambah'=>'bg-blue-50 text-blue-600',
    'update'=>'bg-amber-50 text-amber-700',    'buka'=>'bg-indigo-50 text-indigo-600',
    'edit'=>'bg-amber-50 text-amber-700',
];
if (isset($_GET['msg'])&&$_GET['msg']==='bersih') $pesan='success:Log lama berhasil dibersihkan.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Log Aktivitas - Pharma Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
.hero-bg{background:linear-gradient(135deg,#1e293b 0%,#334155 50%,#0f172a 100%);}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.5s ease forwards;}
</style>
</head>
<body class="text-slate-800 flex min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

<!-- HEADER -->
<header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 fade-up">
  <div>
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">System Audit Trail</p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
      Log <span class="text-blue-600">Aktivitas.</span>
    </h1>
  </div>
  <div class="flex items-center gap-3">
    <?php if ($role==='Super Admin'): ?>
    <a href="activity_log.php?bersihkan=30"
       onclick="return confirm('Hapus log lebih dari 30 hari?')"
       class="bg-rose-50 text-rose-600 px-4 py-2 rounded-full font-black text-[9px] uppercase tracking-widest hover:bg-rose-500 hover:text-white transition shadow-sm">
      <i class="fas fa-trash mr-1"></i> Bersihkan Log Lama
    </a>
    <?php endif;?>
    <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
      <div class="flex flex-col items-end px-3">
        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role;?></p>
        <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users);?></p>
      </div>
      <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-black text-sm border-2 border-white shadow-inner">
        <?php echo strtoupper(substr($users,0,1));?>
      </div>
    </div>
  </div>
</header>

<!-- HERO -->
<div class="hero-bg relative overflow-hidden p-10 rounded-[3rem] text-white mb-8 smooth-shadow fade-up">
  <div class="flex items-center gap-6 relative z-10">
    <div class="w-16 h-16 bg-white/15 backdrop-blur border border-white/20 rounded-3xl flex items-center justify-center shrink-0">
      <i class="fas fa-history text-3xl text-white"></i>
    </div>
    <div>
      <h2 class="text-2xl font-black italic mb-1">Audit Trail Sistem</h2>
      <p class="text-slate-300 text-xs font-medium opacity-90">
        <?php echo $role==='Super Admin' ? 'Semua aktivitas dari seluruh apotek.' : 'Aktivitas di apotek Anda.'; ?>
      </p>
    </div>
  </div>
  <i class="fas fa-shield-alt absolute -right-8 -bottom-8 text-[10rem] opacity-10"></i>
</div>

<?php if(isset($pesan)):[$t,$m]=explode(':',$pesan,2);?>
<div class="mb-6 p-4 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-2xl text-xs font-bold flex items-center gap-2 fade-up">
  <i class="fas fa-check-circle"></i><?php echo htmlspecialchars($m);?>
</div>
<?php endif;?>

<!-- STATS -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 fade-up">
  <div class="bg-white p-5 rounded-2xl smooth-shadow border border-slate-50 text-center">
    <h4 class="text-3xl font-black text-blue-600"><?php echo $total_log;?></h4>
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Hasil Filter</p>
  </div>
  <div class="bg-white p-5 rounded-2xl smooth-shadow border border-slate-50 text-center">
    <h4 class="text-3xl font-black text-emerald-600"><?php echo $total_hari_ini;?></h4>
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Hari Ini</p>
  </div>
</div>

<!-- FILTER FORM -->
<form method="GET" class="bg-white rounded-[2rem] smooth-shadow border border-slate-50 p-6 mb-6 fade-up">
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Tanggal</label>
      <input type="date" name="tgl" value="<?php echo $f_date;?>"
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Username</label>
      <input type="text" name="user" value="<?php echo htmlspecialchars($f_user);?>" placeholder="Cari username..."
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
    </div>
    <div>
      <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Aksi</label>
      <input type="text" name="aksi" value="<?php echo htmlspecialchars($f_aksi);?>" placeholder="Cari aksi..."
        class="w-full p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition">
    </div>
    <div class="flex gap-2">
      <button type="submit" class="flex-1 bg-slate-900 text-white p-3 rounded-xl text-[9px] font-black hover:bg-blue-600 transition shadow-lg uppercase tracking-widest">
        <i class="fas fa-filter mr-1"></i> Filter
      </button>
      <a href="activity_log.php" class="flex-1 bg-slate-100 text-slate-500 p-3 rounded-xl text-[9px] font-black hover:bg-slate-200 transition text-center flex items-center justify-center uppercase tracking-widest">
        Reset
      </a>
    </div>
  </div>
</form>

<!-- TABEL LOG -->
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
  <div class="overflow-x-auto">
    <table class="w-full text-left">
      <thead class="bg-slate-50/50 border-b border-slate-100">
        <tr>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Waktu</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">User</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Role</th>
          <?php if($role==='Super Admin'):?>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Apotek</th>
          <?php endif;?>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Aksi</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Detail</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">IP</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php if(mysqli_num_rows($log_result)>0):
          while($log=mysqli_fetch_assoc($log_result)):
            $al = strtolower($log['aksi']);
            $warna = 'bg-slate-100 text-slate-600';
            foreach($aksi_warna as $k=>$v) { if(str_contains($al,$k)){ $warna=$v; break; } }
        ?>
        <tr class="hover:bg-blue-50/20 transition-colors">
          <td class="p-4 whitespace-nowrap">
            <div class="text-[10px] font-black text-slate-700"><?php echo date('d M Y',strtotime($log['created_at']));?></div>
            <div class="text-[9px] text-slate-400 font-bold"><?php echo date('H:i:s',strtotime($log['created_at']));?></div>
          </td>
          <td class="p-4">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-black text-[10px]">
                <?php echo strtoupper(substr($log['username'],0,1));?>
              </div>
              <span class="font-black text-slate-800 text-xs"><?php echo htmlspecialchars($log['username']);?></span>
            </div>
          </td>
          <td class="p-4 text-center">
            <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-lg uppercase tracking-tighter">
              <?php echo htmlspecialchars($log['role']);?>
            </span>
          </td>
          <?php if($role==='Super Admin'):?>
          <td class="p-4 text-[10px] font-bold text-slate-500"><?php echo htmlspecialchars($log['nama_apotek']??'—');?></td>
          <?php endif;?>
          <td class="p-4">
            <span class="px-3 py-1 <?php echo $warna;?> text-[9px] font-black rounded-full uppercase tracking-tighter">
              <?php echo htmlspecialchars($log['aksi']);?>
            </span>
          </td>
          <td class="p-4 text-[10px] text-slate-500 font-medium max-w-xs truncate" title="<?php echo htmlspecialchars($log['detail']);?>">
            <?php echo htmlspecialchars($log['detail']?:'—');?>
          </td>
          <td class="p-4 text-center text-[9px] font-bold text-slate-400 font-mono">
            <?php echo htmlspecialchars($log['ip_address']);?>
          </td>
        </tr>
        <?php endwhile; else:?>
        <tr>
          <td colspan="<?php echo $role==='Super Admin'?7:6;?>" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
            Tidak ada log aktivitas ditemukan.
          </td>
        </tr>
        <?php endif;?>
      </tbody>
    </table>
  </div>
</div>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Audit Intelligence</p>
</footer>
</main>
</body>
</html>