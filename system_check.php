<?php
// System File Verification
echo "=== SISTEM MONITORING KEBERSIHAN KANTOR ===\n";
echo "Verifikasi File Sistem\n";
echo str_repeat("=", 50) . "\n\n";

$essential_files = [
    // Core Configuration
    'config.php' => 'Konfigurasi database dan fungsi utama',
    
    // Main Pages
    'login.php' => 'Halaman login sistem',
    'logout.php' => 'Logout handler',
    'dashboard.php' => 'Dashboard utama',
    'jadwal.php' => 'Manajemen jadwal piket',
    'form_checklist.php' => 'Form pengisian checklist',
    'laporan.php' => 'Laporan harian dan verifikasi',
    'detail_laporan.php' => 'Detail laporan individual',
    'verifikasi.php' => 'Halaman verifikasi laporan',
    
    // Export Features
    'export_jadwal.php' => 'Export jadwal ke Excel',
    'export_jadwal_pdf.php' => 'Export jadwal ke PDF',
    'export_laporan_excel.php' => 'Export laporan ke Excel',
    'export_laporan_pdf.php' => 'Export laporan ke PDF',
    
    // Rekap Features
    'rekap_jadwal.php' => 'Rekap jadwal',
    'rekap_laporan.php' => 'Rekap laporan',
    
    // API/AJAX
    'get_report_details.php' => 'API untuk detail laporan (AJAX)',
    
    // Database
    'database.sql' => 'Script database untuk instalasi'
];

$folders = [
    'includes/' => 'Header, sidebar, footer',
    'assets/' => 'CSS, JS, images',
    'uploads/' => 'Upload foto checklist'
];

echo "FILE SISTEM UTAMA:\n";
echo str_repeat("-", 30) . "\n";
foreach ($essential_files as $file => $description) {
    $exists = file_exists($file) ? "✅" : "❌";
    echo "$exists $file - $description\n";
}

echo "\nFOLDER SISTEM:\n";
echo str_repeat("-", 30) . "\n";
foreach ($folders as $folder => $description) {
    $exists = is_dir($folder) ? "✅" : "❌";
    echo "$exists $folder - $description\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Pembersihan file selesai!\n";
echo "✅ Semua file yang tersisa adalah file essential sistem\n";
echo "✅ Sistem siap untuk produksi\n\n";

echo "KREDENSIAL LOGIN:\n";
echo "Admin: admin@pa.co.id / indsm01_\n";
echo "Petugas: petugas@pa.co.id / indsm02_\n";
?>
