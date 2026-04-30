-- ============================================
-- MONITORING KEBERSIHAN KANTOR - DATABASE SCRIPT
-- Optimized for Free Hosting MySQL
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if exist (for clean installation)
DROP TABLE IF EXISTS `checklist_items`;
DROP TABLE IF EXISTS `verifikasi`;
DROP TABLE IF EXISTS `laporan_harian`;
DROP TABLE IF EXISTS `jadwal_piket`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `activity_logs`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas') NOT NULL DEFAULT 'petugas',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: jadwal_piket
-- ============================================
CREATE TABLE `jadwal_piket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `ruangan` varchar(100) NOT NULL,
  `status` enum('belum','selesai') NOT NULL DEFAULT 'belum',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_ruangan` (`ruangan`),
  KEY `idx_status` (`status`),
  KEY `idx_tanggal_ruangan` (`tanggal`, `ruangan`),
  CONSTRAINT `fk_jadwal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: laporan_harian
-- ============================================
CREATE TABLE `laporan_harian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `jadwal_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `ruangan` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('menunggu','terverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_jadwal_id` (`jadwal_id`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_ruangan` (`ruangan`),
  KEY `idx_status` (`status`),
  KEY `idx_tanggal_ruangan` (`tanggal`, `ruangan`),
  CONSTRAINT `fk_laporan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_laporan_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal_piket` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: checklist_items
-- ============================================
CREATE TABLE `checklist_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `laporan_id` int(11) NOT NULL,
  `item_name` varchar(50) NOT NULL,
  `status` enum('bersih','kotor','rusak') NOT NULL,
  `kendala` text DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_laporan_id` (`laporan_id`),
  KEY `idx_item_name` (`item_name`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_checklist_laporan` FOREIGN KEY (`laporan_id`) REFERENCES `laporan_harian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: verifikasi
-- ============================================
CREATE TABLE `verifikasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `laporan_id` int(11) NOT NULL,
  `status_verifikasi` enum('terima','tolak') NOT NULL,
  `catatan_verifikasi` text DEFAULT NULL,
  `verified_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_laporan_id` (`laporan_id`),
  KEY `idx_verified_by` (`verified_by`),
  KEY `idx_status` (`status_verifikasi`),
  CONSTRAINT `fk_verifikasi_laporan` FOREIGN KEY (`laporan_id`) REFERENCES `laporan_harian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_verifikasi_user` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE: activity_logs (for security monitoring)
-- ============================================
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT INITIAL DATA
-- ============================================

-- Insert users with updated credentials
INSERT INTO `users` (`nama`, `email`, `password`, `role`) VALUES
('Admin System', 'admin@pa.co.id', MD5('indsm01_'), 'admin'),
('Petugas', 'petugas@pa.co.id', MD5('indsm02_'), 'petugas');

-- ============================================
-- CREATE VIEWS FOR REPORTING
-- ============================================

-- View for complete report data
CREATE VIEW `view_laporan_lengkap` AS
SELECT 
    lh.id,
    lh.tanggal,
    lh.ruangan,
    lh.deskripsi,
    lh.status,
    lh.created_at,
    COALESCE(u.nama, 'Admin') as nama_petugas,
    u.email as email_petugas,
    jp.id as jadwal_id,
    v.status_verifikasi,
    v.catatan_verifikasi,
    v.created_at as verified_at,
    admin.nama as verified_by_nama
FROM `laporan_harian` lh
LEFT JOIN `users` u ON lh.user_id = u.id
LEFT JOIN `jadwal_piket` jp ON lh.jadwal_id = jp.id
LEFT JOIN `verifikasi` v ON lh.id = v.laporan_id
LEFT JOIN `users` admin ON v.verified_by = admin.id;

-- View for jadwal summary
CREATE VIEW `view_jadwal_summary` AS
SELECT 
    jp.id,
    jp.tanggal,
    jp.ruangan,
    jp.status as jadwal_status,
    u.nama as nama_petugas,
    u.email,
    lh.id as laporan_id,
    lh.status as laporan_status,
    jp.created_at
FROM `jadwal_piket` jp
LEFT JOIN `users` u ON jp.user_id = u.id
LEFT JOIN `laporan_harian` lh ON jp.id = lh.jadwal_id
ORDER BY jp.tanggal DESC, jp.ruangan;

-- ============================================
-- OPTIMIZE TABLES
-- ============================================
OPTIMIZE TABLE `users`;
OPTIMIZE TABLE `jadwal_piket`;
OPTIMIZE TABLE `laporan_harian`;
OPTIMIZE TABLE `checklist_items`;
OPTIMIZE TABLE `verifikasi`;
OPTIMIZE TABLE `activity_logs`;

-- ============================================
-- FINAL SETTINGS
-- ============================================

-- Set charset
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Success message
SELECT 'Database berhasil dibuat! Sistem monitoring kebersihan siap digunakan.' as message;

-- Show table status
SHOW TABLE STATUS;

-- ============================================
-- END OF SCRIPT
-- ============================================
