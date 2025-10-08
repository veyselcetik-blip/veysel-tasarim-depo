<?php
require_once 'includes/header.php';

// Değişkenleri tanımla
$stats = [];
$winning_submissions = [];
$my_active_competitions = [];
$participated_competitions = [];

if (is_logged_in()) {
    // --- GİRİŞ YAPMIŞ KULLANICI İÇİN VERİ ÇEKME ---
    $user_id = $_SESSION['user_id'];
    
    // Benim başlattığım ve hala aktif olan son 3 yarışma
    $my_comps_result = $conn->query(
        "SELECT id, title, (SELECT COUNT(*) FROM submissions WHERE competition_id = c.id) as sub_count 
         FROM competitions c WHERE user_id = $user_id AND status = 'active' ORDER BY created_at DESC LIMIT 3"
    );
    if($my_comps_result) { while($row = $my_comps_result->fetch_assoc()) { $my_active_competitions[] = $row; } }

    // Katıldığım ve hala aktif olan son 3 yarışma
    $part_comps_result = $conn->query(
        "SELECT c.id, c.title, c.status 
         FROM competitions c JOIN submissions s ON c.id = s.competition_id
         WHERE s.user_id = $user_id AND c.status = 'active' GROUP BY c.id ORDER BY MAX(s.uploaded_at) DESC LIMIT 3"
    );
    if($part_comps_result) { while($row = $part_comps_result->fetch_assoc()) { $participated_competitions[] = $row; } }

} else {
    // --- ZİYARETÇİ İÇİN VERİ ÇEKME ---
    $stats['total_users'] = $conn->query("SELECT COUNT(id) FROM users")->fetch_row()[0];
    $stats['completed_competitions'] = $conn->query("SELECT COUNT(id) FROM competitions WHERE status = 'completed'")->fetch_row()[0];
    $total_budget_raw = $conn->query("SELECT SUM(budget) FROM competitions WHERE status = 'completed'")->fetch_row()[0];
    $stats['total_budget'] = $total_budget_raw ?? 0;

    $winners_result = $conn->query("SELECT s.file_path, c.title as competition_title FROM competitions c JOIN submissions s ON c.winner_submission_id = s.id WHERE c.status = 'completed' ORDER BY c.created_at DESC LIMIT 3");
    if($winners_result) { while($row = $winners_result->fetch_assoc()) { $winning_submissions[] = $row; } }
}
?>

<?php if (is_logged_in()): ?>
    <div class="container">
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
            <div class="container-fluid">
                <h1 class="display-5 fw-bold">Tekrar Hoş Geldin, <?= e($_SESSION['username']) ?>!</h1>
                <p class="col-md-8 fs-4">Aktivitelerini aşağıdan takip edebilir veya yeni bir yarışma başlatabilirsin.</p>
                <a href="dashboard.php" class="btn btn-primary btn-lg">Panelime Git</a>
                <a href="create_competition.php" class="btn btn-success btn-lg">Yeni Yarışma Başlat</a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5><a href="my_competitions.php" class="text-decoration-none">Başlattığım Aktif Yarışmalar</a></h5></div>
                    <div class="list-group list-group-flush">
                        <?php if (count($my_active_competitions) > 0): foreach($my_active_competitions as $c): ?>
                        <a href="competition_view.php?id=<?= $c['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <?= e($c['title']) ?>
                            <span class="badge bg-primary rounded-pill"><?= $c['sub_count'] ?> Sunum</span>
                        </a>
                        <?php endforeach; else: ?>
                        <div class="list-group-item text-muted">Aktif bir yarışmanız yok.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h5>Katıldığım Aktif Yarışmalar</h5></div>
                    <div class="list-group list-group-flush">
                        <?php if (count($participated_competitions) > 0): foreach($participated_competitions as $c): ?>
                        <a href="competition_view.php?id=<?= $c['id'] ?>" class="list-group-item list-group-item-action">
                            <?= e($c['title']) ?>
                        </a>
                        <?php endforeach; else: ?>
                        <div class="list-group-item text-muted">Aktif bir yarışmaya katılmadınız.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="container">
        <div class="p-5 mb-5 text-center bg-white rounded-3 shadow-sm">
            <h1 class="display-4 fw-bold">Harika Fikirler, Harika Tasarımlarla Buluşuyor.</h1>
            <p class="fs-4 lead text-muted">İhtiyacınız olan tasarım için bir yarışma başlatın, onlarca tasarımcıdan yaratıcı çözümler alın ve en iyisini seçin.</p>
            <a href="register.php" class="btn btn-primary btn-lg px-4 me-2">Hemen Ücretsiz Katıl</a>
            <a href="competitions.php" class="btn btn-outline-secondary btn-lg px-4">Yarışmalara Göz At</a>
        </div>
        <div class="row text-center mb-5">
            <div class="col-md-4"><h3><?= $stats['total_users'] ?>+</h3><p class="text-muted">Toplam Tasarımcı</p></div>
            <div class="col-md-4"><h3><?= $stats['completed_competitions'] ?>+</h3><p class="text-muted">Tamamlanan Yarışma</p></div>
            <div class="col-md-4"><h3><?= number_format($stats['total_budget'], 0, ',', '.') ?> TL+</h3><p class="text-muted">Dağıtılan Toplam Ödül</p></div>
        </div>
        <?php if(count($winning_submissions) > 0): ?>
        <h2 class="text-center mb-4">Başarı Hikayeleri: Son Kazananlar</h2>
        <div class="row">
            <?php foreach($winning_submissions as $winner): ?>
            <div class="col-md-4 mb-4"><div class="card h-100"><img src="<?= e($winner['file_path']) ?>" class="card-img-top" style="height: 220px; object-fit: cover;"><div class="card-body"><p class="card-text small text-muted">"<?= e($winner['competition_title']) ?>" yarışmasının kazananı.</p></div></div></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>