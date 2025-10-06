<?php
require_once 'includes/header.php';
require_login(); // Bu sayfayı görmek için giriş zorunlu

$user_id = $_SESSION['user_id'];

// Önce okunmamış bildirimleri "okundu" olarak işaretleyelim.
// Bu işlemi, verileri çekmeden ÖNCE yapmak daha güvenlidir.
$update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();

// Şimdi kullanıcının tüm bildirimlerini veritabanından çekelim (en yeniden eskiye)
$select_stmt = $conn->prepare("SELECT id, message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$select_stmt->bind_param("i", $user_id);
$select_stmt->execute();
$notifications_result = $select_stmt->get_result();
$notifications = [];
if ($notifications_result) {
    while($row = $notifications_result->fetch_assoc()){
        $notifications[] = $row;
    }
}

?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h1 class="h4 mb-0">🔔 Bildirimler</h1>
    </div>
    <div class="card-body">
        <div class="list-group list-group-flush">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                    <a href="<?= e($notif['link']) ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <p class="mb-1"><?= $notif['message'] // Mesaj HTML içerdiği için e() kullanmıyoruz ?></p>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted p-3">Gösterilecek bildirim yok.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>