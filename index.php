<?php
require_once 'includes/header.php';

// Giriş yapmış kullanıcılar için birkaç aktif yarışmayı çekelim
$active_competitions = [];
if (is_logged_in()) {
    $result = $conn->query("SELECT * FROM competitions WHERE status = 'active' ORDER BY end_date ASC LIMIT 3");
    while ($row = $result->fetch_assoc()) {
        $active_competitions[] = $row;
    }
}
?>

<div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
    <div class="container-fluid py-5 text-center">
        <?php if (is_logged_in()): ?>
            <h1 class="display-5 fw-bold">Tekrar Hoş Geldin, <?= e($_SESSION['username']) ?>!</h1>
            <p class="fs-4">Yaratıcılığını sergilemeye hazır mısın? Aktif yarışmalara göz at veya yeni bir tane başlat.</p>
            <a href="competitions.php" class="btn btn-primary btn-lg mx-2">Yarışmalara Göz At</a>
            <a href="create_competition.php" class="btn btn-success btn-lg mx-2">Yarışma Başlat</a>
        <?php else: ?>
            <h1 class="display-5 fw-bold">Tasarım Yarışmaları Platformuna Hoş Geldiniz!</h1>
            <p class="fs-4">Yaratıcı projeler için en iyi tasarımcılarla buluşun veya yeteneklerinizi sergileyerek ödüller kazanın.</p>
            <a href="login.php" class="btn btn-primary btn-lg mx-2">Giriş Yap</a>
            <a href="register.php" class="btn btn-secondary btn-lg mx-2">Hemen Kayıt Ol</a>
        <?php endif; ?>
    </div>
</div>

<?php if (is_logged_in() && count($active_competitions) > 0): ?>
    <h2 class="mb-4 text-center">Öne Çıkan Aktif Yarışmalar</h2>
    <div class="row">
        <?php foreach ($active_competitions as $c): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= e($c['title']) ?></h5>
                        <p class="mb-2"><span class="badge bg-info"><?= e($c['category']) ?></span></p>
                        <div class="mt-auto">
                            <h4 class="text-primary my-2"><?= e($c['budget']) ?> TL Ödül</h4>
                            <a href="competition_view.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary w-100 mt-2">Detayları Gör</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php require_once 'includes/footer.php'; ?>