<?php
/**
 * login.php (VERSI BARU - tambahkan redirect Super Admin)
 * GANTI file login.php yang lama dengan file ini
 */
session_start();
include 'koneksi.php';

// Cek jika sudah login
if (isset($_COOKIE['users'])) {
    $role = $_COOKIE['role'];
    if ($role === 'Pending')      header("Location: pending.php");
    elseif ($role === 'Kasir')    header("Location: kasir_dashboard.php");
    elseif ($role === 'Super Admin') header("Location: super_admin_dashboard.php");
    else                          header("Location: dashboard.php");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password = trim($_POST['password']);

    $stmt = $koneksi->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        if (password_verify($password, $data['password'])) {
            setcookie('users', $data['username'], time() + 86400, "/");
            setcookie('role',  $data['role'],     time() + 86400, "/");
            session_regenerate_id(true);

            // Catat log login (gunakan mysqli langsung karena log_aktivitas belum di-include)
            $uname = mysqli_real_escape_string($koneksi, $data['username']);
            $urole = mysqli_real_escape_string($koneksi, $data['role']);
            $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $ap_id = $data['id_apotek'] ? "'{$data['id_apotek']}'" : "NULL";
            mysqli_query($koneksi,
                "INSERT INTO activity_log (id_apotek, username, role, aksi, detail, ip_address)
                 VALUES ($ap_id, '$uname', '$urole', 'Login', 'Login berhasil dari IP: $ip', '$ip')"
            );

            $role = $data['role'];
            if ($role === 'Pending')         header("Location: pending.php");
            elseif ($role === 'Kasir')       header("Location: kasir_dashboard.php");
            elseif ($role === 'Super Admin') header("Location: super_admin_dashboard.php");
            elseif (in_array($role, ['Admin','Apoteker','Manager Gudang'])) header("Location: dashboard.php");
            else                             header("Location: dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-blue-600 uppercase tracking-tighter flex items-center justify-center gap-3">
                <i class="fas fa-pills"></i> Pharma <span class="text-slate-800">Stock</span>
            </h1>
            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mt-2">Management System v2.0</p>
        </div>

        <div class="bg-white p-10 rounded-[3rem] shadow-2xl shadow-blue-100 border border-slate-100 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-50 rounded-full"></div>
            <div class="relative">
                <h2 class="text-2xl font-black text-slate-800 mb-2">Selamat Datang! 👋</h2>
                <p class="text-slate-500 text-sm mb-8 italic">Silakan masuk untuk mengelola stok obat hari ini.</p>

                <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-50 text-red-600 text-xs font-bold rounded-xl border border-red-100">
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'logout'): ?>
                <div class="mb-4 p-3 bg-emerald-50 text-emerald-600 text-xs font-bold rounded-xl border border-emerald-100">
                    <i class="fas fa-check-circle mr-1"></i> Berhasil keluar dari sistem.
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-4 mb-1 block">Username</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                            <input type="text" name="username" placeholder="Masukkan username" required
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-4 mb-1 block">Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                            <input type="password" name="password" placeholder="••••••••" required
                                class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 active:scale-95 transition uppercase tracking-widest mt-4">
                        Masuk Sekarang <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </button>
                </form>

                <!-- Link ke landing page (tanpa login) -->
                <div class="mt-4 text-center">
                    <a href="landing.php" class="text-blue-500 text-xs font-bold hover:underline flex items-center justify-center gap-1">
                        <i class="fas fa-store"></i> Cek Stok Tanpa Login (Mode Pembeli)
                    </a>
                </div>

                <div class="mt-6 text-center border-t border-slate-50 pt-6">
                    <p class="text-sm text-slate-500">
                        Belum punya akun?
                        <a href="register.php" class="text-blue-600 font-bold hover:underline">Daftar di sini</a>
                    </p>
                </div>
            </div>
        </div>

        <footer class="mt-10 text-center text-slate-400 text-[10px] font-bold uppercase tracking-[0.3em]">
            &copy; 2026 Pharma Stock 💊
        </footer>
    </div>
</body>
</html>