<?php
session_start();
include 'koneksi.php';

if (isset($_COOKIE['users'])) {
    header("Location: dashboard.php");
    exit();
}

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];
    $role = "Pending"; 

    $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");

    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>
                alert('Gagal! Username sudah digunakan.');
                window.location='register.php';
              </script>";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password_hash', '$role')";
        
        if (mysqli_query($koneksi, $query)) {
            echo "<script>
                    alert('Registrasi Berhasil! Silakan Login.');
                    window.location='login.php'; 
                  </script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Pharma Stock</title>
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
            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mt-2">Sistem Informasi Apotek</p>
        </div>

        <div class="bg-white p-10 rounded-[3rem] shadow-2xl shadow-blue-100 border border-slate-100 relative overflow-hidden">
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-blue-50 rounded-full"></div>
            
            <div class="relative">
                <h2 class="text-2xl font-black text-slate-800 mb-2">Buat Akun 📝</h2>
                <p class="text-slate-500 text-sm mb-8 italic">Silakan isi data untuk mendaftar sebagai anggota.</p>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-4 mb-1 block">Username</label>
                        <div class="relative">
                            <i class="fas fa-users-plus absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                            <input type="text" name="username" placeholder="Contoh: apoteker_rudi" required 
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

                    <button name="register" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-black shadow-lg shadow-slate-200 hover:bg-slate-900 active:scale-95 transition uppercase tracking-widest mt-4">
                        Daftar Sekarang <i class="fas fa-check-circle ml-2 text-xs"></i>
                    </button>
                </form>

                <div class="mt-8 text-center border-t border-slate-50 pt-6">
                    <p class="text-sm text-slate-500">
                        Sudah punya akun? 
                        <a href="login.php" class="text-blue-600 font-bold hover:underline">Masuk di sini</a>
                    </p>
                </div>
            </div>
        </div>

        <footer class="mt-10 text-center text-slate-400 text-[10px] font-bold uppercase tracking-[0.3em]">
            &copy; 2026 Pharma Stock • Secure Authentication 💊
        </footer>
    </div>

</body>
</html>