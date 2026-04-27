/**
 * Fungsi untuk mengambil data dari API BPS (api_bps.php)
 */
async function ambilDataAPI() {
    const url = 'api/api_bps.php'; // Pastikan file ini ada di folder yang sama

    try {
        console.log("Memulai permintaan ke API...");
        const response = await fetch(url);

        // Cek apakah respon berhasil
        if (!response.ok) {
            throw new Error(`Terjadi kesalahan HTTP: ${response.status}`);
        }

        const data = await response.json();
        
        // Cek status data (apakah data asli BPS atau data cadangan/offline dari api_bps.php)
        if (data.status === "offline") {
            console.warn("Peringatan: Menampilkan data cadangan karena API utama tidak terjangkau.");
        }

        console.log("Data berhasil diterima:", data);
        
        // Memanggil fungsi untuk menampilkan data ke elemen HTML
        tampilkanKeLayar(data);

    } catch (error) {
        console.error("Gagal mengambil data dari API:", error);
        
        // Menampilkan pesan error ke user jika elemen 'hasil' ada
        const container = document.getElementById('hasil');
        if (container) {
            container.innerHTML = `<p class="text-red-500 text-xs italic">Koneksi ke API atau file api/api_bps.php bermasalah.</p>`;
        }
    }
}

/**
 * Fungsi untuk menampilkan data spesifik ke elemen HTML <div id="hasil"></div>
 */
function tampilkanKeLayar(data) {
    const container = document.getElementById('hasil');
    
    // Validasi: Apakah elemen container ada di HTML dan apakah data memiliki isi?
    if (container && data['data-content']) {
        const content = data['data-content'];
        
        /**
         * Penjelasan Key BPS:
         * 137 = Tahun 2014, 1 = Modern, 1 = Laki-laki
         */
        const keyTahun = "134_1_1"; 
        const nilai = content[keyTahun];
        
        if (nilai !== undefined) {
            // Tampilan UI yang rapi dengan Tailwind CSS
            container.innerHTML = `
                <div class="p-5 bg-white border border-slate-200 rounded-[2rem] shadow-sm mt-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-info-circle text-xs"></i>
                        </div>
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Info Tren Terkini</h4>
                    </div>
                    <p class="text-sm text-slate-600 leading-tight">
                        Berdasarkan data BPS terakhir, penggunaan <span class="font-bold text-blue-600">Obat Modern</span> pada laki-laki mencapai:
                    </p>
                    <h5 class="text-2xl font-black text-slate-800 mt-1">${nilai}%</h5>
                </div>
            `;
        } else {
            container.innerHTML = `<p class="text-slate-400 text-xs">Data untuk tahun tersebut tidak ditemukan dalam file API.</p>`;
        }
    }
}

// Menjalankan fungsi secara otomatis setelah seluruh elemen halaman dimuat
document.addEventListener('DOMContentLoaded', ambilDataAPI);