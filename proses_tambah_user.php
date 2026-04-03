<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'penjual') {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: kelola_user.php"); exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role']     ?? 'pembeli');

if (!$username || !$password) {
    header("Location: kelola_user.php?error_user=empty"); exit;
}
if (strlen($username) < 4) {
    header("Location: kelola_user.php?error_user=short"); exit;
}
if (strlen($password) < 6) {
    header("Location: kelola_user.php?error_user=pw_short"); exit;
}
if (!in_array($role, ['penjual','pembeli'])) $role = 'pembeli';

// Cek username duplikat
$cek = $conn->prepare("SELECT id FROM users WHERE username=?");
$cek->bind_param("s", $username);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
    $cek->close();
    header("Location: kelola_user.php?error_user=taken"); exit;
}
$cek->close();

$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?,?,?)");
$stmt->bind_param("sss", $username, $password, $role);
$ok = $stmt->execute();
$stmt->close();

header("Location: kelola_user.php?" . ($ok ? "saved=1" : "error_user=failed"));
exit;