<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — PT JUMA TIGA SEANTERO</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
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
        .reg-card {
            width: 460px;
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.55s cubic-bezier(.22,.68,0,1.2) both;
        }
        .reg-header {
            background: var(--navy);
            padding: 32px 40px 28px;
            text-align: center;
            position: relative;
        }
        .reg-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 50%;
            transform: translateX(-50%);
            width: 50px; height: 3px;
            background: var(--gold);
            border-radius: 2px;
        }
        .logo-ring {
            width: 58px; height: 58px;
            border-radius: 50%;
            border: 1.5px solid var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            margin: 0 auto 14px;
        }
        .reg-header h1 {
            font-family: 'Playfair Display', serif;
            color: var(--white);
            font-size: 20px;
        }
        .reg-header p {
            color: var(--gold-light);
            font-size: 11px;
            font-weight: 300;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .reg-body {
            padding: 30px 40px 36px;
            background: var(--cream);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 44px; }
        .toggle-pw {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; font-size: 16px;
            color: var(--muted);
            padding: 4px;
        }
        .toggle-pw:hover { color: var(--navy); }
        .strength-bar {
            height: 3px;
            background: #e0e4ec;
            border-radius: 2px;
            margin-top: 6px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }
        .strength-text {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: var(--muted);
        }
        .login-link a {
            color: var(--navy);
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover { text-decoration: underline; }
        @media (max-width: 480px) {
            .reg-card { width: 100%; }
            .reg-header, .reg-body { padding-left: 22px; padding-right: 22px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<style>
.back-bar-global {
    background: var(--white);
    border-bottom: 1px solid #eef0f7;
    padding: 10px 40px;
    display: flex; align-items: center; gap: 16px;
}
.back-btn-global {
    display: inline-flex; align-items: center; gap: 7px;
    color: var(--navy); text-decoration: none;
    font-size: 13px; font-weight: 600;
    padding: 6px 16px; border-radius: 50px;
    border: 1.5px solid #e0e4ec; background: var(--cream);
    transition: all 0.2s;
}
.back-btn-global:hover { background: var(--navy); color: white; border-color: var(--navy); }
</style>
<div class="back-bar-global" style="position:fixed;top:0;left:0;right:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,0.1);">
    <a href="login.php" class="back-btn-global">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        ← Kembali ke Login
    </a>
    <div style="font-size:12px;color:#999;">🏢 PT JUMA TIGA SEANTERO &nbsp;›&nbsp; Daftar Akun</div>
</div>
<div style="height:50px;"></div>

<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Normalisasi role (support admin_assets dan admin_asset)
if (isset($_SESSION['role'])) {
    $_SESSION['role'] = rtrim($_SESSION['role'], 's') === 'admin_asset'
        ? 'admin_asset'
        : $_SESSION['role'];
    $_SESSION['role'] = rtrim($_SESSION['role'], 's') === 'admin_program'
        ? 'admin_program'
        : $_SESSION['role'];
}
// Kalau sudah login, redirect
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php"); exit;
}
?>

<div class="reg-card">
    <div class="reg-header">
        <div class="logo-ring" style="width:90px;height:90px;border:3px solid var(--gold);background:white;padding:5px;box-shadow:0 0 28px rgba(201,168,76,0.3);">
            <img src="assets/logo_jt2.jpeg" alt="Logo PT JUMA TIGA SEANTERO"
                 style="width:100%;height:100%;object-fit:contain;border-radius:50%;">
        </div>
        <h1>Daftar Akun Baru</h1>
        <p>PT JUMA TIGA SEANTERO · Pembeli</p>
    </div>
    <div class="reg-body">

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $err = $_GET['error'];
                if ($err === 'username_taken') echo '⚠️ Username sudah digunakan, pilih yang lain.';
                elseif ($err === 'password_mismatch') echo '⚠️ Konfirmasi password tidak cocok.';
                elseif ($err === 'empty') echo '⚠️ Semua field wajib diisi.';
                else echo '⚠️ Terjadi kesalahan, coba lagi.';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ Akun berhasil dibuat! <a href="login.php" style="color:var(--success);font-weight:600;">Login sekarang →</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="proses_register.php" id="formReg">
            <div class="form-row">
                <div class="field">
                    <label>Nama Depan <span style="color:red">*</span></label>
                    <input type="text" name="nama_depan" placeholder="Budi" required
                        value="<?= htmlspecialchars($_GET['nama_depan'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Nama Belakang</label>
                    <input type="text" name="nama_belakang" placeholder="Santoso"
                        value="<?= htmlspecialchars($_GET['nama_belakang'] ?? '') ?>">
                </div>
            </div>

            <div class="field">
                <label>Username <span style="color:red">*</span></label>
                <input type="text" name="username" id="inputUsername"
                    placeholder="Minimal 4 karakter, tanpa spasi" required
                    value="<?= htmlspecialchars($_GET['username'] ?? '') ?>"
                    oninput="cekUsername(this)">
                <div id="usernameMsg" style="font-size:11px;margin-top:4px;"></div>
            </div>

            <div class="field">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@contoh.com"
                    value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
            </div>

            <div class="field">
                <label>Password <span style="color:red">*</span></label>
                <div class="password-wrap">
                    <input type="password" name="password" id="pw1"
                        placeholder="Minimal 6 karakter" required oninput="cekStrength(this.value)">
                    <button type="button" class="toggle-pw" onclick="togglePw('pw1', this)">👁️</button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div class="strength-text" id="strengthText"></div>
            </div>

            <div class="field">
                <label>Konfirmasi Password <span style="color:red">*</span></label>
                <div class="password-wrap">
                    <input type="password" name="konfirmasi" id="pw2"
                        placeholder="Ulangi password" required oninput="cekMatch()">
                    <button type="button" class="toggle-pw" onclick="togglePw('pw2', this)">👁️</button>
                </div>
                <div id="matchMsg" style="font-size:11px;margin-top:4px;"></div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:8px;">
                Daftar Sekarang →
            </button>
        </form>

        <p class="login-link">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text'; btn.textContent = '🙈'; }
    else { input.type = 'password'; btn.textContent = '👁️'; }
}

function cekStrength(val) {
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;
    const levels = [
        { pct:'0%',   color:'#e0e4ec', label:'' },
        { pct:'25%',  color:'#e53e3e', label:'Lemah' },
        { pct:'50%',  color:'#d97706', label:'Cukup' },
        { pct:'75%',  color:'#2563eb', label:'Kuat' },
        { pct:'100%', color:'#16a34a', label:'Sangat Kuat' },
    ];
    const lv = levels[Math.min(score, 4)];
    fill.style.width = lv.pct;
    fill.style.background = lv.color;
    text.textContent = lv.label;
    text.style.color = lv.color;
}

function cekMatch() {
    const pw1 = document.getElementById('pw1').value;
    const pw2 = document.getElementById('pw2').value;
    const msg = document.getElementById('matchMsg');
    if (!pw2) { msg.textContent = ''; return; }
    if (pw1 === pw2) { msg.textContent = '✅ Password cocok'; msg.style.color = '#16a34a'; }
    else             { msg.textContent = '❌ Password tidak cocok'; msg.style.color = '#e53e3e'; }
}

function cekUsername(input) {
    const val = input.value.trim();
    const msg = document.getElementById('usernameMsg');
    if (val.length < 4) { msg.textContent = '⚠️ Minimal 4 karakter'; msg.style.color='#d97706'; return; }
    if (/\s/.test(val)) { msg.textContent = '⚠️ Tidak boleh ada spasi'; msg.style.color='#e53e3e'; return; }
    msg.textContent = '✓ Format OK'; msg.style.color = '#16a34a';
}
</script>
</body>
</html>