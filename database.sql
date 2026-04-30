CREATE DATABASE monitoring_kebersihan;
USE monitoring_kebersihan;

DROP TABLE IF EXISTS users;
DROP TABLE jadwal_piket;  
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas') DEFAULT 'petugas',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Jadwal piket table
CREATE TABLE jadwal_piket (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    user_id INT NOT NULL,
    ruangan VARCHAR(100) NOT NULL,
    status ENUM('belum', 'selesai') DEFAULT 'belum',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
DROP TABLE laporan_harian;

-- Laporan harian table
CREATE TABLE laporan_harian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    jadwal_id INT NULL,
    tanggal DATE NOT NULL,
    ruangan VARCHAR(100) NOT NULL,
    status ENUM('menunggu', 'terverifikasi', 'ditolak') DEFAULT 'menunggu',
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal_piket(id) ON DELETE SET NULL
);
DROP TABLE laporan_harian;
DROP TABLE checklist_items;
-- Checklist items table
CREATE TABLE checklist_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    laporan_id INT NOT NULL,
    item_name VARCHAR(50) NOT NULL,
    status ENUM('bersih', 'kotor', 'rusak') NOT NULL,
    kendala TEXT,
    foto_path VARCHAR(255),
    prioritas ENUM('rendah', 'sedang', 'tinggi') DEFAULT 'sedang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan_harian(id) ON DELETE CASCADE
);

-- Verifikasi table
CREATE TABLE verifikasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    laporan_id INT NOT NULL,
    status_verifikasi ENUM('terima', 'tolak', 'revisi') NOT NULL,
    catatan_verifikasi TEXT,
    verified_by INT NOT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan_harian(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE template_checklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_ruangan VARCHAR(100) NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
DROP TABLE template_checklist;

INSERT INTO checklist_items (laporan_id, item_name) 
('Ruang Laktasi', 'Kebersihan Lantai'),
('Ruang Laktasi', 'Kebersihan Meja'),
('Ruang Laktasi', 'Kebersihan Kursi'),
('Ruang Laktasi', 'Membersihkan sudut-sudut plafon ruangan dari sawangan'),
('Halaman Belakang', 'Kebersihan halaman belakang'),
('Halaman Belakang', 'Menyiram tanaman'),
('Halaman Belakang', 'Rumput berada dalam kondisi rapi dan terawat');
-- Insert sample users
INSERT INTO users (nama, email, password, role) VALUES
('Admin System', 'hhabibirrohim@gmail.com', MD5('habibi14'), 'admin'),
('Petugas', 'habibirrohim14@upi.edu', MD5('habibi14'), 'petugas');

INSERT INTO users (nama, email, password, role) VALUES
('Habibi', 'habibi12@gmail.com', MD5('1234'), 'petugas');

DROP TABLE jadwal_piket;

-- Insert sample schedules
INSERT INTO jadwal_piket (tanggal, user_id, ruangan) VALUES
(CURDATE(), 2, 'Ruang Pengolahan'),
(CURDATE(), 3, 'Ruang Pantri & Toilet pegawai'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), 4, 'Ruang Dinamis (flexible area'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), 5, 'Ruang Mushola'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), 2, 'Ruang Arsip'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), 3, 'Ruang Gudang'),
(DATE_ADD(CURDATE(), INTERVAL 3 DAY), 4, 'Halaman Samping'),
(DATE_ADD(CURDATE(), INTERVAL 3 DAY), 5, 'Ruang Laktasi');

use laporan_harian;

-- Insert sample reports
INSERT INTO laporan_harian (user_id, tanggal, ruangan, status, deskripsi) VALUES
(2, CURDATE(), 'Ruang Meeting A', 'menunggu', 'Membersihkan ruang meeting, menyapu lantai, mengepel, membersihkan meja dan kursi'),
(3, CURDATE(), 'Ruang Kerja Utama', 'terverifikasi', 'Membersihkan ruang kerja utama, vacuum karpet, lap meja kerja, bersihkan komputer');

-- Insert sample checklist items
INSERT INTO checklist_items (laporan_id, item_name, status, kendala) VALUES
(1, 'lantai', 'bersih', ''),
(1, 'meja', 'bersih', ''),
(1, 'kaca', 'kotor', 'Kaca jendela bagian atas masih kotor'),
(1, 'dinding', 'bersih', ''),
(1, 'sampah', 'bersih', ''),
(2, 'lantai', 'bersih', ''),
(2, 'meja', 'bersih', ''),
(2, 'kaca', 'bersih', ''),
(2, 'dinding', 'bersih', ''),
(2, 'sampah', 'bersih', '');

-- Create indexes for better performance
CREATE INDEX idx_laporan_tanggal ON laporan_harian(tanggal);
CREATE INDEX idx_laporan_status ON laporan_harian(status);
CREATE INDEX idx_jadwal_tanggal ON jadwal_piket(tanggal);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_checklist_laporan ON checklist_items(laporan_id);


-- ZONA
CREATE TABLE zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_zona VARCHAR(50)
);

-- RUANGAN
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_ruangan VARCHAR(100),
    zone_id INT,
    FOREIGN KEY (zone_id) REFERENCES zones(id)
);

-- CHECKLIST PER RUANGAN
CREATE TABLE template_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    item_name VARCHAR(255),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

ALTER TABLE users ADD zone_id INT;

ALTER TABLE users
ADD CONSTRAINT fk_users_zone
FOREIGN KEY (zone_id) REFERENCES zones(id);

ALTER TABLE jadwal_piket ADD room_id INT;

ALTER TABLE jadwal_piket
ADD CONSTRAINT fk_jadwal_room
FOREIGN KEY (room_id) REFERENCES rooms(id);

DROP TABLE template_checklist;
CREATE TABLE template_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    item_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

use template_checklist;

INSERT INTO template_checklist (room_id, item_name) VALUES
(1, 'Kebersihan Karpet'),
(1, 'Tempat Wudhu'),
(2, 'Kebersihan Lantai'),
(3, 'Kerapihan Gudang');

INSERT INTO zones (nama_zona) VALUES ('Zona 3');

INSERT INTO rooms (nama_ruangan, zone_id) VALUES
('Ruang Mushola', 1),
('Ruang Arsip', 1),
('Ruang Gudang', 1);

UPDATE users SET zone_id = 1 WHERE id = 2;