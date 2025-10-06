<?php
require_once 'includes/header.php';
require_login();

$profile_user_id = (int)($_GET['id'] ?? $_SESSION['user_id']);
$is_own_profile = ($profile_user_id == $_SESSION['user_id']);

// Kullanıcı bilgilerini çek
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $profile_user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
if (!$user) die("Kullanıcı bulunamadı.");

// Yetenekleri çek
$skills = [];
$skills_result = $conn->query("SELECT skill_name FROM user_skills WHERE user_id = $profile_user_id");
if($skills_result) { while($row = $skills_result->fetch_assoc()) { $skills[] = $row['skill_name']; } }

// Değerlendirmeleri ve puan ortalamasını çek
$reviews = [];
$avg_rating = 0;
$total_reviews = 0;
$reviews_result = $conn->query("SELECT r.*, u.username as reviewer_name FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.reviewee_id = $profile_user_id ORDER BY r.created_at DESC");
if ($reviews_result && $reviews_result->num_rows > 0) {
    $total_reviews = $reviews_result->num_rows;
    $total_rating = 0;
    while($row = $reviews_result->fetch_assoc()) {
        $total_rating += $row['rating'];
        $reviews[] = $row;
    }
    $avg_rating = round($total_rating / $total_reviews, 1);
}

// Portfolyo çalışmalarını çek
$portfolio_items = [];
$portfolio_result = $conn->query("SELECT * FROM portfolio_items WHERE user_id = $profile_user_id ORDER BY uploaded_at DESC");
if ($portfolio_result) { while($row = $portfolio_result->fetch_assoc()){ $portfolio_items[] = $row; } }

// Katıldığı yarışmaları çek
$submissions = [];
$stmt_subs = $conn->prepare("SELECT c.id, c.title FROM submissions s JOIN competitions c ON s.competition_id = c.id WHERE s.user_id = ? GROUP BY c.id ORDER BY MAX(s.uploaded_at) DESC");
$stmt_subs->bind_param("i", $profile_user_id);
$stmt_subs->execute();
$submissions_result = $stmt_subs->get_result();
if($submissions_result) { while($row = $submissions_result->fetch_assoc()){ $submissions[] = $row; } }
?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 position-sticky" style="top: 20px;">
            <div class="card-body text-center">
                <img src="<?= e($user['profile_image'] ?? 'uploads/profile/default.png') ?>" alt="Profil Fotoğrafı" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3 class="mb-1"><?= e($user['username']) ?></h3>
                <div class="my-3">
                    <span class="fs-4 fw-bold"><?= $avg_rating ?></span> / 5.0
                    <div class="text-warning"><?php for($i=1; $i<=5; $i++) { echo $i <= floor($avg_rating) ? '★' : '☆'; } ?></div>
                    <small class="text-muted">(<?= $total_reviews ?> değerlendirme)</small>
                </div>
                <?php if ($is_own_profile): ?>
                    <a href="edit_profile.php" class="btn btn-primary w-100">Profili Düzenle</a>
                <?php else: ?>
                    <a href="conversation.php?with=<?= $user['id'] ?>" class="btn btn-success w-100">✉️ Mesaj Gönder</a>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white text-center">
                 <?php $can_see_email = $is_own_profile || ($user['email_visibility'] == 'registered'); if ($can_see_email): ?><small class="text-muted"><?= e($user['email']) ?></small><?php else: ?><small class="text-muted"><em>E-posta adresi gizli</em></small><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4"><div class="card-body"><h5 class="card-title">Hakkında</h5><p class="card-text text-muted"><?= !empty($user['bio']) ? nl2br(e($user['bio'])) : '<em>Bu kullanıcı henüz hakkında bir bilgi girmemiş.</em>' ?></p></div></div>
        <div class="card shadow-sm border-0 mb-4"><div class="card-body"><h5 class="card-title">Yetenekler</h5><div><?php if (count($skills) > 0): foreach ($skills as $skill): ?><span class="badge bg-secondary me-1 mb-1 fs-6"><?= e($skill) ?></span><?php endforeach; else: ?><p class="card-text text-muted"><em>Bu kullanıcı henüz yeteneklerini eklememiş.</em></p><?php endif; ?></div></div></div>
        <div class="card shadow-sm border-0 mb-4"><div class="card-body"><h5 class="card-title mb-3">Portfolyo</h5><div class="row row-cols-1 row-cols-md-2 g-3"><?php if(count($portfolio_items) > 0): foreach($portfolio_items as $item): ?><div class="col"><div class="card"><a href="<?= e($item['image_path']) ?>" target="_blank"><img src="<?= e($item['image_path']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;"></a><div class="card-body"><h6 class="card-title"><?= e($item['title']) ?></h6><p class="card-text small text-muted"><?= e($item['description']) ?></p></div></div></div><?php endforeach; else: ?><p class="text-muted">Bu kullanıcı henüz portfolyosuna bir çalışma eklememiş.</p><?php endif; ?></div></div></div>
        <div class="card shadow-sm border-0 mb-4"><div class="card-body"><h5 class="card-title">Kullanıcı Değerlendirmeleri</h5><?php if (count($reviews) > 0): ?><ul class="list-group list-group-flush"><?php foreach ($reviews as $review): ?><li class="list-group-item px-0"><div class="d-flex w-100 justify-content-between"><h6 class="mb-1"><?= e($review['reviewer_name']) ?></h6><span class="text-warning"><?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?></span></div><?php if(!empty($review['comment'])): ?><p class="mb-1">"<?= e($review['comment']) ?>"</p><?php endif; ?></li><?php endforeach; ?></ul><?php else: ?><p class="text-muted">Bu kullanıcı henüz hiç değerlendirme almamış.</p><?php endif; ?></div></div>
        <div class="card shadow-sm border-0"><div class="card-body"><h5 class="card-title">Katıldığı Yarışmalar</h5><?php if (count($submissions) > 0): ?><ul class="list-group list-group-flush"><?php foreach ($submissions as $sub): ?><li class="list-group-item"><a href="competition_view.php?id=<?= $sub['id'] ?>"><?= e($sub['title']) ?></a></li><?php endforeach; ?></ul><?php else: ?><p class="card-text text-muted"><em>Bu kullanıcı henüz bir yarışmaya katılmamış.</em></p><?php endif; ?></div></div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>