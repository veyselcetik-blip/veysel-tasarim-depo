<?php
require_once 'includes/header.php';

// --- YENİ: ÖNCE SADECE ÖNE ÇIKAN YARIŞMALARI ÇEK ---
$featured_competitions = [];
$featured_result = $conn->query(
    "SELECT c.*, u.username as owner_name, 
    (SELECT COUNT(*) FROM submissions s WHERE s.competition_id = c.id) as submission_count
     FROM competitions c JOIN users u ON c.user_id = u.id
     WHERE c.is_featured = 1 AND c.status = 'active'
     ORDER BY c.created_at DESC"
);
if ($featured_result) {
    while($row = $featured_result->fetch_assoc()) {
        $featured_competitions[] = $row;
    }
}


// --- FİLTRELEME VE SIRALAMA MANTIĞI (SİZİN KODUNUZDAKİ GİBİ) ---

// 1. Formdan gelen veya varsayılan değerleri al
$filter_category = $_GET['category'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

// 2. Filtreleme formunda göstermek için tüm benzersiz kategorileri çek
$categories_result = $conn->query("SELECT DISTINCT category FROM competitions WHERE category != '' ORDER BY category ASC");
$categories = [];
while($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// 3. SQL sorgusunu dinamik olarak oluştur
$sql = "SELECT c.*, u.username as owner_name, 
        (SELECT COUNT(*) FROM submissions s WHERE s.competition_id = c.id) as submission_count
        FROM competitions c 
        JOIN users u ON c.user_id = u.id";

// WHERE koşulunu ekle (kategori filtresi için VE ÖNE ÇIKANLARI HARİÇ TUT)
$where_clauses = ["c.is_featured = 0"]; // BU SATIR EKLENDİ
$params = [];
$types = '';
if (!empty($filter_category)) {
    $where_clauses[] = "c.category = ?";
    $params[] = $filter_category;
    $types .= 's';
}
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// ORDER BY koşulunu ekle (sıralama için)
switch ($sort_by) {
    case 'prize_high':
        $sql .= " ORDER BY c.budget DESC";
        break;
    case 'prize_low':
        $sql .= " ORDER BY c.budget ASC";
        break;
    case 'popular':
        $sql .= " ORDER BY submission_count DESC, c.created_at DESC";
        break;
    default: // 'newest'
        $sql .= " ORDER BY c.created_at DESC";
        break;
}

// 4. Sorguyu çalıştır ve sonuçları ayır
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$active_competitions = [];
$finished_competitions = [];
if ($result) {
    while ($comp = $result->fetch_assoc()) {
        if ($comp['status'] === 'active') { $active_competitions[] = $comp; } 
        else { $finished_competitions[] = $comp; }
    }
}
?>

<div class="text-center mb-5">
    </div>

<?php if (count($featured_competitions) > 0): ?>
<div class="mb-5">
    <h2 class="mb-4 pb-2 border-bottom">⭐ Öne Çıkan Yarışmalar</h2>
    <div class="row">
        <?php foreach ($featured_competitions as $c): ?>
        <div class="col-lg-6 mb-4">
            <div class="card h-100 border-primary border-2 shadow-lg">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><a href="competition_view.php?id=<?= $c['id'] ?>" class="text-decoration-none"><?= e($c['title']) ?></a></h5>
                    <small class="text-muted mb-2">Başlatan: <a href="profile.php?id=<?= $c['user_id'] ?>"><?= e($c['owner_name']) ?></a></small>
                    <div class="d-flex justify-content-between text-muted small mb-3"><span><span class="badge bg-info"><?= e($c['category']) ?></span></span><span><span class="badge bg-primary"><?= $c['submission_count'] ?> Sunum</span></span></div>
                    <div class="mt-auto"><h4 class="text-success my-2"><?= e($c['budget']) ?> TL Ödül</h4><a href="competition_view.php?id=<?= $c['id'] ?>" class="btn btn-primary w-100 mt-2">Hemen İncele</a></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-5">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6"><label for="category" class="form-label">Kategoriye Göre Filtrele</label><select name="category" id="category" class="form-select"><option value="">Tüm Kategoriler</option><?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>" <?= ($filter_category == $cat) ? 'selected' : '' ?>><?= e($cat) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label for="sort" class="form-label">Sırala</label><select name="sort" id="sort" class="form-select"><option value="newest" <?= ($sort_by == 'newest') ? 'selected' : '' ?>>En Yeni</option><option value="prize_high" <?= ($sort_by == 'prize_high') ? 'selected' : '' ?>>En Yüksek Ödül</option><option value="prize_low" <?= ($sort_by == 'prize_low') ? 'selected' : '' ?>>En Düşük Ödül</option><option value="popular" <?= ($sort_by == 'popular') ? 'selected' : '' ?>>En Popüler</option></select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Uygula</button></div>
        </form>
    </div>
</div>

<div class="mb-5">
    <h2 class="mb-4 pb-2 border-bottom">Diğer Aktif Yarışmalar</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (count($active_competitions) > 0): foreach ($active_competitions as $c): ?>
        <div class="col"><div class="card h-100"><div class="card-body d-flex flex-column"><small class="text-muted mb-2">Başlatan: <a href="profile.php?id=<?= $c['user_id'] ?>"><?= e($c['owner_name']) ?></a></small><h5 class="card-title"><?= e($c['title']) ?></h5><div class="d-flex justify-content-between text-muted small mb-2"><span><span class="badge bg-info"><?= e($c['category']) ?></span></span><span><span class="badge bg-primary"><?= $c['submission_count'] ?> Sunum</span></span></div><div class="mt-auto"><h4 class="text-success my-2"><?= e($c['budget']) ?> TL Ödül</h4><a href="competition_view.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary w-100 mt-2">İncele ve Katıl</a></div></div></div></div>
        <?php endforeach; else: ?>
        <div class="col-12"><div class="alert alert-info">Bu kriterlere uygun başka aktif bir yarışma bulunamadı.</div></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>