<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Geçersiz istek.");

$competition_id = (int)($_POST['competition_id'] ?? 0);
$reviewee_id = (int)($_POST['reviewee_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$reviewer_id = $_SESSION['user_id'];

if ($competition_id <= 0 || $reviewee_id <= 0 || $rating < 1 || $rating > 5) {
    die("Eksik veya geçersiz veri.");
}

// DÜZELTME: Kazananın ID'sini (freelancer_id) de sorguya ekliyoruz
$stmt_comp = $conn->prepare("SELECT c.user_id, s.user_id as freelancer_id, c.status FROM competitions c JOIN submissions s ON c.winner_submission_id = s.id WHERE c.id = ?");
$stmt_comp->bind_param("i", $competition_id);
$stmt_comp->execute();
$competition = $stmt_comp->get_result()->fetch_assoc();

$is_owner = ($competition['user_id'] == $reviewer_id);
$is_winner = ($competition['freelancer_id'] == $reviewer_id);
$can_review = ($is_owner && $reviewee_id == $competition['freelancer_id']) || ($is_winner && $reviewee_id == $competition['user_id']);

if (!$competition || $competition['status'] !== 'completed' || !$can_review) {
    die("Bu değerlendirmeyi yapma yetkiniz yok.");
}

$stmt_insert = $conn->prepare(
    "INSERT INTO reviews (competition_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)"
);
$stmt_insert->bind_param("iiiis", $competition_id, $reviewer_id, $reviewee_id, $rating, $comment);

if ($stmt_insert->execute()) {
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Değerlendirmeniz için teşekkür ederiz!'];
} else {
    $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Bu kişiyi bu yarışma için zaten değerlendirdiniz.'];
}

header("Location: competition_view.php?id=" . $competition_id);
exit();
?>