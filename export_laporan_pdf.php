<?php
require_once 'config.php';

// Check login
checkLogin();

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat mengakses fitur export PDF.';
    header('Location: rekap_laporan.php');
    exit();
}

// Get filter parameters
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

// Get connection
$conn = getConnection();

// Get reports data
$stmt = $conn->prepare("
    SELECT lh.*, COALESCE(u.nama, 'Admin') as nama_petugas 
    FROM laporan_harian lh 
    LEFT JOIN users u ON lh.user_id = u.id 
    WHERE MONTH(lh.tanggal) = ? AND YEAR(lh.tanggal) = ?
    ORDER BY lh.tanggal DESC
");
$stmt->bind_param("ii", $selected_month, $selected_year);
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

$month_name = date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Laporan Bulanan - <?php echo $month_name; ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3B82F6;
        }
        
        .header h1 {
            color: #1E40AF;
            font-size: 28px;
            margin: 0 0 10px 0;
            font-weight: 700;
        }
        
        .header .subtitle {
            color: #6B7280;
            font-size: 16px;
            margin: 5px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 5px solid;
        }
        
        .stat-card.total { border-left-color: #3B82F6; }
        .stat-card.verified { border-left-color: #10B981; }
        .stat-card.pending { border-left-color: #F59E0B; }
        .stat-card.rejected { border-left-color: #EF4444; }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6B7280;
            font-size: 14px;
        }
        
        .total .stat-number { color: #3B82F6; }
        .verified .stat-number { color: #10B981; }
        .pending .stat-number { color: #F59E0B; }
        .rejected .stat-number { color: #EF4444; }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .room-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .room-item {
            background: #F9FAFB;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
        }
        
        .room-name {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .room-progress {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .progress-bar {
            flex: 1;
            height: 8px;
            background: #E5E7EB;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #3B82F6;
            transition: width 0.3s ease;
        }
        
        .room-stats-text {
            font-size: 12px;
            color: #6B7280;
            white-space: nowrap;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        
        th {
            background: #F8FAFC;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            color: #6B7280;
        }
        
        tr:nth-child(even) {
            background: #F9FAFB;
        }
        
        tr:hover {
            background: #F3F4F6;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-menunggu { background: #FEF3C7; color: #92400E; }
        .status-terverifikasi { background: #D1FAE5; color: #065F46; }
        .status-ditolak { background: #FEE2E2; color: #991B1B; }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9CA3AF;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            color: #6B7280;
            font-size: 12px;
        }
        
        .no-print {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .print-btn, .back-btn {
            display: inline-block;
            margin: 0 10px;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .print-btn {
            background: #dc3545;
            color: white;
        }
        
        .print-btn:hover {
            background: #c82333;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <h3 style="margin-bottom: 15px; color: #495057;">Export PDF Rekap Laporan</h3>
        <p style="margin-bottom: 20px; color: #6c757d;">Pilih aksi yang ingin dilakukan:</p>
        <button onclick="window.print()" class="print-btn">
            📄 Cetak / Simpan PDF
        </button>
        <button onclick="goBackToRecap()" class="back-btn">
            ← Kembali ke Halaman Rekap
        </button>
        <p style="margin-top: 15px; font-size: 12px; color: #6c757d;">
            <i>Tip: Tekan ESC untuk kembali ke halaman rekap</i>
        </p>
    </div>

    <div class="container">
        <div class="header">
            <h1>REKAP LAPORAN KEBERSIHAN BULANAN</h1>
            <div class="subtitle">Periode: <?php echo $month_name; ?></div>
            <div class="subtitle">Dicetak pada: <?php echo date('d F Y, H:i'); ?> WIB</div>
        </div>

        <div class="section">
            <div class="section-title">📊 Statistik Umum</div>
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $total_laporan; ?></div>
                    <div class="stat-label">Total Laporan</div>
                </div>
                <div class="stat-card verified">
                    <div class="stat-number"><?php echo $laporan_terverifikasi; ?></div>
                    <div class="stat-label">Terverifikasi</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo $laporan_menunggu; ?></div>
                    <div class="stat-label">Menunggu Verifikasi</div>
                </div>
                <div class="stat-card rejected">
                    <div class="stat-number"><?php echo $laporan_ditolak; ?></div>
                    <div class="stat-label">Ditolak</div>
                </div>
            </div>
            
            <?php if ($total_laporan > 0): ?>
            <div style="text-align: center; background: #F0F9FF; padding: 15px; border-radius: 8px; border: 1px solid #0EA5E9;">
                <strong style="color: #0C4A6E;">Tingkat Verifikasi: <?php echo $persentase_terverifikasi; ?>%</strong>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($room_stats)): ?>
        <div class="section">
            <div class="section-title">🏢 Statistik per Ruangan</div>
            <div class="room-stats">
                <?php foreach($room_stats as $room => $stats): ?>
                <div class="room-item">
                    <div class="room-name"><?php echo htmlspecialchars($room); ?></div>
                    <div class="room-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['terverifikasi'] / $stats['total']) * 100 : 0; ?>%"></div>
                        </div>
                        <div class="room-stats-text">
                            <?php echo $stats['terverifikasi']; ?>/<?php echo $stats['total']; ?> laporan
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-title">📋 Detail Laporan Harian</div>
            <?php if (!empty($reports)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Petugas</th>
                        <th>Ruangan</th>
                        <th>Status</th>
                        <th>Waktu Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reports as $report): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($report['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars($report['nama_petugas']); ?></td>
                        <td><?php echo htmlspecialchars($report['ruangan']); ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch($report['status']) {
                                case 'menunggu':
                                    $status_class = 'status-menunggu';
                                    $status_text = 'Menunggu';
                                    break;
                                case 'terverifikasi':
                                    $status_class = 'status-terverifikasi';
                                    $status_text = 'Terverifikasi';
                                    break;
                                case 'ditolak':
                                    $status_class = 'status-ditolak';
                                    $status_text = 'Ditolak';
                                    break;
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i>📭</i>
                <h3>Tidak Ada Data Laporan</h3>
                <p>Belum ada laporan untuk bulan <?php echo $month_name; ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p><strong>Sistem Monitoring Kebersihan Kantor</strong></p>
            <p>Laporan ini digenerate secara otomatis pada <?php echo date('d F Y \p\u\k\u\l H:i'); ?> WIB</p>
        </div>
    </div>

    <script>
        // Auto print when page loads (disabled for better UX)
        window.onload = function() {
            // Set document title for PDF filename
            document.title = 'Rekap_Laporan_<?php echo $month_name; ?>';
            
            // Optional auto-print - disabled for better user experience
            // setTimeout(function() {
            //     window.print();
            // }, 1000);
        };
        
        // Handle after print - quietly close or go back
        window.onafterprint = function() {
            // Try to close the window without confirmation
            setTimeout(function() {
                window.close();
                // If window.close() doesn't work (some browsers block it), try to go back
                if (!window.closed) {
                    // Go back to the recap page without asking
                    const month = <?php echo $selected_month; ?>;
                    const year = <?php echo $selected_year; ?>;
                    window.location.href = `rekap_laporan.php?month=${month}&year=${year}`;
                }
            }, 1000);
        };
        
        // Add a manual close function for the back button
        function goBackToRecap() {
            const month = <?php echo $selected_month; ?>;
            const year = <?php echo $selected_year; ?>;
            window.location.href = `rekap_laporan.php?month=${month}&year=${year}`;
        }
        
        // Handle ESC key to go back
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                goBackToRecap();
            }
        });
    </script>
</body>
</html>
