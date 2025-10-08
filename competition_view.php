<?php
require_once 'includes/header.php';
require_login(); 

$competition_id = (int)($_GET['id'] ?? 0);
if ($competition_id <= 0) die("Geçersiz yarışma ID'si.");

// Yarışma, sahibi ve kazananı bilgilerini tek seferde çek (Sizin kodunuz)
$stmt_comp = $conn->prepare(
    "SELECT c.*, 
            owner.username as owner_name, 
            winner.username as winner_username, 
            s.user_id as winner_user_id 
     FROM competitions c
     JOIN users owner ON c.user_id = owner.id
     LEFT JOIN submissions s ON c.winner_submission_id = s.id 
     LEFT JOIN users winner ON s.user_id = winner.id 
     WHERE c.id = ?"
);
$stmt_comp->bind_param("i", $competition_id);
$stmt_comp->execute();
$competition = $stmt_comp->get_result()->fetch_assoc();
if (!$competition) die("Yarışma bulunamadı.");

$is_owner = ($competition['user_id'] == $_SESSION['user_id']);
$is_winner = ($competition['status'] !== 'active' && !empty($competition['winner_user_id']) && $competition['winner_user_id'] == $_SESSION['user_id']);

// YENİ: Final sunum dosyasını çek (varsa)
$final_submission = null;
if($competition['status'] === 'files_delivered' || $competition['status'] === 'completed' || $competition['status'] === 'in_progress') {
    $final_submission_result = $conn->query("SELECT * FROM final_submissions WHERE competition_id = $competition_id");
    if($final_submission_result && $final_submission_result->num_rows > 0) {
        $final_submission = $final_submission_result->fetch_assoc();
    }
}

// Sayfalama mantığı (Sizin kodunuzdaki gibi)
$page = (int)($_GET['page'] ?? 1);
$submissions_per_page = 9; 
$offset = ($page - 1) * $submissions_per_page;
$total_submissions = $conn->query("SELECT COUNT(id) as total FROM submissions WHERE competition_id = $competition_id")->fetch_assoc()['total'];
$total_pages = ceil($total_submissions / $submissions_per_page);

// Sunumları, beğeni ve yorum bilgileriyle birlikte çek (Sizin kodunuzdaki gibi)
$stmt_subs = $conn->prepare(
    "SELECT s.*, u.username, 
            (SELECT COUNT(*) FROM submission_likes sl WHERE sl.submission_id = s.id) as like_count,
            (SELECT COUNT(*) FROM submission_likes sl WHERE sl.submission_id = s.id AND sl.user_id = ?) as user_liked
     FROM submissions s 
     JOIN users u ON s.user_id = u.id 
     WHERE s.competition_id = ? 
     ORDER BY s.uploaded_at DESC 
     LIMIT ? OFFSET ?"
);
$stmt_subs->bind_param("iiii", $_SESSION['user_id'], $competition_id, $submissions_per_page, $offset);
$stmt_subs->execute();
$submissions_result = $stmt_subs->get_result();
$submissions = [];
while($row = $submissions_result->fetch_assoc()) {
    $submissions[$row['id']] = $row;
    $submissions[$row['id']]['comments'] = [];
}
if(count($submissions) > 0) {
    $submission_ids = implode(',', array_keys($submissions));
    $comments_result = $conn->query("SELECT sc.*, u.username FROM submission_comments sc JOIN users u ON sc.user_id = u.id WHERE sc.submission_id IN ($submission_ids) ORDER BY sc.created_at ASC");
    if($comments_result) { while($comment = $comments_result->fetch_assoc()) { $submissions[$comment['submission_id']]['comments'][] = $comment; } }
}

