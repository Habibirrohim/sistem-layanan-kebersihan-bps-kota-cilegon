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
$current_page = 'rekap_laporan';
$page_title = 'Rekap Laporan Bulanan - Monitoring Kebersihan';

// Get filter parameters
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

// Get connection
$conn = getConnection();

// Get reports data
if ($_SESSION['role'] == 'admin') {
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        WHERE MONTH(lh.tanggal) = ? AND YEAR(lh.tanggal) = ?
        ORDER BY lh.tanggal DESC
    ");
    $stmt->bind_param("ii", $selected_month, $selected_year);
} else {
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        WHERE MONTH(lh.tanggal) = ? AND YEAR(lh.tanggal) = ?
        ORDER BY lh.tanggal DESC
    ");
    $stmt->bind_param("ii", $selected_month, $selected_year);
}

$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_laporan = count($reports);
$laporan_terverifikasi = count(array_filter($reports, function($r) { return $r['status'] == 'terverifikasi'; }));
$laporan_ditolak = count(array_filter($reports, function($r) { return $r['status'] == 'ditolak'; }));
$laporan_menunggu = count(array_filter($reports, function($r) { return $r['status'] == 'menunggu'; }));
$persentase_terverifikasi = $total_laporan > 0 ? round(($laporan_terverifikasi / $total_laporan) * 100, 1) : 0;

// Get reports by room
$room_stats = [];
foreach ($reports as $report) {
    $room = $report['ruangan'];
    if (!isset($room_stats[$room])) {
        $room_stats[$room] = ['total' => 0, 'terverifikasi' => 0];
    }
    $room_stats[$room]['total']++;
    if ($report['status'] == 'terverifikasi') {
        $room_stats[$room]['terverifikasi']++;
    }
}

// Include header
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="lg:ml-64 pt-16 min-h-screen main-content transition-sidebar flex flex-col">
    <div class="flex-1 p-6 mt-5">
        <!-- Breadcrumb -->
        <nav class="mb-4">
            <ol class="flex items-center space-x-2 text-sm text-gray-600 font-medium">
                <li><a href="dashboard.php" class="hover:text-blue-900 text-base">Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-base"></i></li>
                <li class="text-gray-900 font-medium text-base">Rekap Laporan Bulanan</li>
            </ol>
        </nav>
        
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold">Rekap Laporan Bulanan</h2>
                <p class="text-gray-700">Rekap dan analisis laporan harian bulanan</p>
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
                        <p class="text-sm text-gray-600">Total Laporan</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_laporan; ?></p>
                    </div>
                    <div class="bg-blue-100 p-5 rounded full">
                        <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Terverifikasi</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $laporan_terverifikasi; ?></p>
                    </div>
                    <div class="bg-green-100 p-5 rounded full">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Menunggu</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo $laporan_menunggu; ?></p>
                    </div>
                    <div class="bg-orange-100 p-5 rounded full">
                        <i class="fas fa-clock text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Tingkat Verifikasi</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $persentase_terverifikasi; ?>%</p>
                    </div>
                    <div class="bg-purple-100 p-5 rounded full">
                        <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Chart 1: Status Laporan -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Distribusi Status Laporan</h3>
                <?php if ($total_laporan > 0): ?>
                <div class="relative h-64">
                    <canvas id="statusChart"></canvas>
                </div>
                <?php else: ?>
                <div class="flex flex-col items-center justify-center h-64 text-gray-500">
                    <i class="fas fa-chart-pie text-6xl mb-4 text-gray-300"></i>
                    <p class="text-lg font-medium">Tidak ada data laporan</p>
                    <p class="text-sm">untuk bulan <?php echo date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Chart 2: Laporan per Ruangan -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Laporan per Ruangan</h3>
                <?php if (!empty($room_stats)): ?>
                <div class="space-y-3">
                    <?php foreach($room_stats as $room => $stats): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700"><?php echo $room; ?></span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500"><?php echo $stats['total']; ?> laporan</span>
                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $stats['total'] > 0 ? ($stats['terverifikasi'] / $stats['total']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="flex flex-col items-center justify-center h-32 text-gray-500">
                    <i class="fas fa-building text-4xl mb-2 text-gray-300"></i>
                    <p class="text-sm">Tidak ada data ruangan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    Detail Laporan <?php echo date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)); ?>
                </h3>
                <?php if ($total_laporan > 0): ?>
                <div class="flex space-x-2">
                    <button onclick="exportPDF()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </button>
                    <button onclick="exportExcel()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
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
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Petugas</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ruangan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="<?php echo ($_SESSION['role'] == 'admin') ? '5' : '4'; ?>" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center text-gray-500">
                                    <i class="fas fa-inbox text-6xl mb-4 text-gray-300"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data laporan</h3>
                                    <p class="text-sm">Belum ada laporan untuk bulan <?php echo date('F Y', mktime(0,0,0,$selected_month,1,$selected_year)); ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($reports as $report): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo date('d-m-Y', strtotime($report['tanggal'])); ?></td>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <td class="px-6 py-4"><?php echo $report['nama_petugas']; ?></td>
                            <?php endif; ?>
                            <td class="px-6 py-4"><?php echo $report['ruangan']; ?></td>
                            <td class="px-6 py-4">
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
                                <span class="<?php echo $status_class; ?> px-2 py-1 rounded-full text-sm"><?php echo $status_text; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="viewDetail(<?php echo $report['id']; ?>)" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">
                                    <i class="fas fa-eye mr-1"></i>Detail
                                </button>
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

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($total_laporan > 0): ?>
// Status Chart - Only render if there's data
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Terverifikasi', 'Menunggu', 'Ditolak'],
        datasets: [{
            data: [<?php echo $laporan_terverifikasi; ?>, <?php echo $laporan_menunggu; ?>, <?php echo $laporan_ditolak; ?>],
            backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = <?php echo $total_laporan; ?>;
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

function viewDetail(id) {
    window.location.href = 'detail_laporan.php?id=' + id;
}

function exportPDF() {
    const month = <?php echo $selected_month; ?>;
    const year = <?php echo $selected_year; ?>;
    window.location.href = `export_laporan_pdf.php?month=${month}&year=${year}`;
}

function exportExcel() {
    const month = <?php echo $selected_month; ?>;
    const year = <?php echo $selected_year; ?>;
    window.location.href = `export_laporan_excel.php?month=${month}&year=${year}`;
}
</script>
