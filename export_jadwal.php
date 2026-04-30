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
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Set headers for download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Rekap_Jadwal_' . date('F_Y', mktime(0, 0, 0, $month, 1, $year)) . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Rekap Jadwal " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . "</title>";
echo "</head>";
echo "<body>";

echo "<h2>REKAP JADWAL PIKET BULANAN</h2>";
echo "<h3>Periode: " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . "</h3>";
echo "<br>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<thead>";
echo "<tr style='background-color: #f3f4f6; font-weight: bold;'>";
echo "<th>No</th>";
echo "<th>Tanggal</th>";
echo "<th>Nama Petugas</th>";
echo "<th>Ruangan</th>";
echo "<th>Status Jadwal</th>";
echo "<th>Status Laporan</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$no = 1;
foreach ($schedules as $schedule) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . date('d-m-Y', strtotime($schedule['tanggal'])) . "</td>";
    echo "<td>" . ($schedule['nama_petugas'] ?? 'Tidak Ditugaskan') . "</td>";
    echo "<td>" . $schedule['ruangan'] . "</td>";
    echo "<td>" . ($schedule['status'] == 'selesai' ? 'Selesai' : 'Belum') . "</td>";
    
    $laporan_status = 'Belum Ada';
    if ($schedule['laporan_status']) {
        switch ($schedule['laporan_status']) {
            case 'terverifikasi':
                $laporan_status = 'Terverifikasi';
                break;
            case 'ditolak':
                $laporan_status = 'Ditolak';
                break;
            case 'menunggu':
                $laporan_status = 'Menunggu';
                break;
        }
    }
    echo "<td>" . $laporan_status . "</td>";
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
