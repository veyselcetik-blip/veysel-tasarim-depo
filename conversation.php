<?php
require_once 'includes/header.php';
require_login();

$user_id = $_SESSION['user_id'];
$with_user_id = (int)($_GET['with'] ?? 0);

if ($with_user_id <= 0) {
    die("Geçersiz kullanıcı ID'si.");
}

// Konuşulan diğer kullanıcının bilgilerini al
$stmt_user = $conn->prepare("SELECT id, username, profile_image FROM users WHERE id = ?");
$stmt_user->bind_param("i", $with_user_id);
$stmt_user->execute();
$with_user = $stmt_user->get_result()->fetch_assoc();

if (!$with_user) {
    die("Konuşulacak kullanıcı bulunamadı.");
}

// Yeni mesaj gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_content = trim($_POST['message_content']);
    if (!empty($message_content)) {
        $stmt_insert = $conn->prepare(
            "INSERT INTO messages (sender_id, receiver_id, message_content) VALUES (?, ?, ?)"
        );
        $stmt_insert->bind_param("iis", $user_id, $with_user_id, $message_content);
        $stmt_insert->execute();
        // Sayfayı yenileyerek gönderilen mesajı göster
        header("Location: conversation.php?with=" . $with_user_id);
        exit();
    }
}

// Bu sohbetteki, karşı tarafın gönderdiği okunmamış mesajları "okundu" olarak işaretle
$conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $with_user_id AND receiver_id = $user_id AND is_read = 0");

// İki kullanıcı arasındaki tüm mesajları çek
$stmt_messages = $conn->prepare(
    "SELECT * FROM messages 
     WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
     ORDER BY sent_at ASC"
);
$stmt_messages->bind_param("iiii", $user_id, $with_user_id, $with_user_id, $user_id);
$stmt_messages->execute();
$messages = $stmt_messages->get_result();
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex align-items-center">
        <a href="messages.php" class="btn btn-sm btn-outline-secondary me-3">← Geri</a>
        <img src="<?= e($with_user['profile_image'] ?? 'uploads/profile/default.png') ?>" alt="<?= e($with_user['username']) ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
        <h1 class="h5 mb-0"><?= e($with_user['username']) ?> ile Sohbet</h1>
    </div>

    <div class="card-body" style="height: 500px; overflow-y: auto;" id="message-box">
        <?php if ($messages->num_rows > 0): ?>
            <?php while ($msg = $messages->fetch_assoc()): ?>
                <?php if ($msg['sender_id'] == $user_id): // Bu mesajı ben gönderdim ?>
                    <div class="d-flex justify-content-end mb-3">
                        <div class="bg-primary text-white p-2 rounded" style="max-width: 70%;">
                            <?= nl2br(e($msg['message_content'])) ?>
                            <div class="text-end text-light opacity-75" style="font-size: 0.75rem;"><?= date('H:i', strtotime($msg['sent_at'])) ?></div>
                        </div>
                    </div>
                <?php else: // Bu mesaj bana geldi ?>
                    <div class="d-flex justify-content-start mb-3">
                        <div class="bg-light p-2 rounded" style="max-width: 70%;">
                             <?= nl2br(e($msg['message_content'])) ?>
                            <div class="text-end text-muted" style="font-size: 0.75rem;"><?= date('H:i', strtotime($msg['sent_at'])) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center text-muted">Bu sohbet için henüz hiç mesaj yok. İlk mesajı siz gönderin!</p>
        <?php endif; ?>
    </div>

    <div class="card-footer">
        <form method="POST">
            <div class="input-group">
                <textarea name="message_content" class="form-control" placeholder="Mesajınızı yazın..." rows="2" required></textarea>
                <button class="btn btn-primary" type="submit" name="send_message">Gönder</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Sayfa yüklendiğinde mesaj kutusunu en aşağıya kaydır
    const messageBox = document.getElementById('message-box');
    messageBox.scrollTop = messageBox.scrollHeight;
</script>


<?php require_once 'includes/footer.php'; ?>