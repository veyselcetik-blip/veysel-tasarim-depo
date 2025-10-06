<?php
require_once 'includes/header.php';
require_login();

// Formdan veya URL'den yarışma ID'sini güvenli bir şekilde al
$competition_id = (int)($_GET['competition_id'] ?? $_POST['competition_id'] ?? 0);
$errors = [];

// Yarışma ID'si geçerli değilse, işlemi en başta durdur
if ($competition_id <= 0) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Geçersiz bir yarışmaya katılmaya çalıştınız.'];
    header('Location: competitions.php');
    exit();
}

// Yarışma bilgilerini çek
$stmt_comp = $conn->prepare("SELECT user_id, title, created_at FROM competitions WHERE id = ? AND status = 'active'");
$stmt_comp->bind_param("i", $competition_id);
$stmt_comp->execute();
$competition = $stmt_comp->get_result()->fetch_assoc();

// Eğer yarışma bulunamazsa veya aktif değilse, kullanıcıyı bilgilendir
if (!$competition) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Bu yarışma artık aktif değil veya bulunamadı.'];
    header('Location: competitions.php');
    exit();
}

// KURAL 1: Kendi yarışmana katılamazsın.
if ($competition['user_id'] == $_SESSION['user_id']) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Kendi başlattığınız bir yarışmaya katılamazsınız.'];
    header("Location: competition_view.php?id=" . $competition_id);
    exit();
}

// KURAL 2: Yarışma başladıktan sonra üye olanlar katılamaz (Hile Engelleme).
if (!isset($_SESSION['user_created_at']) || strtotime($_SESSION['user_created_at']) > strtotime($competition['created_at'])) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Bu yarışma siz üye olmadan önce başlatıldığı için katılamazsınız.'];
    header("Location: competition_view.php?id=" . $competition_id);
    exit();
}

// Eğer form gönderildiyse...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
        $file = $_FILES['submission_file'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Sadece JPG, PNG veya GIF formatında resim yükleyebilirsiniz.";
        }
        // Diğer dosya kontrolleri (boyut vb.) buraya eklenebilir.

        if (empty($errors)) {
            $upload_dir = "uploads/submissions/{$competition_id}/";
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            
            $new_filepath = $upload_dir . time() . '_' . basename($file['name']);

            if (move_uploaded_file($file['tmp_name'], $new_filepath)) {
                $stmt_insert = $conn->prepare("INSERT INTO submissions (user_id, competition_id, file_path, comment) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("iiss", $user_id, $competition_id, $new_filepath, $comment);
                
                if ($stmt_insert->execute()) {
                    // Yarışma sahibine bildirim gönder
                    $notification_message = "<b>" . e($_SESSION['username']) . "</b>, '" . e($competition['title']) . "' yarışmanız için yeni bir sunum gönderdi.";
                    $notification_link = "competition_view.php?id=" . $competition_id;
                    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                    $stmt_notify->bind_param("iss", $competition['user_id'], $notification_message, $notification_link);
                    $stmt_notify->execute();

                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Sunumunuz başarıyla yüklendi!'];
                    header("Location: competition_view.php?id=" . $competition_id);
                    exit();
                } else { $errors[] = "Sunum kaydedilirken bir veritabanı hatası oluştu."; }
            } else { $errors[] = "Dosya yüklenirken bir sunucu hatası oluştu."; }
        }
    } else { $errors[] = "Lütfen bir tasarım dosyası seçin."; }
}
?>

<div class="card shadow-sm">
    <div class="card-header"><h2 class="h4 mb-0">'<?= e($competition['title']) ?>' için Sunum Yükle</h2></div>
    <div class="card-body">
         <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>" . e($error) . "</p>"; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="submission_file" class="form-label">Tasarım Dosyanız (Sadece Resim)</label>
                <input type="file" name="submission_file" id="submission_file" class="form-control" required accept="image/jpeg,image/png,image/gif">
            </div>
            <div class="mb-3">
                <label for="comment" class="form-label">Tasarım Hakkında Yorum (İsteğe Bağlı)</label>
                <textarea name="comment" id="comment" class="form-control" rows="3" placeholder="Tasarımınızı anlatan kısa bir not ekleyebilirsiniz..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Sunumu Gönder</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>