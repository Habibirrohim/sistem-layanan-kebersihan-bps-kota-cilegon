<?php
require_once 'config.php';
checkLogin();

// Only admin can verify
if (getUserRole() != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$conn = getConnection();

// Handle verification submission
if ($_POST['action'] == 'verify_report') {
    $laporan_id = $_POST['laporan_id'];
    $verifikasi_data = $_POST['verifikasi'];
    
    // Update report status
    $stmt = $conn->prepare("UPDATE laporan_harian SET status = 'terverifikasi' WHERE id = ?");
    $stmt->bind_param("i", $laporan_id);
    $stmt->execute();
    
    // Insert verification details
    foreach ($verifikasi_data as $item => $status) {
        $stmt = $conn->prepare("
            INSERT INTO verifikasi (laporan_id, item_name, status_verifikasi, verified_by, verified_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issi", $laporan_id, $item, $status, $_SESSION['user_id']);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Laporan berhasil diverifikasi']);
    exit();
}

// Get report details for verification
if (isset($_GET['laporan_id'])) {
    $laporan_id = $_GET['laporan_id'];
    
    // Get report info
    $stmt = $conn->prepare("
        SELECT lh.*, u.nama, jp.ruangan 
        FROM laporan_harian lh 
        JOIN users u ON lh.user_id = u.id 
        JOIN jadwal_piket jp ON lh.jadwal_id = jp.id 
        WHERE lh.id = ?
    ");
    $stmt->bind_param("i", $laporan_id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    
    // Get checklist items
    $stmt = $conn->prepare("SELECT * FROM checklist_items WHERE laporan_id = ?");
    $stmt->bind_param("i", $laporan_id);
    $stmt->execute();
    $checklist_items = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $checklist_items[$row['item_name']] = $row;
    }
}
?>