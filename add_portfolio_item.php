<?php
require_once 'core/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (empty($title)) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Portfolyo çalışması için bir başlık girmelisiniz.'];
        header('Location: edit_profile.php');
        exit();
    }

    if (isset($_FILES['portfolio_image']) && $_FILES['portfolio_image']['error'] == 0) {
        $file = $_FILES['portfolio_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Sadece JPG, PNG veya GIF formatında resim yükleyebilirsiniz.'];
        } else {
            $upload_dir = "uploads/portfolio/{$user_id}/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
            $new_filepath = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $new_filepath)) {
                $stmt_insert = $conn->prepare(
                    "INSERT INTO portfolio_items (user_id, title, description, image_path) VALUES (?, ?, ?, ?)"
                );
                $stmt_insert->bind_param("isss", $user_id, $title, $description, $new_filepath);
                
                if ($stmt_insert->execute()) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Yeni portfolyo çalışmanız başarıyla eklendi!'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Portfolyo kaydedilirken bir hata oluştu.'];
                }
            }
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Lütfen bir portfolyo resmi seçin.'];
    }
}

header('Location: edit_profile.php');
exit();
?>