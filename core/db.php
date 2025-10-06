<?php
// Veritabanı bağlantı bilgileri
$host = "localhost";
$username = "root";
$password = "";
$dbname = "uyelik_platformu"; // Lütfen kendi veritabanı adınızla değiştirin

// Bağlantıyı oluştur
$conn = new mysqli($host, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
  die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Karakter setini ayarla
$conn->set_charset("utf8mb4");
?>