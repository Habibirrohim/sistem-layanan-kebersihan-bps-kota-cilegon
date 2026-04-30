<?php
require_once 'config.php';

checkLogin();
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}


$conn = getConnection();

// HANDLE ACTION
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $action = $_POST['action'];

    if ($action == 'add') {
        $stmt = $conn->prepare("INSERT INTO users (nama,email,password,role) VALUES (?,?,?,?)");
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt->bind_param("ssss", $_POST['nama'], $_POST['email'], $pass, $_POST['role']);
        $stmt->execute();
    }

    if ($action == 'edit') {
        $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $_POST['nama'], $_POST['email'], $_POST['role'], $_POST['id']);
        $stmt->execute();
    }

    if ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $_POST['id']);
        $stmt->execute();
    }

    header("Location: users.php");
    exit();
}

// DATA
$users = $conn->query("SELECT * FROM users ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<?php if (isset($_SESSION['success'])): ?>
    <div id="alertBox" class="fixed top-20 right-5 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 z-50 animate-bounce">
        <i class="fas fa-check-circle"></i>
        <span><?php echo $_SESSION['success']; ?></span>
        <button onclick="closeAlert()" class="ml-3 text-white font-bold">×</button>
    </div>
<?php unset($_SESSION['success']);
endif; ?>

<div class="lg:ml-64 pt-16 min-h-screen main-content flex flex-col">
    <div class="flex-1 p-6 mt-5">

        <!-- HEADER -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Manajemen User</h2>
                <p class="text-gray-600">Kelola admin dan petugas</p>
            </div>

            <button onclick="openAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Tambah User
            </button>
        </div>



        <!-- TABLE -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-left">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?= $u['id'] ?></td>
                                <td class="px-4 py-2"><?= $u['nama'] ?></td>
                                <td class="px-4 py-2"><?= $u['email'] ?></td>

                                <td class="px-4 py-2">
                                    <span class="<?= $u['role'] == 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?> px-2 py-1 rounded full text-xs">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>

                                <td class="px-4 py-2 space-x-2">

                                    <button onclick="openEditModal(<?= $u['id'] ?>,'<?= $u['nama'] ?>','<?= $u['email'] ?>','<?= $u['role'] ?>')"
                                        class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">
                                        Edit
                                    </button>

                                    <button onclick="openDeleteModal(<?= $u['id'] ?>)"
                                        class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                        Hapus
                                    </button>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <?php include 'includes/footer.php'; ?>
</div>


<!-- ================= MODAL TAMBAH ================= -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg w-96">
        <h3 class="text-lg font-bold mb-4">Tambah User</h3>

        <form method="POST">
            <input type="hidden" name="action" value="add">

            <input name="nama" placeholder="Nama" class="w-full border p-2 mb-2 rounded" required>
            <input name="email" placeholder="Email" class="w-full border p-2 mb-2 rounded" required>

            <div class="flex items-center">
                <input type="password" id="addPass" name="password" placeholder="Password"
                    class="w-full border p-2 mb-2 rounded" required>

                <button type="button" onclick="togglePass('addPass', this)"
                    class="ml-2 px-2 py-1 rounded hover:bg-gray-100 transition text-gray-600">
                    <i class="fas fa-lock"></i>
                </button>
            </div>

            <select name="role" class="w-full border p-2 mb-4 rounded">
                <option value="petugas">Petugas</option>
                <option value="admin">Admin</option>
            </select>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAddModal()" class="bg-gray-400 px-4 py-2 rounded">Batal</button>
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
            </div>

        </form>
    </div>
</div>


<!-- ================= MODAL EDIT ================= -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg w-96">
        <h3 class="text-lg font-bold mb-4">Edit User</h3>

        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">

            <input id="editNama" name="nama" class="w-full border p-2 mb-2 rounded" required>
            <input id="editEmail" name="email" class="w-full border p-2 mb-2 rounded" required>

            <select id="editRole" name="role" class="w-full border p-2 mb-3 rounded">
                <option value="admin">Admin</option>
                <option value="petugas">Petugas</option>
            </select>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="bg-gray-400 px-4 py-2 rounded">Batal</button>
                <button class="bg-yellow-500 text-white px-4 py-2 rounded">Update</button>
            </div>

        </form>
    </div>
</div>


<!-- ================= MODAL DELETE ================= -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg text-center w-80">
        <h3 class="text-lg font-bold mb-4">Hapus User?</h3>

        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteId">

            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeDeleteModal()" class="bg-gray-400 px-4 py-2 rounded">Batal</button>
                <button class="bg-red-600 text-white px-4 py-2 rounded">Hapus</button>
            </div>
        </form>

    </div>
</div>


<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function openEditModal(id, nama, email, role) {
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editId').value = id;
        document.getElementById('editNama').value = nama;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function openDeleteModal(id) {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteId').value = id;
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');

        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-lock");
            icon.classList.add("fa-unlock");
        } else {
            input.type = "password";
            icon.classList.remove("fa-unlock");
            icon.classList.add("fa-lock");
        }
    }
</script>