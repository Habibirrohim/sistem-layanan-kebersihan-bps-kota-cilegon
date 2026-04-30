<?php
require_once 'config.php';

// Check login
checkLogin();

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Akses ditolak! Hanya admin yang dapat mengakses halaman ini.');
}

// Get parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Get connection
$conn = getConnection();

// Get data
$stmt = $conn->prepare("
    SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
    FROM laporan_harian lh 
    LEFT JOIN users u ON lh.user_id = u.id 
    WHERE MONTH(lh.tanggal) = ? AND YEAR(lh.tanggal) = ?
    ORDER BY lh.tanggal DESC
");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Set headers for download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Rekap_Laporan_' . date('F_Y', mktime(0, 0, 0, $month, 1, $year)) . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Rekap Laporan " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . "</title>";
echo "</head>";
echo "<body>";

echo "<h2>REKAP LAPORAN HARIAN BULANAN</h2>";
echo "<h3>Periode: " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . "</h3>";
echo "<br>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<thead>";
echo "<tr style='background-color: #f3f4f6; font-weight: bold;'>";
echo "<th>No</th>";
echo "<th>Tanggal</th>";
echo "<th>Nama Petugas</th>";
echo "<th>Ruangan</th>";
echo "<th>Status</th>";
echo "<th>Deskripsi</th>";
echo "<th>Dibuat Pada</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$no = 1;
foreach ($reports as $report) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . date('d-m-Y', strtotime($report['tanggal'])) . "</td>";
    echo "<td>" . $report['nama_petugas'] . "</td>";
    echo "<td>" . $report['ruangan'] . "</td>";
    
    $status_text = '';
    switch ($report['status']) {
        case 'menunggu':
            $status_text = 'Menunggu Verifikasi';
            break;
        case 'terverifikasi':
            $status_text = 'Terverifikasi';
            break;
        case 'ditolak':
            $status_text = 'Ditolak';
            break;
    }
    echo "<td>" . $status_text . "</td>";
    echo "<td>" . ($report['deskripsi'] ?? '-') . "</td>";
    echo "<td>" . date('d-m-Y H:i', strtotime($report['created_at'])) . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

echo "<br><br>";
echo "<p><strong>Dicetak pada:</strong> " . date('d-m-Y H:i:s') . "</p>";
echo "<p><strong>Dicetak oleh:</strong> " . $_SESSION['nama'] . " (" . $_SESSION['role'] . ")</p>";

echo "</body>";
echo "</html>";
?>
