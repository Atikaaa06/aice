<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php"); exit;
}

$nama_depan    = trim($_POST['nama_depan']    ?? '');
$nama_belakang = trim($_POST['nama_belakang'] ?? '');
$username      = trim($_POST['username']      ?? '');
$email         = trim($_POST['email']         ?? '');
$password      = $_POST['password']           ?? '';
$konfirmasi    = $_POST['konfirmasi']         ?? '';

// Validasi wajib
if (!$nama_depan || !$username || !$password || !$konfirmasi) {
    header("Location: register.php?error=empty"); exit;
}

// Validasi format username
if (strlen($username) < 4 || preg_match('/\s/', $username)) {
    header("Location: register.php?error=username_format"); exit;
}

// Validasi password minimal 6 karakter
if (strlen($password) < 6) {
    header("Location: register.php?error=password_short"); exit;
}

// Validasi konfirmasi password
if ($password !== $konfirmasi) {
    header("Location: register.php?error=password_mismatch"); exit;
}

// Cek username sudah ada
$cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
$cek->bind_param("s", $username);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
    $cek->close();
    header("Location: register.php?error=username_taken&username=" . urlencode($username)); exit;
}
$cek->close();

// Simpan — password plain text (konsisten dengan sistem yang ada)
// Ganti dengan password_hash($password, PASSWORD_DEFAULT) jika ingin lebih aman
$nama_lengkap = trim("$nama_depan $nama_belakang");
$role = 'pembeli';

$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?,?,?)");
$stmt->bind_param("sss", $username, $password, $role);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    header("Location: register.php?success=1");
} else {
    header("Location: register.php?error=failed");
}
exit;