<?php
// ===========================
// Konfigurasi Database JKW Features
// ===========================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jkw_db');

// Buat koneksi
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: cek login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Helper: cek role tertentu
function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['user_role'], (array)$roles)) {
        header('Location: dashboard.php?err=akses');
        exit;
    }
}

// Helper: format rupiah
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper: format tanggal Indonesia
function tglIndo($tgl) {
    if (!$tgl) return '-';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $d = explode('-', $tgl);
    return "{$d[2]} {$bulan[(int)$d[1]]} {$d[0]}";
}

// Helper: ROT21 encryption
function rot21($str) {
    $result = '';
    for ($i = 0; $i < strlen($str ?? ''); $i++) {
        $c = ord($str[$i]);
        if ($c >= 97 && $c <= 122) {
            $result .= chr((($c - 97 + 21) % 26) + 97);
        } else if ($c >= 65 && $c <= 90) {
            $result .= chr((($c - 65 + 21) % 26) + 65);
        } else {
            $result .= $str[$i];
        }
    }
    return $result;
}
?>
