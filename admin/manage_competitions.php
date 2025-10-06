<?php
require_once '../includes/header.php';
// require_admin(); // Gerçek sitede bu satırı aktif edin! Şimdilik test için kapalı.
require_login(); // Geçici olarak sadece giriş kontrolü

// Durum güncelleme işlemi
if (isset($_GET['toggle_feature']) && isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $current_status = (int)$_GET['status'];
  $new_status = $current_status == 1 ? 0 : 1;
  
  $stmt = $conn->prepare("UPDATE competitions SET is_featured = ? WHERE id = ?");
  $stmt->bind_param("ii", $new_status, $id);
  $stmt->execute();

  header("Location: manage_competitions.php");
  exit;
}

// Yarışmaları çek
$competitions = $conn->query("SELECT id, title, status, is_featured FROM competitions ORDER BY created_at DESC");
?>

<div class="card shadow-sm">
    <div class="card-header"><h2 class="h4 mb-0">🛠️ Yarışmaları Yönet</h2></div>
    <div class="card-body">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Yarışma Adı</th>
                    <th>Durum</th>
                    <th>Öne Çıkan</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $competitions->fetch_assoc()): ?>
                <tr>
                    <td><?= e($c['title']) ?></td>
                    <td><span class="badge <?= $c['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $c['status'] ?></span></td>
                    <td><?= $c['is_featured'] == 1 ? 'Evet' : 'Hayır' ?></td>
                    <td>
                        <a href="?id=<?= $c['id'] ?>&toggle_feature=1&status=<?= $c['is_featured'] ?>" class="btn btn-sm <?= $c['is_featured'] == 1 ? 'btn-warning' : 'btn-primary' ?>">
                            <?= $c['is_featured'] == 1 ? '⭐ Öne Çıkarmayı Kaldır' : '⭐ Öne Çıkar' ?>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>