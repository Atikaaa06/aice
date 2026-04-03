<?php
$host = "localhost";
$user = "u449030995_root_aice";
$pass = "Ee9oxLp1&";
$db   = "u449030995_dbaicejumatiga";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8");
