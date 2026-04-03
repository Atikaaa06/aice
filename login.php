<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            top: -200px; right: -200px;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,168,76,0.12), transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -150px; left: -150px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,168,76,0.07), transparent 70%);
            pointer-events: none;
        }
        .login-card {
            width: 420px;
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.55s cubic-bezier(.22,.68,0,1.2) both;
        }
        .login-header {
            background: var(--navy);
            padding: 36px 40px 32px;
            text-align: center;
            position: relative;
        }
        .login-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 50%;
            transform: translateX(-50%);
            width: 50px; height: 3px;
            background: var(--gold);
            border-radius: 2px;
        }
        .logo-ring {
            width: 62px; height: 62px;
            border-radius: 50%;
            border: 1.5px solid var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            margin: 0 auto 16px;
        }
        .login-header h1 {
            font-family: 'Playfair Display', serif;
            color: var(--white);
            font-size: 22px;
        }
        .login-header p {
            color: var(--gold-light);
            font-size: 11px;
            font-weight: 300;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .login-body {
            padding: 36px 40px 40px;
            background: var(--cream);
        }
        .login-footer {
            text-align: center;
            color: var(--muted);
            font-size: 11px;
            margin-top: 24px;
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="logo-ring" style="width:100px;height:100px;border:3px solid var(--gold);background:white;padding:6px;box-shadow:0 0 30px rgba(201,168,76,0.3);">
            <img src="assets/logo_jt2.jpeg" alt="Logo PT JUMA TIGA SEANTERO"
                 style="width:100%;height:100%;object-fit:contain;border-radius:50%;">
        </div>
        <h1>PT JUMA TIGA SEANTERO</h1>
        <p>Sistem Manajemen Penjualan</p>
    </div>
    <div class="login-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">⚠️ Username atau password salah. Silakan coba lagi.</div>
        <?php endif; ?>
        <form method="POST" action="proses_login.php">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary">Masuk →</button>
        </form>
        <p style="text-align:center;margin-top:14px;font-size:13px;color:var(--muted);">Belum punya akun? <a href="register.php" style="color:var(--navy);font-weight:600;">Daftar di sini</a></p>
        <p style="text-align:center;margin-top:14px;font-size:13px;color:var(--muted);">
        <a href="index.php" style="color:var(--navy);font-weight:600;text-decoration:none;">
            ← Kembali ke Beranda
        </a>
    </p>
    <p class="login-footer">© <?= date('Y') ?> PT JUMA TIGA SEANTERO. All rights reserved.</p>
    </div>
</div>
</body>
</html>