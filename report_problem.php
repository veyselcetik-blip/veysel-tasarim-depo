<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek.");
}

$competition_id = (int)($_POST['competition_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$reporter_id = $_SESSION['user_id'];

if ($competition_id <= 0 || empty($reason)) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Sorunu bildirmek için bir neden belirtmelisiniz.'];
    header("Location: competition_view.php?id=" . $competition_id);
    exit();
}

// Güvenlik: Sadece yarışma sahibi, dosyalar teslim edildikten sonra sorun bildirebilir
$stmt_check = $conn->prepare("SELECT user_id FROM competitions WHERE id = ? AND user_id = ? AND status = 'files_delivered'");
$stmt_check->bind_param("ii", $competition_id, $reporter_id);
$stmt_check->execute();

if ($stmt_check->get_result()->num_rows > 0) {
    // 1. Yarışmanın durumunu 'disputed' (anlaşmazlık var) olarak güncelle
    $conn->query("UPDATE competitions SET status = 'disputed' WHERE id = $competition_id");

    // 2. 'disputes' tablosuna yeni bir kayıt ekle
    $stmt_insert = $conn->prepare("INSERT INTO disputes (competition_id, reporter_id, reason) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("iis", $competition_id, $reporter_id, $reason);
    $stmt_insert->execute();

    $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Sorun bildiriminiz alındı. Adminler en kısa sürede durumu inceleyecektir.'];
    
} else {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Bu işlemi yapma yetkiniz yok veya yarışma doğru durumda değil.'];
}

header("Location: competition_view.php?id=" . $competition_id);
exit();
?>