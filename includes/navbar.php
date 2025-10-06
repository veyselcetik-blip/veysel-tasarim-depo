<?php
// ... (bildirimleri çeken kodlar burada aynı kalıyor) ...
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">🎨 Tasarım Yarışmaları</a>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item"><a class="nav-link" href="index.php">Anasayfa</a></li>
        <li class="nav-item"><a class="nav-link" href="competitions.php">Yarışmalar</a></li> 
        
        <?php if (is_logged_in()): ?>
          <li class="nav-item ms-2"><a class="nav-link btn btn-success text-white" href="create_competition.php">Yarışma Başlat</a></li> 

          <li class="nav-item dropdown ms-2">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= e($_SESSION['username'] ?? 'Kullanıcı') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="dashboard.php">Panelim</a></li>
              <li><a class="dropdown-item" href="my_competitions.php">Yayınladığım Yarışmalar</a></li>
              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="admin/index.php">Admin Panel</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php">Çıkış Yap</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
          <li class="nav-item"><a class="nav-link btn btn-primary ms-2 text-white" href="register.php">Kayıt Ol</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>