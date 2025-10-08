<?php
require_once 'core/init.php';
require_login();

// --- 1. İstek Metodunu Kontrol Et ---
// Sadece POST metodu ile gelen istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // die() yerine kullanıcıya hata mesajı verip yönlendirelim. Bu daha profesyonel.
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Geçersiz istek türü.'];
    header('Location: my_competitions.php'); // Veya index.php
    exit();
}

// --- 2. Gelen Veriyi Güvenle Al ve Doğrula ---
$competition_id = (int)($_POST['competition_id'] ?? 0);
$user_id = $_SESSION['user_id']; // Oturumu açık olan kullanıcı (yarışma sahibi)

// Eğer competition_id gelmediyse veya 0 ise, işlemi devam ettirme.
if ($competition_id === 0) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Geçersiz yarışma kimliği.'];
    header('Location: my_competitions.php');
    exit();
}

// --- 3. Yetki Kontrolü (Hazırlıklı İfade ile) ---
// Bu işlemi bu kullanıcı yapabilir mi? (Yarışma bu kullanıcıya ait mi ve durumu uygun mu?)
$sql_check = "SELECT user_id FROM competitions WHERE id = ? AND user_id = ? AND status = 'files_delivered' LIMIT 1";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $competition_id, $user_id);
$stmt_check->execute();
$result = $stmt_check->get_result();

// --- 4. Ana Mantık ve Veritabanı Güncellemesi ---
if ($result->num_rows === 1) {
    
    // ****** DÜZELTİLEN EN ÖNEMLİ KISIM BURASI ******
    // UPDATE sorgusunu da GÜVENLİ HALE GETİRELİM (Hazırlıklı İfade ile)
    $sql_update = "UPDATE competitions SET status = 'completed' WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $competition_id);
    
    // Sorguyu çalıştır ve sonucunu kontrol et
    if ($stmt_update->execute()) {
        
        // BONUS/İYİLEŞTİRME: İşlem başarılıysa kullanıcıya bildirim gönderelim.
        $notification_message = "Yarışmanız başarıyla 'tamamlandı' olarak işaretlendi. Artık tarafları değerlendirebilirsiniz.";
        $sql_insert_notification = "INSERT INTO notifications (user_id, competition_id, message, status) VALUES (?, ?, ?, 'unread')";
        $stmt_notification = $conn->prepare($sql_insert_notification);
        // Bildirim yarışma sahibine gideceği için user_id'yi kullanıyoruz.
        $stmt_notification->bind_param("iis", $user_id, $competition_id, $notification_message);
        $stmt_notification->execute();

        // Başarılı mesajını ayarla
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Yarışma başarıyla tamamlandı! Şimdi tarafları değerlendirebilirsiniz.'];

    } else {
        // Eğer UPDATE sorgusu çalışmazsa veritabanı hatası verelim.
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Veritabanı güncellenirken bir hata oluştu.'];
    }

} else {
    // Yetki kontrolü başarısız olursa
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Bu işlemi yapma yetkiniz yok veya yarışma uygun durumda değil.'];
}
    
// --- 5. Sonuç ne olursa olsun kullanıcıyı ilgili sayfaya geri yönlendir ---
header("Location: competition_view.php?id=" . $competition_id);
exit();
?>