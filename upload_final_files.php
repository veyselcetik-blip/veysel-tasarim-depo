<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek.");
}

$competition_id = (int)($_POST['competition_id'] ?? 0);
$user_id = $_SESSION['user_id']; // Dosyayı yükleyen (kazanan) tasarımcı

// Güvenlik Kontrolü: Bu işlemi sadece bu yarışmanın kazananı yapabilir.
$stmt_check = $conn->prepare("SELECT s.user_id FROM competitions c JOIN submissions s ON c.winner_submission_id = s.id WHERE c.id = ? AND c.status = 'in_progress'");
$stmt_check->bind_param("i", $competition_id);
$stmt_check->execute();
$result = $stmt_check->get_result();
$winner_check = $result->fetch_assoc();

if (!$winner_check || $winner_check['user_id'] != $user_id) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Bu işlemi yapma yetkiniz yok.'];
    header("Location: competition_view.php?id=" . $competition_id);
    exit();
}

if (isset($_FILES['final_file']) && $_FILES['final_file']['error'] == 0) {
    $file = $_FILES['final_file'];
    $allowed_mimes = ['application/zip', 'application/x-rar-compressed', 'application/octet-stream'];
    
    if (!in_array($file['type'], $allowed_mimes)) {
         $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Lütfen sadece .zip veya .rar formatında bir dosya yükleyin.'];
    } else {
        $upload_dir = "uploads/final/{$competition_id}/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        $new_filepath = $upload_dir . 'final_' . time() . '_' . basename($file['name']);

        if (move_uploaded_file($file['tmp_name'], $new_filepath)) {
            // final_submissions tablosuna ekle
            $stmt_insert = $conn->prepare("INSERT INTO final_submissions (competition_id, user_id, file_path) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iis", $competition_id, $user_id, $new_filepath);
            $stmt_insert->execute();

            // Yarışmanın durumunu ve onay son tarihini (3 gün sonrası) güncelle
            $delivery_deadline = date('Y-m-d H:i:s', strtotime('+3 days'));
            $conn->query("UPDATE competitions SET status = 'files_delivered', delivery_deadline = '$delivery_deadline' WHERE id = $competition_id");
            
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Final dosyaları başarıyla yüklendi. Müşteri onayı bekleniyor.'];
        }
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Dosya yüklenirken bir hata oluştu. Lütfen tekrar deneyin.'];
}

header("Location: competition_view.php?id=" . $competition_id);
exit();
?>