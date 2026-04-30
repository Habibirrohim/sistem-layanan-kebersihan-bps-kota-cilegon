<?php
require_once 'config.php';

// Check login
checkLogin();

// Set page variables
$current_page = 'laporan';
$page_title = 'Laporan Harian - Monitoring Kebersihan';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $conn = getConnection();
        
        if ($_POST['action'] == 'verify_report' && $_SESSION['role'] == 'admin') {
            // Verify report
            $laporan_id = sanitizeInput($_POST['laporan_id']);
            $status = sanitizeInput($_POST['status']); // 'terverifikasi' or 'ditolak'
            $catatan = sanitizeInput($_POST['catatan'] ?? '');
            $verified_by = $_SESSION['user_id'];
            
            // Update laporan status
            $stmt = $conn->prepare("UPDATE laporan_harian SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $laporan_id);
            
            if ($stmt->execute()) {
                // Insert verification record
                $stmt = $conn->prepare("INSERT INTO verifikasi (laporan_id, status_verifikasi, catatan_verifikasi, verified_by) VALUES (?, ?, ?, ?)");
                $verify_status = ($status == 'terverifikasi') ? 'terima' : 'tolak';
                $stmt->bind_param("issi", $laporan_id, $verify_status, $catatan, $verified_by);
                $stmt->execute();
                
                // If verified (accepted), update jadwal_piket status to 'selesai'
                if ($status == 'terverifikasi') {
                    $stmt = $conn->prepare("
                        UPDATE jadwal_piket jp 
                        JOIN laporan_harian lh ON (jp.tanggal = lh.tanggal AND jp.ruangan = lh.ruangan)
                        SET jp.status = 'selesai' 
                        WHERE lh.id = ?
                    ");
                    $stmt->bind_param("i", $laporan_id);
                    $stmt->execute();
                }
                
                $_SESSION['success'] = 'Laporan berhasil diverifikasi!';
            } else {
                $_SESSION['error'] = 'Gagal memverifikasi laporan!';
            }
            
            header('Location: laporan.php');
            exit();
        } elseif ($_POST['action'] == 'submit_checklist' && $_SESSION['role'] == 'petugas') {
            // Submit new checklist
            $user_id = $_SESSION['user_id'];
            $jadwal_id = intval($_POST['jadwal_id'] ?? 0);
            $schedule_date = sanitizeInput($_POST['schedule_date'] ?? date('Y-m-d'));
            $ruangan = sanitizeInput($_POST['ruangan'] ?? '');
            $deskripsi = sanitizeInput($_POST['deskripsi'] ?? '');
            $assigned_employee = sanitizeInput($_POST['assigned_employee'] ?? '');
            
            // If jadwal_id is provided, use that specific schedule
            if ($jadwal_id > 0) {
                $stmt = $conn->prepare("SELECT user_id, tanggal, ruangan FROM jadwal_piket WHERE id = ?");
                $stmt->bind_param("i", $jadwal_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result) {
                    $assigned_user_id = $result['user_id'];
                    $tanggal = $result['tanggal'];
                    $ruangan = $result['ruangan'];
                } else {
                    $_SESSION['error'] = 'Jadwal tidak ditemukan!';
                    header('Location: jadwal.php');
                    exit();
                }
            } else {
                // Legacy mode - use current date and find schedule
                $tanggal = $schedule_date;
                $assigned_user_id = null;
                if (!empty($ruangan)) {
                    $stmt = $conn->prepare("
                        SELECT jp.user_id, jp.id as jadwal_id 
                        FROM jadwal_piket jp 
                        WHERE jp.tanggal = ? AND jp.ruangan = ?
                    ");
                    $stmt->bind_param("ss", $tanggal, $ruangan);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $assigned_user_id = $result ? $result['user_id'] : $user_id;
                    $jadwal_id = $result ? $result['jadwal_id'] : null;
                }
            }
            
            // Validate required fields
            if (empty($ruangan)) {
                $_SESSION['error'] = 'Ruangan harus diisi!';
                header('Location: form_checklist.php' . ($jadwal_id > 0 ? '?jadwal_id=' . $jadwal_id : ''));
                exit();
            }
            
            // Check if already submitted today for this room (prevent duplicate submissions for same room)
            $stmt = $conn->prepare("SELECT id FROM laporan_harian WHERE tanggal = ? AND ruangan = ?");
            $stmt->bind_param("ss", $tanggal, $ruangan);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows == 0) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/checklist_photos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Insert new report with assigned user_id and jadwal_id (so it appears under the scheduled employee's name)
                if ($jadwal_id) {
                    // Insert with jadwal_id if we found a matching schedule
                    $stmt = $conn->prepare("INSERT INTO laporan_harian (user_id, tanggal, ruangan, deskripsi, status, jadwal_id) VALUES (?, ?, ?, ?, 'menunggu', ?)");
                    $stmt->bind_param("isssi", $assigned_user_id, $tanggal, $ruangan, $deskripsi, $jadwal_id);
                } else {
                    // Insert without jadwal_id if no matching schedule found
                    $stmt = $conn->prepare("INSERT INTO laporan_harian (user_id, tanggal, ruangan, deskripsi, status) VALUES (?, ?, ?, ?, 'menunggu')");
                    $stmt->bind_param("isss", $assigned_user_id, $tanggal, $ruangan, $deskripsi);
                }
                
                if ($stmt->execute()) {
                    $laporan_id = $conn->insert_id;
                    
                    // Insert checklist items
                    $checklist_items = ['lantai', 'meja', 'kaca', 'dinding', 'sampah'];
                    $all_items_processed = true;
                    
                    foreach ($checklist_items as $item) {
                        if (isset($_POST[$item])) {
                            $status = $_POST[$item];
                            $kendala = $_POST["kendala_$item"] ?? '';
                            $foto_path = '';
                            
                            // Handle photo upload if status is 'kotor'
                            if ($status == 'kotor' && isset($_FILES["foto_$item"]) && $_FILES["foto_$item"]['error'] == 0) {
                                $file = $_FILES["foto_$item"];
                                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                                
                                if (in_array($file_extension, $allowed_extensions)) {
                                    $new_filename = $laporan_id . '_' . $item . '_' . time() . '.' . $file_extension;
                                    $foto_path = $upload_dir . $new_filename;
                                    
                                    if (move_uploaded_file($file['tmp_name'], $foto_path)) {
                                        // Photo uploaded successfully
                                    } else {
                                        $foto_path = '';
                                    }
                                }
                            }
                            
                            $stmt = $conn->prepare("INSERT INTO checklist_items (laporan_id, item_name, status, kendala, foto_path) VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("issss", $laporan_id, $item, $status, $kendala, $foto_path);
                            $stmt->execute();
                        } else {
                            $all_items_processed = false;
                        }
                    }
                    
                    if ($all_items_processed) {
                        $_SESSION['success'] = 'Checklist berhasil disubmit untuk ' . $assigned_employee . '!';
                    } else {
                        $_SESSION['success'] = 'Checklist berhasil disubmit untuk ' . $assigned_employee . '! (Beberapa item tidak terisi)';
                    }
                } else {
                    $_SESSION['error'] = 'Gagal menyimpan checklist: ' . $conn->error;
                }
            } else {
                $_SESSION['error'] = 'Checklist untuk ruangan ' . $ruangan . ' sudah diisi hari ini!';
            }
            
            header('Location: laporan.php');
            exit();
        }
    }
}

// Get reports based on role
$conn = getConnection();

if ($_SESSION['role'] == 'admin') {
    // Admin can see all reports including those created by admin without user_id
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        ORDER BY lh.created_at DESC
    ");
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Petugas can see all reports (including those created by admin) but only their own if user_id exists
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        ORDER BY lh.created_at DESC
    ");
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Include header
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen main-content transition-sidebar flex flex-col">
    <div class="flex-1 p-6 mt-5">
        <!-- Breadcrumb -->
        <nav class="mb-4">
            <ol class="flex items-center space-x-2 text-sm text-gray-600 font-medium">
                <li><a href="dashboard.php" class="hover:text-blue-600 text-base">Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-base"></i></li>
                <li class="text-gray-900 font-medium text-base">Laporan Harian</li>
            </ol>
        </nav>
        
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold">Laporan Harian</h2>
                <p class="text-gray-700">
                    <?php echo ($_SESSION['role'] == 'admin') ? 'Kelola dan verifikasi laporan kebersihan harian' : 'Kelola laporan kebersihan harian Anda'; ?>
                </p>
            </div>
            <?php if ($_SESSION['role'] == 'petugas'): ?>
            <div class="flex space-x-3">
                <a href="form_checklist.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Isi Checklist
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Pegawai</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ruangan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
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
                                        $status_text = 'Menunggu Verifikasi';
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
                                <span class="<?php echo $status_class; ?> px-2 py-1 rounded full text-sm"><?php echo $status_text; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($_SESSION['role'] == 'admin' && $report['status'] == 'menunggu'): ?>
                                <button onclick="openVerificationModal(<?php echo $report['id']; ?>, '<?php echo $report['nama_petugas']; ?>', '<?php echo date('d-m-Y', strtotime($report['tanggal'])); ?>')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 mr-2">
                                    <i class="fas fa-check mr-1"></i>Verifikasi
                                </button>
                                <?php endif; ?>
                                <button onclick="viewReportDetail(<?php echo $report['id']; ?>)" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                                    <i class="fas fa-eye mr-1"></i>Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Footer positioned at bottom -->
    <?php include 'includes/footer.php'; ?>
</div>

<!-- Verification Modal -->
<div id="verificationModal" class="fixed inset-0 bg-black bg-opacity-50 modal-overlay z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full modal-content">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-clipboard-check text-blue-600 mr-3"></i>
                        Verifikasi Laporan
                    </h3>
                    <p class="text-gray-600 mt-1">Review dan verifikasi laporan kebersihan harian</p>
                </div>
                <button onclick="closeVerificationModal()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-full">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Loading indicator -->
            <div id="modalLoading" class="hidden text-center py-12">
                <div class="flex flex-col items-center loading-pulse">
                    <div class="relative">
                        <i class="fas fa-spinner loading-spinner text-4xl mb-4"></i>
                        <div class="absolute inset-0 bg-blue-200 rounded-full animate-ping"></div>
                    </div>
                    <p class="text-gray-600 text-lg font-medium">Memuat detail laporan...</p>
                    <div class="mt-4 w-48 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full animate-pulse"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Mohon tunggu sebentar</p>
                </div>
            </div>
            
            <!-- Report Details -->
            <div id="modalReportDetails" class="hidden">
                <!-- Info Card -->
                <div class="info-card rounded-xl p-6 mb-6">
                    <h4 class="font-semibold mb-4 text-gray-900 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Informasi Laporan
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <i class="fas fa-calendar-alt text-blue-600 text-2xl mb-2"></i>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Tanggal</label>
                                <p id="modalDetailTanggal" class="text-gray-900 font-bold text-lg"></p>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <i class="fas fa-user text-green-600 text-2xl mb-2"></i>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Petugas</label>
                                <p id="modalDetailPetugas" class="text-gray-900 font-bold text-lg"></p>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="bg-white rounded-lg p-4 shadow-sm">
                                <i class="fas fa-door-open text-purple-600 text-2xl mb-2"></i>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Ruangan</label>
                                <p id="modalDetailRuangan" class="text-gray-900 font-bold text-lg"></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Checklist Details -->
                <div class="mb-6">
                    <h4 class="font-semibold mb-4 text-gray-900 flex items-center">
                        <i class="fas fa-tasks text-green-600 mr-2"></i>
                        Detail Checklist Kebersihan
                    </h4>
                    <div id="modalChecklistItems" class="space-y-4">
                        <!-- Checklist items will be loaded here -->
                    </div>
                </div>
                
                <!-- Description -->
                <div id="modalDescriptionSection" class="mb-6 hidden">
                    <h4 class="font-semibold mb-3 text-gray-900 flex items-center">
                        <i class="fas fa-comment-alt text-indigo-600 mr-2"></i>
                        Deskripsi Akhir
                    </h4>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                        <p id="modalDescription" class="text-gray-700 italic"></p>
                    </div>
                </div>
            </div>
            
            <!-- Verification Form -->
            <form method="POST" id="verificationForm">
                <input type="hidden" name="action" value="verify_report">
                <input type="hidden" name="laporan_id" id="modalLaporanId">
                
                <div class="verification-form-section p-6 mt-6">
                    <h4 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-gavel mr-2"></i>
                        Keputusan Verifikasi
                    </h4>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-3">Status Verifikasi</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center p-4 bg-white bg-opacity-20 rounded-lg cursor-pointer hover:bg-opacity-30 transition-all">
                                <input type="radio" name="status" value="terverifikasi" class="radio-custom mr-3" required>
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-300 text-xl mr-2"></i>
                                    <span class="font-medium">Terverifikasi</span>
                                </div>
                            </label>
                            <label class="flex items-center p-4 bg-white bg-opacity-20 rounded-lg cursor-pointer hover:bg-opacity-30 transition-all">
                                <input type="radio" name="status" value="ditolak" class="radio-custom mr-3" required>
                                <div class="flex items-center">
                                    <i class="fas fa-times-circle text-red-300 text-xl mr-2"></i>
                                    <span class="font-medium">Ditolak</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-2">Catatan Verifikasi (Opsional)</label>
                        <textarea name="catatan" class="w-full px-4 py-3 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-white bg-white bg-opacity-90 text-gray-800 placeholder-gray-500" rows="3" placeholder="Berikan catatan atau alasan untuk keputusan verifikasi..."></textarea>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex space-x-4 mt-6">
                    <button type="submit" class="flex-1 btn-verify text-white py-3 px-6 rounded-lg font-medium">
                        <i class="fas fa-check mr-2"></i>Proses Verifikasi
                    </button>
                    <button type="button" onclick="closeVerificationModal()" class="flex-1 btn-cancel text-white py-3 px-6 rounded-lg font-medium">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openVerificationModal(laporanId, petugas, tanggal) {
    document.getElementById('modalLaporanId').value = laporanId;
    document.getElementById('verificationModal').classList.remove('hidden');
    
    // Show loading
    document.getElementById('modalLoading').classList.remove('hidden');
    document.getElementById('modalReportDetails').classList.add('hidden');
    
    // Load report details via AJAX
    loadReportDetails(laporanId);
}

function loadReportDetails(laporanId) {
    fetch(`get_report_details.php?id=${laporanId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide loading and show details
                document.getElementById('modalLoading').classList.add('hidden');
                document.getElementById('modalReportDetails').classList.remove('hidden');
                
                // Populate report info
                document.getElementById('modalDetailTanggal').textContent = data.report.tanggal_formatted;
                document.getElementById('modalDetailPetugas').textContent = data.report.nama_petugas;
                document.getElementById('modalDetailRuangan').textContent = data.report.ruangan;
                
                // Populate checklist items
                const checklistContainer = document.getElementById('modalChecklistItems');
                checklistContainer.innerHTML = '';
                
                if (data.checklist_items && data.checklist_items.length > 0) {
                    data.checklist_items.forEach(item => {
                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'checklist-item rounded-lg p-4 bg-white';
                        
                        const statusClass = item.status === 'bersih' ? 'status-badge-bersih' : 
                                          item.status === 'kotor' ? 'status-badge-kotor' : 
                                          'status-badge-rusak';
                        
                        const statusText = item.status === 'bersih' ? 'Bersih' : 
                                         item.status === 'kotor' ? 'Kotor' : 'Rusak';
                        
                        const statusIcon = item.status === 'bersih' ? 'fas fa-check-circle' : 
                                         item.status === 'kotor' ? 'fas fa-times-circle' : 
                                         'fas fa-exclamation-triangle';
                        
                        let itemHTML = `
                            <div class="flex justify-between items-center mb-3">
                                <span class="font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-cleaning text-blue-600 mr-2"></i>
                                    ${item.item_name_display}
                                </span>
                                <span class="${statusClass} px-3 py-1 rounded-full text-sm font-semibold flex items-center">
                                    <i class="${statusIcon} mr-1"></i>
                                    ${statusText}
                                </span>
                            </div>
                        `;
                        
                        if (item.kendala) {
                            itemHTML += `
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mt-3 rounded">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                        <p class="text-sm text-yellow-800"><strong>Kendala:</strong> ${item.kendala}</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        if (item.foto_path) {
                            itemHTML += `
                                <div class="mt-4 flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <img src="${item.foto_path}" alt="Foto bukti" class="photo-thumbnail w-24 h-24 object-cover rounded-lg cursor-pointer" onclick="openImageModal('${item.foto_path}')">
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700 flex items-center">
                                            <i class="fas fa-camera text-blue-600 mr-2"></i>
                                            Foto Bukti
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">Klik untuk memperbesar</p>
                                    </div>
                                </div>
                            `;
                        }
                        
                        itemDiv.innerHTML = itemHTML;
                        checklistContainer.appendChild(itemDiv);
                    });
                } else {
                    checklistContainer.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                            <p class="text-lg">Tidak ada data checklist</p>
                        </div>
                    `;
                }
                
                // Show/hide description
                if (data.report.deskripsi) {
                    document.getElementById('modalDescription').textContent = data.report.deskripsi;
                    document.getElementById('modalDescriptionSection').classList.remove('hidden');
                } else {
                    document.getElementById('modalDescriptionSection').classList.add('hidden');
                }
            } else {
                alert('Gagal memuat detail laporan: ' + data.message);
                closeVerificationModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memuat detail laporan');
            closeVerificationModal();
        });
}

function closeVerificationModal() {
    document.getElementById('verificationModal').classList.add('hidden');
    document.getElementById('verificationForm').reset();
}

function viewReportDetail(laporanId) {
    window.location.href = 'detail_laporan.php?id=' + laporanId;
}

function openImageModal(imageSrc) {
    // Enhanced image modal with better styling
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4';
    modal.style.animation = 'fadeIn 0.3s ease-out';
    modal.innerHTML = `
        <div class="relative max-w-5xl max-h-full">
            <button onclick="this.parentElement.parentElement.remove()" class="absolute -top-12 right-0 text-white hover:text-gray-300 transition-colors text-xl bg-black bg-opacity-50 rounded-full p-3">
                <i class="fas fa-times"></i>
            </button>
            <div class="bg-white p-2 rounded-lg shadow-2xl">
                <img src="${imageSrc}" alt="Foto bukti" class="max-w-full max-h-[80vh] object-contain rounded">
            </div>
            <div class="text-center mt-4">
                <p class="text-white text-sm opacity-75">
                    <i class="fas fa-info-circle mr-1"></i>
                    Klik di luar gambar untuk menutup
                </p>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Close on click outside
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => this.remove(), 300);
        }
    });
    
    // Close on ESC key
    const handleEsc = (e) => {
        if (e.key === 'Escape') {
            modal.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => modal.remove(), 300);
            document.removeEventListener('keydown', handleEsc);
        }
    };
    document.addEventListener('keydown', handleEsc);
}

// Close modal when clicking outside
document.getElementById('verificationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVerificationModal();
    }
});

// Enhanced form submission with loading state
document.getElementById('verificationForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner loading-spinner mr-2"></i>Memproses...';
    submitBtn.classList.add('opacity-75');
    
    // Add small delay to show loading (optional)
    setTimeout(() => {
        // Form will submit naturally after this
    }, 500);
});

// Add success animation for radio buttons
document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Remove previous selection styling
        document.querySelectorAll('input[name="status"]').forEach(r => {
            r.closest('label').classList.remove('ring-2', 'ring-white', 'bg-opacity-40');
        });
        
        // Add selection styling
        this.closest('label').classList.add('ring-2', 'ring-white', 'bg-opacity-40');
        
        // Add a subtle animation
        this.closest('label').style.transform = 'scale(1.02)';
        setTimeout(() => {
            this.closest('label').style.transform = 'scale(1)';
        }, 200);
    });
});
</script>
