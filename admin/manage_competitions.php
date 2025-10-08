<?php
require_once '../includes/header.php';
// require_admin(); // Gerçek bir sitede bu satırı açarak sadece adminlerin erişmesini sağlayın
require_login(); // Şimdilik test için sadece giriş yapmış olmak yeterli

// Bir işlemi (öne çıkarma/silme) gerçekleştir
if (isset($_GET['action']) && isset($_GET['id'])) {
    $competition_id = intval($_GET['id']);
    
    // Öne çıkarma/kaldırma işlemi
    if ($_GET['action'] === 'toggle_feature') {
        $current_status = (int)$_GET['status'];
        $new_status = $current_status == 1 ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE competitions SET is_featured = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $competition_id);
        $stmt->execute();
    }
    
    // Silme işlemi (DİKKATLİ KULLANIN!)
    if ($_GET['action'] === 'delete') {
        // Güvenlik için, silmeden önce emin misiniz diye soran bir JavaScript onayı eklenebilir.
        // Bu işlem, yarışmayla ilişkili tüm sunumları, yorumları vb. de silebilir (veritabanı ayarınıza göre).
        $stmt = $conn->prepare("DELETE FROM competitions WHERE id = ?");
        $stmt->bind_param("i", $competition_id);
        $stmt->execute();
    }

    header("Location: manage_competitions.php");
    exit;
}

// Tüm yarışmaları, sahibi ve sunum sayısı ile birlikte çek
$competitions = $conn->query(
    "SELECT c.id, c.title, c.status, c.is_featured, u.username as owner_name, 
    (SELECT COUNT(*) FROM submissions s WHERE s.competition_id = c.id) as submission_count
    FROM competitions c 
    JOIN users u ON c.user_id = u.id 
    ORDER BY c.created_at DESC"
);
?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h4 mb-0">🛠️ Yarışmaları Yönet</h2>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">← Panele Dön</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Yarışma Başlığı</th>
                        <th>Başlatan</th>
                        <th>Sunum Sayısı</th>
                        <th>Durum</th>
                        <th>Öne Çıkan</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $competitions->fetch_assoc()): ?>
                    <tr>
                        <td><a href="../competition_view.php?id=<?= $c['id'] ?>"><?= e($c['title']) ?></a></td>
                        <td><?= e($c['owner_name']) ?></td>
                        <td><?= $c['submission_count'] ?></td>
                        <td><span class="badge <?= $c['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= e($c['status']) ?></span></td>
                        <td><span class="badge <?= $c['is_featured'] == 1 ? 'bg-primary' : 'bg-light text-dark' ?>"><?= $c['is_featured'] == 1 ? 'Evet' : 'Hayır' ?></span></td>
                        <td>
                            <a href="?id=<?= $c['id'] ?>&action=toggle_feature&status=<?= $c['is_featured'] ?>" class="btn btn-sm <?= $c['is_featured'] == 1 ? 'btn-warning' : 'btn-info text-white' ?>">
                                <?= $c['is_featured'] == 1 ? '⭐ Kaldır' : '⭐ Öne Çıkar' ?>
                            </a>
                            <a href="?id=<?= $c['id'] ?>&action=delete" class="btn btn-sm btn-danger" onclick="return confirm('Bu yarışmayı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')">
                                Sil
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>