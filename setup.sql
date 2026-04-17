-- =============================================
-- Setup Database untuk JKW Features
-- PT Jadi Kaya Wajib
-- =============================================

CREATE DATABASE IF NOT EXISTS jkw_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jkw_db;

-- -------------------------
-- Tabel Users (Login System)
-- role: admin | afiliator | user
-- -------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    telepon VARCHAR(20),
    role ENUM('admin','afiliator','user') DEFAULT 'user',
    afiliator_id INT DEFAULT NULL COMMENT 'NULL jika admin/afiliator. Diisi ID afiliator jika role=user',
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    password_plain VARCHAR(255) DEFAULT 'password123',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------
-- Tabel Data Chip Vaksin (milik user)
-- -------------------------
CREATE TABLE IF NOT EXISTS data_vaksin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kode_chip VARCHAR(30) NOT NULL UNIQUE,
    nama_pemegang VARCHAR(150) NOT NULL,
    tanggal_aktivasi DATE,
    jenis_vaksin VARCHAR(100) DEFAULT 'ChipVax Pro',
    status_chip ENUM('aktif','nonaktif','pending') DEFAULT 'aktif',
    lokasi VARCHAR(150),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -------------------------
-- Insert Password: password123
-- Hash bcrypt dari 'password123'
-- -------------------------

-- Admin utama
INSERT INTO users (nama, username, password, email, telepon, role, afiliator_id, password_plain) VALUES
('Administrator JKW', 'admin', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'admin@jkwfeatures.id', '0811-0000-0001', 'admin', NULL, 'password123');

-- Afiliator
INSERT INTO users (nama, username, password, email, telepon, role, afiliator_id, password_plain) VALUES
('Budi Santoso', 'afiliator1', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'budi@jkwfeatures.id', '0812-1111-2222', 'afiliator', NULL, 'password123'),
('Siti Aminah', 'afiliator2', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'siti@jkwfeatures.id', '0813-3333-4444', 'afiliator', NULL, 'password123');

-- User (dipegang afiliator1 ID=2, afiliator2 ID=3)
INSERT INTO users (nama, username, password, email, telepon, role, afiliator_id, password_plain) VALUES
('Ahmad Fauzi', 'user1', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'ahmad@gmail.com', '0821-1111-0001', 'user', 2, 'password123'),
('Dewi Permata', 'user2', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'dewi@gmail.com', '0821-1111-0002', 'user', 2, 'password123'),
('Rudi Hartono', 'user3', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'rudi@gmail.com', '0821-1111-0003', 'user', 2, 'password123'),
('Maya Indah', 'user4', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'maya@gmail.com', '0821-1111-0004', 'user', 3, 'password123'),
('Hendra Wijaya', 'user5', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'hendra@gmail.com', '0821-1111-0005', 'user', 3, 'password123'),
('Linda Susanti', 'user6', '$2y$10$.e6pjQXPzcRkgXUz7cH2VuWvEnPvDaa6W2H3sQkpUcDsVfwbNFqEy', 'linda@gmail.com', '0821-1111-0006', 'user', 3, 'password123');

-- Data chip vaksin untuk setiap user (user_id 4-9)
INSERT INTO data_vaksin (user_id, kode_chip, nama_pemegang, tanggal_aktivasi, jenis_vaksin, status_chip, lokasi, catatan) VALUES
(4, 'CHIP-AF-2024-001', 'Ahmad Fauzi', '2024-01-15', 'ChipVax Pro v2', 'aktif', 'Jakarta Selatan', 'Aktivasi berhasil. Garansi 2 tahun.'),
(4, 'CHIP-AF-2024-002', 'Ahmad Fauzi', '2024-06-10', 'ChipVax Shield', 'aktif', 'Jakarta Selatan', 'Unit kedua, perpanjangan kontrak.'),
(5, 'CHIP-DP-2024-001', 'Dewi Permata', '2024-02-20', 'ChipVax Pro v2', 'aktif', 'Bandung', 'Registrasi lengkap.'),
(6, 'CHIP-RH-2024-001', 'Rudi Hartono', '2024-03-05', 'ChipVax Lite', 'pending', 'Yogyakarta', 'Menunggu verifikasi dokumen.'),
(7, 'CHIP-MI-2024-001', 'Maya Indah', '2024-01-28', 'ChipVax Pro v2', 'aktif', 'Surabaya', 'Aktif normal.'),
(8, 'CHIP-HW-2024-001', 'Hendra Wijaya', '2024-04-12', 'ChipVax Elite', 'aktif', 'Makassar', 'Paket premium.'),
(8, 'CHIP-HW-2024-002', 'Hendra Wijaya', '2024-07-01', 'ChipVax Pro v2', 'aktif', 'Makassar', 'Chip tambahan untuk keluarga.'),
(9, 'CHIP-LS-2024-001', 'Linda Susanti', '2024-05-15', 'ChipVax Lite', 'nonaktif', 'Denpasar', 'Dinonaktifkan sementara atas permintaan pemegang.');
