<?php
session_start();
include 'koneksi.php';
include 'autentikasi.php';
$role = $role_saat_ini;
$users = $_COOKIE['users'];

$role_boleh = ['Admin', 'Apoteker'];
if (!in_array($role_saat_ini, $role_boleh)) {
    header("Location: dashboard.php");
    exit();
}

$query_obat = mysqli_query($koneksi, "SELECT * FROM medicines ORDER BY nama_obat ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Racikan - Pharma Stock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-[#f4f7fe] text-slate-800 p-6 md:p-12">
    <div class="max-w-4xl mx-auto">
        <a href="racikan_obat.php" class="text-blue-600 font-bold text-xs uppercase tracking-widest flex items-center gap-2 mb-6">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>

        <div class="bg-white rounded-[2.5rem] shadow-xl p-10 border border-slate-50">
            <h1 class="text-2xl font-black text-slate-900 mb-8 uppercase italic">Buat <span class="text-blue-600">Racikan Baru.</span></h1>
            
            <form action="proses_tambah_racikan.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nama Racikan</label>
                        <input type="text" name="nama_racikan" required placeholder="Contoh: Puyer Flu Anak" class="w-full bg-slate-50 border border-slate-100 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tipe</label>
                        <select name="tipe_racikan" class="w-full bg-slate-50 border border-slate-100 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            <option value="Puyer">Puyer</option>
                            <option value="Sirup">Sirup</option>
                            <option value="Salep">Salep</option>
                            <option value="Kapsul">Kapsul</option>
                        </select>
                    </div>
                </div>

                <div class="mb-8">
                    <div class="flex justify-between items-end mb-4">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Pilih Bahan Baku</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" id="cariObat" placeholder="Cari nama obat..." class="pl-8 pr-4 py-1.5 bg-slate-100 border-none rounded-full text-[11px] focus:ring-2 focus:ring-blue-500 outline-none w-48 md:w-64">
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 max-h-80 overflow-y-auto" id="kontainerObat">
                        <div class="space-y-3">
                            <?php while($obat = mysqli_fetch_array($query_obat)) : ?>
                            <div class="item-obat flex items-center justify-between bg-white p-3 rounded-xl shadow-sm border border-slate-100" data-nama="<?php echo strtolower($obat['nama_obat']); ?>">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" name="obat_dipilih[]" value="<?php echo $obat['id']; ?>" id="check-<?php echo $obat['id']; ?>" class="hidden-checkbox w-4 h-4 text-blue-600 rounded">
                                    <span class="text-xs font-bold text-slate-700"><?php echo $obat['nama_obat']; ?></span>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase">Stok: <span class="text-blue-600"><?php echo $obat['jumlah']; ?></span></span>
                                    
                                    <div class="flex items-center bg-slate-100 rounded-lg p-1">
                                        <input type="number" name="jumlah_pakai[<?php echo $obat['id']; ?>]" 
                                               data-nama-obat="<?php echo $obat['nama_obat']; ?>"
                                               oninput="updateKeterangan(this, <?php echo $obat['id']; ?>)"
                                               placeholder="0" 
                                               class="w-12 bg-transparent border-none text-center text-xs font-bold focus:ring-0">
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Stok Hasil Racikan</label>
                        <input type="number" name="stok_racikan" required placeholder="Misal: 10" class="w-full bg-slate-50 border border-slate-100 rounded-xl p-3">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Keterangan Tambahan (Otomatis)</label>
                        <textarea name="keterangan" id="keteranganOtomatis" rows="3" placeholder="Pilih obat untuk mengisi otomatis..." class="w-full bg-slate-50 border border-slate-100 rounded-xl p-3 text-xs italic text-slate-500 focus:outline-none"></textarea>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white p-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-blue-700 shadow-lg shadow-blue-100 transition active:scale-[0.98]">
                    Simpan & Proses Racikan <i class="fas fa-check-circle ml-2"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        // FUNGSI PENCARIAN OBAT
        const inputCari = document.getElementById('cariObat');
        const itemsObat = document.querySelectorAll('.item-obat');

        inputCari.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            itemsObat.forEach(item => {
                const nama = item.getAttribute('data-nama');
                if (nama.includes(filter)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // FUNGSI UPDATE KETERANGAN OTOMATIS & CHECKBOX
        function updateKeterangan(input, id) {
            const checkbox = document.getElementById('check-' + id);
            const areaKeterangan = document.getElementById('keteranganOtomatis');
            
            // Otomatis Centang jika jumlah > 0
            if (input.value > 0) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }

            // Susun Ulang Keterangan Berdasarkan obat yang diisi jumlahnya
            let listKeterangan = [];
            const semuaInputJumlah = document.querySelectorAll('input[name^="jumlah_pakai"]');
            
            semuaInputJumlah.forEach(inp => {
                if (inp.value > 0) {
                    const namaObat = inp.getAttribute('data-nama-obat');
                    listKeterangan.push(namaObat + " (" + inp.value + ")");
                }
            });

            if (listKeterangan.length > 0) {
                areaKeterangan.value = "Komposisi: " + listKeterangan.join(", ") + ".";
            } else {
                areaKeterangan.value = "";
            }
        }
    </script>
</body>
</html>