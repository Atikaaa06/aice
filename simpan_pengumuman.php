<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'penjual') {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: kelola_pengumuman.php"); exit;
}

$id     = (int)   ($_POST['id']    ?? 0);
$judul  = trim($_POST['judul']     ?? '');
$isi    = trim($_POST['isi']       ?? '');
$tipe   = trim($_POST['tipe']      ?? 'info');
$badge  = trim($_POST['badge']     ?? '');
$aktif  = (int)   ($_POST['aktif'] ?? 1);

if (!$judul || !$isi) {
    echo "<script>alert('Judul dan isi tidak boleh kosong!'); history.back();</script>"; exit;
}
$tipeValid = ['diskon','promo','info','penting'];
if (!in_array($tipe, $tipeValid)) $tipe = 'info';
$badgeVal = $badge ?: null;

if ($id === 0) {
    $stmt = $conn->prepare("INSERT INTO pengumuman (judul, isi, tipe, badge, aktif) VALUES (?,?,?,?,?)");
    $stmt->bind_param("ssssi", $judul, $isi, $tipe, $badgeVal, $aktif);
} else {
    $stmt = $conn->prepare("UPDATE pengumuman SET judul=?, isi=?, tipe=?, badge=?, aktif=? WHERE id=?");
    $stmt->bind_param("ssssii", $judul, $isi, $tipe, $badgeVal, $aktif, $id);
}
$ok = $stmt->execute();
$stmt->close();

header("Location: kelola_pengumuman.php?" . ($ok ? "saved=1" : "error=1"));
exit;