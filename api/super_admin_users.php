<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users_aktif = $_COOKIE['users'] ?? '';
$role        = $role_saat_ini;

if ($role !== 'Super Admin') { header("Location: dashboard.php"); exit(); }

// UPDATE ROLE & APOTEK USER
if (isset($_POST['update_user'])) {
    $id         = (int)$_POST['id'];
    $new_role   = mysqli_real_escape_string($koneksi, $_POST['role']);
    $new_apotek = (int)$_POST['id_apotek'];
    $ap_val     = $new_apotek > 0 ? "'$new_apotek'" : "NULL";
    mysqli_query($koneksi, "UPDATE users SET role='$new_role', id_apotek=$ap_val WHERE id='$id'");
    catat_log($koneksi, 'Super Admin Update User', "User ID:$id, Role:$new_role, Apotek:$new_apotek");
    $pesan = 'success:User berhasil diperbarui!';
}

// HAPUS USER
if (isset($_GET['hapus'])) {
    $id    = (int)$_GET['hapus'];
    $uname = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT username FROM users WHERE id='$id'"))['username'] ?? '-';
    mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
    catat_log($koneksi, 'Super Admin Hapus User', "Username:$uname");
    header("Location: super_admin_users.php?msg=hapus"); exit();
}
if (isset($_GET['msg']) && $_GET['msg']==='hapus') $pesan = 'success:User berhasil dihapus.';

