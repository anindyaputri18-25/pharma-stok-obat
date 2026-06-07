<?php
session_start();
include 'koneksi.php';

if (isset($_COOKIE['users'])) {
    $role = $_COOKIE['role'];
    if ($role === 'Pending')         header("Location: pending.php");
    elseif ($role === 'Kasir')       header("Location: kasir_dashboard.php");
    elseif ($role === 'Super Admin') header("Location: super_admin_dashboard.php");
    else                             header("Location: dashboard.php");
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

            // Catat log login
            $uname = mysqli_real_escape_string($koneksi, $data['username']);
            $urole = mysqli_real_escape_string($koneksi, $data['role']);
            $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $ap_id = !empty($data['id_apotek']) ? "'{$data['id_apotek']}'" : "NULL";
            mysqli_query($koneksi,
                "INSERT INTO activity_log (id_apotek, username, role, aksi, detail, ip_address)
                 VALUES ($ap_id, '$uname', '$urole', 'Login', 'Login berhasil dari IP: $ip', '$ip')"
            );

            $role = $data['role'];
            if ($role === 'Pending')         header("Location: pending.php");
            elseif ($role === 'Kasir')       header("Location: kasir_dashboard.php");
            elseif ($role === 'Super Admin') header("Location: super_admin_dashboard.php");
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Plus Jakarta Sans',sans-serif;background:#f4f7fe;}
        *{transition:all 0.3s cubic-bezier(0.4,0,0.2,1);}
        .hero-bg{background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 50%,#0f172a 100%);}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeUp 0.6s ease forwards;}
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
<div class="w-full max-w-md fade-up">

    <!-- LOGO -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-3 mb-2">
            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-blue-200">
                <i class="fas fa-pills text-xl"></i>
            </div>
        </div>
        <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">
            Pharma<span class="text-blue-600">Stock</span>
        </h1>
        <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">Management System v2.0</p>
    </div>

    <!-- CARD -->
    <div class="bg-white rounded-[3rem] shadow-2xl shadow-blue-100 border border-slate-100 overflow-hidden">
        <div class="hero-bg p-8 text-white relative overflow-hidden">
            <h2 class="text-2xl font-black italic mb-1 relative z-10">Selamat Datang! 👋</h2>
            <p class="text-blue-100 text-xs font-medium relative z-10">Silakan masuk untuk mengelola stok obat hari ini.</p>
            <i class="fas fa-pills absolute -right-4 -bottom-4 text-[8rem] opacity-10"></i>
        </div>

        <div class="p-8">
            <?php if (!empty($error)): ?>
            <div class="mb-5 p-3 bg-red-50 text-red-600 text-xs font-bold rounded-xl border border-red-100 flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'logout'): ?>
            <div class="mb-5 p-3 bg-emerald-50 text-emerald-600 text-xs font-bold rounded-xl border border-emerald-100 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> Berhasil keluar dari sistem.
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Username</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="text" name="username" placeholder="Masukkan username" required
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm font-bold">
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition text-sm font-bold">
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 active:scale-95 transition uppercase tracking-widest text-[10px]">
                    Masuk Sekarang <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </button>
            </form>

            <div class="mt-6 text-center border-t border-slate-50 pt-6 space-y-3">
                <p class="text-sm text-slate-500">
                    Belum punya akun?
                    <a href="register.php" class="text-blue-600 font-bold hover:underline">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>

    <p class="text-center text-slate-400 text-[10px] font-bold uppercase tracking-[0.3em] mt-8">
        &copy; 2026 Pharma Stock 💊
    </p>
</div>
</body>
</html>