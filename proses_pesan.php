<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: produk.php");
    exit;
}

$id_produk = (int) $_POST['id_produk'];
$jumlah    = (int) $_POST['jumlah'];
$catatan   = trim($_POST['catatan'] ?? '');
$username  = $_SESSION['username'];

if ($jumlah <= 0) {
    echo "<script>alert('Jumlah harus lebih dari 0!'); history.back();</script>";
    exit;
}

$stmt = $conn->prepare("INSERT INTO pesan_order (id_produk, username, jumlah, catatan) VALUES (?,?,?,?)");
$stmt->bind_param("isis", $id_produk, $username, $jumlah, $catatan);
$sukses = $stmt->execute();
$stmt->close();

if ($sukses) {
    echo "<script>alert('Pesanan berhasil dikirim! Admin akan segera memproses pesananmu.'); window.location='beli.php?id=" . $id_produk . "&tab=pesan';</script>";
} else {
    echo "<script>alert('Gagal mengirim pesanan.'); history.back();</script>";
}