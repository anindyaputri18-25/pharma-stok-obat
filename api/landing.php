<?php
/**
 * landing.php
 * Halaman publik untuk PEMBELI:
 * - Lihat stok obat dari semua apotek (read-only)
 * - Cari apotek terdekat via Google Maps embed
 * - Tidak perlu login/register
 * - Integrasi WA untuk tanya apotek
 */
include 'koneksi.php';

// Ambil semua apotek aktif
$apotek_list = mysqli_query($koneksi,
    "SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");

// Ambil filter apotek dari URL (opsional)
$filter_apotek = isset($_GET['apotek']) ? (int)$_GET['apotek'] : 0;

// Query stok obat publik (join ke apotek, tampilkan harga jika ada)
if ($filter_apotek > 0) {
    $sql_obat = "
        SELECT m.*, a.nama_apotek, a.wa_apotek, a.kota,
               IFNULL(h.harga_jual, 0) AS harga_jual,
               IFNULL(h.satuan, 'tablet') AS satuan
        FROM medicines m
        LEFT JOIN apotek a ON m.id_apotek = a.id
        LEFT JOIN harga_obat h ON h.id_obat = m.id AND h.id_apotek = m.id_apotek
        WHERE m.id_apotek = '$filter_apotek' AND a.status='aktif'
        ORDER BY m.nama_obat ASC
    ";
} else {
    $sql_obat = "
        SELECT m.*, a.nama_apotek, a.wa_apotek, a.kota,
               IFNULL(h.harga_jual, 0) AS harga_jual,
               IFNULL(h.satuan, 'tablet') AS satuan
        FROM medicines m
        LEFT JOIN apotek a ON m.id_apotek = a.id
        LEFT JOIN harga_obat h ON h.id_obat = m.id AND h.id_apotek = m.id_apotek
        WHERE a.status='aktif'
        ORDER BY a.nama_apotek ASC, m.nama_obat ASC
    ";
}
$obat_result = mysqli_query($koneksi, $sql_obat);

