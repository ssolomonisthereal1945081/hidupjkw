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
            if ($user['role'] === 'admin') {
                $error = 'Admin dilarang masuk lewat sini. Silakan gunakan portal khusus administrator.';
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
    <title>Login — JKW Features Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

    <div class="login-bg-orb orb-a"></div>
    <div class="login-bg-orb orb-b"></div>

    <div class="login-wrapper">
        <!-- Logo -->
        <a href="index.php" class="login-brand">
            <div class="login-logo-wrap">
                <svg width="36" height="36" viewBox="0 0 40 40" fill="none">
                    <rect width="40" height="40" rx="10" fill="url(#loginLogoGrad)"/>
                    <circle cx="20" cy="20" r="8" fill="none" stroke="white" stroke-width="2"/>
                    <circle cx="20" cy="20" r="3" fill="white"/>
                    <line x1="20" y1="5" x2="20" y2="12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <line x1="20" y1="28" x2="20" y2="35" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <line x1="5" y1="20" x2="12" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <line x1="28" y1="20" x2="35" y2="20" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    <defs>
                        <linearGradient id="loginLogoGrad" x1="0" y1="0" x2="40" y2="40">
                            <stop offset="0%" stop-color="#0ea5e9"/>
                            <stop offset="100%" stop-color="#6366f1"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="login-brand-text">
                <span class="login-brand-name">JKW Features</span>
                <span class="login-brand-sub">PT Jadi Kaya Wajib</span>
            </div>
        </a>

        <!-- Card -->
        <div class="login-card">
            <div class="login-header">
                <h1>Selamat Datang</h1>
                <p>Masuk ke portal manajemen JKW Features</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="username" name="username" placeholder="Masukkan username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
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
                    <span class="btn-text">Masuk</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>

            <div class="login-hint">
                <p><strong>Akun Demo:</strong></p>
                <table class="demo-table">
                    <tr><td>Afiliator</td><td><code>afiliator1</code></td><td>password123</td></tr>
                    <tr><td>User</td><td><code>user1</code></td><td>password123</td></tr>
                </table>
            </div>
        </div>

        <footer class="login-footer">
            <a href="index.php">&larr; Kembali ke Landing Page</a>
            <p>&copy; <?= date('Y') ?> <strong>PT Jadi Kaya Wajib</strong>. Hak Cipta Dilindungi.</p>
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
            btn.querySelector('.btn-text').textContent = 'Memproses...';
        });
    </script>
</body>
</html>
