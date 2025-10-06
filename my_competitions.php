<?php
require_once 'includes/header.php';
require_login();

$user_id = $_SESSION['user_id'];

// Kullanıcının başlattığı yarışmaları, her birine gelen sunum sayısıyla birlikte çek
$stmt = $conn->prepare(
    "SELECT 
        c.id, c.title, c.status, c.created_at,
        (SELECT COUNT(*) FROM submissions s WHERE s.competition_id = c.id) AS submission_count
     FROM competitions c
     WHERE c.user_id = ?
     ORDER BY c.created_at DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$competitions = $stmt->get_result();
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h1 class="h4 mb-0">🗒️ Başlattığım Yarışmalar</h1>
    </div>
    <div class="card-body">
        <div class="list-group list-group-flush">
            <?php if ($competitions->num_rows > 0): ?>
                <?php while ($comp = $competitions->fetch_assoc()): ?>
                    <div class="list-group-item list-group-item-action px-0 py-3">
                        <div class="d-flex w-100 justify-content-between">
                            <div>
                                <a href="competition_view.php?id=<?= $comp['id'] ?>" class="h5 mb-1 text-decoration-none"><?= e($comp['title']) ?></a>
                                <p class="mb-1 text-muted">
                                    <small>Başlatma: <?= date('d/m/Y', strtotime($comp['created_at'])) ?></small>
                                </p>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $comp['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $comp['status'] === 'active' ? 'Aktif' : 'Sonuçlandı' ?>
                                </span>
                                <p class="mb-1 mt-1">
                                    <span class="badge bg-info"><?= $comp['submission_count'] ?> Sunum</span>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-muted p-3">Henüz hiç yarışma başlatmadınız. <a href="create_competition.php">Hemen ilk yarışmanızı başlatın!</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>