<?php
require_once 'core/init.php';
require_login();

$notification_id = (int)($_GET['notif_id'] ?? 0);
$redirect_to = $_GET['redirect_to'] ?? 'index.php';
$user_id = $_SESSION['user_id'];

if ($notification_id > 0) {
    // Güvenlik kontrolü: Kullanıcının sadece kendi bildirimini okuyabildiğinden emin ol
    $stmt = $conn->prepare(
        "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
}

// URL'nin geçerli ve güvenli olduğundan emin ol (isteğe bağlı ama önerilir)
// Basit bir kontrol: Sadece kendi sitenizdeki linklere yönlendirme yapın.
if (filter_var($redirect_to, FILTER_VALIDATE_URL) && parse_url($redirect_to, PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
    $redirect_to = 'index.php'; // Güvenli değilse anasayfaya yönlendir
}


// Kullanıcıyı gitmek istediği asıl sayfaya yönlendir
header("Location: " . $redirect_to);
exit();
?>