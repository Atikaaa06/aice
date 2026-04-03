<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['role'])) {
    $roleMap = ['admin_assets'=>'admin_asset','admin_programs'=>'admin_program'];
    $_SESSION['role'] = $roleMap[$_SESSION['role']] ?? $_SESSION['role'];
}
if (!in_array($_SESSION['role'], ['penjual','admin_program'])) { header("Location: dashboard.php"); exit; }
include 'koneksi.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: kelola_wilayah.php"); exit; }

$id            = (int)  ($_POST['id']            ?? 0);
$nama_wilayah  = trim($_POST['nama_wilayah']     ?? '');
$nama_admin    = trim($_POST['nama_admin']        ?? '');
$no_hp         = trim($_POST['no_hp']            ?? '');
$area_coverage = trim($_POST['area_coverage']    ?? '');
$urutan        = (int)  ($_POST['urutan']        ?? 1);
$aktif         = (int)  ($_POST['aktif']         ?? 1);

if (!$nama_wilayah || !$nama_admin || !$no_hp) {
    echo "<script>alert('Nama wilayah, admin, dan no HP wajib diisi!'); history.back();</script>"; exit;
}

// Normalisasi nomor HP: 08xx → 628xx
$no_hp = preg_replace('/[^0-9]/', '', $no_hp);
if (substr($no_hp, 0, 1) === '0') $no_hp = '62' . substr($no_hp, 1);

if ($id === 0) {
    $stmt = $conn->prepare("INSERT INTO wilayah (nama_wilayah, nama_admin, no_hp, area_coverage, urutan, aktif) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssii", $nama_wilayah, $nama_admin, $no_hp, $area_coverage, $urutan, $aktif);
} else {
    $stmt = $conn->prepare("UPDATE wilayah SET nama_wilayah=?, nama_admin=?, no_hp=?, area_coverage=?, urutan=?, aktif=? WHERE id=?");
    $stmt->bind_param("ssssiii", $nama_wilayah, $nama_admin, $no_hp, $area_coverage, $urutan, $aktif, $id);
}

$ok = $stmt->execute();
$stmt->close();
header("Location: kelola_wilayah.php?" . ($ok ? "saved=1" : "error=1")); exit;