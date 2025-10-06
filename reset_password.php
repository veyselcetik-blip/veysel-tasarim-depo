<?php
require_once 'includes/header.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = 'info';
$show_form = false;

if (empty($token)) {
    $message = "Geçersiz sıfırlama linki.";
    $message_type = 'danger';
} else {
    // Token'ı ve geçerlilik süresini kontrol et
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $show_form = true; // Token geçerli, formu göster
    } else {
        $message = "Bu şifre sıfırlama linki geçersiz veya süresi dolmuş. Lütfen yeni bir talep gönderin.";
        $message_type = 'danger';
    }
}

if ($show_form && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (strlen($password) < 6) {
        $message = "Şifre en az 6 karakter olmalıdır.";
        $message_type = 'danger';
    } elseif ($password !== $password_confirm) {
        $message = "Şifreler eşleşmiyor.";
        $message_type = 'danger';
    } else {
        // Şifreyi güncelle ve token'ı temizle
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $hashed_password, $token);
        $stmt->execute();

        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Şifreniz başarıyla güncellendi! Şimdi giriş yapabilirsiniz.'];
        header("Location: login.php");
        exit();
    }
}

?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center">Yeni Şifre Belirle</h2>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                <?php endif; ?>

                <?php if ($show_form): ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label">Yeni Şifre</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Yeni Şifre (Tekrar)</label>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Şifreyi Güncelle</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>