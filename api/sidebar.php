<?php
/**
 * sidebar.php — Komponen sidebar universal
 * Include setelah koneksi.php + autentikasi.php + log_aktivitas.php
 * Variabel yang dibutuhkan: $role, $users, $current_page (basename file)
 */
$current_page = basename($_SERVER['PHP_SELF']);

// Ikon & warna per role
$role_icon  = match($role) {
    'Super Admin'    => ['icon' => 'fa-crown',          'bg' => 'bg-amber-400',   'text' => 'text-slate-900'],
    'Admin'          => ['icon' => 'fa-shield-alt',     'bg' => 'bg-blue-600',    'text' => 'text-white'],
    'Manager Gudang' => ['icon' => 'fa-warehouse',      'bg' => 'bg-emerald-500', 'text' => 'text-white'],
    'Apoteker'       => ['icon' => 'fa-mortar-pestle',  'bg' => 'bg-purple-600',  'text' => 'text-white'],
    'Kasir'          => ['icon' => 'fa-cash-register',  'bg' => 'bg-orange-500',  'text' => 'text-white'],
    default          => ['icon' => 'fa-clock',          'bg' => 'bg-slate-400',   'text' => 'text-white'],
};

// Sidebar background per role
$sidebar_bg = ($role === 'Super Admin')
    ? 'bg-gradient-to-b from-slate-900 to-slate-800 border-slate-700'
    : 'bg-white border-slate-100';

$nav_base    = ($role === 'Super Admin')
    ? 'text-slate-400 hover:bg-white/10 hover:text-white'
    : 'text-slate-400 hover:bg-slate-50 hover:text-blue-600';

$active_cls  = ($role === 'Super Admin')
    ? 'bg-amber-400 text-slate-900 shadow-xl'
    : 'bg-blue-600 text-white shadow-xl shadow-blue-100';

if (!function_exists('nav_link')) {
    function nav_link($href, $icon, $label, $current, $base, $active) {
        $is = (basename($current) === basename($href));
        $cls = $is ? $active : $base;
        return "<a href=\"$href\" class=\"flex items-center justify-center md:justify-start p-3 $cls rounded-xl transition\">
            <i class=\"fas $icon w-5 text-center\"></i>
            <span class=\"hidden md:inline ml-3\">$label</span>
        </a>";
    }
}
?>
<aside class="w-20 md:w-64 <?php echo $sidebar_bg; ?> border-r flex flex-col items-center py-8 sticky top-0 h-screen z-50">
    <!-- Logo -->
    <div class="mb-10 w-10 h-10 <?php echo $role_icon['bg']; ?> rounded-xl flex items-center justify-center <?php echo $role_icon['text']; ?> shadow-lg">
        <i class="fas fa-pills text-lg"></i>
    </div>

    <nav class="flex flex-col gap-1.5 w-full px-4 font-bold h-full text-[12px]">

        <?php if ($role === 'Super Admin'): ?>
            <?php echo nav_link('super_admin_dashboard.php', 'fa-home',      'Dashboard',        $current_page, $nav_base, $active_cls); ?>
            <?php echo nav_link('super_admin_apotek.php',   'fa-clinic-medical', 'Semua Apotek', $current_page, $nav_base, $active_cls); ?>
            <?php echo nav_link('super_admin_users.php',    'fa-users-cog', 'Semua User',        $current_page, $nav_base, $active_cls); ?>
            <?php echo nav_link('activity_log.php',         'fa-history',   'Log Aktivitas',     $current_page, $nav_base, $active_cls); ?>
            <a href="landing.php" target="_blank" class="flex items-center justify-center md:justify-start p-3 <?php echo $nav_base; ?> rounded-xl transition">
                <i class="fas fa-globe w-5 text-center"></i>
                <span class="hidden md:inline ml-3">Landing Page</span>
            </a>

        <?php else: ?>
            <!-- Beranda -->
            <?php
            $home = ($role === 'Kasir') ? 'kasir_dashboard.php' : 'dashboard.php';
            echo nav_link($home, 'fa-home', 'Beranda', $current_page, $nav_base, $active_cls);
            ?>

            <!-- Stok Obat -->
            <?php echo nav_link('stok_obat.php', 'fa-box', 'Stok Obat', $current_page, $nav_base, $active_cls); ?>

            <!-- Racikan Obat (Admin, Apoteker) -->
            <?php if (in_array($role, ['Admin', 'Apoteker'])): ?>
                <?php echo nav_link('racikan_obat.php', 'fa-mortar-pestle', 'Racikan Obat', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <!-- Analisis (semua kecuali Pending) -->
            <?php if (in_array($role, ['Admin', 'Manager Gudang', 'Apoteker', 'Kasir'])): ?>
                <?php echo nav_link('analisis.php', 'fa-chart-bar', 'Analisis', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <!-- Harga Obat (Admin, Manager Gudang bisa edit; Kasir hanya lihat) -->
            <?php if (in_array($role, ['Admin', 'Manager Gudang', 'Kasir'])): ?>
                <?php echo nav_link('harga_obat.php', 'fa-tags', 'Harga Obat', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <!-- Laporan (Admin, Manager Gudang) -->
            <?php if (in_array($role, ['Admin', 'Manager Gudang'])): ?>
                <?php echo nav_link('laporan.php', 'fa-file-alt', 'Laporan', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <!-- User Management (Admin) -->
            <?php if ($role === 'Admin'): ?>
                <?php echo nav_link('admin_users.php', 'fa-users-cog', 'User Management', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <!-- Log Aktivitas (Admin only) -->
            <?php if ($role === 'Admin'): ?>
                <?php echo nav_link('activity_log.php', 'fa-history', 'Log Aktivitas', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Bottom: Profil & Keluar -->
        <div class="mt-auto flex flex-col gap-1.5">
            <?php echo nav_link('profil.php', 'fa-user', 'Profil', $current_page, $nav_base, $active_cls); ?>
            <a href="logout.php" class="flex items-center justify-center md:justify-start p-3 text-red-<?php echo ($role==='Super Admin') ? '400' : '500'; ?> hover:bg-red-<?php echo ($role==='Super Admin') ? '900/30' : '50'; ?> rounded-xl transition">
                <i class="fas fa-sign-out-alt w-5 text-center"></i>
                <span class="hidden md:inline ml-3">Keluar</span>
            </a>
        </div>
    </nav>
</aside>