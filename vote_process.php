<?php
require_once 'core/init.php';
require_login();

// Sadece POST metoduyla gelen istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek metodu.");
}

$submission_id = (int)($_POST['submission_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Geçerli bir gönderi ID'si var mı kontrol et
if ($submission_id <= 0) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Hata: Geçersiz gönderi.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']); // Bir önceki sayfaya yönlendir
    exit();
}

// Oy verilecek gönderi ve ait olduğu yarışma bilgilerini çek
$stmt = $conn->prepare(
    "SELECT s.user_id AS owner_id, c.status AS competition_status
     FROM submissions s
     JOIN competitions c ON s.competition_id = c.id
     WHERE s.id = ?"
);
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$submission_data = $stmt->get_result()->fetch_assoc();

// --- GÜVENLİK VE MANTIK KONTROLLERİ ---

// 1. Gönderi veritabanında var mı?
if (!$submission_data) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Hata: Oylamaya çalıştığınız gönderi bulunamadı.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// 2. Kullanıcı kendi gönderisine oy verebilir mi? (Genellikle istenmez)
if ($submission_data['owner_id'] == $user_id) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Kendi gönderinize oy veremezsiniz.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// 3. Yarışma hala aktif mi?
if ($submission_data['competition_status'] !== 'active') {
    $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Bu yarışma kapandığı için oylama yapamazsınız.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// 4. Kullanıcı bu gönderiye daha önce oy vermiş mi?
$stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND submission_id = ?");
$stmt->bind_param("ii", $user_id, $submission_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['flash_message'] = ['type' => 'info', 'text' => 'Bu gönderiye zaten daha önce oy vermişsiniz.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// --- OYU VERİTABANINA EKLEME ---

$stmt = $conn->prepare("INSERT INTO votes (user_id, submission_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $submission_id);

if ($stmt->execute()) {
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Oyunuz başarıyla kaydedildi!'];
} else {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Oylama sırasında bir veritabanı hatası oluştu.'];
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>