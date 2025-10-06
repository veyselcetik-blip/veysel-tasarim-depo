<?php
// Geliştirme ortamı için tüm hataları göstermeye zorla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Güvenli oturum ayarları
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Lax'
]);

// Veritabanı bağlantısını ve fonksiyonları dahil et
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
?>