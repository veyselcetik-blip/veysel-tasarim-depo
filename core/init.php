<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/db.php'; // DB bağlantısını önce yap

// YENİ: TÜM SİTE AYARLARINI ÇEK VE GLOBAL YAP
$settings = [];
$settings_result = $conn->query("SELECT * FROM settings");
if ($settings_result) {
    while($row = $settings_result->fetch_assoc()){
        $settings[$row['setting_name']] = $row['setting_value'];
    }
}

// YENİ: BAKIM MODU KONTROLÜ
if (($settings['maintenance_mode'] ?? '0') == '1') {
    // Eğer bakım modu aktifse ve kullanıcı admin değilse
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        die("<h1>Site şu anda bakımda.</h1><p>Lütfen daha sonra tekrar deneyin.</p>");
    }
}

define('BASE_URL', '/uyelik_platformu/');
require_once __DIR__ . '/functions.php';
?>