<?php
require_once 'config.php';

// Check login
checkLogin();

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat mengakses halaman ini.';
    header('Location: dashboard.php');
    exit();
}

// Set page variables
$current_page = 'rekap_jadwal';
$page_title = 'Rekap Jadwal Bulanan - Monitoring Kebersihan';

// Get filter parameters
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

// Get connection
$conn = getConnection();

// Build query based on role
if ($_SESSION['role'] == 'admin') {
    $stmt = $conn->prepare("
        SELECT jp.*, u.nama as nama_petugas,
               lh.id as laporan_id, lh.status as laporan_status,
               v.status_verifikasi 
        FROM jadwal_piket jp 
        JOIN users u ON jp.user_id = u.id 
        LEFT JOIN laporan_harian lh ON (jp.tanggal = lh.tanggal AND jp.ruangan = lh.ruangan)
        LEFT JOIN verifikasi v ON lh.id = v.laporan_id
        WHERE MONTH(jp.tanggal) = ? AND YEAR(jp.tanggal) = ?
        ORDER BY jp.tanggal ASC
    ");
    $stmt->bind_param("ii", $selected_month, $selected_year);
} else {
    $stmt = $conn->prepare("
        SELECT jp.*, u.nama as nama_petugas,
               lh.id as laporan_id, lh.status as laporan_status,
               v.status_verifikasi 
        FROM jadwal_piket jp 
        LEFT JOIN users u ON jp.user_id = u.id 
        LEFT JOIN laporan_harian lh ON (jp.tanggal = lh.tanggal AND jp.ruangan = lh.ruangan)
        LEFT JOIN verifikasi v ON lh.id = v.laporan_id
        WHERE MONTH(jp.tanggal) = ? AND YEAR(jp.tanggal) = ?
        ORDER BY jp.tanggal ASC
    ");
    $stmt->bind_param("ii", $selected_month, $selected_year);
}

$stmt->execute();
$schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics with better logic
$total_jadwal = count($schedules);
$jadwal_selesai = 0;
$jadwal_belum = 0;
$jadwal_pending = 0;

foreach ($schedules as $schedule) {
    // Check if schedule has completed report (terverifikasi)
    if ($schedule['laporan_status'] == 'terverifikasi' || $schedule['status'] == 'selesai') {
        $jadwal_selesai++;
    } elseif ($schedule['laporan_id'] != null && $schedule['laporan_status'] == 'menunggu') {
        // Has report but waiting for verification
        $jadwal_pending++;
    } else {
        // No report submitted yet
        $jadwal_belum++;
    }
}

$persentase_selesai = $total_jadwal > 0 ? round(($jadwal_selesai / $total_jadwal) * 100, 1) : 0;

// Include header
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="lg:ml-64 pt-16 min-h-screen main-content transition-sidebar flex flex-col">
    <div class="flex-1 p-6 mt-5">
        <!-- Breadcrumb -->
        <nav class="mb-4">
            <ol class="flex items-center space-x-2 text-sm">
                <li><a href="dashboard.php" class="hover:text-blue-600 text-base font-medium">Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-base"></i></li>
                <li class="text-gray-900 font-medium text-base">Rekap Jadwal Bulanan</li>
            </ol>
        </nav>
        
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Rekap Jadwal Bulanan</h2>
                <p class="text-gray-600">Rekap dan analisis jadwal piket bulanan</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="flex space-x-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                    <select name="month" class="border border-gray-300 rounded-lg px-3 py-2">
                        <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo $selected_month == sprintf('%02d', $m) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                    <select name="year" class="border border-gray-300 rounded-lg px-3 py-2">
                        <?php for($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Jadwal</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_jadwal; ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded full">
                        <i class="fas fa-calendar text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Selesai</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $jadwal_selesai; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Terverifikasi</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded full">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Menunggu Verifikasi</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $jadwal_pending; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Laporan sudah ada</p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded full">
                        <i class="fas fa-hourglass-half text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Belum Dikerjakan</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $jadwal_belum; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Belum ada laporan</p>
                    </div>
                    <div class="bg-red-100 p-3 rounded full">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    Detail Jadwal <?php echo date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)); ?>
                </h3>
                <?php if ($total_jadwal > 0): ?>
                <div class="flex space-x-2">
                    <button onclick="exportPDF()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </button>
                    <button onclick="exportData()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Petugas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ruangan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Jadwal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Laporan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center text-gray-500">
                                    <i class="fas fa-calendar-times text-6xl mb-4 text-gray-300"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada jadwal piket</h3>
                                    <p class="text-sm">Belum ada jadwal untuk bulan <?php echo date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)); ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($schedules as $schedule): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo date('d-m-Y', strtotime($schedule['tanggal'])); ?></td>
                            <td class="px-6 py-4"><?php echo $schedule['nama_petugas'] ?? 'Tidak Ditugaskan'; ?></td>
                            <td class="px-6 py-4"><?php echo $schedule['ruangan']; ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                // Display status based on our improved logic
                                if($schedule['laporan_status'] == 'terverifikasi' || $schedule['status'] == 'selesai'): ?>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded full text-sm">Selesai</span>
                                <?php elseif($schedule['laporan_id'] != null && $schedule['laporan_status'] == 'menunggu'): ?>
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded full text-sm">Menunggu Verifikasi</span>
                                <?php elseif($schedule['laporan_status'] == 'ditolak'): ?>
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded full text-sm">Laporan Ditolak</span>
                                <?php else: ?>
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded full text-sm">Belum Dikerjakan</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if($schedule['laporan_status']): ?>
                                    <?php if($schedule['laporan_status'] == 'terverifikasi'): ?>
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded full text-sm">Terverifikasi</span>
                                    <?php elseif($schedule['laporan_status'] == 'ditolak'): ?>
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded full text-sm">Ditolak</span>
                                    <?php else: ?>
                                    <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded full text-sm">Menunggu</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded full text-sm">Belum Ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</div>

<script>
function exportData() {
    const month = <?php echo $selected_month; ?>;
    const year = <?php echo $selected_year; ?>;
    window.location.href = `export_jadwal.php?month=${month}&year=${year}`;
}

function exportPDF() {
    const month = <?php echo $selected_month; ?>;
    const year = <?php echo $selected_year; ?>;
    window.open(`export_jadwal_pdf.php?month=${month}&year=${year}`, '_blank');
}
</script>
