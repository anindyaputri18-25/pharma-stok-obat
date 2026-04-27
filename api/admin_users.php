<?php
include 'koneksi.php';
include 'autentikasi.php';

// Pastikan hanya Admin yang bisa akses
if ($role_saat_ini != 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Konsistensi data user dari Cookie
$user_aktif = $_COOKIE['users'] ?? 'Admin';
$role       = $role_saat_ini;

// Update Role
if (isset($_POST['update_role'])) {
    $id       = (int)$_POST['id'];
    $new_role = mysqli_real_escape_string($koneksi, $_POST['role']);
    mysqli_query($koneksi, "UPDATE users SET role='$new_role' WHERE id='$id'");
    echo "<script>alert('Role berhasil diperbarui!'); window.location='admin_users.php';</script>";
    exit();
}

// Hapus User
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
    header("Location: admin_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f7fe; font-size: 13px; }
        * { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .smooth-shadow { box-shadow: 0 10px 30px rgba(139,153,178,0.1); }
        .nav-text { font-size: 12px; letter-spacing: 0.02em; }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

    <aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50">
        <div class="mb-10 w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
            <i class="fas fa-pills text-lg"></i>
        </div>
        <nav class="flex flex-col gap-2 w-full px-4 font-bold h-full nav-text">
            <a href="dashboard.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-home w-5 text-center"></i><span class="hidden md:inline ml-3">Beranda</span>
            </a>
            <a href="stok_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-box w-5 text-center"></i><span class="hidden md:inline ml-3">Stok Obat</span>
            </a>
            <a href="racikan_obat.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-mortar-pestle w-5 text-center"></i><span class="hidden md:inline ml-3">Racikan Obat</span>
            </a>
            <a href="analisis.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-chart-bar w-5 text-center"></i><span class="hidden md:inline ml-3">Analisis</span>
            </a>
            <a href="laporan.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                <i class="fas fa-file-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Laporan</span>
            </a>
            <a href="admin_users.php" class="flex items-center justify-center md:justify-start p-3 bg-blue-600 text-white rounded-xl shadow-xl shadow-blue-100 transition">
                <i class="fas fa-users-cog w-5 text-center"></i><span class="hidden md:inline ml-3">User Management</span>
            </a>
            <div class="mt-auto flex flex-col gap-2">
                <a href="profil.php" class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-slate-50 hover:text-blue-600 rounded-xl transition">
                    <i class="fas fa-user w-5 text-center"></i><span class="hidden md:inline ml-3">Profil</span>
                </a>
                <a href="logout.php" class="flex items-center justify-center md:justify-start p-3 text-red-500 hover:bg-red-50 rounded-xl transition">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i><span class="hidden md:inline ml-3">Keluar</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="text-left">
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Access Control</p>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">Manage <span class="text-blue-600">Users.</span></h1>
            </div>
            <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow shrink-0">
                <div class="flex flex-col items-end px-3">
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
                    <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($user_aktif); ?></p>
                </div>
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 font-black text-sm border-2 border-white shadow-inner">
                    <?php echo strtoupper(substr($user_aktif, 0, 1)); ?>
                </div>
            </div>
        </header>

        <?php
        $users_result = mysqli_query($koneksi, "SELECT * FROM users WHERE username != '$user_aktif' ORDER BY id DESC");
        $total_users  = mysqli_num_rows($users_result);
        $pending_count = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM users WHERE role='Pending'"));
        ?>

        <!-- Stats bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded-2xl smooth-shadow border border-slate-50 text-center">
                <h4 class="text-2xl font-black text-slate-800"><?php echo $total_users; ?></h4>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Total User</p>
            </div>
            <div class="bg-white p-4 rounded-2xl smooth-shadow border border-slate-50 text-center">
                <h4 class="text-2xl font-black text-rose-500"><?php echo $pending_count; ?></h4>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Pending</p>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr>
                            <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Pengguna</th>
                            <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Jabatan Saat Ini</th>
                            <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Otorisasi Baru</th>
                            <th class="p-6 text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        // Reset result pointer
                        mysqli_data_seek($users_result, 0);
                        if (mysqli_num_rows($users_result) > 0):
                            while ($u = mysqli_fetch_array($users_result)):
                        ?>
                        <tr class="group hover:bg-blue-50/30 transition">
                            <td class="p-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 font-black text-sm">
                                        <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800 text-sm tracking-tight"><?php echo htmlspecialchars($u['username']); ?></p>
                                        <p class="text-[8px] font-bold text-slate-300 uppercase italic mt-0.5">ID: #<?php echo $u['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="inline-block px-4 py-1.5 rounded-lg font-black text-[10px] uppercase tracking-wider
                                    <?php echo ($u['role'] == 'Pending') ? 'bg-rose-100 text-rose-600' : 'bg-blue-50 text-blue-600'; ?>">
                                    <?php echo htmlspecialchars($u['role']); ?>
                                </span>
                            </td>
                            <td class="p-6">
                                <!-- FIX: name="id" agar konsisten dengan $_POST['id'] di atas -->
                                <form method="POST" class="flex items-center justify-center gap-2">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <select name="role" class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl text-[10px] font-bold uppercase outline-none focus:bg-white focus:ring-2 focus:ring-blue-500 transition appearance-none min-w-[150px]">
                                        <option value="Pending"        <?php if($u['role']=='Pending')         echo 'selected'; ?>>Pending</option>
                                        <option value="Manager Gudang" <?php if($u['role']=='Manager Gudang') echo 'selected'; ?>>Manager Gudang</option>
                                        <option value="Apoteker"       <?php if($u['role']=='Apoteker')       echo 'selected'; ?>>Apoteker</option>
                                        <option value="Kasir"          <?php if($u['role']=='Kasir')          echo 'selected'; ?>>Kasir</option>
                                        <option value="Admin"          <?php if($u['role']=='Admin')          echo 'selected'; ?>>Admin</option>
                                    </select>
                                    <button name="update_role" class="bg-slate-900 text-white px-4 py-2.5 rounded-xl text-[9px] font-black hover:bg-blue-600 transition shadow-lg shadow-slate-100 active:scale-95 uppercase tracking-widest">
                                        Update
                                    </button>
                                </form>
                            </td>
                            <td class="p-6 text-center">
                                <a href="admin_users.php?hapus=<?php echo $u['id']; ?>" onclick="return confirm('Hapus user ini secara permanen?')" class="w-9 h-9 flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition rounded-xl shadow-sm mx-auto">
                                    <i class="fas fa-trash-alt text-[11px]"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile;
                        else: ?>
                        <tr>
                            <td colspan="4" class="p-16 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px] italic">Tidak ada pengguna lain terdaftar.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="mt-16 pb-6 text-center">
            <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
            <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em] italic">&copy; 2026 Pharma Stock • Admin Only Area</p>
        </footer>
    </main>
</body>
</html>