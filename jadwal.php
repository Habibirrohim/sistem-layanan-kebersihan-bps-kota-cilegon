<?php
require_once 'config.php';

// Check login
checkLogin();

// Set page variables
$current_page = 'jadwal';
$page_title = 'Jadwal Piket - Monitoring Kebersihan';

// Handle form submissions (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'admin') {
    $conn = getConnection();

    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_schedule') {
            $tanggal = sanitizeInput($_POST['tanggal']);
            $user_id = sanitizeInput($_POST['user_id']);
            $ruangan = sanitizeInput($_POST['ruangan']);

            $stmt = $conn->prepare("INSERT INTO jadwal_piket (tanggal, user_id, ruangan) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $tanggal, $user_id, $ruangan);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Jadwal piket berhasil ditambahkan!';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan jadwal piket!';
            }
        } elseif ($_POST['action'] == 'edit_schedule') {
            $id = sanitizeInput($_POST['id']);
            $tanggal = sanitizeInput($_POST['tanggal']);
            $user_id = sanitizeInput($_POST['user_id']);
            $ruangan = sanitizeInput($_POST['ruangan']);

            $stmt = $conn->prepare("UPDATE jadwal_piket SET tanggal = ?, user_id = ?, ruangan = ? WHERE id = ?");
            $stmt->bind_param("sisi", $tanggal, $user_id, $ruangan, $id);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Jadwal piket berhasil diperbarui!';
            } else {
                $_SESSION['error'] = 'Gagal memperbarui jadwal piket!';
            }
        } elseif ($_POST['action'] == 'delete_schedule') {
            $id = sanitizeInput($_POST['id']);

            $stmt = $conn->prepare("DELETE FROM jadwal_piket WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Jadwal piket berhasil dihapus!';
            } else {
                $_SESSION['error'] = 'Gagal menghapus jadwal piket!';
            }
        }
    }

    header('Location: jadwal.php');
    exit();
}

// Get schedules based on role
$conn = getConnection();

