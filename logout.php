<?php
// Herhangi bir HTML veya boşluktan önce oturumu başlat
session_start();

// Tüm oturum değişkenlerini sıfırla
$_SESSION = array();

// Oturumu sonlandır
session_destroy();

// Oturum çerezini de temizle (en güvenli yöntem)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Her şey temizlendikten sonra giriş sayfasına yönlendir
header("Location: login.php");
exit();
?>