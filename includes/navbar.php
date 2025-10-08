<?php
$unread_notifications = 0;
$notifications = [];
if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    // ... (bildirimleri çeken kodlar aynı kalıyor) ...
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>index.php">🎨 Tasarım Yarışmaları</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php">Anasayfa</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>competitions.php">Yarışmalar</a></li> 
        
        <?php if (is_logged_in()): ?>
          <li class="nav-item ms-2"><a class="nav-link btn btn-success text-white" href="<?= BASE_URL ?>create_competition.php">Yarışma Başlat</a></li> 
          
          <li class="nav-item dropdown ms-2">
            <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown"><span class="position-relative">🔔<?php if ($unread_notifications > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unread_notifications ?></span><?php endif; ?></span></a>
            <ul class="dropdown-menu dropdown-menu-end" style="width: 350px;">
              <?php if (count($notifications) > 0): foreach ($notifications as $notif): ?>
              <li><a class="dropdown-item p-2" href="<?= BASE_URL . 'read_notification.php?notif_id=' . $notif['id'] . '&redirect_to=' . urlencode(BASE_URL . $notif['link']) ?>" style="white-space: normal; <?= $notif['is_read'] == 0 ? 'font-weight: bold;' : '' ?>"><small><?= $notif['message'] ?></small></a></li>
              <?php endforeach; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-center" href="<?= BASE_URL ?>notifications.php">Tüm bildirimleri gör</a></li>
              <?php else: ?><li><p class="dropdown-item text-center text-muted p-3">Hiç bildiriminiz yok.</p></li><?php endif; ?>
            </ul>
          </li>

          <li class="nav-item dropdown ms-2">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown"><?= e($_SESSION['username'] ?? 'Kullanıcı') ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php">Profilim</a></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>my_competitions.php">Yayınladığım Yarışmalar</a></li>
              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="<?= BASE_URL ?>admin/index.php">Admin Panel</a></li><?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= BASE_URL ?>logout.php">Çıkış Yap</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>login.php">Giriş</a></li>
          <li class="nav-item"><a class="nav-link btn btn-primary ms-2 text-white" href="<?= BASE_URL ?>register.php">Kayıt Ol</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>