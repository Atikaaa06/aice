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
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }
if (!in_array($_SESSION['role'], ['penjual'])) { header("Location: dashboard.php"); exit; }

// Penjual dan admin_asset bisa hapus
if (!in_array($_SESSION['role'], ['penjual','admin_asset'])) {
    header("Location: customer.php"); exit;
}

include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: customer.php"); exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) { header("Location: customer.php"); exit; }

$stmt = $conn->prepare("DELETE FROM customer WHERE id=?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

header("Location: customer.php?" . ($ok ? "deleted=1" : "error=1"));
exit;