<?php
require_once '../includes/header.php';
require_admin();

// Temel istatistikler
$total_users = $conn->query("SELECT COUNT(id) AS count FROM users")->fetch_assoc()['count'];
$total_competitions = $conn->query("SELECT COUNT(id) AS count FROM competitions")->fetch_assoc()['count'];
$total_submissions = $conn->query("SELECT COUNT(id) AS count FROM submissions")->fetch_assoc()['count'];

?>

<h1 class="mb-4">Admin Paneli</h1>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Toplam Kullanıcı</h5>
                <p class="card-text fs-2"><?= $total_users ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Toplam Yarışma</h5>
                <p class="card-text fs-2"><?= $total_competitions ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <h5 class="card-title">Toplam Gönderi</h5>
                <p class="card-text fs-2"><?= $total_submissions ?></p>
            </div>
        </div>
    </div>
</div>

<div class="list-group">
  <a href="manage_competitions.php" class="list-group-item list-group-item-action">Yarışmaları Yönet</a>
  <a href="#" class="list-group-item list-group-item-action disabled">Kullanıcıları Yönet (Yakında)</a>
  <a href="#" class="list-group-item list-group-item-action disabled">Site Ayarları (Yakında)</a>
</div>


<?php require_once '../includes/footer.php'; ?>