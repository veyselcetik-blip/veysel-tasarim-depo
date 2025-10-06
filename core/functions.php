<?php
// Kullanıcının giriş yapıp yapmadığını kontrol eder
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Sadece giriş yapmış kullanıcıların erişebileceği sayfalar için kullanılır
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

// Sadece adminlerin erişebileceği sayfalar için kullanılır
function require_admin() {
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die("Bu sayfaya erişim yetkiniz yok.");
    }
}

// HTML çıktısını güvenli hale getirmek için kullanılır (XSS saldırılarını önler)
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>