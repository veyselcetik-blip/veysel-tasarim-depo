<?php
require_once 'includes/header.php';

$message = '';
$message_type = 'info';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Lütfen geçerli bir e-posta adresi girin.";
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Güvenli bir sıfırlama anahtarı (token) oluştur
            $token = bin2hex(random_bytes(32));
            $expires = new DateTime('now +1 hour'); // 1 saat geçerli
            $expires_str = $expires->format('Y-m-d H:i:s');

            // Token'ı veritabanına kaydet
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->bind_param("sss", $token, $expires_str, $email);
            $stmt->execute();

            // E-posta gönderme simülasyonu
            $reset_link = "http://localhost/uyelik_platformu/reset_password.php?token=" . $token;

            $message = "Şifre sıfırlama talebiniz alındı. Normalde, aşağıdaki link size e-posta olarak gönderilecekti. Lütfen bu linke tıklayarak şifrenizi sıfırlayın (Bu mesaj sadece test amaçlıdır):<br><a href='{$reset_link}'>{$reset_link}</a>";
            $message_type = 'success';

        } else {
            $message = "Bu e-posta adresi sistemde kayıtlı değil.";
            $message_type = 'warning';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center">Şifremi Unuttum</h2>
                <p class="text-center text-muted">Kayıtlı e-posta adresinizi girin, size şifrenizi sıfırlamanız için bir link göndereceğiz.</p>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta Adresiniz</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Sıfırlama Linki Gönder</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>