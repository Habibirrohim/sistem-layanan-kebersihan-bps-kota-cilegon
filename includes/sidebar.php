<!-- Sidebar -->
<div id="sidebar" class="fixed left-0 top-16 h-full w-64 bg-white shadow-lg transform sidebar-transition z-40 flex flex-col">

    <!-- Menu Area -->
    <div class="p-4 flex-1 mt-8">
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <!-- Admin Menu -->
            <div id="adminMenu" class="space-y-2">
                <a href="dashboard.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'dashboard') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-tachometer-alt text-orange-600"></i>
                    <span>Dashboard</span>
                </a>

                <div class="border-t border-gray-200 my-2"></div>

                <a href="laporan.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'laporan') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-file-alt text-orange-600"></i>
                    <span>Laporan & Verifikasi</span>
                </a>

                <a href="rekap_laporan.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'rekap_laporan') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-chart-line text-orange-600"></i>
                    <span>Rekap Laporan</span>
                </a>

                <div class="border-t border-gray-200 my-2"></div>

                <a href="jadwal.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'jadwal') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-calendar-alt text-orange-600"></i>
                    <span>Jadwal Piket</span>
                </a>

                <a href="rekap_jadwal.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'rekap_jadwal') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-chart-bar text-orange-600"></i>
                    <span>Rekap Jadwal</span>
                </a>
                <a href="users.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'users') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-users-cog text-orange-600"></i>
                    <span>Manajemen User</span>
                </a>
            </div>
        <?php else: ?>
            <!-- Petugas Menu -->
            <div id="petugasMenu" class="space-y-2">
                <a href="dashboard.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'dashboard') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-tachometer-alt text-orange-600"></i>
                    <span>Dashboard</span>
                </a>

                <a href="laporan.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'laporan') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-file-alt text-orange-600"></i>
                    <span>Laporan Harian</span>
                </a>

                <a href="jadwal.php" class="w-full text-left px-4 py-3 rounded-lg hover:bg-orange-50 flex items-center space-x-3 menu-item <?php echo ($current_page == 'jadwal') ? 'active bg-orange-100' : ''; ?>">
                    <i class="fas fa-calendar-alt text-orange-600"></i>
                    <span>Jadwal Piket</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Logo Bawah Sidebar -->
    <div class="p-2 border-t border-gray-200 flex justify-center">
        <img src="assets/images/bps.png"
            alt="Logo BPS"
            class="w-30 h-30 object-contain">
    </div>

</div>

<!-- Overlay -->
<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>