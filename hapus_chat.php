<?php
session_start();
include 'koneksi.php';

$pengirim = $_SESSION['username'];
$penerima = $_POST['penerima'];
$pesan    = $_POST['pesan'];

$gambar = "";

if($_FILES['gambar']['name']){
    $nama = time() . "_" . $_FILES['gambar']['name'];
    $tmp  = $_FILES['gambar']['tmp_name'];

    move_uploaded_file($tmp, "upload/".$nama);
    $gambar = $nama;
}

mysqli_query($conn, "
INSERT INTO pesan_chat (pengirim, penerima, pesan, gambar, created_at)
VALUES ('$pengirim','$penerima','$pesan','$gambar', NOW())
");

header("Location: chat.php");