<?php
// Production Configuration Template for Free Hosting
// Copy this to config.php and edit the database credentials

// ============================================
// DATABASE CONFIGURATION FOR HOSTING
// ============================================

// GANTI DENGAN CREDENTIALS DARI HOSTING ANDA
$db_host = 'localhost';                    // Biasanya 'localhost' atau IP khusus
$db_name = 'epiz_xxxxx_monitoring';        // Nama database dari hosting (contoh format InfinityFree)
$db_user = 'epiz_xxxxx';                   // Username database dari hosting
$db_pass = 'password_anda';                // Password database dari hosting

// ============================================
// SYSTEM CONFIGURATION
// ============================================

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting untuk production (OFF)
error_reporting(0);
ini_set('display_errors', 0);

// Session configuration
session_start();

// ============================================
// DATABASE CONNECTION FUNCTION
// ============================================
function getConnection() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            // Log error instead of displaying (production security)
            error_log("Database connection failed: " . $conn->connect_error);
            die("Koneksi database gagal. Silakan hubungi administrator.");
        }
        
        $conn->set_charset("utf8");
        return $conn;
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        die("Terjadi kesalahan sistem. Silakan coba lagi nanti.");
    }
}

// ============================================
// SECURITY FUNCTIONS
// ============================================

// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: login.php');
        exit();
    }
}

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check admin role
function checkAdmin() {
    checkLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}

// Check petugas role
function checkPetugas() {
    checkLogin();
    if ($_SESSION['role'] !== 'petugas') {
        header('Location: dashboard.php');
        exit();
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

// Format Indonesian date
function formatIndonesianDate($date) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

// Get status color class
function getStatusColor($status) {
    switch(strtolower($status)) {
        case 'menunggu':
            return 'bg-orange-100 text-orange-800';
        case 'terverifikasi':
            return 'bg-green-100 text-green-800';
        case 'ditolak':
            return 'bg-red-100 text-red-800';
        case 'selesai':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// FILE UPLOAD CONFIGURATION
// ============================================

// Allowed file types for photo upload
$allowed_image_types = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 2 * 1024 * 1024; // 2MB

// Upload directory (make sure this is writable)
$upload_directory = 'uploads/checklist_photos/';

// Create upload directory if not exists
if (!file_exists($upload_directory)) {
    mkdir($upload_directory, 0755, true);
}

// ============================================
// HOSTING SPECIFIC SETTINGS
// ============================================

// Increase memory limit if allowed by hosting
ini_set('memory_limit', '128M');

// Set max execution time
ini_set('max_execution_time', 30);

// Set upload limits
ini_set('upload_max_filesize', '2M');
ini_set('post_max_size', '8M');

// ============================================
// PRODUCTION SECURITY HEADERS
// ============================================

// Security headers for production
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// ============================================
// LOGGING FUNCTION
// ============================================

function logActivity($user_id, $action, $description = '') {
    $conn = getConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    
    if ($stmt) {
        $stmt->bind_param("issss", $user_id, $action, $description, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
    
    $conn->close();
}

// ============================================
// VERSION INFORMATION
// ============================================
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_NAME', 'Monitoring Kebersihan Kantor');
define('SYSTEM_AUTHOR', 'PPNPN');

// ============================================
// END OF CONFIGURATION
// ============================================
?>
