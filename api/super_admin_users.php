<?php
/**
 * super_admin_users.php
 * Super Admin: lihat & kelola semua user dari semua apotek
 * Bisa assign user ke apotek, ubah role, hapus user
 */
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users_aktif = $_COOKIE['users'] ?? '';
$role        = $role_saat_ini;

if ($role !== 'Super Admin') {
    header("Location: dashboard.php");
    exit();
}

// UPDATE ROLE & APOTEK USER
if (isset($_POST['update_user'])) {
    $id        = (int)$_POST['id'];
    $new_role  = mysqli_real_escape_string($koneksi, $_POST['role']);
    $new_apotek= (int)$_POST['id_apotek'];
    $apotek_val = $new_apotek > 0 ? "'$new_apotek'" : "NULL";

    mysqli_query($koneksi, "UPDATE users SET role='$new_role', id_apotek=$apotek_val WHERE id='$id'");
    catat_log($koneksi, 'Super Admin Update User', "User ID: $id, Role: $new_role, Apotek: $new_apotek");
    $pesan = 'success:User berhasil diperbarui!';
}

// HAPUS USER
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
    header("Location: super_admin_users.php?msg=hapus");
    exit();
}

$users_result = mysqli_query($koneksi,
    "SELECT u.*, a.nama_apotek
     FROM users u
     LEFT JOIN apotek a ON u.id_apotek = a.id
     WHERE u.username != '$users_aktif'
     ORDER BY a.nama_apotek ASC, u.role ASC, u.username ASC");

$apotek_all = mysqli_query($koneksi, "SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");
$pending_ct = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) c FROM users WHERE role='Pending'"))['c'];

