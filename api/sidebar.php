<?php
/**
 * sidebar.php — Komponen sidebar universal
 * Include setelah koneksi.php + autentikasi.php + log_aktivitas.php
 * Variabel yang dibutuhkan: $role, $users
 */
$current_page = basename($_SERVER['PHP_SELF']);

$role_icon = match($role ?? '') {
    'Super Admin'    => ['icon' => 'fa-crown',         'bg' => 'bg-amber-400',   'text' => 'text-slate-900'],
    'Admin'          => ['icon' => 'fa-shield-alt',    'bg' => 'bg-blue-600',    'text' => 'text-white'],
    'Manager Gudang' => ['icon' => 'fa-warehouse',     'bg' => 'bg-emerald-500', 'text' => 'text-white'],
    'Apoteker'       => ['icon' => 'fa-mortar-pestle', 'bg' => 'bg-purple-600',  'text' => 'text-white'],
    'Kasir'          => ['icon' => 'fa-cash-register', 'bg' => 'bg-orange-500',  'text' => 'text-white'],
    default          => ['icon' => 'fa-clock',         'bg' => 'bg-slate-400',   'text' => 'text-white'],
};

$nav_base   = 'text-slate-400 hover:bg-slate-50 hover:text-blue-600';
$active_cls = 'bg-blue-600 text-white shadow-xl shadow-blue-100';

if (!function_exists('nav_link')) {
    function nav_link($href, $icon, $label, $current, $base, $active) {
        $is  = (basename($current) === basename($href));
        $cls = $is ? $active : $base;
        return "<a href=\"$href\" class=\"flex items-center justify-center md:justify-start p-3 $cls rounded-xl transition\">
            <i class=\"fas $icon w-5 text-center\"></i>
            <span class=\"hidden md:inline ml-3\">$label</span>
        </a>";
    }
}
?>
<aside class="w-20 md:w-64 bg-white border-r border-slate-100 flex flex-col items-center py-8 sticky top-0 h-screen z-50 overflow-y-auto">
    <!-- Logo -->
    <div class="mb-10 w-10 h-10 <?php echo $role_icon['bg']; ?> rounded-xl flex items-center justify-center <?php echo $role_icon['text']; ?> shadow-lg shrink-0">
        <i class="fas fa-pills text-lg"></i>
    </div>

    <nav class="flex flex-col gap-1.5 w-full px-4 font-bold h-full text-[12px]">

        <?php if (($role ?? '') === 'Super Admin'): ?>
            <?php echo nav_link('super_admin_dashboard.php', 'fa-home',           'Dashboard',    $current_page, $nav_base, $active_cls); ?>
            <?php echo nav_link('super_admin_dashboard.php', 'fa-clinic-medical', 'Semua Apotek', $current_page, $nav_base, $active_cls); ?>
            <?php echo nav_link('super_admin_users.php',     'fa-users-cog',      'Semua User',   $current_page, $nav_base, $active_cls); ?>
            <?php echo nav_link('activity_log.php',          'fa-history',        'Log Aktivitas',$current_page, $nav_base, $active_cls); ?>
            <a href="landing.php" target="_blank"
               class="flex items-center justify-center md:justify-start p-3 <?php echo $nav_base; ?> rounded-xl transition">
                <i class="fas fa-globe w-5 text-center"></i>
                <span class="hidden md:inline ml-3">Landing Page</span>
            </a>

        <?php else: ?>
            <?php
            $home = ($role === 'Kasir') ? 'kasir_dashboard.php' : 'dashboard.php';
            echo nav_link($home, 'fa-home', 'Beranda', $current_page, $nav_base, $active_cls);
            ?>
            <?php echo nav_link('stok_obat.php', 'fa-box', 'Stok Obat', $current_page, $nav_base, $active_cls); ?>

            <?php if (in_array($role, ['Admin', 'Apoteker'])): ?>
                <?php echo nav_link('racikan_obat.php', 'fa-mortar-pestle', 'Racikan Obat', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <?php if (in_array($role, ['Admin', 'Manager Gudang', 'Apoteker', 'Kasir'])): ?>
                <?php echo nav_link('analisis.php', 'fa-chart-bar', 'Analisis', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <?php if (in_array($role, ['Admin', 'Manager Gudang', 'Kasir'])): ?>
                <?php echo nav_link('harga_obat.php', 'fa-tags', 'Harga Obat', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <?php if (in_array($role, ['Admin', 'Manager Gudang'])): ?>
                <?php echo nav_link('laporan.php', 'fa-file-alt', 'Laporan', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <?php if ($role === 'Admin'): ?>
                <?php echo nav_link('admin_users.php', 'fa-users-cog', 'User Management', $current_page, $nav_base, $active_cls); ?>
                <?php echo nav_link('activity_log.php', 'fa-history', 'Log Aktivitas', $current_page, $nav_base, $active_cls); ?>
            <?php endif; ?>

            <!-- Link Cek Stok Publik untuk semua role -->
            <a href="landing.php" target="_blank"
               class="flex items-center justify-center md:justify-start p-3 text-slate-400 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition border border-dashed border-slate-200 hover:border-blue-300 mt-1">
                <i class="fas fa-store w-5 text-center text-xs"></i>
                <span class="hidden md:inline ml-3">Cek Stok Publik</span>
            </a>
        <?php endif; ?>

        <!-- Bottom: Profil & Keluar -->
        <div class="mt-auto flex flex-col gap-1.5 pt-2">
            <?php echo nav_link('profil.php', 'fa-user', 'Profil', $current_page, $nav_base, $active_cls); ?>
            <a href="logout.php"
               class="flex items-center justify-center md:justify-start p-3 text-red-500 hover:bg-red-50 rounded-xl transition">
                <i class="fas fa-sign-out-alt w-5 text-center"></i>
                <span class="hidden md:inline ml-3">Keluar</span>
            </a>
        </div>
    </nav>
</aside>