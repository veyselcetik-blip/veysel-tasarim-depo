<?php
require_once '../includes/header.php';
// require_admin(); // Gerçek sitede bu satırı aktif edin!
require_login(); 

// Rol güncelleme işlemi
if (isset($_GET['toggle_admin']) && isset($_GET['id'])) {
    $user_id_to_toggle = intval($_GET['id']);
    // Kendi rolünüzü değiştirememelisiniz
    if ($user_id_to_toggle != $_SESSION['user_id']) {
        $current_role = $_GET['role'];
        $new_role = $current_role === 'admin' ? 'user' : 'admin';
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id_to_toggle);
        $stmt->execute();
    }
    header("Location: manage_users.php");
    exit;
}

// Tüm kullanıcıları çek
$users = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
?>
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="h4 mb-0">👥 Kullanıcıları Yönet</h2>
        <a href="../index.php" class="btn btn-sm btn-outline-secondary">← Siteye Dön</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Kullanıcı Adı</th>
                        <th>E-posta</th>
                        <th>Kayıt Tarihi</th>
                        <th>Rol</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><a href="../profile.php?id=<?= $u['id'] ?>"><?= e($u['username']) ?></a></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td><span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>"><?= e($u['role']) ?></span></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): // Kendi kendini düzenleme butonu olmasın ?>
                            <a href="?id=<?= $u['id'] ?>&toggle_admin=1&role=<?= $u['role'] ?>" class="btn btn-sm <?= $u['role'] === 'admin' ? 'btn-warning' : 'btn-primary' ?>">
                                <?= $u['role'] === 'admin' ? 'Adminliği Kaldır' : 'Admin Yap' ?>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>