if (isset($_GET['msg']) && $_GET['msg']==='hapus') $pesan = 'success:User berhasil dihapus.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua User - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fe; font-size: 13px; }
        * { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .smooth-shadow { box-shadow: 0 10px 30px rgba(139,153,178,0.1); }
        .nav-text { font-size: 12px; letter-spacing: 0.02em; }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-20 md:w-64 bg-gradient-to-b from-slate-900 to-slate-800 border-r border-slate-700 flex flex-col items-center py-8 sticky top-0 h-screen z-50">
        <div class="mb-10 w-10 h-10 bg-amber-400 rounded-xl flex items-center justify-center text-slate-900 shadow-lg shadow-amber-200">
            <i class="fas fa-crown text-lg"></i>
        </div>
        <nav class="flex flex-col gap-2 w-full px-4 font-bold h-full nav-text">
            <a href="super_admin_dashboard.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-700 hover:text-white rounded-xl transition">
                <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Dashboard</span>
            </a>
            <a href="activity_log.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-700 hover:text-white rounded-xl transition">
                <i class="fas fa-history w-5 text-center"></i><span class="hidden md:inline ml-3">Log Aktivitas</span>
            </a>
            <a href="super_admin_users.php" class="flex items-center justify-center md:justify-start p-3 bg-amber-400 text-slate-900 rounded-xl shadow-xl transition">
                <i class="fas fa-users-cog w-5 text-center"></i><span class="hidden md:inline ml-3">Semua User</span>
            </a>
            <a href="landing.php" target="_blank" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-700 hover:text-white rounded-xl transition">
                <i class="fas fa-globe w-5 text-center"></i><span class="hidden md:inline ml-3">Lihat Landing Page</span>
            </a>
            <div class="mt-auto flex flex-col gap-2">
                <a href="profil.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-700 hover:text-white rounded-xl transition">
                    <i class="fas fa-user w-5 text-center"></i><span class="hidden md:inline ml-3">Profil</span>
                </a>
                <a href="logout.php" class="flex items-center justify-center md:justify-start p-3 text-red-400 hover:bg-red-900/30 rounded-xl transition">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Keluar</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div>
                <p class="text-amber-500 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">User Control</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
                    Semua <span class="text-amber-500">User.</span>
                </h1>
            </div>
            <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
                <div class="flex flex-col items-end px-3">
                    <p class="text-[9px] text-amber-500 font-black uppercase tracking-widest">⭐ Super Admin</p>
                    <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users_aktif); ?></p>
                </div>
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-black text-sm border-2 border-white shadow-inner">
                    <?php echo strtoupper(substr($users_aktif,0,1)); ?>
                </div>
            </div>
        </header>

        <?php if (isset($pesan)):
            [$t,$m] = explode(':', $pesan, 2);
            $bg = $t==='success' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100';
        ?>
        <div class="mb-6 p-4 <?php echo $bg; ?> border rounded-2xl text-xs font-bold flex items-center gap-2">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($m); ?>
        </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded-2xl smooth-shadow border border-slate-50 text-center">
                <h4 class="text-2xl font-black text-blue-600"><?php echo mysqli_num_rows($users_result); ?></h4>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Total User</p>
            </div>
            <div class="bg-white p-4 rounded-2xl smooth-shadow border border-slate-50 text-center">
                <h4 class="text-2xl font-black text-rose-500"><?php echo $pending_ct; ?></h4>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Pending</p>
            </div>
        </div>

        <!-- TABEL SEMUA USER -->
        <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">User</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Apotek Saat Ini</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Role Saat Ini</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Set Role & Apotek</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        mysqli_data_seek($users_result, 0);
                        if (mysqli_num_rows($users_result) > 0):
                            while ($u = mysqli_fetch_assoc($users_result)):
                                $role_color = match($u['role']) {
                                    'Admin'          => 'bg-rose-50 text-rose-600',
                                    'Manager Gudang' => 'bg-amber-50 text-amber-700',
                                    'Apoteker'       => 'bg-purple-50 text-purple-600',
                                    'Kasir'          => 'bg-emerald-50 text-emerald-700',
                                    'Pending'        => 'bg-orange-50 text-orange-600',
                                    'Super Admin'    => 'bg-yellow-50 text-yellow-700',
                                    default          => 'bg-slate-100 text-slate-500',
                                };
                        ?>
                        <tr class="hover:bg-amber-50/20 transition-colors">
                            <td class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-slate-100 rounded-xl flex items-center justify-center font-black text-slate-500">
                                        <?php echo strtoupper(substr($u['username'],0,1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800 text-sm"><?php echo htmlspecialchars($u['username']); ?></p>
                                        <p class="text-[8px] text-slate-300 font-bold uppercase italic">ID: #<?php echo $u['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-5 text-[10px] font-bold text-slate-600">
                                <?php echo htmlspecialchars($u['nama_apotek'] ?? '— Tidak terikat'); ?>
                            </td>
                            <td class="p-5 text-center">
                                <span class="px-3 py-1.5 <?php echo $role_color; ?> text-[9px] font-black rounded-lg uppercase tracking-tight">
                                    <?php echo htmlspecialchars($u['role']); ?>
                                </span>
                            </td>
                            <td class="p-5">
                                <form method="POST" class="flex items-center gap-2 flex-wrap">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <!-- Pilih Apotek -->
                                    <select name="id_apotek" class="p-2 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-bold uppercase outline-none focus:ring-2 focus:ring-amber-400 transition min-w-[150px]">
                                        <option value="0">— Tanpa Apotek —</option>
                                        <?php
                                        mysqli_data_seek($apotek_all, 0);
                                        while ($ap = mysqli_fetch_assoc($apotek_all)):
                                        ?>
                                        <option value="<?php echo $ap['id']; ?>" <?php echo ($u['id_apotek']==$ap['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ap['nama_apotek']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <!-- Pilih Role -->
                                    <select name="role" class="p-2 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-bold uppercase outline-none focus:ring-2 focus:ring-amber-400 transition min-w-[130px]">
                                        <?php
                                        $roles = ['Pending','Kasir','Apoteker','Manager Gudang','Admin','Super Admin'];
                                        foreach ($roles as $r):
                                        ?>
                                        <option value="<?php echo $r; ?>" <?php echo ($u['role']===$r) ? 'selected' : ''; ?>>
                                            <?php echo $r; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button name="update_user" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[9px] font-black hover:bg-amber-400 hover:text-slate-900 transition shadow-sm active:scale-95 uppercase tracking-widest">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                            <td class="p-5 text-center">
                                <a href="super_admin_users.php?hapus=<?php echo $u['id']; ?>"
                                   onclick="return confirm('Hapus user <?php echo addslashes($u['username']); ?> secara permanen?')"
                                   class="w-9 h-9 flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition rounded-xl mx-auto">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile;
                        else: ?>
                        <tr>
                            <td colspan="5" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">
                                Tidak ada user terdaftar.
                            </td>
                        </tr>
                        <?php endif; ?>
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