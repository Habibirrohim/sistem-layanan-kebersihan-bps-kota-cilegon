<?php
require_once 'config.php';

// Check login
checkLogin();

// Set page variables
$current_page = 'dashboard';
$page_title = 'Dashboard - Monitoring Kebersihan';

// Get statistics based on role
if ($_SESSION['role'] == 'admin') {
    // Admin statistics
    $conn = getConnection();
    
    // Total laporan harian
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM laporan_harian");
    $stmt->execute();
    $total_laporan = $stmt->get_result()->fetch_assoc()['total'];
    
    // Menunggu verifikasi
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM laporan_harian WHERE status = 'menunggu'");
    $stmt->execute();
    $menunggu_verifikasi = $stmt->get_result()->fetch_assoc()['total'];
    
    // Jadwal mendatang
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jadwal_piket WHERE tanggal >= CURDATE() AND status = 'belum'");
    $stmt->execute();
    $jadwal_mendatang = $stmt->get_result()->fetch_assoc()['total'];
    
    // Recent reports - show all reports including admin-created ones
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        ORDER BY lh.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Recent schedules
    $stmt = $conn->prepare("
        SELECT jp.*, u.nama as nama_petugas 
        FROM jadwal_piket jp 
        JOIN users u ON jp.user_id = u.id 
        WHERE jp.tanggal >= CURDATE() 
        ORDER BY jp.tanggal ASC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} else {
    // Petugas statistics - show all reports for consistency
    $conn = getConnection();
    $user_id = $_SESSION['user_id'];
    
    // Total laporan (all reports, not just user's own)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM laporan_harian");
    $stmt->execute();
    $total_laporan = $stmt->get_result()->fetch_assoc()['total'];
    
    // Menunggu verifikasi (all pending reports)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM laporan_harian WHERE status = 'menunggu'");
    $stmt->execute();
    $menunggu_verifikasi = $stmt->get_result()->fetch_assoc()['total'];
    
    // Jadwal mendatang - show all upcoming schedules
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jadwal_piket WHERE tanggal >= CURDATE() AND status = 'belum'");
    $stmt->execute();
    $jadwal_mendatang = $stmt->get_result()->fetch_assoc()['total'];
    
    // My recent reports - show all reports for petugas to see admin inputs too
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        ORDER BY lh.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Recent schedules - show all schedules for petugas to see admin assignments
    $stmt = $conn->prepare("
        SELECT jp.*, u.nama as nama_petugas 
        FROM jadwal_piket jp 
        LEFT JOIN users u ON jp.user_id = u.id 
        WHERE jp.tanggal >= CURDATE() 
        ORDER BY jp.tanggal ASC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Include header
include 'includes/header.php';
include 'includes/sidebar.php';
?>


<!-- Main Content -->
<div class="lg:ml-64 pt-16 min-h-screen main-content transition-sidebar flex flex-col">
    <div class="flex-1 p-6 mt-5">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Dashboard <?php echo ucfirst($_SESSION['role']); ?></h2>
                <p class="text-base text-gray-900">Selamat datang di sistem monitoring kebersihan kantor BPS Kota Cilegon</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <a href="laporan.php" class="bg-white rounded-lg shadow-md p-6 card-hover hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-16px text-gray-800 font-bold">Total Laporan Harian</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_laporan; ?></p>
                        <p class="text-16px text-gray-800">Laporan yang telah diinput</p>
                    </div>
                    <div class="bg-blue-100 p-5 rounded full">
                        <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
            </a>
            <a href="<?php echo ($_SESSION['role'] == 'admin') ? 'laporan.php' : 'laporan.php'; ?>" class="bg-white rounded-lg shadow-md p-6 card-hover hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-16px text-gray-800 font-bold">Menunggu Verifikasi</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo $menunggu_verifikasi; ?></p>
                        <p class="text-16px text-gray-800">Laporan belum diverifikasi</p>
                    </div>
                    <div class="bg-orange-100 p-5 rounded full">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
                    </div>
                </div>
            </a>
            <a href="jadwal.php" class="bg-white rounded-lg shadow-md p-5 card-hover hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-16px text-gray-800 font-bold">Jadwal Mendatang</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $jadwal_mendatang; ?></p>
                        <p class="text-16px text-gray-800">Jadwal piket akan datang</p>
                    </div>
                    <div class="bg-green-100 p-5 rounded full">
                        <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Reports Preview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">Laporan Harian Terbaru</h3>
                    <a href="laporan.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Tanggal</th>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <th class="px-4 py-2 text-left">Nama Pegawai</th>
                                <th class="px-4 py-2 text-left">Ruangan</th>
                                <?php else: ?>
                                <th class="px-4 py-2 text-left">Ruangan</th>
                                <?php endif; ?>
                                <th class="px-4 py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_reports as $report): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo date('d-m-Y', strtotime($report['tanggal'])); ?></td>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <td class="px-4 py-2"><?php echo $report['nama_petugas']; ?></td>
                                <td class="px-4 py-2"><?php echo $report['ruangan'] ?? 'N/A'; ?></td>
                                <?php else: ?>
                                <td class="px-4 py-2"><?php echo $report['ruangan'] ?? 'N/A'; ?></td>
                                <?php endif; ?>
                                <td class="px-4 py-2">
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch($report['status']) {
                                        case 'menunggu':
                                            $status_class = 'bg-orange-100 text-orange-800 rounded full';
                                            $status_text = 'Menunggu';
                                            break;
                                        case 'terverifikasi':
                                            $status_class = 'bg-green-100 text-green-800 rounded full';
                                            $status_text = 'Terverifikasi';
                                            break;
                                        case 'ditolak':
                                            $status_class = 'bg-red-100 text-red-800 rounded full';
                                            $status_text = 'Ditolak';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?> px-2 py-1 rounded full text-xs"><?php echo $status_text; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">Jadwal Piket Terbaru</h3>
                    <a href="jadwal.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Tanggal</th>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <th class="px-4 py-2 text-left">Nama Pegawai</th>
                                <?php endif; ?>
                                <th class="px-4 py-2 text-left">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_schedules as $schedule): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo date('d-m-Y', strtotime($schedule['tanggal'])); ?></td>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <td class="px-4 py-2"><?php echo $schedule['nama_petugas']; ?></td>
                                <?php endif; ?>
                                <td class="px-4 py-2"><?php echo $schedule['ruangan']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</div>
