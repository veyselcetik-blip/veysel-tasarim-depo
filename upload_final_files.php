<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Geçersiz istek.");

$competition_id = (int)$_POST['competition_id'];
$user_id = $_SESSION['user_id'];

// Güvenlik kontrolleri...

if (isset($_FILES['final_file']) && $_FILES['final_file']['error'] == 0) {
    $file = $_FILES['final_file'];
    $filename = $file['name'];
    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_extensions = ['zip', 'rar'];

    if (!in_array($file_extension, $allowed_extensions)) {
         $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Lütfen sadece .zip veya .rar uzantılı bir dosya yükleyin.'];
    } else {
        $upload_dir = "uploads/final/{$competition_id}/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        $new_filepath = $upload_dir . 'final_' . time() . '.' . $file_extension;

        if (move_uploaded_file($file['tmp_name'], $new_filepath)) {
            $stmt_insert = $conn->prepare("INSERT INTO final_submissions (competition_id, user_id, file_path) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iis", $competition_id, $user_id, $new_filepath);
            $stmt_insert->execute();

            // DURUMU 'files_delivered' OLARAK GÜNCELLE
            $delivery_deadline = date('Y-m-d H:i:s', strtotime('+3 days'));
            $conn->query("UPDATE competitions SET status = 'files_delivered', delivery_deadline = '$delivery_deadline' WHERE id = $competition_id");
            
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Final dosyaları başarıyla yüklendi. Müşteri onayı bekleniyor.'];
        }
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Dosya yüklenirken bir hata oluştu.'];
}
header("Location: competition_view.php?id=" . $competition_id);
exit();
?>