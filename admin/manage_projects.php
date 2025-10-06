<?php
require_once '../includes/header.php';
require_admin();

// YENİ PROJE EKLEME
if (isset($_POST['add_project'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $end_date = $_POST['end_date'];
    $budget = (float)($_POST['budget'] ?? 0);
    $client_id = $_SESSION['user_id']; // Projeyi oluşturan admin aynı zamanda müşteri

    if (empty($title) || empty($end_date)) {
        // Hata yönetimi...
    } else {
        $stmt = $conn->prepare("INSERT INTO projects (client_id, title, description, end_date, budget) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssd", $client_id, $title, $description, $end_date, $budget);
        if ($stmt->execute()) {
            // Başarı mesajı...
        } else {
            // Hata mesajı...
        }
    }
}

// Projeleri listele
$projects = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
?>

<h1 class="mb-4">Projeleri Yönet</h1>

<div class="card mb-4 shadow-sm">
    <div class="card-header"><h2 class="h5 mb-0">Yeni Proje Oluştur</h2></div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Proje Başlığı</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>
                 <div class="col-md-3 mb-3">
                    <label for="budget" class="form-label">Bütçe (TL)</label>
                    <input type="number" step="0.01" name="budget" id="budget" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="end_date" class="form-label">Bitiş Tarihi</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                </div>
                <div class="col-12 mb-3">
                    <label for="description" class="form-label">Açıklama</label>
                    <textarea name="description" id="description" class="form-control"></textarea>
                </div>
            </div>
            <button type="submit" name="add_project" class="btn btn-primary">Projeyi Yayınla</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><h2 class="h5 mb-0">Mevcut Projeler</h2></div>
    <div class="card-body">
        <table class="table table-striped">
             <thead>
                <tr>
                    <th>Başlık</th>
                    <th>Bütçe</th>
                    <th>Bitiş Tarihi</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $projects->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($p['title']) ?></td>
                        <td><?= e($p['budget']) ?> TL</td>
                        <td><?= date('d/m/Y', strtotime($p['end_date'])) ?></td>
                        <td>
                            <?php if ($p['status'] === 'active'): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Kapalı</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>