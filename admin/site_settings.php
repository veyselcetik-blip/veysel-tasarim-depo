<?php
require_once '../includes/header.php';
// require_admin(); // Gerçek sitede bu satırı açın
require_login(); 

// Form gönderildiyse ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_title = trim($_POST['site_title']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';

    // Ayarları veritabanında güncelle (INSERT ... ON DUPLICATE KEY UPDATE ile tek sorguda)
    $stmt = $conn->prepare("INSERT INTO settings (setting_name, setting_value) VALUES ('site_title', ?), ('maintenance_mode', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param("ss", $site_title, $maintenance_mode);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Site ayarları başarıyla güncellendi.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Ayarlar güncellenirken bir hata oluştu.'];
    }
    
    header("Location: site_settings.php");
    exit;
}

// Mevcut ayarları formda göstermek için çek
$current_settings = [];
$settings_result = $conn->query("SELECT * FROM settings");
if ($settings_result) {
    while($row = $settings_result->fetch_assoc()){
        $current_settings[$row['setting_name']] = $row['setting_value'];
    }
}
?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h4 mb-0">⚙️ Site Ayarları</h2>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">← Panele Dön</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="site_title" class="form-label">Site Başlığı</label>
                <input type="text" name="site_title" id="site_title" class="form-control" value="<?= e($current_settings['site_title'] ?? '') ?>">
                <div class="form-text">Sitenizin tarayıcı sekmesinde ve başlıklarında görünecek isim.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Bakım Modu</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" <?= ($current_settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="maintenance_mode">Aktif (Siteye sadece adminler girebilir)</label>
                </div>
                <div class="form-text">Bu ayarı aktif ettiğinizde, admin dışındaki tüm kullanıcılar "Site bakımda" uyarısı görür.</div>
            </div>
            <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>