// Hile engelleme kontrolü (Sizin kodunuzdaki gibi)
$user_can_participate = false;
if (isset($_SESSION['user_created_at'])) {
    $user_can_participate = (strtotime($_SESSION['user_created_at']) <= strtotime($competition['created_at']));
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h1 class="card-title"><?= e($competition['title']) ?></h1>
        <p class="text-muted fs-5"><a href="profile.php?id=<?= $competition['user_id'] ?>"><?= e($competition['owner_name']) ?></a> tarafından başlatıldı.</p>
        
        <?php if ($competition['status'] === 'completed' && !empty($competition['winner_username'])): ?>
            <div class="alert alert-success">Bu yarışma tamamlandı. Kazanan: <strong><a href="profile.php?id=<?= $competition['winner_user_id'] ?>"><?= e($competition['winner_username']) ?></a></strong></div>
        <?php elseif ($competition['status'] === 'active'): ?>
            <?php if (!$is_owner): ?>
                <?php if ($user_can_participate): ?>
                    <a href="submit_entry.php?competition_id=<?= $competition_id ?>" class="btn btn-success btn-lg">Bu Yarışmaya Katıl</a>
                <?php else: ?>
                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="Bu yarışma siz üye olmadan önce başlatıldığı için katılamazsınız."><a href="#" class="btn btn-success btn-lg disabled" aria-disabled="true">Bu Yarışmaya Katıl</a></span>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">Bu sizin tarafınızdan başlatılan bir yarışmadır.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($competition['status'] === 'in_progress' || $competition['status'] === 'files_delivered' || $competition['status'] === 'disputed'): ?>
<div class="card shadow-sm my-4 border-warning">
    <div class="card-header bg-warning"><h3 class="h5 mb-0">Final Dosya Teslim Süreci</h3></div>
    <div class="card-body">
        <?php // Burayı kazanan tasarımcı görür
        if ($is_winner): ?>
            <?php if($competition['status'] === 'in_progress'): ?>
                <h5>Tebrikler, yarışmayı kazandınız!</h5>
                <p>Lütfen tasarımınızın orijinal ve yüksek çözünürlüklü versiyonlarını içeren bir ZIP veya RAR dosyası hazırlayıp aşağıdan yükleyin.</p>
                <form action="upload_final_files.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="competition_id" value="<?= $competition_id ?>">
                    <div class="mb-3"><label for="final_file" class="form-label">Final Dosyaları (.zip, .rar)</label><input type="file" name="final_file" id="final_file" class="form-control" required accept=".zip,.rar"></div>
                    <button type="submit" class="btn btn-primary">Final Dosyalarını Gönder</button>
                </form>
            <?php else: // status === 'files_delivered' or 'disputed' ?>
                 <div class="alert alert-success">Final dosyalarını başarıyla yüklediniz. Müşterinin dosyaları indirip onaylaması bekleniyor.</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php // Burayı yarışma sahibi görür
        if ($is_owner): ?>
            <p>Yarışmanın kazananı <strong><?= e($competition['winner_username']) ?></strong> oldu. Şimdi, tasarımcının final dosyalarını yüklemesi bekleniyor.</p>
            <?php if($competition['status'] === 'files_delivered' && $final_submission): ?>
                <hr><h5>Tasarımcı Final Dosyalarını Yükledi!</h5>
                <p>Lütfen aşağıdaki linkten dosyaları indirip kontrol edin. Her şey yolundaysa yarışmayı tamamlayın.</p>
                <div class="p-3 bg-light rounded">
                    <a href="<?= e($final_submission['file_path']) ?>" class="btn btn-lg btn-success">Final Dosyalarını İndir</a>
                    <form action="complete_competition.php" method="POST" class="d-inline ms-2">
                        <input type="hidden" name="competition_id" value="<?= $competition_id ?>">
                        <button type="submit" class="btn btn-lg btn-primary">✅ Onayla ve Yarışmayı Tamamla</button>
                    </form>
                </div>
                <small class="text-muted d-block mt-2">Onaylamak için son tarih: <?= date('d/m/Y', strtotime($competition['delivery_deadline'])) ?></small>
            <?php elseif($competition['status'] === 'disputed'): ?>
                <div class="alert alert-danger">Bu teslimat için bir sorun bildirdiniz. Admin incelemesi bekleniyor.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


<h2 class="mb-4">Gelen Sunumlar (<?= $total_submissions ?>)</h2>
<?php if($total_pages > 1): ?>
    <?php endif; ?>

<script>var t=[].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));var o=t.map(function(t){return new bootstrap.Tooltip(t)});</script>
<?php require_once 'includes/footer.php'; ?>