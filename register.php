<?php
require_once 'includes/header.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username)) $errors[] = "Kullanıcı adı boş bırakılamaz.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Geçerli bir e-posta adresi girin.";
    if (strlen($password) < 6) $errors[] = "Şifre en az 6 karakter olmalıdır.";
    if ($password !== $password_confirm) $errors[] = "Şifreler eşleşmiyor.";

    if (empty($errors)) {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $errors[] = "Bu kullanıcı adı veya e-posta zaten kayıtlı.";
        }
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            // Kayıttan sonra otomatik giriş yap
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            
            // Kayıt tarihini veritabanından çekip session'a ekle
            $user_created_at_result = $conn->query("SELECT created_at FROM users WHERE id = $user_id");
            $_SESSION['user_created_at'] = $user_created_at_result->fetch_assoc()['created_at'];

            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Kayıt sırasında bir veritabanı hatası oluştu.";
        }
    }
}
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <h2 class="card-title text-center mb-4">Hesap Oluştur</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error) echo "<p class='mb-0'>" . e($error) . "</p>"; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="register.php">
                    <div class="mb-3"><label for="username" class="form-label">Kullanıcı Adı</label><input type="text" name="username" id="username" class="form-control" required></div>
                    <div class="mb-3"><label for="email" class="form-label">E-posta</label><input type="email" name="email" id="email" class="form-control" required></div>
                    <div class="mb-3"><label for="password" class="form-label">Şifre</label><input type="password" name="password" id="password" class="form-control" required></div>
                    <div class="mb-3"><label for="password_confirm" class="form-label">Şifre Tekrar</label><input type="password" name="password_confirm" id="password_confirm" class="form-control" required></div>
                    <button type="submit" class="btn btn-primary w-100">Hesap Oluştur</button>
                </form>
                 <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none"><small>Zaten bir hesabın var mı? Giriş Yap</small></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>