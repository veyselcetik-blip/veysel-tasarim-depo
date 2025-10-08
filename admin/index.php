<?php
require_once '../includes/header.php';
// require_admin(); // Gerçek bir sitede bu satırı açarak sadece adminlerin erişmesini sağlayın
require_login(); // Şimdilik test için sadece giriş yapmış olmak yeterli

// Temel istatistikleri çek
$total_users = $conn->query("SELECT COUNT(id) AS count FROM users")->fetch_assoc()['count'];
$total_competitions = $conn->query("SELECT COUNT(id) AS count FROM competitions")->fetch_assoc()['count'];
$total_submissions = $conn->query("SELECT COUNT(id) AS count FROM submissions")->fetch_assoc()['count'];
$active_competitions = $conn->query("SELECT COUNT(id) AS count FROM competitions WHERE status = 'active'")->fetch_assoc()['count'];
?>

<div class="container mt-4">
    <h1 class="display-5 mb-4">Admin Paneli</h1>

    <div class="row">
        <div class="col-md-3 mb-4"><div class="card text-center h-100"><div class="card-body"><h3 class="card-title"><?= $total_users ?></h3><p class="card-text text-muted">Toplam Kullanıcı</p></div></div></div>
        <div class="col-md-3 mb-4"><div class="card text-center h-100"><div class="card-body"><h3 class="card-title"><?= $total_competitions ?></h3><p class="card-text text-muted">Toplam Yarışma</p></div></div></div>
        <div class="col-md-3 mb-4"><div class="card text-center h-100"><div class="card-body"><h3 class="card-title"><?= $active_competitions ?></h3><p class="card-text text-muted">Aktif Yarışma</p></div></div></div>
        <div class="col-md-3 mb-4"><div class="card text-center h-100"><div class="card-body"><h3 class="card-title"><?= $total_submissions ?></h3><p class="card-text text-muted">Toplam Sunum</p></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header"><h2 class="h5 mb-0">Yönetim Araçları</h2></div>
        <div class="list-group list-group-flush">
          <a href="manage_users.php" class="list-group-item list-group-item-action">Kullanıcıları Yönet</a>
          <a href="manage_competitions.php" class="list-group-item list-group-item-action">Yarışmaları Yönet</a>
          <a href="site_settings.php" class="list-group-item list-group-item-action">Site Ayarları</a>
          <a href="#" class="list-group-item list-group-item-action disabled">Anlaşmazlık Çözüm Merkezi (Yakında)</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>