if ($_SESSION['role'] == 'admin') {
    // Admin can see all schedules with report status
    $stmt = $conn->prepare("
        SELECT jp.*, u.nama as nama_petugas,
               lh.id as laporan_id, lh.status as laporan_status,
               v.status_verifikasi 
        FROM jadwal_piket jp 
        JOIN users u ON jp.user_id = u.id 
        LEFT JOIN laporan_harian lh ON (jp.tanggal = lh.tanggal AND jp.ruangan = lh.ruangan)
        LEFT JOIN verifikasi v ON lh.id = v.laporan_id
        ORDER BY jp.tanggal ASC
    ");
    $stmt->execute();
    $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get users for dropdown
    $stmt = $conn->prepare("SELECT id, nama FROM users WHERE role = 'petugas'");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Petugas can see all schedules with report status
    $stmt = $conn->prepare("
        SELECT jp.*, u.nama as nama_petugas,
               lh.id as laporan_id, lh.status as laporan_status,
               v.status_verifikasi 
        FROM jadwal_piket jp 
        LEFT JOIN users u ON jp.user_id = u.id 
        LEFT JOIN laporan_harian lh ON (jp.tanggal = lh.tanggal AND jp.ruangan = lh.ruangan)
        LEFT JOIN verifikasi v ON lh.id = v.laporan_id
        ORDER BY jp.tanggal ASC
    ");
    $stmt->execute();
    $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                <li><a href="dashboard.php" class="hover:text-blue-600 text-base">Dashboard</a></li>
                <li><i class="fas fa-chevron-right text-base"></i></li>
                <li class="text-gray-900 font-medium text-base">Jadwal Piket</li>
            </ol>
        </nav>

        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 id="pageTitle" class="text-lg font-bold">Jadwal Piket</h2>
                <p id="pageDescription" class="text-gray-700">
                    <?php echo ($_SESSION['role'] == 'admin') ? 'Kelola jadwal piket kebersihan' : 'Jadwal piket kebersihan Anda'; ?>
                </p>
            </div>
            <div class="flex space-x-3">
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <button id="toggleBtn" onclick="toggleForm()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Tambah Jadwal
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Add Schedule Form (Admin Only) -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <div id="scheduleForm" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
                <h3 class="text-lg font-semibold mb-4">Tambah Jadwal Piket</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_schedule">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                            <input type="date" name="tanggal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pegawai</label>
                            <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Pilih Pegawai</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo $user['nama']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ruangan</label>
                            <select name="ruangan" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Pilih Ruangan</option>
                                <option value="Ruang Harmoni (Rapat Besar)">Ruang Harmoni (Rapat Besar)</option>
                                <option value="Ruang Rapat Kecil">Ruang Rapat Kecil</option>
                                <option value="Ruang Resepsionis dan Pintu Masuk">Ruang Resepsionis dan Pintu Masuk</option>
                                <option value="Ruang PST">Ruang PST</option>
                                <option value="Ruang Ruang Tata Usaha">Ruang Tata Usaha</option>
                                <option value="Ruang Laktasi">Ruang Laktasi</option>
                                <option value="Ruang Pengolahan">Ruang Pengolahan</option>
                                <option value="Ruang Pantri & Toilet pegawai">Ruang Pantri & Toilet pegawai</option>
                                <option value="Ruang Dinamis (flexible area)">Ruang Dinamis (flexible area)</option>
                                <option value="Ruang Mushola">Ruang Mushola</option>
                                <option value="Ruang Arsip">Ruang Arsip</option>
                                <option value="Ruang Gudang">Ruang Gudang</option>
                                <option value="Toilet Penghujung">Toilet Penghujung</option>
                                <option value="Halaman Depan">Halaman Depan</option>
                                <option value="Halaman Samping">Halaman Samping</option>
                                <option value="Halaman Belakang">Halaman Belakang</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex space-x-4">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i>Simpan Jadwal
                        </button>
                        <button type="button" onclick="toggleForm()" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700">
                            <i class="fas fa-times mr-2"></i>Batal
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Schedules Table -->
        <div id="schedulesTable" class="bg-white rounded-lg shadow-md p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ruangan</th>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Pegawai</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td class="px-6 py-4"><?php echo date('d-m-Y', strtotime($schedule['tanggal'])); ?></td>
                                <td class="px-6 py-4"><?php echo $schedule['ruangan']; ?></td>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <td class="px-6 py-4"><?php echo $schedule['nama_petugas']; ?></td>
                                <?php endif; ?>
                                <td class="px-6 py-4">
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    $action_button = '';

                                    // Determine status based on report and verification
                                    if ($schedule['laporan_id']) {
                                        // Report exists
                                        if ($schedule['status_verifikasi'] == 'terima') {
                                            // Report verified and accepted
                                            $status_class = 'bg-green-100 text-green-800 rounded full';
                                            $status_text = 'Selesai';
                                        } else if ($schedule['laporan_status'] == 'menunggu') {
                                            // Report submitted but waiting verification
                                            $status_class = 'bg-yellow-100 text-yellow-800 rounded full';
                                            $status_text = 'Menunggu Verifikasi';
                                        } else if ($schedule['status_verifikasi'] == 'tolak') {
                                            // Report rejected
                                            $status_class = 'bg-red-100 text-red-800 rounded full';
                                            $status_text = 'Laporan Ditolak';
                                            if ($schedule['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {
                                                $action_button = '<br><small><a href="form_checklist.php?jadwal_id=' . $schedule['id'] . '" class="text-blue-600 font-size:24px hover:underline">Isi ulang laporan</a></small>';
                                            }
                                        }
                                    } else {
                                        // Jika belum ada laporan
                                        $isFutureDate = strtotime($schedule['tanggal']) > time();
                                    
                                        if ($isFutureDate) {
                                            // Jadwal masih akan datang
                                            $status_class = 'bg-blue-100 text-blue-800 rounded-full';
                                            $status_text  = 'Mendatang';
                                        } else {
                                            // Jadwal sudah lewat namun belum ada laporan
                                            $status_class = 'inline-block px-4 py-2 bg-orange-100 text-orange-800 rounded-lg';
                                            $status_text  = 'Belum Ada Laporan';
                                    
                                            // Tombol tampil untuk petugas terkait atau admin
                                            if ($schedule['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {
                                                $action_button = '
                                                    <div class="mt-2">
                                                        <a href="form_checklist.php?jadwal_id=' . $schedule['id'] . '" 
                                                           class="inline-block px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-blue-300 transition duration-200">
                                                            Isi Laporan
                                                        </a>
                                                    </div>';
                                            }
                                        }
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?> px-2 py-1 rounded full text-sm"><?php echo $status_text; ?></span>
                                    <?php echo $action_button; ?>
                                </td>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <td class="px-6 py-4">
                                        <button onclick="openEditModal(<?php echo $schedule['id']; ?>, '<?php echo $schedule['tanggal']; ?>', <?php echo $schedule['user_id']; ?>, '<?php echo addslashes($schedule['ruangan']); ?>')" class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 mr-2" title="Edit Jadwal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline delete-form">
                                            <input type="hidden" name="action" value="delete_schedule">
                                            <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700" title="Hapus Jadwal">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Edit Jadwal Piket</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit_schedule">
                <input type="hidden" name="id" id="editId">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="tanggal" id="editTanggal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Petugas</label>
                    <select name="user_id" id="editUserId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Pilih Petugas</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo $user['nama']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ruangan</label>
                    <select name="ruangan" id="editRuangan" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Pilih Ruangan</option>
                        <option value="Ruang Kerja A">Ruang Kerja A</option>
                        <option value="Ruang Kerja B">Ruang Kerja B</option>
                        <option value="Ruang Meeting">Ruang Meeting</option>
                        <option value="Ruang Direktur">Ruang Direktur</option>
                        <option value="Lobby">Lobby</option>
                        <option value="Toilet Lantai 1">Toilet Lantai 1</option>
                        <option value="Toilet Lantai 2">Toilet Lantai 2</option>
                        <option value="Pantry">Pantry</option>
                    </select>
                </div>

                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-save mr-2"></i>Perbarui
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-96 p-6 text-center animate-scaleIn">
        <div class="text-red-600 text-5xl mb-3">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 class="text-xl font-semibold mb-2">Konfirmasi Hapus</h2>
        <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus jadwal ini?</p>

        <div class="flex justify-center gap-4">
            <button id="cancelDelete" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                Batal
            </button>
            <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes scaleIn {
        0% {
            transform: scale(0.8);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .animate-scaleIn {
        animation: scaleIn 0.2s ease-out;
    }
</style>

<script>
    function toggleForm() {
        const form = document.getElementById('scheduleForm');
        const table = document.getElementById('schedulesTable');
        const toggleBtn = document.getElementById('toggleBtn');
        const pageTitle = document.getElementById('pageTitle');
        const pageDescription = document.getElementById('pageDescription');

        const isFormHidden = form.classList.contains('hidden');

        form.classList.toggle('hidden');
        table.classList.toggle('hidden');

        if (isFormHidden) {
            // Form akan ditampilkan
            toggleBtn.innerHTML = '<i class="fas fa-list mr-2"></i>Lihat Jadwal';
            toggleBtn.className = 'bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700';
            pageTitle.textContent = 'Tambah Jadwal Piket';
            pageDescription.textContent = 'Buat jadwal piket kebersihan baru';
        } else {
            // Form akan disembunyikan
            toggleBtn.innerHTML = '<i class="fas fa-plus mr-2"></i>Tambah Jadwal';
            toggleBtn.className = 'bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700';
            pageTitle.textContent = 'Jadwal Piket';
            pageDescription.textContent = 'Kelola jadwal piket kebersihan';
        }
    }

    function openEditModal(id, tanggal, userId, ruangan) {
        document.getElementById('editId').value = id;
        document.getElementById('editTanggal').value = tanggal;
        document.getElementById('editUserId').value = userId;
        document.getElementById('editRuangan').value = ruangan;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editForm').reset();
    }

    // Close modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    // DELETE CONFIRM SCRIPT (TIDAK MENGUBAH BACKEND)
    let selectedForm = null;

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            selectedForm = this;
            document.getElementById('deleteModal').classList.remove('hidden');
        });
    });

    document.getElementById('cancelDelete').addEventListener('click', function() {
        document.getElementById('deleteModal').classList.add('hidden');
    });

    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (selectedForm) {
            selectedForm.submit();
        }
    });
</script>