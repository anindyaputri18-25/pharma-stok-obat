<?php
include 'koneksi.php';
include 'autentikasi.php';
include 'log_aktivitas.php';

$users     = $_COOKIE['users'] ?? '';
$role      = $role_saat_ini;
$id_apotek = get_id_apotek_user($koneksi);
$apotek    = get_apotek($koneksi, $id_apotek);

$role_boleh = ['Admin','Manager Gudang','Kasir','Apoteker','Super Admin'];
if (!in_array($role, $role_boleh)) {
    echo "<script>alert('Akses Ditolak!'); window.location='dashboard.php';</script>"; exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis - Pharma Stock</title>
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
        @keyframes spin-slow{to{transform:rotate(360deg)}}
        .spin-slow{animation:spin-slow 12s linear infinite;}
        @keyframes pulse-ring{0%{transform:scale(1);opacity:.6}100%{transform:scale(1.5);opacity:0}}
        .pulse-ring{animation:pulse-ring 2s ease-out infinite;}
        #bpsTableContent table{width:100%;border-collapse:collapse;}
        #bpsTableContent th,#bpsTableContent td{border:1px solid #e2e8f0;padding:10px 12px;text-align:center;font-size:11px;}
        #bpsTableContent th{background:#f8fafc;font-weight:800;color:#1e293b;}
        .skeleton{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;}
        @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    </style>
</head>
<body class="text-slate-800 flex min-h-screen">

<?php include 'sidebar.php'; ?>

<main class="flex-1 p-6 md:p-10 lg:p-12 max-w-[1600px] mx-auto w-full">

    <!-- HEADER -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 fade-up">
        <div>
            <p class="text-blue-600 font-extrabold text-[9px] uppercase tracking-[0.3em] mb-1">Health Data Analysis</p>
            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter leading-none italic">
                Analisis <span class="text-blue-600">BPS.</span>
            </h1>
        </div>
        <div class="flex items-center gap-3 bg-white p-1.5 rounded-full border border-slate-100 smooth-shadow">
            <div class="flex flex-col items-end px-3">
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest"><?php echo $role; ?></p>
                <p class="text-xs font-black text-slate-800"><?php echo htmlspecialchars($users); ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-black text-sm border-2 border-white shadow-inner">
                <?php echo strtoupper(substr($users,0,1)); ?>
            </div>
        </div>
    </header>

    <!-- HERO BANNER dengan LOGO di kotak biru -->
    <div class="hero-bg relative overflow-hidden p-10 rounded-[3rem] text-white mb-8 smooth-shadow fade-up">
        <div class="flex items-center gap-8 relative z-10">
            <!-- LOGO KOTAK BIRU -->
            <div class="shrink-0 w-20 h-20 bg-white/15 backdrop-blur border border-white/20 rounded-3xl flex items-center justify-center relative">
                <i class="fas fa-chart-line text-3xl text-white relative z-10"></i>
                <div class="absolute inset-0 rounded-3xl pulse-ring border-2 border-white/30"></div>
            </div>
            <div>
                <span class="bg-white/20 border border-white/20 text-[9px] px-4 py-1.5 rounded-full font-black uppercase tracking-widest mb-3 inline-block">
                    📊 Badan Pusat Statistik Indonesia
                </span>
                <h2 class="text-2xl md:text-3xl font-black italic tracking-tight mb-1">Data Kesehatan Nasional</h2>
                <p class="text-blue-100 text-xs font-medium opacity-90">
                    Persentase Penduduk yang Mempunyai Keluhan Kesehatan dan Penggunaan Obat • 2009–2014
                </p>
            </div>
        </div>
        <i class="fas fa-database absolute -right-10 -bottom-10 text-[12rem] opacity-10 spin-slow"></i>
    </div>

    <!-- STATUS & TOMBOL REFRESH BPS -->
    <div class="flex items-center justify-between mb-4 fade-up">
        <div class="flex items-center gap-3">
            <div id="bpsStatus" class="flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-500 rounded-full text-[10px] font-black uppercase tracking-widest">
                <div class="w-2 h-2 bg-slate-400 rounded-full"></div>
                Memuat data BPS...
            </div>
        </div>
        <button onclick="muatDataBPS()" class="flex items-center gap-2 bg-white border border-slate-200 text-slate-600 px-5 py-2 rounded-full font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 hover:text-white hover:border-blue-600 smooth-shadow transition">
            <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh Data
        </button>
    </div>

    <!-- BPS TABLE CONTAINER -->
    <div class="bg-white rounded-[2.5rem] smooth-shadow border border-slate-50 p-8 mb-8 fade-up">
        <!-- Skeleton loader -->
        <div id="bpsSkeleton" class="space-y-3">
            <div class="skeleton h-8 rounded-xl w-full"></div>
            <div class="skeleton h-6 rounded-xl w-full"></div>
            <div class="skeleton h-6 rounded-xl w-5/6"></div>
            <div class="skeleton h-6 rounded-xl w-full"></div>
            <div class="skeleton h-6 rounded-xl w-4/5"></div>
        </div>

        <div id="bpsTableContainer" class="hidden">
            <div class="flex items-center gap-3 mb-6">
                <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-table text-xs"></i>
                </span>
                <div>
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Data Tabel BPS</h3>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-0.5 italic">Sumber: webapi.bps.go.id</p>
                </div>
            </div>
            <div id="bpsTableContent" class="text-xs text-slate-600 overflow-x-auto"></div>
        </div>

        <!-- Error state -->
        <div id="bpsError" class="hidden text-center py-8">
            <div class="w-16 h-16 bg-rose-50 text-rose-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-circle text-2xl"></i>
            </div>
            <h4 class="font-black text-slate-700 text-sm mb-2">Data BPS Tidak Tersedia</h4>
            <p id="bpsErrorMsg" class="text-[11px] text-slate-400 font-medium mb-4"></p>
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-left max-w-md mx-auto">
                <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest mb-2">Cara Memperbaiki:</p>
                <ol class="text-[10px] text-amber-600 font-bold space-y-1 list-decimal list-inside">
                    <li>Login ke <a href="https://webapi.bps.go.id" target="_blank" class="underline">webapi.bps.go.id</a></li>
                    <li>Salin API Key Anda dari menu "Key API"</li>
                    <li>Ganti nilai <code class="bg-amber-100 px-1 rounded">$apiKey</code> di file <code class="bg-amber-100 px-1 rounded">api_bps.php</code></li>
                    <li>Pastikan server mengaktifkan ekstensi <code class="bg-amber-100 px-1 rounded">curl</code></li>
                </ol>
            </div>
        </div>
    </div>

    <!-- INFO CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 fade-up">
        <div class="bg-white p-6 rounded-[2rem] border border-slate-50 smooth-shadow relative overflow-hidden group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-10 h-10 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-100">
                    <i class="fas fa-hospital-users text-sm"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-800 uppercase tracking-tighter text-sm">Modern Care</h3>
                    <p class="text-[9px] text-blue-600 font-bold uppercase tracking-widest">Medical Method</p>
                </div>
            </div>
            <p class="text-slate-400 text-[11px] leading-relaxed font-medium italic">
                Data menunjukkan persentase penduduk yang mengakses fasilitas medis profesional dan obat-obatan farmasi modern di seluruh Indonesia.
            </p>
            <i class="fas fa-microscope absolute -bottom-4 -right-4 text-7xl text-slate-50 group-hover:text-blue-50"></i>
        </div>
        <div class="bg-white p-6 rounded-[2rem] border border-slate-50 smooth-shadow relative overflow-hidden group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-10 h-10 bg-emerald-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-100">
                    <i class="fas fa-leaf text-sm"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-800 uppercase tracking-tighter text-sm">Traditional Care</h3>
                    <p class="text-[9px] text-emerald-600 font-bold uppercase tracking-widest">Natural Method</p>
                </div>
            </div>
            <p class="text-slate-400 text-[11px] leading-relaxed font-medium italic">
                Mencakup penggunaan kearifan lokal, jamu herbal, dan metode pengobatan tradisional yang masih kental dalam budaya masyarakat Indonesia.
            </p>
            <i class="fas fa-mortar-pestle absolute -bottom-4 -right-4 text-7xl text-slate-50 group-hover:text-emerald-50"></i>
        </div>
    </div>

    <footer class="mt-16 pb-6 text-center">
        <div class="w-12 h-1 bg-slate-200 mx-auto mb-6 rounded-full"></div>
        <p class="text-slate-400 text-[9px] font-black uppercase tracking-[0.4em]">&copy; 2026 Pharma Stock • Analytical Intelligence</p>
    </footer>
</main>

<script>
async function muatDataBPS() {
    const skeleton   = document.getElementById('bpsSkeleton');
    const container  = document.getElementById('bpsTableContainer');
    const errorBox   = document.getElementById('bpsError');
    const errorMsg   = document.getElementById('bpsErrorMsg');
    const statusEl   = document.getElementById('bpsStatus');
    const refreshIcon= document.getElementById('refreshIcon');

    // Reset state
    skeleton.classList.remove('hidden');
    container.classList.add('hidden');
    errorBox.classList.add('hidden');
    refreshIcon.classList.add('animate-spin');

    statusEl.innerHTML = `<div class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></div> Menghubungi server BPS...`;

    try {
        // Timeout 15 detik
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);

        const response = await fetch('api_bps.php', { signal: controller.signal });
        clearTimeout(timeout);

        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

        const result = await response.json();
        skeleton.classList.add('hidden');
        refreshIcon.classList.remove('animate-spin');

        if (result.status === 'OK' || result.status === 'ok') {
            // Ambil tabel HTML dari struktur BPS API
            const tableHtml = result?.data?.table ?? result?.data ?? null;

            if (tableHtml && typeof tableHtml === 'string') {
                // Decode HTML entities & tampilkan
                const ta = document.createElement('textarea');
                ta.innerHTML = tableHtml;
                document.getElementById('bpsTableContent').innerHTML =
                    `<iframe style="width:100%;height:520px;border:none;background:white;border-radius:12px;"
                             srcdoc="${ta.value.replace(/"/g, '&quot;')}"></iframe>`;
            } else {
                // Coba tampilkan sebagai JSON jika tidak ada tabel HTML
                document.getElementById('bpsTableContent').innerHTML =
                    `<pre class="text-[10px] bg-slate-50 p-4 rounded-xl overflow-auto max-h-96">${JSON.stringify(result, null, 2)}</pre>`;
            }

            container.classList.remove('hidden');
            statusEl.innerHTML = `<div class="w-2 h-2 bg-emerald-500 rounded-full"></div><span class="text-emerald-600">Data BPS berhasil dimuat</span>`;
            statusEl.className = 'flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-black uppercase tracking-widest';

        } else {
            throw new Error(result.message ?? 'Status tidak dikenal dari server BPS.');
        }

    } catch (err) {
        skeleton.classList.add('hidden');
        refreshIcon.classList.remove('animate-spin');
        errorBox.classList.remove('hidden');

        const isTimeout = err.name === 'AbortError';
        errorMsg.textContent = isTimeout
            ? 'Koneksi ke API BPS timeout (>15 detik). Periksa koneksi server atau coba lagi.'
            : err.message;

        statusEl.innerHTML = `<div class="w-2 h-2 bg-rose-500 rounded-full"></div><span class="text-rose-600">Gagal terhubung ke BPS</span>`;
        statusEl.className = 'flex items-center gap-2 px-4 py-2 bg-rose-50 text-rose-500 rounded-full text-[10px] font-black uppercase tracking-widest';
        console.error('BPS API Error:', err);
    }
}
window.addEventListener('DOMContentLoaded', muatDataBPS);
</script>
</body>
</html>