<?php
include 'koneksi.php';
include 'autentikasi.php';

// Kasir tidak boleh edit, gunakan variabel dari autentikasi.php
if (isset($role_saat_ini) && $role_saat_ini == 'Kasir') {
    header("Location: stok_obat.php");
    exit();
}

// Konsistensi data user untuk header
$users = $_COOKIE['users'] ?? 'Guest';
$role  = $role_saat_ini;

if (!isset($_GET['id'])) {
    header("Location: stok_obat.php");
    exit();
}

$id    = (int)$_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM medicines WHERE id = '$id'");
$data  = mysqli_fetch_array($query);

if (!$data) {
    header("Location: stok_obat.php");
    exit();
}

// Proses Update tetap menggunakan mysqli_real_escape_string untuk keamanan
if (isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_obat']);
    $kat  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $qty  = (int)$_POST['jumlah'];
    $exp  = $_POST['expired_date'];
    $supp = mysqli_real_escape_string($koneksi, $_POST['supplier']);
    $wa   = mysqli_real_escape_string($koneksi, $_POST['wa_supplier']);

    $sql = "UPDATE medicines SET 
                nama_obat    = '$nama', 
                kategori     = '$kat', 
                jumlah       = '$qty', 
                expired_date = '$exp', 
                supplier     = '$supp', 
                wa_supplier  = '$wa' 
            WHERE id = '$id'";
    
    if (mysqli_query($koneksi, $sql)) {
        echo "<script>alert('Data berhasil diupdate!'); window.location='stok_obat.php';</script>";
    } else {
        echo "<script>alert('Gagal update data!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Obat - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">

    <div class="w-full max-w-md bg-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-200 border border-slate-100 relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-24 h-24 bg-blue-50 rounded-full"></div>
        <div class="relative">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600">
                    <i class="fas fa-pills text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Edit Obat</h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">ID: #<?php echo $data['id']; ?></p>
                </div>
            </div>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase ml-2 mb-1 block">Nama Obat</label>
                    <input type="text" name="nama_obat" value="<?php echo htmlspecialchars($data['nama_obat']); ?>" required
                        class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase ml-2 mb-1 block">Kategori</label>
                    <div class="relative">
                        <select name="kategori" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition appearance-none cursor-pointer">
                            <option value="Obat Bebas"          <?php if($data['kategori']=='Obat Bebas')          echo 'selected'; ?>>Obat Bebas</option>
                            <option value="Obat Bebas Terbatas" <?php if($data['kategori']=='Obat Bebas Terbatas') echo 'selected'; ?>>Obat Bebas Terbatas</option>
                            <option value="Obat Keras"          <?php if($data['kategori']=='Obat Keras')          echo 'selected'; ?>>Obat Keras</option>
                            <option value="Obat Tradisional"    <?php if($data['kategori']=='Obat Tradisional')    echo 'selected'; ?>>Obat Tradisional</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase ml-2 mb-1 block">Nama Supplier</label>
                    <input type="text" name="supplier" value="<?php echo htmlspecialchars($data['supplier']); ?>" placeholder="Nama PT / Distributor" required
                        class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase ml-2 mb-1 block">WhatsApp Supplier</label>
                    <div class="relative">
                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-xs">+</span>
                        <input type="text" name="wa_supplier" value="<?php echo htmlspecialchars($data['wa_supplier']); ?>"
                            placeholder="62812xxx" required
                            class="w-full pl-8 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-2 mb-1 block">Stok</label>
                        <input type="number" name="jumlah" value="<?php echo $data['jumlah']; ?>" required min="0"
                            class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-2 mb-1 block">Kadaluarsa</label>
                        <input type="date" name="expired_date" value="<?php echo $data['expired_date']; ?>" required
                            class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs transition">
                    </div>
                </div>

                <div class="pt-6 flex items-center gap-4">
                    <a href="stok_obat.php" class="flex-1 text-center py-4 text-slate-400 font-bold hover:text-slate-600 transition uppercase text-[10px] tracking-widest">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button name="update" type="submit" class="flex-1 bg-slate-800 text-white py-4 rounded-2xl font-black shadow-lg shadow-slate-200 hover:bg-blue-600 active:scale-95 transition uppercase text-[10px] tracking-widest">
                        Simpan <i class="fas fa-check-circle ml-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>