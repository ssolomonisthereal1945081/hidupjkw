<?php
require_once 'config.php';
requireLogin();

$user_id   = $_SESSION['user_id'];
$user_nama = $_SESSION['user_nama'];
$user_role = $_SESSION['user_role'];

// ---- Eksekusi Simpan Catatan ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan_catatan') {
    $note = trim($_POST['catatan_pribadi'] ?? '');
    $upd = mysqli_prepare($conn, "UPDATE users SET catatan_pribadi=? WHERE id=?");
    mysqli_stmt_bind_param($upd, 'si', $note, $user_id);
    mysqli_stmt_execute($upd);
    
    header('Location: profil.php?msg=' . urlencode('ok:Catatan pribadi berhasil disimpan!'));
    exit;
}

// Fetch semua data user
$user_q = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($user_q);
$catatan_pribadi = $user_data['catatan_pribadi'] ?? '';

// Flash message
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
    <title>Profil Saya — JKW Features</title>
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
        <a href="dashboard.php" class="nav-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="profil.php" class="nav-item active">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profil Saya
        </a>

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
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ===================== MAIN ===================== -->
<main class="main-content">
    <header class="topbar">
        <button class="sidebar-toggle" onclick="openSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="topbar-title">
            <h2>👤 Profil Saya</h2>
            <span>Kelola informasi akun dan catatan pribadi</span>
        </div>
    </header>

    <?php if ($flash_text): ?>
    <div class="flash-msg flash-<?= $flash_type === 'ok' ? 'success' : 'error' ?>" id="flashMsg">
        <?= $flash_type === 'ok' ? '✅' : '❌' ?>
        <?= htmlspecialchars($flash_text) ?>
        <button onclick="this.parentElement.remove()" class="flash-close">×</button>
    </div>
    <?php endif; ?>

    <div class="stats-grid" style="grid-template-columns: 1fr; max-width: 800px;">
        <!-- Profil Detail -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h3>Informasi Akun</h3>
            </div>
            <div style="padding: 24px;">
                <div class="profil-banner" style="margin-bottom: 20px;">
                    <div class="profil-avatar" style="width: 80px; height: 80px; font-size: 32px;"><?= strtoupper(substr($user_nama, 0, 1)) ?></div>
                    <div class="profil-info">
                        <h2><?= htmlspecialchars($user_data['nama']) ?></h2>
                        <div class="user-role-badge role-<?= $user_role ?>" style="display:inline-block; margin-top:8px;"><?= ucfirst($user_role) ?></div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?= htmlspecialchars($user_data['username']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Status Akun</label>
                        <input type="text" value="<?= ucfirst($user_data['status']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" value="<?= htmlspecialchars($user_data['email'] ?: '-') ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text" value="<?= htmlspecialchars($user_data['telepon'] ?: '-') ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Bergabung Sejak</label>
                        <input type="text" value="<?= tglIndo(substr($user_data['created_at'], 0, 10)) ?>" disabled>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan Pribadi -->
        <div class="card">
            <div class="card-header">
                <h3>📝 Catatan Pribadi Anda</h3>
            </div>
            <form method="POST" action="profil.php">
                <input type="hidden" name="action" value="simpan_catatan">
                <div class="form-grid" style="padding-bottom: 0;">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <textarea name="catatan_pribadi" placeholder="Tulis catatan rahasia Anda di sini... Hanya Anda yang bisa melihat dan mengubahnya." style="min-height: 150px;"><?= htmlspecialchars($catatan_pribadi) ?></textarea>
                    </div>
                </div>
                <div class="form-actions" style="margin-top: 10px;">
                    <button type="submit" class="btn-primary">Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function openSidebar() { document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebarOverlay').classList.add('show'); }
    function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('show'); }
</script>
</body>
</html>
