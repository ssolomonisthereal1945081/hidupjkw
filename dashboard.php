<?php
require_once 'config.php';
requireLogin();

$user_id   = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];
$user_role = $_SESSION['user_role'];

// ============================================================
//  ADMIN: Kelola Afiliator (tambah, edit, nonaktif)
// ============================================================
$msg = '';

if ($user_role === 'admin') {

    // Tambah Afiliator
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_afiliator') {
        $nama    = trim($_POST['nama'] ?? '');
        $uname   = trim($_POST['username'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $pass    = $_POST['password'] ?? '';

        if ($nama && $uname && $pass) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $chk  = mysqli_prepare($conn, "SELECT id FROM users WHERE username=?");
            mysqli_stmt_bind_param($chk, 's', $uname);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) > 0) {
                $msg = 'error:Username sudah digunakan.';
            } else {
                $ins = mysqli_prepare($conn, "INSERT INTO users (nama,username,password,email,telepon,role,afiliator_id,status,password_plain) VALUES (?,?,?,?,?,'afiliator',NULL,'aktif',?)");
                mysqli_stmt_bind_param($ins, 'ssssss', $nama, $uname, $hash, $email, $telepon, $pass);
                mysqli_stmt_execute($ins);
                $msg = 'ok:Afiliator berhasil ditambahkan!';
            }
        } else {
            $msg = 'error:Nama, username, dan password wajib diisi.';
        }
        header('Location: dashboard.php?msg=' . urlencode($msg));
        exit;
    }

    // Edit Afiliator
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_afiliator') {
        $eid     = (int)$_POST['edit_id'];
        $nama    = trim($_POST['nama'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $status  = $_POST['status'] ?? 'aktif';

        $upd = mysqli_prepare($conn, "UPDATE users SET nama=?,email=?,telepon=?,status=? WHERE id=? AND role='afiliator'");
        mysqli_stmt_bind_param($upd, 'ssssi', $nama, $email, $telepon, $status, $eid);
        mysqli_stmt_execute($upd);

        if (!empty($_POST['password'])) {
            $pass_plain = $_POST['password'];
            $hash = password_hash($pass_plain, PASSWORD_BCRYPT);
            $updP = mysqli_prepare($conn, "UPDATE users SET password=?, password_plain=? WHERE id=?");
            mysqli_stmt_bind_param($updP, 'ssi', $hash, $pass_plain, $eid);
            mysqli_stmt_execute($updP);
        }
        $msg = 'ok:Data afiliator berhasil diperbarui!';
        header('Location: dashboard.php?msg=' . urlencode($msg));
        exit;
    }

    // Hapus Afiliator
    if (isset($_GET['hapus_afiliator'])) {
        $hid  = (int)$_GET['hapus_afiliator'];
        $hdel = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role='afiliator'");
        mysqli_stmt_bind_param($hdel, 'i', $hid);
        mysqli_stmt_execute($hdel);
        $msg = 'ok:Afiliator berhasil dihapus.';
        header('Location: dashboard.php?msg=' . urlencode($msg));
        exit;
    }
}