$users_result = mysqli_query($koneksi,
    "SELECT u.*, a.nama_apotek
     FROM users u
     LEFT JOIN apotek a ON u.id_apotek=a.id
     WHERE u.username != '$users_aktif'
     ORDER BY a.nama_apotek ASC, u.role ASC, u.username ASC");

$apotek_all = mysqli_query($koneksi,"SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");
$pending_ct = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM users WHERE role='Pending'"))['c'];
$total_user = mysqli_num_rows($users_result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Semua User - Super Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;font-size:13px;}
*{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
.smooth-shadow{box-shadow:0 10px 30px rgba(139,153,178,0.1);}
.sa-hero{background:linear-gradient(135deg,#1e293b 0%,#334155 50%,#0f172a 100%);}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp 0.5s ease forwards;}
</style>
</head>
<body class="text-slate-800 flex min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

<header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 fade-up">
  <div>
    <p class="text-amber-500 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">User Control</p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Semua <span class="text-amber-500">User.</span></h1>
  </div>
  <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
    <div class="flex flex-col items-end px-3">
      <p class="text-[9px] text-amber-500 font-black uppercase tracking-widest">⭐ Super Admin</p>
      <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users_aktif);?></p>
    </div>
    <div class="w-10 h-10 bg-amber-400 rounded-full flex items-center justify-center text-slate-900 font-black text-sm border-2 border-white shadow-inner">
      <?php echo strtoupper(substr($users_aktif,0,1));?>
    </div>
  </div>
</header>

<!-- HERO -->
<div class="sa-hero relative overflow-hidden p-10 rounded-[3rem] text-white mb-8 smooth-shadow fade-up">
  <div class="flex items-center gap-6 relative z-10">
    <div class="w-16 h-16 bg-white/15 backdrop-blur border border-white/20 rounded-3xl flex items-center justify-center shrink-0">
      <i class="fas fa-users-cog text-3xl text-white"></i>
    </div>
    <div>
      <h2 class="text-2xl font-black italic mb-1">Kelola Semua Pengguna</h2>
      <p class="text-slate-300 text-xs font-medium">Assign role dan apotek untuk setiap pengguna di seluruh jaringan.</p>
    </div>
  </div>
  <i class="fas fa-users absolute -right-8 -bottom-8 text-[10rem] opacity-10"></i>
</div>

<?php if(isset($pesan)):[$t,$m]=explode(':',$pesan,2);
  $bg=$t==='success'?'bg-emerald-50 text-emerald-700 border-emerald-100':'bg-rose-50 text-rose-700 border-rose-100';?>
<div class="mb-6 p-4 <?php echo $bg;?> border rounded-2xl text-xs font-bold flex items-center gap-2 fade-up">
  <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($m);?>
</div>
<?php endif;?>

<!-- STATS -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 fade-up">
  <div class="bg-white p-5 rounded-2xl smooth-shadow border border-slate-50 text-center">
    <h4 class="text-3xl font-black text-blue-600"><?php echo $total_user;?></h4>
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Total User</p>
  </div>
  <div class="bg-white p-5 rounded-2xl smooth-shadow border border-slate-50 text-center">
    <h4 class="text-3xl font-black text-rose-500"><?php echo $pending_ct;?></h4>
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Pending</p>
  </div>
</div>

<!-- TABEL -->
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
  <div class="overflow-x-auto">
    <table class="w-full text-left">
      <thead class="bg-slate-50/50 border-b border-slate-100">
        <tr>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">User</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Apotek Saat Ini</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Role Saat Ini</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Set Role & Apotek</th>
          <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Hapus</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php
        mysqli_data_seek($users_result,0);
        if(mysqli_num_rows($users_result)>0):
          while($u=mysqli_fetch_assoc($users_result)):
            $rc=match($u['role']){
              'Admin'=>'bg-rose-50 text-rose-600',
              'Manager Gudang'=>'bg-amber-50 text-amber-700',
              'Apoteker'=>'bg-purple-50 text-purple-600',
              'Kasir'=>'bg-emerald-50 text-emerald-700',
              'Pending'=>'bg-orange-50 text-orange-600',
              'Super Admin'=>'bg-yellow-50 text-yellow-700',
              default=>'bg-slate-100 text-slate-500'};
        ?>
        <tr class="hover:bg-amber-50/20 transition-colors">
          <td class="p-5">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 bg-slate-100 rounded-xl flex items-center justify-center font-black text-slate-500 text-sm">
                <?php echo strtoupper(substr($u['username'],0,1));?>
              </div>
              <div>
                <p class="font-black text-slate-800 text-sm"><?php echo htmlspecialchars($u['username']);?></p>
                <p class="text-[8px] text-slate-300 font-bold uppercase italic">ID: #<?php echo $u['id'];?></p>
              </div>
            </div>
          </td>
          <td class="p-5 text-[10px] font-bold text-slate-600">
            <?php echo htmlspecialchars($u['nama_apotek'] ?? '— Tidak terikat');?>
          </td>
          <td class="p-5 text-center">
            <span class="px-3 py-1.5 <?php echo $rc;?> text-[9px] font-black rounded-xl uppercase tracking-tight">
              <?php echo htmlspecialchars($u['role']);?>
            </span>
          </td>
          <td class="p-5">
            <form method="POST" class="flex items-center gap-2 flex-wrap">
              <input type="hidden" name="id" value="<?php echo $u['id'];?>">
              <select name="id_apotek" class="p-2 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-bold outline-none focus:ring-2 focus:ring-amber-400 transition min-w-[150px]">
                <option value="0">— Tanpa Apotek —</option>
                <?php
                mysqli_data_seek($apotek_all,0);
                while($ap=mysqli_fetch_assoc($apotek_all)):?>
                <option value="<?php echo $ap['id'];?>" <?php echo($u['id_apotek']==$ap['id'])?'selected':'';?>>
                  <?php echo htmlspecialchars($ap['nama_apotek']);?>
                </option>
                <?php endwhile;?>
              </select>
              <select name="role" class="p-2 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-bold outline-none focus:ring-2 focus:ring-amber-400 transition min-w-[130px]">
                <?php foreach(['Pending','Kasir','Apoteker','Manager Gudang','Admin','Super Admin'] as $r):?>
                <option value="<?php echo $r;?>" <?php echo($u['role']===$r)?'selected':'';?>><?php echo $r;?></option>
                <?php endforeach;?>
              </select>
              <button name="update_user"
                class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[9px] font-black hover:bg-amber-400 hover:text-slate-900 transition shadow-sm active:scale-95 uppercase tracking-widest">
                Simpan
              </button>
            </form>
          </td>
          <td class="p-5 text-center">
            <a href="super_admin_users.php?hapus=<?php echo $u['id'];?>"
               onclick="return confirm('Hapus user <?php echo addslashes($u['username']);?> secara permanen?')"
               class="w-9 h-9 flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition rounded-xl mx-auto">
              <i class="fas fa-trash-alt text-[10px]"></i>
            </a>
          </td>
        </tr>
        <?php endwhile; else:?>
        <tr>
          <td colspan="5" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
            Tidak ada user terdaftar.
          </td>
        </tr>
        <?php endif;?>
      </tbody>
    </table>
  </div>
</div>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Super Admin Control</p>
</footer>
</main>
</body>
</html>