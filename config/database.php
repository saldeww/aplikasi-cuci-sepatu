
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_cuci_sepatu";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Mengatur charset
$conn->set_charset("utf8mb4");
