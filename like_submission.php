<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Geçersiz istek.");

$submission_id = (int)($_POST['submission_id'] ?? 0);
$competition_id = (int)($_POST['competition_id'] ?? 0); // Geri yönlendirme için
$user_id = $_SESSION['user_id'];

if ($submission_id <= 0 || $competition_id <= 0) {
    die("Eksik veya geçersiz veri.");
}

// Kullanıcı bu sunumu daha önce beğenmiş mi?
$stmt_check = $conn->prepare("SELECT id FROM submission_likes WHERE user_id = ? AND submission_id = ?");
$stmt_check->bind_param("ii", $user_id, $submission_id);
$stmt_check->execute();
$like = $stmt_check->get_result()->fetch_assoc();

if ($like) {
    // Eğer zaten beğenmişse, beğeniyi geri al (sil)
    $stmt_delete = $conn->prepare("DELETE FROM submission_likes WHERE id = ?");
    $stmt_delete->bind_param("i", $like['id']);
    $stmt_delete->execute();
} else {
    // Eğer beğenmemişse, yeni bir beğeni ekle
    $stmt_insert = $conn->prepare("INSERT INTO submission_likes (user_id, submission_id) VALUES (?, ?)");
    $stmt_insert->bind_param("ii", $user_id, $submission_id);
    $stmt_insert->execute();

    // (İsteğe bağlı) Tasarımcıya bildirim gönder
    // ...
}

// Kullanıcıyı yarışma sayfasına, ilgili sunumun olduğu yere geri yönlendir
header("Location: competition_view.php?id=" . $competition_id . "#submission-" . $submission_id);
exit();
?>