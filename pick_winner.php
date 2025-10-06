<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { die("Geçersiz istek metodu."); }

$submission_id = (int)($_POST['submission_id'] ?? 0);
$competition_id = (int)($_POST['competition_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Güvenlik Kontrolü: Bu işlemi sadece yarışmanın sahibi yapabilir.
$stmt = $conn->prepare("SELECT user_id, title FROM competitions WHERE id = ? AND user_id = ? AND status = 'active'");
$stmt->bind_param("ii", $competition_id, $user_id);
$stmt->execute();
$competition_result = $stmt->get_result();

if ($competition_result->num_rows === 0) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Bu işlemi yapma yetkiniz yok veya yarışma artık aktif değil.'];
    header("Location: competition_view.php?id=" . $competition_id);
    exit();
}
$competition = $competition_result->fetch_assoc();

// Kazanan sunumu gönderen tasarımcının ID'sini al
$stmt_sub = $conn->prepare("SELECT user_id FROM submissions WHERE id = ? AND competition_id = ?");
$stmt_sub->bind_param("ii", $submission_id, $competition_id);
$stmt_sub->execute();
$submission = $stmt_sub->get_result()->fetch_assoc();

if ($submission) {
    $winner_id = $submission['user_id'];

    // !!! EN ÖNEMLİ DÜZELTME BURADA !!!
    // Yarışmanın durumunu 'completed' yerine 'in_progress' yapıyoruz.
    // Bu, dosya teslim sürecini BAŞLATACAK olan durumdur.
    $stmt_update = $conn->prepare("UPDATE competitions SET winner_submission_id = ?, status = 'in_progress' WHERE id = ?");
    $stmt_update->bind_param("ii", $submission_id, $competition_id);

    if ($stmt_update->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Yarışma kazananı başarıyla seçildi! Şimdi dosya teslim süreci başladı.'];

        // Kazanan tasarımcıya bildirim gönder
        $notification_message = "Tebrikler! <b>'" . e($competition['title']) . "'</b> adlı yarışmayı kazandınız. Lütfen final dosyalarını yükleyin.";
        $notification_link = "competition_view.php?id=" . $competition_id;
        
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $stmt_notify->bind_param("iss", $winner_id, $notification_message, $notification_link);
        $stmt_notify->execute();
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Kazanan seçilirken bir veritabanı hatası oluştu.'];
    }
}

header("Location: competition_view.php?id=" . $competition_id);
exit();
?>