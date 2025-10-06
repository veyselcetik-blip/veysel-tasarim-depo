<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek.");
}

$competition_id = (int)($_POST['competition_id'] ?? 0);
$user_id = $_SESSION['user_id']; // Onaylayan (yarışma sahibi)

// Güvenlik Kontrolü: Bu işlemi sadece yarışma sahibi yapabilir ve dosyalar teslim edilmiş olmalıdır.
$stmt_check = $conn->prepare("SELECT user_id FROM competitions WHERE id = ? AND user_id = ? AND status = 'files_delivered'");
$stmt_check->bind_param("ii", $competition_id, $user_id);
$stmt_check->execute();

if ($stmt_check->get_result()->num_rows > 0) {
    // Yarışmanın durumunu 'completed' (tamamlandı) yap
    $conn->query("UPDATE competitions SET status = 'completed' WHERE id = $competition_id");
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Yarışma başarıyla tamamlandı! Şimdi tarafları değerlendirebilirsiniz.'];
} else {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Bu işlemi yapma yetkiniz yok veya dosyalar henüz teslim edilmedi.'];
}
    
header("Location: competition_view.php?id=" . $competition_id);
exit();
?>