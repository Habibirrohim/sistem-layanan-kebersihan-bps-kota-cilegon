<?php
require_once 'config.php';

// Check login
checkLogin();

// Get report ID
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$report_id) {
    header('Location: laporan.php');
    exit();
}

// Set page variables
$current_page = 'laporan';
$page_title = 'Detail Laporan - Monitoring Kebersihan';

$conn = getConnection();

// Get report details
if ($_SESSION['role'] == 'admin') {
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        WHERE lh.id = ?
    ");
    $stmt->bind_param("i", $report_id);
} else {
    // Petugas can see all reports (not just their own)
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        WHERE lh.id = ?
    ");
    $stmt->bind_param("i", $report_id);
}

$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    $_SESSION['error'] = 'Laporan tidak ditemukan atau Anda tidak memiliki akses ke laporan ini.';
    header('Location: laporan.php');
    exit();
}

// Get checklist items
$stmt = $conn->prepare("SELECT * FROM checklist_items WHERE laporan_id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$checklist_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get verification details if verified
$verification = null;
if ($report['status'] != 'menunggu') {
    $stmt = $conn->prepare("
        SELECT v.*, u.nama as verified_by_name 
        FROM verifikasi v 
        JOIN users u ON v.verified_by = u.id 
        WHERE v.laporan_id = ?
    ");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $verification = $stmt->get_result()->fetch_assoc();
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
                <li><a href="dashboard.php" class="hover:text-blue-600 font-medium text-base">Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-base"></i></li>
                <li><a href="laporan.php" class="hover:text-blue-600 font-medium text-base">Laporan Harian</a></li>
                <li><i class="fas fa-chevron-right text-base"></i></li>
                <li class="text-gray-900 font-medium text-base">Detail Laporan</li>
            </ol>
        </nav>

        <div class="mb-4 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-gray-800">Detail Laporan Harian</h2>
                <p class="text-gray-600">Laporan tanggal <?php echo date('d-m-Y', strtotime($report['tanggal'])); ?></p>
            </div>
            <a href="laporan.php"
                class="inline-flex items-center justify-center 
          text-xs sm:text-sm
          px-3 py-2 sm:px-4 sm:py-2
          bg-blue-600 text-white rounded-md
          hover:bg-blue-700 transition">
                <i class="fas fa-arrow-left mr-1 sm:mr-2 text-xs sm:text-sm"></i>
                Kembali
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- Report Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 pb-6 border-b">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <p class="text-gray-900 font-medium"><?php echo date('d-m-Y', strtotime($report['tanggal'])); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pegawai</label>
                    <p class="text-gray-900 font-medium"><?php echo $report['nama_petugas']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ruangan</label>
                    <p class="text-gray-900 font-medium"><?php echo $report['ruangan']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <?php
                    $status_class = '';
                    $status_text = '';
                    switch ($report['status']) {
                        case 'menunggu':
                            $status_class = 'bg-orange-100 text-orange-800';
                            $status_text = 'Menunggu Verifikasi';
                            break;
                        case 'terverifikasi':
                            $status_class = 'bg-green-100 text-green-800';
                            $status_text = 'Terverifikasi';
                            break;
                        case 'ditolak':
                            $status_class = 'bg-red-100 text-red-800';
                            $status_text = 'Ditolak';
                            break;
                    }
                    ?>
                    <span class="<?php echo $status_class; ?> px-3 py-1 rounded full text-sm font-medium"><?php echo $status_text; ?></span>
                </div>
            </div>

            <!-- Checklist Items -->
            <h3 class="text-lg font-semibold mb-4">Detail Checklist Kebersihan</h3>
            <div class="space-y-4 mb-6">
                <?php
                $item_names = [
                    'lantai' => 'Kebersihan Lantai',
                    'meja' => 'Kebersihan dan Kerapihan Meja dan Kursi',
                    'kaca' => 'Kebersihan Kaca',
                    'dinding' => 'Kebersihan Dinding/Loteng dari Jaring Laba-laba',
                    'sampah' => 'Kebersihan Tong Sampah/Mengganti Kantong Plastik'
                ];

                foreach ($checklist_items as $item):
                ?>
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-medium"><?php echo $item_names[$item['item_name']] ?? ucfirst($item['item_name']); ?></span>
                            <?php
                            $item_status_class = '';
                            $item_status_text = '';
                            switch ($item['status']) {
                                case 'bersih':
                                    $item_status_class = 'bg-green-100 text-green-800';
                                    $item_status_text = 'Bersih';
                                    break;
                                case 'kotor':
                                    $item_status_class = 'bg-red-100 text-red-800';
                                    $item_status_text = 'Kotor';
                                    break;
                                case 'rusak':
                                    $item_status_class = 'bg-yellow-100 text-yellow-800';
                                    $item_status_text = 'Rusak';
                                    break;
                            }
                            ?>
                            <span class="<?php echo $item_status_class; ?> px-2 py-1 rounded full text-sm font-medium"><?php echo $item_status_text; ?></span>
                        </div>
                        <?php if ($item['kendala']): ?>
                            <p class="text-sm text-gray-600 mt-2">
                                <strong>Kendala:</strong> <?php echo htmlspecialchars($item['kendala']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($item['foto_path']): ?>
                            <div class="mt-3">
                                <img src="<?php echo $item['foto_path']; ?>" alt="Foto bukti" class="w-24 h-24 object-cover rounded border cursor-pointer" onclick="openImageModal('<?php echo $item['foto_path']; ?>')">
                                <p class="text-xs text-gray-500 mt-1">Foto bukti (klik untuk memperbesar)</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Description -->
            <?php if ($report['deskripsi']): ?>
                <div class="mb-6 pb-6 border-b">
                    <h4 class="font-medium mb-2 text-gray-900">Deskripsi Akhir</h4>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($report['deskripsi'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Verification Details -->
            <?php if ($verification): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-medium mb-3 text-gray-900">Detail Verifikasi</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Diverifikasi oleh</label>
                            <p class="text-gray-900"><?php echo $verification['verified_by_name']; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Verifikasi</label>
                            <p class="text-gray-900"><?php echo date('d-m-Y H:i', strtotime($verification['verified_at'])); ?></p>
                        </div>
                    </div>
                    <?php if ($verification['catatan_verifikasi']): ?>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Verifikasi</label>
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($verification['catatan_verifikasi'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4">
    <div class="relative max-w-4xl max-h-full">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <img id="modalImage" src="" alt="Foto bukti" class="max-w-full max-h-full object-contain">
    </div>
</div>

<script>
    function openImageModal(imageSrc) {
        document.getElementById('modalImage').src = imageSrc;
        document.getElementById('imageModal').classList.remove('hidden');
    }

    function closeImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeImageModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });
</script>