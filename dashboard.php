<?php
require_once 'includes/header.php';
require_login();

$user_id = $_SESSION['user_id'];

// İstatistikleri çek
$my_competitions_count = $conn->query("SELECT COUNT(id) as count FROM competitions WHERE user_id = $user_id")->fetch_assoc()['count'];
$my_submissions_count = $conn->query("SELECT COUNT(DISTINCT competition_id) as count FROM submissions WHERE user_id = $user_id")->fetch_assoc()['count'];
$wins_count = $conn->query("SELECT COUNT(c.id) as count FROM competitions c JOIN submissions s ON c.winner_submission_id = s.id WHERE s.user_id = $user_id")->fetch_assoc()['count'];

// Son 3 aktif yarışmam
$my_active_competitions = $conn->query("SELECT id, title, status FROM competitions WHERE user_id = $user_id AND status = 'active' ORDER BY created_at DESC LIMIT 3");

// Katıldığım son 3 yarışma
$participated_competitions = $conn->query(
    "SELECT c.id, c.title, c.status 
     FROM competitions c JOIN submissions s ON c.id = s.competition_id
     WHERE s.user_id = $user_id GROUP BY c.id ORDER BY MAX(s.uploaded_at) DESC LIMIT 3"
);

// Son 3 okunmamış bildirim
$unread_notifications = $conn->query("SELECT message, link FROM notifications WHERE user_id = $user_id AND is_read = 0 ORDER BY created_at DESC LIMIT 3");
?>

<div class="mb-4">
    <h1 class="display-5">Panelim</h1>
    <p class="lead">Hoş geldin <?= e($_SESSION['username']) ?>! Platformdaki aktivitelerini buradan yönetebilirsin.</p>
</div>

<div class="row">
    <div class="col-md-4 mb-4"><div class="card text-center h-100"><div class="card-body"><h3 class="card-title"><?= $my_competitions_count ?></h3><p class="card-text text-muted">Başlattığın Yarışma</p></div></div></div>
    <div class="col-md-4 mb-4"><div class="card text-center h-100"><div class="card-body"><h3 class="card-title"><?= $my_submissions_count ?></h3><p class="card-text text-muted">Katıldığın Yarışma</p></div></div></div>
    <div class="col-md-4 mb-4"><div class="card text-center h-100 bg-success text-white"><div class="card-body"><h3 class="card-title">🏆 <?= $wins_count ?></h3><p class="card-text">Kazandığın Yarışma</p></div></div></div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5>Aktif Yarışmalarım</h5></div>
            <div class="list-group list-group-flush">
                <?php if ($my_active_competitions->num_rows > 0): while($c = $my_active_competitions->fetch_assoc()): ?>
                <a href="competition_view.php?id=<?= $c['id'] ?>" class="list-group-item list-group-item-action"><?= e($c['title']) ?></a>
                <?php endwhile; else: ?>
                <div class="list-group-item">Aktif bir yarışmanız yok.</div>
                <?php endif; ?>
                 <a href="my_competitions.php" class="list-group-item list-group-item-action text-center text-primary">Tümünü Gör...</a>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5>Katıldığım Son Yarışmalar</h5></div>
            <div class="list-group list-group-flush">
                <?php if ($participated_competitions->num_rows > 0): while($c = $participated_competitions->fetch_assoc()): ?>
                <a href="competition_view.php?id=<?= $c['id'] ?>" class="list-group-item list-group-item-action"><?= e($c['title']) ?></a>
                <?php endwhile; else: ?>
                <div class="list-group-item">Henüz bir yarışmaya katılmadınız.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5>Hızlı Erişim</h5></div>
            <div class="list-group list-group-flush">
                <a href="profile.php" class="list-group-item list-group-item-action">Herkese Açık Profilimi Görüntüle</a>
                <a href="edit_profile.php" class="list-group-item list-group-item-action">Profilimi Düzenle</a>
                <a href="messages.php" class="list-group-item list-group-item-action">Mesajlarım</a>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h5>Son Bildirimler</h5></div>
            <div class="list-group list-group-flush">
                 <?php if ($unread_notifications->num_rows > 0): while($n = $unread_notifications->fetch_assoc()): ?>
                <a href="<?= e($n['link']) ?>" class="list-group-item list-group-item-action"><small><?= $n['message'] ?></small></a>
                <?php endwhile; else: ?>
                <div class="list-group-item text-muted">Okunmamış bildiriminiz yok.</div>
                <?php endif; ?>
                <a href="notifications.php" class="list-group-item list-group-item-action text-center text-primary">Tüm Bildirimler...</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>