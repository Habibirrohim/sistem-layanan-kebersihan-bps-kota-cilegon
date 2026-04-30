<?php
require_once 'config.php';

// Check login and role
checkLogin();
if ($_SESSION['role'] != 'petugas') {
    header('Location: dashboard.php');
    exit();
}

// Set page variables
$current_page = 'laporan';
$page_title = 'Form Checklist - Monitoring Kebersihan';

// Check if jadwal_id is provided
$jadwal_id = isset($_GET['jadwal_id']) ? intval($_GET['jadwal_id']) : 0;

// Check if already submitted today
$conn = getConnection();
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// If jadwal_id is provided, get specific schedule and check if report already exists
if ($jadwal_id > 0) {
    $stmt = $conn->prepare("
        SELECT jp.*, u.nama as nama_petugas,
               lh.id as existing_report_id
        FROM jadwal_piket jp 
        LEFT JOIN users u ON jp.user_id = u.id 
        LEFT JOIN laporan_harian lh ON (jp.tanggal = lh.tanggal AND jp.ruangan = lh.ruangan)
        WHERE jp.id = ?
    ");
    $stmt->bind_param("i", $jadwal_id);
    $stmt->execute();
    $schedule_result = $stmt->get_result();

    if ($schedule_result->num_rows == 0) {
        $_SESSION['error'] = 'Jadwal tidak ditemukan!';
        header('Location: jadwal.php');
        exit();
    }

    $selected_schedule = $schedule_result->fetch_assoc();

    // Check if report already exists for this schedule
    if ($selected_schedule['existing_report_id']) {
        $_SESSION['error'] = 'Laporan untuk jadwal ini sudah dibuat!';
        header('Location: jadwal.php');
        exit();
    }

    // Check if user is assigned to this schedule (allow admin to fill any schedule)
    if ($_SESSION['role'] != 'admin' && $selected_schedule['user_id'] != $user_id) {
        $_SESSION['error'] = 'Anda tidak memiliki akses untuk mengisi laporan jadwal ini!';
        header('Location: jadwal.php');
        exit();
    }

    $default_room = $selected_schedule['ruangan'];
    $assigned_employee = $selected_schedule['nama_petugas'] ? $selected_schedule['nama_petugas'] : $_SESSION['nama'];
    $schedule_date = $selected_schedule['tanggal'];
} else {
    // Legacy mode - check general submission for today
    $stmt = $conn->prepare("SELECT id FROM laporan_harian WHERE user_id = ? AND tanggal = ?");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $already_submitted = $stmt->get_result()->num_rows > 0;

    // Get today's schedules (any schedule for today)
    $stmt = $conn->prepare("
        SELECT jp.*, u.nama as nama_petugas 
        FROM jadwal_piket jp 
        LEFT JOIN users u ON jp.user_id = u.id 
        WHERE jp.tanggal = ? 
        ORDER BY jp.ruangan ASC
    ");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $today_schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get the first available schedule for today (auto-assign room)
    $default_room = '';
    $assigned_employee = '';
    $schedule_date = $today;
    if (!empty($today_schedules)) {
        $default_room = $today_schedules[0]['ruangan']; // Use first available room
        $assigned_employee = $today_schedules[0]['nama_petugas'] ? $today_schedules[0]['nama_petugas'] : 'Tidak ada petugas';
    }

    $already_submitted = false; // Override for legacy mode
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
            <ol class="flex items-center space-x-2 text-sm text-gray-600">
                <li><a href="dashboard.php" class="hover:text-blue-600 text-base font-medium">Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-base"></i></li>
                <li><a href="laporan.php" class="hover:text-blue-600 text-base font-medium">Laporan Harian</a></li>
                <li><i class="fas fa-chevron-right text-base"></i></li>
                <li class="text-gray-900 font-medium text-base">Form Checklist</li>
            </ol>
        </nav>

        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Form Checklist Kebersihan</h2>
                <p class="text-gray-600">Isi checklist kebersihan sesuai jadwal piket</p>
            </div>
            <div class="flex space-x-3">
                <a href="laporan.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Laporan
                </a>
            </div>
        </div>

        <?php if ($jadwal_id > 0): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    <div>
                        <strong>Mengisi laporan untuk jadwal:</strong><br>
                        <span class="text-sm">
                            Tanggal: <strong><?php echo date('d-m-Y', strtotime($schedule_date)); ?></strong> |
                            Ruangan: <strong><?php echo $default_room; ?></strong> |
                            Petugas: <strong><?php echo $assigned_employee; ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($today_schedules)): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-info-circle mr-2"></i>
                    Jadwal piket hari ini: <strong><?php echo $default_room; ?></strong> - Petugas: <strong><?php echo $assigned_employee; ?></strong>
                </div>
            <?php else: ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Tidak ada jadwal piket untuk hari ini.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" action="laporan.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_checklist">
                <input type="hidden" name="ruangan" value="<?php echo $default_room; ?>">
                <input type="hidden" name="assigned_employee" value="<?php echo $assigned_employee; ?>">
                <input type="hidden" name="jadwal_id" value="<?php echo $jadwal_id; ?>">
                <input type="hidden" name="schedule_date" value="<?php echo $schedule_date; ?>">>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                        <input type="text" value="<?php echo date('d-m-Y'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                        <p class="text-xs text-gray-500 mt-1">Tanggal otomatis sesuai hari ini</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pegawai</label>
                        <input type="text" value="<?php echo $assigned_employee; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                        <p class="text-xs text-gray-500 mt-1">Sesuai petugas yang ditugaskan</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ruangan</label>
                        <input type="text" value="<?php echo $default_room ? $default_room : 'Tidak ada jadwal hari ini'; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                        <p class="text-xs text-gray-500 mt-1">Otomatis sesuai jadwal piket hari ini</p>
                    </div>
                </div>

                <h3 class="text-lg font-semibold mb-4">Checklist Kebersihan</h3>
                <div class="space-y-6">
                    <!-- Kebersihan Lantai -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-3">Kebersihan Lantai</h4>
                        <div class="space-y-3">
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="lantai" value="bersih" class="mr-2" onchange="toggleKendala('lantai', false)" required>
                                    <span class="text-green-600">Bersih</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="lantai" value="kotor" class="mr-2" onchange="toggleKendala('lantai', true)" required>
                                    <span class="text-red-600">Kotor</span>
                                </label>
                            </div>
                            <div id="kendala-lantai" class="hidden space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kendala</label>
                                    <textarea name="kendala_lantai" class="w-full px-3 py-2 border border-gray-300 rounded-lg" rows="2" placeholder="Deskripsikan kendala yang dihadapi..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti <span class="text-red-500">*</span></label>
                                    <input type="file" name="foto_lantai" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <p class="text-xs text-gray-500 mt-1">Upload foto kondisi lantai yang kotor</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kebersihan Meja dan Kursi -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-3">Kebersihan dan Kerapihan Meja dan Kursi</h4>
                        <div class="space-y-3">
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="meja" value="bersih" class="mr-2" onchange="toggleKendala('meja', false)" required>
                                    <span class="text-green-600">Bersih</span>
                                </label>
                                <label class="flex items-cen    ter">
                                    <input type="radio" name="meja" value="kotor" class="mr-2" onchange="toggleKendala('meja', true)" required>
                                    <span class="text-red-600">Kotor</span>
                                </label>
                            </div>
                            <div id="kendala-meja" class="hidden space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kendala</label>
                                    <textarea name="kendala_meja" class="w-full px-3 py-2 border border-gray-300 rounded-lg" rows="2" placeholder="Deskripsikan kendala yang dihadapi..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti <span class="text-red-500">*</span></label>
                                    <input type="file" name="foto_meja" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <p class="text-xs text-gray-500 mt-1">Upload foto kondisi meja yang kotor</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kebersihan Kaca -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-3">Kebersihan Kaca</h4>
                        <div class="space-y-3">
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="kaca" value="bersih" class="mr-2" onchange="toggleKendala('kaca', false)" required>
                                    <span class="text-green-600">Bersih</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="kaca" value="kotor" class="mr-2" onchange="toggleKendala('kaca', true)" required>
                                    <span class="text-red-600">Kotor</span>
                                </label>
                            </div>
                            <div id="kendala-kaca" class="hidden space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kendala</label>
                                    <textarea name="kendala_kaca" class="w-full px-3 py-2 border border-gray-300 rounded-lg" rows="2" placeholder="Deskripsikan kendala yang dihadapi..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti <span class="text-red-500">*</span></label>
                                    <input type="file" name="foto_kaca" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <p class="text-xs text-gray-500 mt-1">Upload foto kondisi kaca yang kotor</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kebersihan Dinding -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-3">Kebersihan Dinding/Loteng dari Jaring Laba-laba</h4>
                        <div class="space-y-3">
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="dinding" value="bersih" class="mr-2" onchange="toggleKendala('dinding', false)" required>
                                    <span class="text-green-600">Bersih</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="dinding" value="kotor" class="mr-2" onchange="toggleKendala('dinding', true)" required>
                                    <span class="text-red-600">Kotor</span>
                                </label>
                            </div>
                            <div id="kendala-dinding" class="hidden space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kendala</label>
                                    <textarea name="kendala_dinding" class="w-full px-3 py-2 border border-gray-300 rounded-lg" rows="2" placeholder="Deskripsikan kendala yang dihadapi..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti <span class="text-red-500">*</span></label>
                                    <input type="file" name="foto_dinding" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <p class="text-xs text-gray-500 mt-1">Upload foto kondisi dinding yang kotor</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kebersihan Tong Sampah -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-3">Kebersihan Tong Sampah/Mengganti Kantong Plastik</h4>
                        <div class="space-y-3">
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="sampah" value="bersih" class="mr-2" onchange="toggleKendala('sampah', false)" required>
                                    <span class="text-green-600">Bersih</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="sampah" value="kotor" class="mr-2" onchange="toggleKendala('sampah', true)" required>
                                    <span class="text-red-600">Kotor</span>
                                </label>
                            </div>
                            <div id="kendala-sampah" class="hidden space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kendala</label>
                                    <textarea name="kendala_sampah" class="w-full px-3 py-2 border border-gray-300 rounded-lg" rows="2" placeholder="Deskripsikan kendala yang dihadapi..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti <span class="text-red-500">*</span></label>
                                    <input type="file" name="foto_sampah" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    <p class="text-xs text-gray-500 mt-1">Upload foto kondisi tong sampah yang kotor</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Akhir Laporan</label>
                    <textarea name="deskripsi" class="w-full px-3 py-2 border border-gray-300 rounded-lg" rows="4" placeholder="Berikan deskripsi keseluruhan kondisi kebersihan ruangan..."></textarea>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row gap-3">

                    <button type="submit"
                        class="w-full sm:w-auto bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
                        <?php echo ($jadwal_id > 0 ? '' : (empty($today_schedules) ? 'disabled' : '')); ?>>
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Laporan
                    </button>

                    <a href="laporan.php"
                        class="w-full sm:w-auto text-center bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali ke Laporan
                    </a>

                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</div>

<script>
    function toggleKendala(itemName, showKendala) {
        const kendalaDiv = document.getElementById('kendala-' + itemName);
        const textarea = kendalaDiv.querySelector('textarea');
        const fileInput = kendalaDiv.querySelector('input[type="file"]');

        if (showKendala) {
            kendalaDiv.classList.remove('hidden');
            textarea.required = true;
            if (fileInput) {
                fileInput.required = true;
            }
        } else {
            kendalaDiv.classList.add('hidden');
            textarea.required = false;
            textarea.value = '';
            if (fileInput) {
                fileInput.required = false;
                fileInput.value = '';
            }
        }
    }
</script>