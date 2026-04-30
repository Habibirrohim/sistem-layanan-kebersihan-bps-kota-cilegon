<?php
require_once 'config.php';

// Check login
checkLogin();

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat mengakses halaman ini.';
    header('Location: dashboard.php');
    exit();
}

// Get parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Get connection
$conn = getConnection();

// Get schedules data
$stmt = $conn->prepare("
    SELECT jp.*, u.nama as nama_petugas,
           lh.id as laporan_id, lh.status as laporan_status,
           v.status_verifikasi 
    FROM jadwal_piket jp 
    JOIN users u ON jp.user_id = u.id 
    LEFT JOIN laporan_harian lh ON (jp.tanggal = lh.tanggal AND jp.ruangan = lh.ruangan)
    LEFT JOIN verifikasi v ON lh.id = v.laporan_id
    WHERE MONTH(jp.tanggal) = ? AND YEAR(jp.tanggal) = ?
    ORDER BY jp.tanggal ASC
");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_jadwal = count($schedules);
$jadwal_selesai = 0;
$jadwal_belum = 0;
$jadwal_pending = 0;

foreach ($schedules as $schedule) {
    if ($schedule['laporan_status'] == 'terverifikasi' || $schedule['status'] == 'selesai') {
        $jadwal_selesai++;
    } elseif ($schedule['laporan_id'] != null && $schedule['laporan_status'] == 'menunggu') {
        $jadwal_pending++;
    } else {
        $jadwal_belum++;
    }
}

$persentase_selesai = $total_jadwal > 0 ? round(($jadwal_selesai / $total_jadwal) * 100, 1) : 0;
$month_name = date('F_Y', mktime(0,0,0,$month,1,$year));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rekap Jadwal Piket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 16px;
            font-weight: normal;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .stat-item {
            text-align: center;
            flex: 1;
        }
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            font-size: 10px;
        }
        .status-selesai {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
        }
        .status-belum {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        /* Print specific styles */
        @media print {
            body {
                margin: 0;
                padding: 20px;
            }
            .header {
                page-break-after: avoid;
            }
            table {
                page-break-inside: avoid;
            }
            tr {
                page-break-inside: avoid;
            }
        }
        
        /* Hide print button when printing */
        @media print {
            .no-print {
                display: none !important;
            }
        }
        
        /* Print button styling */
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
        <h3 style="margin-bottom: 15px; color: #495057;">Export PDF Rekap Jadwal</h3>
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

    <div class="header">
        <h1>REKAP JADWAL PIKET KEBERSIHAN</h1>
        <h2>Periode: <?php echo date('F Y', mktime(0,0,0,$month,1,$year)); ?></h2>
    </div>

    <div class="stats">
        <div class="stat-item">
            <div class="stat-number"><?php echo $total_jadwal; ?></div>
            <div class="stat-label">Total Jadwal</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $jadwal_selesai; ?></div>
            <div class="stat-label">Selesai</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $jadwal_pending; ?></div>
            <div class="stat-label">Menunggu Verifikasi</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $jadwal_belum; ?></div>
            <div class="stat-label">Belum Dikerjakan</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $persentase_selesai; ?>%</div>
            <div class="stat-label">Persentase Selesai</div>
        </div>
    </div>

    <?php if (!empty($schedules)): ?>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Tanggal</th>
                <th style="width: 25%;">Petugas</th>
                <th style="width: 20%;">Ruangan</th>
                <th style="width: 20%;">Status Jadwal</th>
                <th style="width: 20%;">Status Laporan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($schedules as $schedule): ?>
            <tr>
                <td><?php echo date('d-m-Y', strtotime($schedule['tanggal'])); ?></td>
                <td><?php echo $schedule['nama_petugas'] ?? 'Tidak Ditugaskan'; ?></td>
                <td><?php echo $schedule['ruangan']; ?></td>
                <td>
                    <?php 
                    if($schedule['laporan_status'] == 'terverifikasi' || $schedule['status'] == 'selesai'): ?>
                    <span class="status-selesai">Selesai</span>
                    <?php elseif($schedule['laporan_id'] != null && $schedule['laporan_status'] == 'menunggu'): ?>
                    <span class="status-pending">Menunggu Verifikasi</span>
                    <?php elseif($schedule['laporan_status'] == 'ditolak'): ?>
                    <span class="status-belum">Laporan Ditolak</span>
                    <?php else: ?>
                    <span class="status-belum">Belum Dikerjakan</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($schedule['laporan_status']): ?>
                        <?php if($schedule['laporan_status'] == 'terverifikasi'): ?>
                        <span class="status-selesai">Terverifikasi</span>
                        <?php elseif($schedule['laporan_status'] == 'ditolak'): ?>
                        <span class="status-belum">Ditolak</span>
                        <?php else: ?>
                        <span class="status-pending">Menunggu</span>
                        <?php endif; ?>
                    <?php else: ?>
                    <span style="color: #6c757d; font-style: italic;">Belum Ada</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align: center; padding: 40px; color: #6c757d;">
        <p style="font-size: 16px; margin: 0;">Tidak ada data jadwal untuk periode ini</p>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Dokumen ini dibuat secara otomatis oleh Sistem Monitoring Kebersihan Kantor</p>
        <p>Tanggal Export: <?php echo date('d F Y, H:i:s'); ?></p>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            // Set document title for PDF filename
            document.title = 'Rekap_Jadwal_<?php echo $month_name; ?>';
            
            // Trigger print dialog after a short delay (optional auto-print)
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
                    const month = <?php echo $month; ?>;
                    const year = <?php echo $year; ?>;
                    window.location.href = `rekap_jadwal.php?month=${month}&year=${year}`;
                }
            }, 1000);
        };
        
        // Add a manual close function for the print button
        function goBackToRecap() {
            const month = <?php echo $month; ?>;
            const year = <?php echo $year; ?>;
            window.location.href = `rekap_jadwal.php?month=${month}&year=${year}`;
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
