<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Normalisasi role (support admin_assets dan admin_asset)
if (isset($_SESSION['role'])) {
    $_SESSION['role'] = rtrim($_SESSION['role'], 's') === 'admin_asset'
        ? 'admin_asset'
        : $_SESSION['role'];
    $_SESSION['role'] = rtrim($_SESSION['role'], 's') === 'admin_program'
        ? 'admin_program'
        : $_SESSION['role'];
}
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if ($data && $data['password'] === $password) {
    $_SESSION['username'] = $data['username'];

    // Normalisasi role — SELALU normalisasi dulu, baru simpan ke session
    $rawRole = $data['role'];
    $roleMap = [
        'admin_assets'    => 'admin_asset',
        'admin_asset'     => 'admin_asset',
        'admin_programs'  => 'admin_program',
        'admin_program'   => 'admin_program',
        'Sales'           => 'sales',
        'sales'           => 'sales',
        'Penjual'         => 'penjual',
        'penjual'         => 'penjual',
        'Pembeli'         => 'pembeli',
        'pembeli'         => 'pembeli',
    ];
    $_SESSION['role'] = $roleMap[$rawRole] ?? $rawRole;

    // Redirect langsung ke dashboard
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: login.php?error=1");
    exit;
}