<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? 'Admin';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

if ($role !== 'Admin') {
    header("Location: dashboard.php"); exit();
}

// Update Role
if (isset($_POST['update_role'])) {
    $id       = (int)$_POST['id'];
    $new_role = mysqli_real_escape_string($koneksi, $_POST['role']);
    mysqli_query($koneksi, "UPDATE users SET role='$new_role' WHERE id='$id'");
    catat_log($koneksi, 'Update Role User', "User ID: $id → Role: $new_role", $id_apotek);
    echo "<script>alert('Role berhasil diperbarui!'); window.location='admin_users.php';</script>";
    exit();
}

// Hapus User
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $uname = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT username FROM users WHERE id='$id'"))['username'] ?? '-';
    mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
    catat_log($koneksi, 'Hapus User', "Username: $uname", $id_apotek);
    header("Location: admin_users.php"); exit();
}

// Query user: hanya user di apotek yang sama (atau semua jika admin tidak punya apotek)
$ap_filter = $id_apotek ? "AND (id_apotek='$id_apotek' OR id_apotek IS NULL)" : "";
$users_result = mysqli_query($koneksi,
    "SELECT * FROM users WHERE username != '$users' AND role != 'Super Admin' $ap_filter ORDER BY id DESC");
$total_users   = mysqli_num_rows($users_result);
$pending_count = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) c FROM users WHERE role='Pending' $ap_filter"))['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>User Management - Pharma Stock</title>
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
<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

<header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 fade-up">
  <div>
    <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">
      <?php echo htmlspecialchars($apotek['nama_apotek'] ?? 'Access Control'); ?>
    </p>
    <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Manage <span class="text-blue-600">Users.</span></h1>
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

<!-- HERO -->
<div class="hero-bg relative overflow-hidden p-8 rounded-[2.5rem] text-white mb-8 smooth-shadow fade-up">
  <div class="relative z-10">
    <h2 class="text-2xl font-black italic mb-1">Manajemen Pengguna</h2>
    <p class="text-blue-100 text-xs font-medium opacity-90">Kelola akses dan role setiap pengguna di apotek Anda.</p>
  </div>
  <i class="fas fa-users-cog absolute -right-8 -bottom-8 text-[10rem] opacity-10"></i>
</div>

<!-- STATS -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 fade-up">
  <div class="bg-white p-5 rounded-2xl smooth-shadow border border-slate-50 text-center">
    <h4 class="text-3xl font-black text-slate-800"><?php echo $total_users; ?></h4>
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Total User</p>
  </div>
  <div class="bg-white p-5 rounded-2xl smooth-shadow border border-slate-50 text-center">
    <h4 class="text-3xl font-black text-rose-500"><?php echo $pending_count; ?></h4>
    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Pending</p>
  </div>
</div>

<!-- TABEL USER -->
<div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden fade-up">
  <div class="overflow-x-auto">
    <table class="w-full text-left">
      <thead class="bg-slate-50/50 border-b border-slate-100">
        <tr>
          <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Pengguna</th>
          <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Role Saat Ini</th>
          <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Ubah Role</th>
          <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php
        mysqli_data_seek($users_result, 0);
        if (mysqli_num_rows($users_result) > 0):
          while ($u = mysqli_fetch_assoc($users_result)):
            $rc = match($u['role']) {
              'Admin'          => 'bg-rose-50 text-rose-600',
              'Manager Gudang' => 'bg-amber-50 text-amber-700',
              'Apoteker'       => 'bg-purple-50 text-purple-600',
              'Kasir'          => 'bg-emerald-50 text-emerald-700',
              'Pending'        => 'bg-orange-50 text-orange-600',
              default          => 'bg-slate-100 text-slate-500',
            };
        ?>
        <tr class="hover:bg-blue-50/20 transition-colors">
          <td class="p-6">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 font-black text-sm">
                <?php echo strtoupper(substr($u['username'],0,1)); ?>
              </div>
              <div>
                <p class="font-black text-slate-800 text-sm tracking-tight"><?php echo htmlspecialchars($u['username']); ?></p>
                <p class="text-[8px] font-bold text-slate-300 uppercase italic mt-0.5">ID: #<?php echo $u['id']; ?></p>
              </div>
            </div>
          </td>
          <td class="p-6 text-center">
            <span class="inline-block px-4 py-1.5 rounded-xl font-black text-[10px] uppercase tracking-wider <?php echo $rc; ?>">
              <?php echo htmlspecialchars($u['role']); ?>
            </span>
          </td>
          <td class="p-6">
            <form method="POST" class="flex items-center justify-center gap-2">
              <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
              <select name="role" class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-bold uppercase outline-none focus:ring-2 focus:ring-blue-500 transition appearance-none min-w-[150px]">
                <?php foreach(['Pending','Kasir','Apoteker','Manager Gudang','Admin'] as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo ($u['role']===$r)?'selected':''; ?>><?php echo $r; ?></option>
                <?php endforeach; ?>
              </select>
              <button name="update_role" class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-[9px] font-black hover:bg-blue-600 transition shadow-lg shadow-slate-100 active:scale-95 uppercase tracking-widest">
                Simpan
              </button>
            </form>
          </td>
          <td class="p-6 text-center">
            <a href="admin_users.php?hapus=<?php echo $u['id']; ?>"
               onclick="return confirm('Hapus user <?php echo addslashes($u['username']); ?> secara permanen?')"
               class="w-9 h-9 flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition rounded-xl shadow-sm mx-auto">
              <i class="fas fa-trash-alt text-[11px]"></i>
            </a>
          </td>
        </tr>
        <?php endwhile; else: ?>
        <tr>
          <td colspan="4" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
            Tidak ada pengguna lain terdaftar.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer class="mt-16 pb-6 text-center">
  <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
  <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Admin Only</p>
</footer>
</main>
</body>
</html>