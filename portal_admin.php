<?php
require_once 'config.php';

// Redirect ke dashboard jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, nama, username, password, role FROM users WHERE username = ? AND status = 'aktif'");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== 'admin') {
                $error = 'Akses Ditolak. Anda bukan Administrator.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username']  = $user['username'];
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Username atau password salah, atau akun tidak aktif.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Admin — JKW Features</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-body { background: #0f172a; }
        .login-bg-orb.orb-a { background: radial-gradient(circle, rgba(220,38,38,0.2) 0%, transparent 70%); }
        .login-bg-orb.orb-b { background: radial-gradient(circle, rgba(185,28,28,0.15) 0%, transparent 70%); }
        .btn-login { background: linear-gradient(135deg, #ef4444, #b91c1c); }
        .btn-login:hover { background: linear-gradient(135deg, #dc2626, #991b1b); box-shadow: 0 4px 15px rgba(220,38,38,0.4); }
    </style>
</head>
<body class="login-body">

    <div class="login-bg-orb orb-a"></div>
    <div class="login-bg-orb orb-b"></div>

    <div class="login-wrapper">
        <!-- Logo -->
        <div class="login-brand" style="pointer-events: none;">
            <div class="login-logo-wrap" style="box-shadow: 0 0 20px rgba(220,38,38,0.3);">
                <svg width="36" height="36" viewBox="0 0 40 40" fill="none">
                    <rect width="40" height="40" rx="10" fill="url(#adminLogoGrad)"/>
                    <path d="M12 20l5 5 11-11" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <defs>
                        <linearGradient id="adminLogoGrad" x1="0" y1="0" x2="40" y2="40">
                            <stop offset="0%" stop-color="#ef4444"/>
                            <stop offset="100%" stop-color="#991b1b"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="login-brand-text">
                <span class="login-brand-name">Portal Admin</span>
                <span class="login-brand-sub">Area Terbatas JKW Features</span>
            </div>
        </div>

        <!-- Card -->
        <div class="login-card" style="border-top: 4px solid #ef4444;">
            <div class="login-header">
                <h1 style="color: #ef4444;">Akses Eksekutif</h1>
                <p>Otentikasi khusus manajemen pusat</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="portal_admin.php" id="loginForm">
                <div class="form-group">
                    <label for="username">Username Admin</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="username" name="username" placeholder="Masukkan username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Security Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                        <button type="button" class="toggle-pass" onclick="togglePassword()" title="Tampilkan password">
                            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="btn-text">Masuk Sistem Utama</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </button>
            </form>
        </div>

        <footer class="login-footer">
            <p>&copy; <?= date('Y') ?> <strong>Security Division PT Jadi Kaya Wajib</strong></p>
            <p>Unauthorized access is strictly prohibited.</p>
        </footer>
    </div>

    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                passInput.type = 'password';
                eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnLogin');
            btn.classList.add('loading');
            btn.querySelector('.btn-text').textContent = 'Otentikasi...';
        });
    </script>
</body>
</html>
