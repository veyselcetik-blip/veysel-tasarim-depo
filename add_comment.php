<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Geçersiz istek.");

$submission_id = (int)($_POST['submission_id'] ?? 0);
$competition_id = (int)($_POST['competition_id'] ?? 0);
$comment_text = trim($_POST['comment_text'] ?? '');
$user_id = $_SESSION['user_id'];

if ($submission_id <= 0 || $competition_id <= 0 || empty($comment_text)) {
    die("Eksik veya geçersiz veri.");
}

// Güvenlik: Sadece yarışma sahibi yorum yapabilir
$stmt_comp = $conn->prepare("SELECT user_id FROM competitions WHERE id = ? AND user_id = ?");
$stmt_comp->bind_param("ii", $competition_id, $user_id);
$stmt_comp->execute();
if ($stmt_comp->get_result()->num_rows === 0) {
    die("Bu sunuma yorum yapma yetkiniz yok.");
}

// Yorumu veritabanına ekle
$stmt_insert = $conn->prepare(
    "INSERT INTO submission_comments (submission_id, user_id, comment_text) VALUES (?, ?, ?)"
);
$stmt_insert->bind_param("iis", $submission_id, $user_id, $comment_text);

if ($stmt_insert->execute()) {
    // Yorum yapılan sunumun sahibine (tasarımcıya) bildirim gönder
    $stmt_sub_owner = $conn->prepare("SELECT user_id FROM submissions WHERE id = ?");
    $stmt_sub_owner->bind_param("i", $submission_id);
    $stmt_sub_owner->execute();
    $designer_id = $stmt_sub_owner->get_result()->fetch_assoc()['user_id'];

    if ($designer_id != $user_id) { // Kendine bildirim göndermesin
        $notification_message = "<b>" . e($_SESSION['username']) . "</b> bir sunumunuza yorum yaptı.";
        $notification_link = "competition_view.php?id=" . $competition_id;
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $stmt_notify->bind_param("iss", $designer_id, $notification_message, $notification_link);
        $stmt_notify->execute();
    }
}

// Kullanıcıyı yarışma sayfasına geri yönlendir
header("Location: competition_view.php?id=" . $competition_id);
exit();
?>