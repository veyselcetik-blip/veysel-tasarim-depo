<?php
require_once 'includes/header.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Kullanıcı adı ve şifre boş bırakılamaz.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role, created_at FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_created_at'] = $user['created_at']; 
                
                header("Location: index.php");
                exit();
            } else {
                $error = "Kullanıcı adı veya şifre hatalı.";
            }
        } else {
            $error = "Kullanıcı adı veya şifre hatalı.";
        }
        $stmt->close();
    }
}
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <h2 class="card-title text-center mb-4">Giriş Yap</h2>
                
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-success"><?= e($_SESSION['flash_message']['text']) ?></div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="text-decoration-none"><small>Şifremi Unuttum</small></a> | 
                    <a href="register.php" class="text-decoration-none"><small>Hesabın yok mu? Kayıt Ol</small></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>