<?php
require_once 'includes/header.php';
require_login();

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Sayfa yüklendiğinde mevcut kullanıcı bilgilerini al
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

// Mevcut yetenekleri, virgülle ayrılmış bir metin olarak al
$skills_result = $conn->query("SELECT skill_name FROM user_skills WHERE user_id = " . $user_id);
$current_skills = [];
if ($skills_result) { while($row = $skills_result->fetch_assoc()) { $current_skills[] = $row['skill_name']; } }
$skills_str = implode(', ', $current_skills);

// Mevcut portfolyo çalışmalarını çek
$portfolio_items = [];
$portfolio_result = $conn->query("SELECT id, title, image_path FROM portfolio_items WHERE user_id = $user_id ORDER BY uploaded_at DESC");
if ($portfolio_result) { while($row = $portfolio_result->fetch_assoc()){ $portfolio_items[] = $row; } }

// Form gönderildiyse...
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Değişiklikleri kaydetmek için mevcut şifreyi doğrula
    if (!password_verify($_POST['current_password'], $user['password'])) {
        $errors[] = "Tüm değişiklikleri kaydetmek için girdiğiniz mevcut şifre yanlış.";
    } else {
        // Formdan gelen verileri al
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $email_visibility = $_POST['email_visibility'];
        $bio = trim($_POST['bio']);
        $skills_input = trim($_POST['skills']);
        $new_password = $_POST['new_password'];

        // Fotoğraf yükleme işlemi
        $profile_image_path = $user['profile_image']; 
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            // ... (Fotoğraf yükleme mantığı) ...
        }

        // Hata yoksa güncelleme sorgusunu hazırla
        if (empty($errors)) {
            $sql = "UPDATE users SET username = ?, email = ?, email_visibility = ?, bio = ?, profile_image = ?";
            $params = [$username, $email, $email_visibility, $bio, $profile_image_path];
            $types = "sssss";
            
            if (!empty($new_password)) {
                if(strlen($new_password) < 6) { $errors[] = "Yeni şifre en az 6 karakter olmalıdır."; } 
                else {
                    $sql .= ", password = ?";
                    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                    $types .= "s";
                }
            }

            if (empty($errors)) {
                $sql .= " WHERE id = ?";
                $params[] = $user_id;
                $types .= "i";
                
                $stmt_update = $conn->prepare($sql);
                $stmt_update->bind_param($types, ...$params);

                if ($stmt_update->execute()) {
                    // Yetenekleri güncelle
                    $conn->query("DELETE FROM user_skills WHERE user_id = " . $user_id);
                    $skills_array = explode(',', $skills_input);
                    if (!empty($skills_input)) {
                        $stmt_skill = $conn->prepare("INSERT INTO user_skills (user_id, skill_name) VALUES (?, ?)");
                        foreach ($skills_array as $skill) {
                            $trimmed_skill = trim($skill);
                            if (!empty($trimmed_skill)) {
                                $stmt_skill->bind_param("is", $user_id, $trimmed_skill);
                                $stmt_skill->execute();
                            }
                        }
                    }

                    $_SESSION['username'] = $username;
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Profiliniz başarıyla güncellendi!'];
                    header("Location: profile.php");
                    exit();
                } else {
                    $errors[] = "Profil güncellenirken bir veritabanı hatası oluştu.";
                }
            }
        }
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header"><h2 class="h4 mb-0">Profili Düzenle</h2></div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>" . e($error) . "</p>"; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3"><label for="username" class="form-label">Kullanıcı Adı</label><input type="text" name="username" id="username" class="form-control" value="<?= e($user['username']) ?>" required></div>
            <div class="mb-3"><label for="email" class="form-label">E-posta</label><input type="email" name="email" id="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
            <div class="mb-3"><label for="email_visibility" class="form-label">E-posta Görünürlüğü</label><select name="email_visibility" id="email_visibility" class="form-select"><option value="registered" <?= ($user['email_visibility'] == 'registered') ? 'selected' : '' ?>>Sadece Kayıtlı Üyeler</option><option value="private" <?= ($user['email_visibility'] == 'private') ? 'selected' : '' ?>>Gizli</option></select></div>
            <div class="mb-3"><label for="bio" class="form-label">Hakkında (Bio)</label><textarea name="bio" id="bio" rows="5" class="form-control"><?= e($user['bio'] ?? '') ?></textarea></div>
            <div class="mb-3"><label for="skills" class="form-label">Yetenekler</label><input type="text" name="skills" id="skills" class="form-control" value="<?= e($skills_str) ?>"><div class="form-text">Yeteneklerinizi virgül (,) ile ayırarak yazın.</div></div>
            <div class="mb-3"><label for="profile_image" class="form-label">Profil Fotoğrafı</label><input type="file" name="profile_image" id="profile_image" class="form-control"></div>
            <hr>
            <div class="mb-3"><label for="new_password" class="form-label">Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label><input type="password" name="new_password" id="new_password" class="form-control"></div>
            <div class="mb-3"><label for="current_password" class="form-label">Değişiklikleri Kaydetmek İçin Mevcut Şifreniz</label><input type="password" name="current_password" id="current_password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
        </form>
    </div>
</div>

<div class="card shadow-sm mt-5">
    <div class="card-header"><h2 class="h4 mb-0">Portfolyo Yönetimi</h2></div>
    <div class="card-body">
        <h5 class="mb-3">Yeni Portfolyo Çalışması Ekle</h5>
        <form action="add_portfolio_item.php" method="POST" enctype="multipart/form-data" class="mb-4 p-3 border rounded">
            <div class="row g-3">
                <div class="col-md-6"><label for="title" class="form-label">Çalışma Başlığı</label><input type="text" name="title" id="title" class="form-control" required></div>
                <div class="col-md-6"><label for="portfolio_image" class="form-label">Resim Dosyası</label><input type="file" name="portfolio_image" id="portfolio_image" class="form-control" required accept="image/*"></div>
                <div class="col-12"><label for="description" class="form-label">Açıklama</small></label><textarea name="description" id="description" class="form-control" rows="2"></textarea></div>
            </div>
            <button type="submit" class="btn btn-success mt-3">Portfolyoya Ekle</button>
        </form>
        <hr>
        <h5 class="mt-4 mb-3">Mevcut Çalışmalarınız</h5>
        <div class="row">
            <?php if(count($portfolio_items) > 0): foreach($portfolio_items as $item): ?>
            <div class="col-md-4 mb-3"><div class="card"><img src="<?= e($item['image_path']) ?>" class="card-img-top" style="height: 150px; object-fit: cover;"><div class="card-body p-2"><p class="card-text fw-bold small"><?= e($item['title']) ?></p></div></div></div>
            <?php endforeach; else: ?>
            <p class="text-muted">Henüz portfolyonuza bir çalışma eklemediniz.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>