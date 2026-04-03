<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: produk.php");
    exit;
}

$id_produk = (int) $_POST['id_produk'];
$jumlah    = (int) $_POST['jumlah'];
$username  = $_SESSION['username'];

// FIX: Validasi jumlah harus lebih dari 0
if ($jumlah <= 0) {
    echo "<script>alert('Jumlah harus lebih dari 0!'); history.back();</script>";
    exit;
}

// Ambil data produk (prepared statement)
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$result = $stmt->get_result();
$produk = $result->fetch_assoc();
$stmt->close();

if (!$produk) {
    echo "<script>alert('Produk tidak ditemukan!'); window.location='produk.php';</script>";
    exit;
}

// FIX: Cek stok jika kolom stok ada
// Hapus blok ini jika tabel produk tidak punya kolom 'stok'
if (isset($produk['stok']) && $produk['stok'] < $jumlah) {
    echo "<script>alert('Stok tidak mencukupi! Stok tersedia: " . $produk['stok'] . "'); history.back();</script>";
    exit;
}

$harga  = $produk['harga'];
$total  = $harga * $jumlah;
$tanggal = date('Y-m-d H:i:s'); // FIX: tambah tanggal transaksi

// Insert transaksi (prepared statement)
$stmt2 = $conn->prepare("INSERT INTO transaksi (id_produk, username, jumlah, total, tanggal) VALUES (?, ?, ?, ?, ?)");
$stmt2->bind_param("isids", $id_produk, $username, $jumlah, $total, $tanggal);
$sukses = $stmt2->execute();
$stmt2->close();

// FIX: Kurangi stok setelah transaksi berhasil (hapus jika tidak ada kolom stok)
if ($sukses && isset($produk['stok'])) {
    $stmt3 = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
    $stmt3->bind_param("ii", $jumlah, $id_produk);
    $stmt3->execute();
    $stmt3->close();
}

if ($sukses) {
    header("Location: riwayat.php?sukses=1&total=" . urlencode(number_format($total,0,',','.'))); exit;
} else {
    echo "<script>alert('Transaksi gagal: " . $conn->error . "'); history.back();</script>";
}