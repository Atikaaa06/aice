<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'penjual') {
    header("Location: login.php"); exit;
}
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: produk.php"); exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: produk.php"); exit;
}

// Cek apakah produk masih punya transaksi
$cek = $conn->prepare("SELECT COUNT(*) as n FROM transaksi WHERE id_produk = ?");
$cek->bind_param("i", $id);
$cek->execute();
$jumlahTrx = $cek->get_result()->fetch_assoc()['n'] ?? 0;
$cek->close();

if ($jumlahTrx > 0) {
    // Produk punya riwayat transaksi — nonaktifkan saja daripada hapus permanen
    // Jika tabel tidak punya kolom aktif, tetap hapus
    $cekKolom = $conn->query("SHOW COLUMNS FROM produk LIKE 'aktif'")->fetch_assoc();
    if ($cekKolom) {
        $stmt = $conn->prepare("UPDATE produk SET aktif=0 WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: produk.php?deleted=1");
    } else {
        // Hapus paksa jika tidak ada kolom aktif
        $stmt = $conn->prepare("DELETE FROM produk WHERE id=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        header("Location: produk.php?" . ($ok ? "deleted=1" : "error=1"));
    }
} else {
    // Tidak ada transaksi — hapus langsung
    $stmt = $conn->prepare("DELETE FROM produk WHERE id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    header("Location: produk.php?" . ($ok ? "deleted=1" : "error=1"));
}
exit;