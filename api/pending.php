<?php
include 'koneksi.php';
include 'autentikasi.php';

$users = $_COOKIE['users'];

// AMBIL STATUS TERBARU DARI DATABASE
$query_cek = mysqli_query($koneksi, "SELECT role FROM users WHERE username = '$users'");
$data_baru = mysqli_fetch_array($query_cek);
$role_terbaru = $data_baru['role'];

// Jika admin sudah mengubah role dari 'Pending' ke role lain
if ($role_terbaru != 'Pending') {
    // Update cookie agar sinkron
    setcookie('role', $role_terbaru, time() + 86400, "/");
    
    // Redirect otomatis ke dashboard sesuai role baru
    if ($role_terbaru == 'Kasir') {
        header("Location: kasir_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$user_session = $users;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Menunggu Persetujuan - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.4); opacity: 0; }
        }
        .pulse-ring::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 1.5rem;
            background: #fed7aa;
            animation: pulse-ring 1.5s ease-out infinite;
        }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-md w-full bg-white p-10 rounded-[3rem] shadow-xl shadow-slate-200 border border-slate-100 text-center relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-orange-50 rounded-full"></div>
        <div class="relative">
            <div class="relative inline-block pulse-ring mb-6">
                <div class="w-20 h-20 bg-orange-100 text-orange-500 rounded-3xl flex items-center justify-center text-3xl relative z-10">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <h2 class="text-2xl font-black text-slate-800 uppercase mb-2">Akses Tertunda</h2>
            <p class="text-slate-500 mb-8 leading-relaxed italic text-sm">
                Halo <span class="font-bold text-blue-600">@<?php echo htmlspecialchars($user_session); ?></span>,
                akun Anda sedang menunggu verifikasi dari Admin.<br>
                <span class="text-xs mt-2 block">Halaman akan terbuka otomatis setelah disetujui.</span>
            </p>

            <div class="bg-orange-50 rounded-2xl p-4 mb-6 border border-orange-100 text-left">
                <p class="text-[10px] font-black text-orange-600 uppercase tracking-widest mb-1">Status Akun</p>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></span>
                    <span class="text-xs font-bold text-orange-700">Menunggu persetujuan Admin</span>
                </div>
            </div>

            <div class="space-y-3">
                <button onclick="window.location.reload()" class="block w-full py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition uppercase text-xs tracking-widest shadow-lg shadow-blue-100">
                    <i class="fas fa-sync-alt mr-2"></i> Cek Status Terbaru
                </button>
                <a href="logout.php" class="block w-full py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-red-50 hover:text-red-600 transition uppercase text-xs tracking-widest">
                    <i class="fas fa-sign-out-alt mr-2"></i> Keluar & Cek Nanti
                </a>
            </div>
        </div>
    </div>
</body>
</html>