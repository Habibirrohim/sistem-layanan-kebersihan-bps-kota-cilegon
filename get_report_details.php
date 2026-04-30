<?php
require_once 'config.php';

// Check login and admin role
checkLogin();
if ($_SESSION['role'] != 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit();
}

// Get report ID
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$report_id) {
    echo json_encode(['success' => false, 'message' => 'ID laporan tidak valid']);
    exit();
}

try {
    $conn = getConnection();
    
    // Get report details
    $stmt = $conn->prepare("
        SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
        FROM laporan_harian lh 
        LEFT JOIN users u ON lh.user_id = u.id 
        WHERE lh.id = ?
    ");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    
    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan']);
        exit();
    }
    
    // Format tanggal
    $report['tanggal_formatted'] = date('d-m-Y', strtotime($report['tanggal']));
    
    // Get checklist items
    $stmt = $conn->prepare("SELECT * FROM checklist_items WHERE laporan_id = ? ORDER BY item_name");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();   
    $checklist_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Add display names for checklist items
    $item_names = [
        'lantai' => 'Kebersihan Lantai',
        'meja' => 'Kebersihan dan Kerapihan Meja dan Kursi',
        'kaca' => 'Kebersihan Kaca',
        'dinding' => 'Kebersihan Dinding/Loteng dari Jaring Laba-laba',
        'sampah' => 'Kebersihan Tong Sampah/Mengganti Kantong Plastik'
    ];
    
    foreach ($checklist_items as &$item) {
        $item['item_name_display'] = $item_names[$item['item_name']] ?? ucfirst($item['item_name']);
    }
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'report' => $report,
        'checklist_items' => $checklist_items
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?>