// Ambil data apotek terpilih untuk maps
$apotek_terpilih = null;
if ($filter_apotek > 0) {
    $apotek_terpilih = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT * FROM apotek WHERE id='$filter_apotek' AND status='aktif' LIMIT 1"));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharma Stock - Cek Stok Obat Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f4f7fe; }
        .smooth-shadow { box-shadow: 0 10px 30px rgba(139,153,178,0.12); }
        .hero-bg {
            background: linear-gradient(135deg, #1d4ed8 0%, #4338ca 50%, #0f172a 100%);
        }
        .card-hover { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(29,78,216,0.15); }
        .stok-badge-aman    { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
        .stok-badge-menipis { background:#fef9c3; color:#854d0e; border:1px solid #fde68a; }
        .stok-badge-habis   { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        #mapFrame { width:100%; height:400px; border:none; border-radius:1.5rem; }
        .floating-btn {
            position: fixed; bottom: 24px; right: 24px; z-index: 999;
            animation: bob 2s ease-in-out infinite;
        }
        @keyframes bob {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-6px); }
        }
        .navbar { backdrop-filter: blur(12px); background: rgba(255,255,255,0.95); }
        .search-input:focus { box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
    </style>
</head>
<body class="text-slate-800 min-h-screen">

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar sticky top-0 z-50 border-b border-slate-100 smooth-shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                    <i class="fas fa-pills text-sm"></i>
                </div>
                <div>
                    <span class="font-black text-slate-900 text-sm uppercase tracking-tight">Pharma</span>
                    <span class="font-black text-blue-600 text-sm uppercase tracking-tight">Stock</span>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-6 text-xs font-bold text-slate-500">
                <a href="#stok" class="hover:text-blue-600 transition uppercase tracking-widest">Stok Obat</a>
                <a href="#apotek" class="hover:text-blue-600 transition uppercase tracking-widest">Apotek</a>
                <a href="#maps" class="hover:text-blue-600 transition uppercase tracking-widest">Lokasi</a>
            </div>
            <a href="login.php" class="bg-blue-600 text-white px-5 py-2 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                <i class="fas fa-sign-in-alt mr-1.5"></i> Staff Login
            </a>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <section class="hero-bg py-20 px-6 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 right-20 w-64 h-64 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 left-10 w-48 h-48 bg-blue-300 rounded-full blur-2xl"></div>
        </div>
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <span class="inline-block bg-white/15 border border-white/20 text-white text-[10px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-6 backdrop-blur">
                ✅ Cek Stok Tanpa Login
            </span>
            <h1 class="text-4xl md:text-6xl font-black italic uppercase tracking-tighter mb-4 leading-tight">
                Cek Stok Obat<br><span class="text-blue-300">Seluruh Indonesia.</span>
            </h1>
            <p class="text-blue-100 text-sm md:text-base font-medium max-w-xl mx-auto mb-10 leading-relaxed">
                Temukan ketersediaan obat di apotek terdekat Anda dari Sabang sampai Merauke, lihat harga, dan hubungi apotek langsung via WhatsApp.
            </p>
            <!-- Search Bar -->
            <div class="flex flex-col md:flex-row gap-3 max-w-2xl mx-auto">
                <input type="text" id="heroSearch" placeholder="🔍  Cari nama obat..." 
                    class="search-input flex-1 px-6 py-4 rounded-2xl bg-white text-slate-800 font-bold text-sm outline-none transition placeholder:text-slate-400">
                <select id="heroApotek" onchange="filterApotek(this.value)"
                    class="px-6 py-4 rounded-2xl bg-white/90 text-slate-700 font-bold text-sm outline-none">
                    <option value="0">Semua Apotek</option>
                    <?php
                    mysqli_data_seek($apotek_list, 0);
                    while ($ap = mysqli_fetch_assoc($apotek_list)):
                    ?>
                    <option value="<?php echo $ap['id']; ?>" <?php echo ($filter_apotek == $ap['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ap['nama_apotek']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </section>

    <!-- ===== STATS BAR ===== -->
    <?php
    $total_apotek_aktif = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM apotek WHERE status='aktif'"));
    $total_obat_tersedia = mysqli_num_rows(mysqli_query($koneksi, "SELECT m.id FROM medicines m JOIN apotek a ON m.id_apotek=a.id WHERE m.jumlah > 0 AND a.status='aktif'"));
    ?>
    <section class="bg-white border-b border-slate-100 py-6">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-black text-blue-600"><?php echo $total_apotek_aktif; ?></p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Apotek Aktif</p>
            </div>
            <div>
                <p class="text-2xl font-black text-emerald-600"><?php echo $total_obat_tersedia; ?></p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Obat Tersedia</p>
            </div>
            <div>
                <p class="text-2xl font-black text-indigo-600">24/7</p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Info Real-Time</p>
            </div>
        </div>
    </section>

    <!-- ===== DAFTAR APOTEK (CARDS) ===== -->
    <section id="apotek" class="max-w-7xl mx-auto px-6 py-14">
        <div class="mb-8">
            <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Jaringan Apotek</p>
            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">Apotek <span class="text-blue-600">Terdekat.</span></h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php
            $apotek_list2 = mysqli_query($koneksi, "SELECT * FROM apotek WHERE status='aktif' ORDER BY nama_apotek ASC");
            while ($ap = mysqli_fetch_assoc($apotek_list2)):
                $jml_obat = mysqli_num_rows(mysqli_query($koneksi,
                    "SELECT id FROM medicines WHERE id_apotek='{$ap['id']}' AND jumlah > 0"));
                $is_active = ($filter_apotek == $ap['id']);
            ?>
            <div class="card-hover bg-white rounded-[2rem] smooth-shadow border-2 p-6 cursor-pointer
                        <?php echo $is_active ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-50'; ?>"
                 onclick="filterApotek(<?php echo $ap['id']; ?>)">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                        <i class="fas fa-clinic-medical text-xl"></i>
                    </div>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[9px] font-black uppercase tracking-wider rounded-full border border-emerald-100">
                        Aktif
                    </span>
                </div>
                <h3 class="font-black text-slate-900 text-sm uppercase italic mb-1"><?php echo htmlspecialchars($ap['nama_apotek']); ?></h3>
                <p class="text-[10px] text-slate-400 font-bold mb-3 leading-relaxed">
                    <i class="fas fa-map-marker-alt text-rose-400 mr-1"></i>
                    <?php echo htmlspecialchars($ap['alamat']); ?>, <?php echo htmlspecialchars($ap['kota']); ?>
                </p>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black text-slate-500">
                        <i class="fas fa-pills text-blue-400 mr-1"></i>
                        <?php echo $jml_obat; ?> jenis obat tersedia
                    </span>
                    <span class="text-[10px] font-bold text-slate-400">
                        <i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars($ap['jam_buka']); ?>
                    </span>
                </div>
                <div class="mt-4 flex gap-2">
                    <?php if (!empty($ap['wa_apotek'])): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $ap['wa_apotek']); ?>?text=Halo%20<?php echo urlencode($ap['nama_apotek']); ?>%2C%20saya%20ingin%20menanyakan%20ketersediaan%20obat."
                       target="_blank"
                       onclick="event.stopPropagation()"
                       class="flex-1 flex items-center justify-center gap-2 bg-emerald-500 text-white py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-emerald-600 transition">
                        <i class="fab fa-whatsapp"></i> Chat WA
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($ap['lat']) && !empty($ap['lng'])): ?>
                    <a href="#maps" onclick="event.stopPropagation(); tampilkanPeta(<?php echo $ap['lat']; ?>, <?php echo $ap['lng']; ?>, '<?php echo addslashes($ap['nama_apotek']); ?>')"
                       class="flex-1 flex items-center justify-center gap-2 bg-blue-50 text-blue-600 py-2 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-blue-600 hover:text-white transition">
                        <i class="fas fa-map-marker-alt"></i> Peta
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- ===== GOOGLE MAPS EMBED ===== -->
    <section id="maps" class="max-w-7xl mx-auto px-6 pb-10">
        <div class="mb-6">
            <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Navigasi</p>
            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">Lokasi <span class="text-blue-600">Apotek.</span></h2>
        </div>
        <div class="bg-white rounded-[2rem] smooth-shadow border border-slate-100 p-4 overflow-hidden">
            <!-- Tombol "Cari Apotek Terdekat Saya" -->
            <div class="flex flex-wrap gap-3 mb-4">
                <button onclick="cariLokasiSaya()"
                    class="flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                    <i class="fas fa-crosshairs"></i> Apotek Terdekat dari Saya
                </button>
                <button onclick="resetPeta()"
                    class="flex items-center gap-2 bg-slate-100 text-slate-600 px-5 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition">
                    <i class="fas fa-globe"></i> Lihat Semua Apotek
                </button>
            </div>
            <div id="mapContainer">
                <?php
                // Default: tampilkan peta Indonesia dengan search apotek
                if ($apotek_terpilih && !empty($apotek_terpilih['lat'])):
                    $q = urlencode($apotek_terpilih['nama_apotek'] . ', ' . $apotek_terpilih['alamat'] . ', ' . $apotek_terpilih['kota']);
                    $mapSrc = "https://www.google.com/maps/embed/v1/place?key=AIzaSyD-9tSrke72PouQMnMX-a7eZSW0jkFMBWY&q={$q}&center={$apotek_terpilih['lat']},{$apotek_terpilih['lng']}&zoom=15";
                else:
                    // Default: Indonesia center
                    $mapSrc = "https://www.google.com/maps/embed/v1/search?key=AIzaSyD-9tSrke72PouQMnMX-a7eZSW0jkFMBWY&q=apotek+indonesia&center=-2.5489,118.0149&zoom=5";
                endif;
                ?>
                <iframe id="mapFrame"
                    src="<?php echo $mapSrc; ?>"
                    allowfullscreen loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-3 text-center italic">
                <i class="fas fa-info-circle mr-1"></i>
                Klik "Apotek Terdekat dari Saya" untuk menggunakan GPS perangkat Anda
            </p>
        </div>
    </section>

    <!-- ===== TABEL STOK OBAT PUBLIK ===== -->
    <section id="stok" class="max-w-7xl mx-auto px-6 pb-20">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Inventaris Publik</p>
                <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tighter italic">
                    Stok <span class="text-blue-600">Obat.</span>
                    <?php if ($filter_apotek && $apotek_terpilih): ?>
                    <span class="text-sm font-bold text-slate-400 normal-case italic ml-2">— <?php echo htmlspecialchars($apotek_terpilih['nama_apotek']); ?></span>
                    <?php endif; ?>
                </h2>
            </div>
            <input type="text" id="tableSearch" placeholder="🔍 Cari obat..." 
                   class="search-input px-5 py-3 rounded-xl bg-white border border-slate-200 text-sm font-bold outline-none transition w-full md:w-64">
        </div>

        <div class="bg-white rounded-[2rem] smooth-shadow border border-slate-50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left" id="tabelObat">
                    <thead class="bg-slate-50/60 border-b border-slate-100">
                        <tr>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Obat</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Apotek</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Kategori</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Harga</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Stok</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            <th class="p-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Tanya WA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50" id="bodyTabel">
                        <?php
                        // Reset result
                        $obat_result2 = mysqli_query($koneksi, $sql_obat);
                        if (mysqli_num_rows($obat_result2) > 0):
                            while ($row = mysqli_fetch_assoc($obat_result2)):
                                if ($row['jumlah'] <= 0) {
                                    $badge = 'stok-badge-habis';
                                    $label = 'Habis';
                                } elseif ($row['jumlah'] <= 15) {
                                    $badge = 'stok-badge-menipis';
                                    $label = 'Menipis';
                                } else {
                                    $badge = 'stok-badge-aman';
                                    $label = 'Tersedia';
                                }
                                $harga_fmt = ($row['harga_jual'] > 0)
                                    ? 'Rp ' . number_format($row['harga_jual'], 0, ',', '.')
                                    : '—';
                        ?>
                        <tr class="hover:bg-blue-50/20 transition-colors obat-row"
                            data-nama="<?php echo strtolower($row['nama_obat']); ?>"
                            data-apotek="<?php echo strtolower($row['nama_apotek'] ?? ''); ?>">
                            <td class="p-5">
                                <div class="font-black text-slate-800 text-xs uppercase italic"><?php echo htmlspecialchars($row['nama_obat']); ?></div>
                            </td>
                            <td class="p-5">
                                <div class="text-[10px] font-bold text-slate-600"><?php echo htmlspecialchars($row['nama_apotek'] ?? '—'); ?></div>
                                <div class="text-[9px] text-slate-400"><?php echo htmlspecialchars($row['kota'] ?? ''); ?></div>
                            </td>
                            <td class="p-5 text-center">
                                <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black rounded-full uppercase tracking-tighter">
                                    <?php echo htmlspecialchars($row['kategori']); ?>
                                </span>
                            </td>
                            <td class="p-5 text-center font-black text-slate-700 text-xs"><?php echo $harga_fmt; ?></td>
                            <td class="p-5 text-center font-black text-slate-800 text-sm"><?php echo $row['jumlah']; ?></td>
                            <td class="p-5 text-center">
                                <span class="<?php echo $badge; ?> px-3 py-1 text-[9px] font-black rounded-full uppercase tracking-tighter">
                                    <?php echo $label; ?>
                                </span>
                            </td>
                            <td class="p-5 text-center">
                                <?php if (!empty($row['wa_apotek'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $row['wa_apotek']); ?>?text=Halo%20<?php echo urlencode($row['nama_apotek']); ?>%2C%20apakah%20obat%20*<?php echo urlencode($row['nama_obat']); ?>*%20masih%20tersedia%3F%20Berapa%20harganya%3F"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 bg-emerald-500 text-white px-4 py-1.5 rounded-full text-[9px] font-black hover:bg-emerald-600 transition uppercase tracking-widest shadow-sm">
                                    <i class="fab fa-whatsapp"></i> Tanya
                                </a>
                                <?php else: ?>
                                <span class="text-[9px] text-slate-300 font-bold italic">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile;
                        else: ?>
                        <tr>
                            <td colspan="7" class="p-16 text-center text-slate-400 font-bold italic text-[10px] uppercase tracking-widest">
                                Tidak ada data stok untuk apotek ini.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="bg-slate-900 text-white py-12 px-6">
        <div class="max-w-7xl mx-auto text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-8 h-8 bg-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-pills text-sm"></i>
                </div>
                <span class="font-black text-xl uppercase tracking-tight">Pharma<span class="text-blue-400">Stock</span></span>
            </div>
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">
                &copy; 2026 PharmaStock • Informasi Stok Obat Apotek Seluruh Indonesia
            </p>
            <div class="mt-4">
                <a href="login.php" class="text-blue-400 text-xs font-bold hover:text-blue-300 transition uppercase tracking-widest">
                    <i class="fas fa-sign-in-alt mr-1"></i> Login Staff / Admin
                </a>
            </div>
        </div>
    </footer>

    <!-- ===== FLOATING WA BUTTON ===== -->
    <div class="floating-btn">
        <a href="https://wa.me/?text=Saya%20ingin%20menanyakan%20stok%20obat%20di%20apotek%20Anda."
           target="_blank"
           class="w-14 h-14 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full flex items-center justify-center shadow-2xl shadow-emerald-300 transition">
            <i class="fab fa-whatsapp text-2xl"></i>
        </a>
    </div>

    <script>
        // ===== FILTER APOTEK =====
        function filterApotek(id) {
            window.location.href = 'landing.php?apotek=' + id + '#stok';
        }

        // ===== SEARCH REAL-TIME =====
        function setupSearch(inputId, rowSelector, colFn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            input.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                document.querySelectorAll(rowSelector).forEach(row => {
                    const match = colFn(row).toLowerCase().includes(q);
                    row.style.display = match ? '' : 'none';
                });
            });
        }

        setupSearch('tableSearch', '.obat-row', row =>
            row.dataset.nama + ' ' + row.dataset.apotek
        );
        setupSearch('heroSearch', '.obat-row', row => row.dataset.nama);

        // Sync hero search ke table search
        document.getElementById('heroSearch').addEventListener('input', function() {
            const ta = document.getElementById('tableSearch');
            if (ta) ta.value = this.value;
        });

        // ===== GOOGLE MAPS: TAMPILKAN APOTEK TERTENTU =====
        function tampilkanPeta(lat, lng, nama) {
            const frame = document.getElementById('mapFrame');
            const q = encodeURIComponent(nama);
            frame.src = `https://www.google.com/maps/embed/v1/place?key=AIzaSyD-9tSrke72PouQMnMX-a7eZSW0jkFMBWY&q=${q}&center=${lat},${lng}&zoom=16`;
            document.getElementById('maps').scrollIntoView({ behavior: 'smooth' });
        }

        // ===== GOOGLE MAPS: CARI APOTEK TERDEKAT (GEOLOCATION) =====
        function cariLokasiSaya() {
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung geolokasi.');
                return;
            }
            navigator.geolocation.getCurrentPosition(function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const frame = document.getElementById('mapFrame');
                // Cari "apotek" di sekitar lokasi pengguna
                frame.src = `https://www.google.com/maps/embed/v1/search?key=AIzaSyD-9tSrke72PouQMnMX-a7eZSW0jkFMBWY&q=apotek&center=${lat},${lng}&zoom=14`;
                document.getElementById('maps').scrollIntoView({ behavior: 'smooth' });
            }, function(err) {
                alert('Gagal mendapatkan lokasi: ' + err.message +
                    '\nPastikan izin lokasi browser diaktifkan.');
            });
        }

        // ===== RESET PETA =====
        function resetPeta() {
            const frame = document.getElementById('mapFrame');
            frame.src = "https://www.google.com/maps/embed/v1/search?key=AIzaSyD-9tSrke72PouQMnMX-a7eZSW0jkFMBWY&q=apotek+indonesia&center=-2.5489,118.0149&zoom=5";
        }
    </script>
</body>
</html>