<?php
require_once 'includes/header.php';
require_login(); // Bu sayfayı görmek için giriş zorunlu

$user_id = $_SESSION['user_id'];

// Kullanıcının konuştuğu diğer tüm kişileri listele
// Bu sorgu, hem gönderilen hem de alınan mesajlardaki diğer kullanıcıları bulur,
// tekrarları kaldırır ve en son mesajlaşmaya göre sıralar.
$stmt = $conn->prepare(
    "SELECT u.id, u.username, u.profile_image, 
            (SELECT MAX(m.sent_at) FROM messages m WHERE (m.sender_id = ? AND m.receiver_id = u.id) OR (m.sender_id = u.id AND m.receiver_id = ?)) as last_message_time
     FROM users u
     WHERE u.id != ? AND EXISTS (
         SELECT 1 FROM messages m WHERE (m.sender_id = ? AND m.receiver_id = u.id) OR (m.sender_id = u.id AND m.receiver_id = ?)
     )
     ORDER BY last_message_time DESC"
);
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result();

?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h1 class="h4 mb-0">✉️ Mesajlarım</h1>
    </div>
    <div class="card-body">
        <div class="list-group list-group-flush">
            <?php if ($conversations->num_rows > 0): ?>
                <?php while ($con = $conversations->fetch_assoc()): ?>
                    <a href="conversation.php?with=<?= $con['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                        <img src="<?= e($con['profile_image'] ?? 'uploads/profile/default.png') ?>" alt="<?= e($con['username']) ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= e($con['username']) ?></h5>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($con['last_message_time'])) ?></small>
                            </div>
                            <p class="mb-1 text-muted">Bu kişiyle olan sohbetinizi görüntülemek için tıklayın.</p>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-muted p-3">Henüz hiç mesajınız yok.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>