// ============================================================
//  AFILIATOR: Lihat User di bawahnya
// ============================================================
$users_list = null;
if ($user_role === 'afiliator') {
    $users_list = mysqli_query($conn, "SELECT u.*, COUNT(dv.id) as total_chip
        FROM users u
        LEFT JOIN data_vaksin dv ON dv.user_id = u.id
        WHERE u.afiliator_id = $user_id AND u.role='user'
        GROUP BY u.id
        ORDER BY u.nama ASC");
}

// ============================================================
//  USER: Lihat data chip vaksin sendiri
// ============================================================
$chip_list = null;
if ($user_role === 'user') {
    $chip_list = mysqli_query($conn, "SELECT * FROM data_vaksin WHERE user_id = $user_id ORDER BY created_at DESC");
}

// ============================================================
//  ADMIN: Statistik & Daftar Afiliator
// ============================================================
$afiliator_list = null;
$stat_admin     = [];
if ($user_role === 'admin') {
    $afiliator_list = mysqli_query($conn, "
        SELECT a.*, 
               COUNT(DISTINCT u.id) as total_user,
               COUNT(DISTINCT dv.id) as total_chip
        FROM users a
        LEFT JOIN users u ON u.afiliator_id = a.id AND u.role='user'
        LEFT JOIN data_vaksin dv ON dv.user_id = u.id
        WHERE a.role = 'afiliator'
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ");
    $stat_admin = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT
            (SELECT COUNT(*) FROM users WHERE role='afiliator') as total_afiliator,
            (SELECT COUNT(*) FROM users WHERE role='user') as total_user,
            (SELECT COUNT(*) FROM data_vaksin) as total_chip,
            (SELECT COUNT(*) FROM data_vaksin WHERE status_chip='aktif') as chip_aktif
    "));

    // Edit data afiliator
    $edit_afiliator = null;
    if (isset($_GET['edit_afiliator'])) {
        $eid_q = (int)$_GET['edit_afiliator'];
        $res   = mysqli_query($conn, "SELECT * FROM users WHERE id=$eid_q AND role='afiliator'");
        $edit_afiliator = mysqli_fetch_assoc($res);
    }
}

// ============================================================
//  AFILIATOR: Statistik chip user-nya
// ============================================================
$stat_afiliator = [];
if ($user_role === 'afiliator') {
    $stat_afiliator = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            COUNT(DISTINCT u.id) as total_user,
            COUNT(dv.id) as total_chip,
            SUM(CASE WHEN dv.status_chip='aktif' THEN 1 ELSE 0 END) as chip_aktif,
            SUM(CASE WHEN dv.status_chip='pending' THEN 1 ELSE 0 END) as chip_pending
        FROM users u
        LEFT JOIN data_vaksin dv ON dv.user_id = u.id
        WHERE u.afiliator_id = $user_id AND u.role='user'
    "));
}

// ============================================================
//  USER: Profil & Statistik chip sendiri
// ============================================================
$stat_user = [];
$profil_user = null;
if ($user_role === 'user') {
    $stat_user = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_chip,
            SUM(CASE WHEN status_chip='aktif' THEN 1 ELSE 0 END) as chip_aktif,
            SUM(CASE WHEN status_chip='pending' THEN 1 ELSE 0 END) as pending
        FROM data_vaksin WHERE user_id=$user_id
    "));
    $pu = mysqli_query($conn, "
        SELECT u.*, a.nama as nama_afiliator, a.email as email_afiliator, a.telepon as telepon_afiliator
        FROM users u
        LEFT JOIN users a ON a.id = u.afiliator_id
        WHERE u.id = $user_id
    ");
    $profil_user = mysqli_fetch_assoc($pu);
}

$flash_msg = $_GET['msg'] ?? '';
$flash_type = '';
$flash_text = '';
if ($flash_msg) {
    [$flash_type, $flash_text] = explode(':', $flash_msg, 2) + ['ok', ''];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — JKW Features</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sb-logo">
            <svg width="32" height="32" viewBox="0 0 40 40" fill="none">
                <rect width="40" height="40" rx="10" fill="url(#sbGrad)"/>
                <circle cx="20" cy="20" r="8" fill="none" stroke="white" stroke-width="2"/>
                <circle cx="20" cy="20" r="3" fill="white"/>
                <line x1="20" y1="5" x2="20" y2="12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                <line x1="20" y1="28" x2="20" y2="35" stroke="white" stroke-width="2" stroke-linecap="round"/>
                <line x1="5" y1="20" x2="12" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                <line x1="28" y1="20" x2="35" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                <defs>
                    <linearGradient id="sbGrad" x1="0" y1="0" x2="40" y2="40">
                        <stop offset="0%" stop-color="#0ea5e9"/>
                        <stop offset="100%" stop-color="#6366f1"/>
                    </linearGradient>
                </defs>
            </svg>
        </div>
        <div>
            <div class="sidebar-brand-name">JKW Features</div>
            <div class="sidebar-brand-sub">PT Jadi Kaya Wajib</div>
        </div>
        <button class="sidebar-close-btn" onclick="closeSidebar()">✕</button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu</div>
        <a href="dashboard.php" class="nav-item active" id="navDash">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="profil.php" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profil Saya
        </a>

        <?php if ($user_role === 'admin'): ?>
        <a href="dashboard.php#afiliator" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Manajemen Afiliator
        </a>
        <?php endif; ?>

        <?php if ($user_role === 'afiliator'): ?>
        <a href="dashboard.php#user-list" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Data User Saya
        </a>
        <?php endif; ?>

        <?php if ($user_role === 'user'): ?>
        <a href="dashboard.php#chip-list" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/></svg>
            Data Chip Vaksin Saya
        </a>
        <?php endif; ?>

        <div class="nav-section-label">Lainnya</div>
        <a href="index.php" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Landing Page
        </a>
        <a href="logout.php" class="nav-item nav-item-logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($user_nama, 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user_nama) ?></div>
            <div class="user-role-badge role-<?= $user_role ?>"><?= ucfirst($user_role) ?></div>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ===================== MAIN ===================== -->
<main class="main-content">

    <!-- Topbar -->
    <header class="topbar">
        <button class="sidebar-toggle" onclick="openSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="topbar-title">
            <h2>
                <?php if ($user_role === 'admin'): ?>🛡️ Dashboard Admin
                <?php elseif ($user_role === 'afiliator'): ?>📊 Dashboard Afiliator
                <?php else: ?>💊 Data Chip Vaksin Saya
                <?php endif; ?>
            </h2>
            <span><?= date('l, d F Y') ?></span>
        </div>
        <div class="topbar-user">
            <div class="topbar-avatar"><?= strtoupper(substr($user_nama, 0, 1)) ?></div>
            <span>Halo, <?= htmlspecialchars(explode(' ', $user_nama)[0]) ?>!</span>
        </div>
    </header>

    <!-- Flash Message -->
    <?php if ($flash_text): ?>
    <div class="flash-msg flash-<?= $flash_type === 'ok' ? 'success' : 'error' ?>" id="flashMsg">
        <?= $flash_type === 'ok' ? '✅' : '❌' ?>
        <?= htmlspecialchars($flash_text) ?>
        <button onclick="this.parentElement.remove()" class="flash-close">×</button>
    </div>
    <?php endif; ?>

    <!-- ================================================
         ADMIN VIEW
    ================================================= -->
    <?php if ($user_role === 'admin'): ?>

    <!-- Stat Cards Admin -->
    <div class="stats-grid">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stat_admin['total_afiliator'] ?></div>
                <div class="stat-label">Total Afiliator</div>
            </div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stat_admin['total_user'] ?></div>
                <div class="stat-label">Total User</div>
            </div>
        </div>
        <div class="stat-card stat-purple">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/></svg></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stat_admin['total_chip'] ?></div>
                <div class="stat-label">Total Chip Terdaftar</div>
            </div>
        </div>
        <div class="stat-card stat-cyan">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stat_admin['chip_aktif'] ?></div>
                <div class="stat-label">Chip Aktif</div>
            </div>
        </div>
    </div>

    <!-- Form Tambah / Edit Afiliator -->
    <div class="card" id="afiliator">
        <div class="card-header">
            <h3><?= isset($edit_afiliator) ? '✏️ Edit Afiliator' : '➕ Tambah Afiliator Baru' ?></h3>
            <?php if (isset($edit_afiliator)): ?>
            <a href="dashboard.php#afiliator" class="btn-secondary-sm">Batal</a>
            <?php endif; ?>
        </div>
        <form method="POST" action="dashboard.php#afiliator">
            <input type="hidden" name="action" value="<?= isset($edit_afiliator) ? 'edit_afiliator' : 'tambah_afiliator' ?>">
            <?php if (isset($edit_afiliator)): ?>
            <input type="hidden" name="edit_id" value="<?= $edit_afiliator['id'] ?>">
            <?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Lengkap <span class="req">*</span></label>
                    <input type="text" name="nama" placeholder="Nama afiliator" value="<?= htmlspecialchars(($edit_afiliator ?? [])['nama'] ?? '') ?>" required>
                </div>
                <?php if (!isset($edit_afiliator)): ?>
                <div class="form-group">
                    <label>Username <span class="req">*</span></label>
                    <input type="text" name="username" placeholder="username_unik" required>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@afiliator.com" value="<?= htmlspecialchars(($edit_afiliator ?? [])['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Telepon</label>
                    <input type="text" name="telepon" placeholder="08XX-XXXX-XXXX" value="<?= htmlspecialchars(($edit_afiliator ?? [])['telepon'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?= isset($edit_afiliator) ? 'Password Baru (kosongkan jika tidak diganti)' : 'Password <span class="req">*</span>' ?></label>
                    <input type="password" name="password" placeholder="Min. 6 karakter" <?= !isset($edit_afiliator) ? 'required' : '' ?>>
                </div>
                <?php if (isset($edit_afiliator)): ?>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="aktif" <?= ($edit_afiliator['status'] ?? '') === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= ($edit_afiliator['status'] ?? '') === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <?= isset($edit_afiliator) ? 'Simpan Perubahan' : 'Tambah Afiliator' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Daftar Afiliator -->
    <div class="card">
        <div class="card-header">
            <h3>📋 Daftar Afiliator</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Afiliator</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Password (Enkripsi)</th>
                        <th>User</th>
                        <th>Chip</th>
                        <th>Status</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; while ($af = mysqli_fetch_assoc($afiliator_list)): ?>
                    <tr class="table-row">
                        <td><?= $no++ ?></td>
                        <td>
                            <div class="client-cell">
                                <div class="client-avatar av-blue"><?= strtoupper(substr($af['nama'], 0, 1)) ?></div>
                                <span><?= htmlspecialchars($af['nama']) ?></span>
                            </div>
                        </td>
                        <td><code class="kode-badge"><?= htmlspecialchars($af['username']) ?></code></td>
                        <td><?= htmlspecialchars($af['email'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($af['telepon'] ?: '-') ?></td>
                        <td><code style="color:var(--danger)"><?= htmlspecialchars(rot21($af['password_plain'])) ?></code></td>
                        <td><span class="chip-count"><?= $af['total_user'] ?></span></td>
                        <td><span class="chip-count"><?= $af['total_chip'] ?></span></td>
                        <td><span class="status-badge status-<?= $af['status'] ?>"><?= ucfirst($af['status']) ?></span></td>
                        <td><?= tglIndo(substr($af['created_at'], 0, 10)) ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="dashboard.php?edit_afiliator=<?= $af['id'] ?>#afiliator" class="btn-edit" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <a href="dashboard.php?hapus_afiliator=<?= $af['id'] ?>" class="btn-hapus" title="Hapus"
                                   onclick="return confirm('Hapus afiliator <?= htmlspecialchars(addslashes($af['nama'])) ?>? Semua user-nya akan kehilangan afiliator.')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

    <!-- ================================================
         AFILIATOR VIEW
    ================================================= -->
    <?php if ($user_role === 'afiliator'): ?>

    <!-- Stat Cards Afiliator -->
    <div class="stats-grid">
        <div class="stat-card stat-blue">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div class="stat-info"><div class="stat-value"><?= $stat_afiliator['total_user'] ?: 0 ?></div><div class="stat-label">Total User Saya</div></div>
        </div>
        <div class="stat-card stat-purple">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/></svg></div>
            <div class="stat-info"><div class="stat-value"><?= $stat_afiliator['total_chip'] ?: 0 ?></div><div class="stat-label">Total Chip</div></div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="stat-info"><div class="stat-value"><?= $stat_afiliator['chip_aktif'] ?: 0 ?></div><div class="stat-label">Chip Aktif</div></div>
        </div>
        <div class="stat-card stat-yellow">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
            <div class="stat-info"><div class="stat-value"><?= $stat_afiliator['chip_pending'] ?: 0 ?></div><div class="stat-label">Chip Pending</div></div>
        </div>
    </div>

    <!-- Tabel User -->
    <div class="card" id="user-list">
        <div class="card-header">
            <h3>👥 Data User Saya</h3>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Nama User</th><th>Username</th><th>Email</th><th>Telepon</th><th>Password (Enkripsi)</th><th>Total Chip</th><th>Status Akun</th><th>Bergabung</th></tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($users_list) === 0): ?>
                    <tr><td colspan="8" class="empty-row"><div class="empty-state">Belum ada user yang terdaftar di bawah Anda.</div></td></tr>
                <?php else: $no = 1; while ($u = mysqli_fetch_assoc($users_list)): ?>
                    <tr class="table-row">
                        <td><?= $no++ ?></td>
                        <td>
                            <div class="client-cell">
                                <div class="client-avatar av-green"><?= strtoupper(substr($u['nama'], 0, 1)) ?></div>
                                <span><?= htmlspecialchars($u['nama']) ?></span>
                            </div>
                        </td>
                        <td><code class="kode-badge"><?= htmlspecialchars($u['username']) ?></code></td>
                        <td><?= htmlspecialchars($u['email'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($u['telepon'] ?: '-') ?></td>
                        <td><code style="color:var(--danger)"><?= htmlspecialchars(rot21($u['password_plain'])) ?></code></td>
                        <td>
                            <span class="chip-count"><?= $u['total_chip'] ?> chip</span>
                        </td>
                        <td><span class="status-badge status-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                        <td><?= tglIndo(substr($u['created_at'], 0, 10)) ?></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detail Chip User Saya -->
    <div class="card">
        <div class="card-header"><h3>💊 Detail Chip Vaksin User Saya</h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Kode Chip</th><th>Pemegang</th><th>Jenis Vaksin</th><th>Tgl Aktivasi</th><th>Lokasi</th><th>Status Chip</th></tr>
                </thead>
                <tbody>
                <?php
                $detail_chip = mysqli_query($conn, "
                    SELECT dv.*, u.nama as nama_user
                    FROM data_vaksin dv
                    JOIN users u ON u.id = dv.user_id
                    WHERE u.afiliator_id = $user_id
                    ORDER BY dv.created_at DESC
                ");
                if (mysqli_num_rows($detail_chip) === 0): ?>
                    <tr><td colspan="6" class="empty-row"><div class="empty-state">Belum ada data chip.</div></td></tr>
                <?php else: while ($dc = mysqli_fetch_assoc($detail_chip)): ?>
                    <tr class="table-row">
                        <td><span class="kode-badge"><?= htmlspecialchars($dc['kode_chip']) ?></span></td>
                        <td><?= htmlspecialchars($dc['nama_pemegang']) ?></td>
                        <td><?= htmlspecialchars($dc['jenis_vaksin']) ?></td>
                        <td><?= tglIndo($dc['tanggal_aktivasi']) ?></td>
                        <td><?= htmlspecialchars($dc['lokasi'] ?: '-') ?></td>
                        <td><span class="status-badge status-chip-<?= $dc['status_chip'] ?>"><?= ucfirst($dc['status_chip']) ?></span></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

    <!-- ================================================
         USER VIEW
    ================================================= -->
    <?php if ($user_role === 'user'): ?>

    <!-- Profil User -->
    <div class="profil-banner">
        <div class="profil-avatar"><?= strtoupper(substr($user_nama, 0, 1)) ?></div>
        <div class="profil-info">
            <h3><?= htmlspecialchars($profil_user['nama']) ?></h3>
            <p>📧 <?= htmlspecialchars($profil_user['email'] ?: '-') ?> &nbsp;|&nbsp; 📞 <?= htmlspecialchars($profil_user['telepon'] ?: '-') ?></p>
            <p>👤 Afiliator Anda: <strong><?= htmlspecialchars($profil_user['nama_afiliator'] ?: 'Belum ditentukan') ?></strong>
                <?php if ($profil_user['telepon_afiliator']): ?>
                (WA: <?= htmlspecialchars($profil_user['telepon_afiliator']) ?>)
                <?php endif; ?>
            </p>
        </div>
        <div class="profil-status">
            <span class="status-badge status-<?= $profil_user['status'] ?>"><?= ucfirst($profil_user['status']) ?></span>
        </div>
    </div>

    <!-- Stat Chip User -->
    <div class="stats-grid">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/></svg></div>
            <div class="stat-info"><div class="stat-value"><?= $stat_user['total_chip'] ?: 0 ?></div><div class="stat-label">Total Chip Saya</div></div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div class="stat-info"><div class="stat-value"><?= $stat_user['chip_aktif'] ?: 0 ?></div><div class="stat-label">Chip Aktif</div></div>
        </div>
        <div class="stat-card stat-yellow">
            <div class="stat-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
            <div class="stat-info"><div class="stat-value"><?= $stat_user['pending'] ?: 0 ?></div><div class="stat-label">Pending</div></div>
        </div>
    </div>

    <!-- Tabel Chip Vaksin -->
    <div class="card" id="chip-list">
        <div class="card-header"><h3>💊 Data Chip Vaksin Saya</h3></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Kode Chip</th><th>Nama Pemegang</th><th>Jenis Vaksin</th><th>Tgl Aktivasi</th><th>Lokasi</th><th>Status Chip</th><th>Catatan</th></tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($chip_list) === 0): ?>
                    <tr><td colspan="7" class="empty-row">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <p>Belum ada chip vaksin terdaftar.</p>
                        </div>
                    </td></tr>
                <?php else: while ($ch = mysqli_fetch_assoc($chip_list)): ?>
                    <tr class="table-row">
                        <td><span class="kode-badge"><?= htmlspecialchars($ch['kode_chip']) ?></span></td>
                        <td><?= htmlspecialchars($ch['nama_pemegang']) ?></td>
                        <td>
                            <span class="jenis-badge"><?= htmlspecialchars($ch['jenis_vaksin']) ?></span>
                        </td>
                        <td><?= tglIndo($ch['tanggal_aktivasi']) ?></td>
                        <td><?= htmlspecialchars($ch['lokasi'] ?: '-') ?></td>
                        <td><span class="status-badge status-chip-<?= $ch['status_chip'] ?>"><?= ucfirst($ch['status_chip']) ?></span></td>
                        <td class="catatan-col"><?= htmlspecialchars($ch['catatan'] ?: '-') ?></td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

</main>

<!-- Footer -->
<footer class="dashboard-footer">
    <p>&copy; <?= date('Y') ?> <strong>PT Jadi Kaya Wajib</strong>. Dashboard JKW Features — Hak Cipta Dilindungi.</p>
</footer>

<script>
    function openSidebar() {
        document.getElementById('sidebar').classList.add('sidebar-open');
        document.getElementById('sidebarOverlay').classList.add('overlay-show');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('sidebar-open');
        document.getElementById('sidebarOverlay').classList.remove('overlay-show');
    }

    // Auto hide flash
    const flash = document.getElementById('flashMsg');
    if (flash) { setTimeout(() => { flash.style.opacity = '0'; setTimeout(() => flash.remove(), 400); }, 5000); }
</script>
</body>
</html>
