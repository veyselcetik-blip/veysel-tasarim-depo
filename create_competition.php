<?php
require_once 'includes/header.php';
require_login(); 

$errors = [];
if (isset($_POST['post_competition'])) {
    // Formdan gelen genel verileri al
    $title = trim($_POST['title']);
    $budget = (float)($_POST['budget'] ?? 0);
    $category = trim($_POST['category']);
    $end_date = $_POST['end_date'];
    $privacy_level = $_POST['privacy_level'];
    $user_id = $_SESSION['user_id'];
    $template_type = $_POST['template_type'] ?? '';

    // Şablona göre 'description' (yarışma özeti) metnini oluştur
    $description = "";
    if ($template_type === 'logo') {
        $description .= "**Şirket/Marka Adı:**\n" . e($_POST['logo_company_name']) . "\n\n";
        $description .= "**Slogan (varsa):**\n" . e($_POST['logo_slogan']) . "\n\n";
        $description .= "**Hedef Kitleniz:**\n" . e($_POST['logo_audience']) . "\n\n";
        $description .= "**İstenen Renkler:**\n" . e($_POST['logo_colors']);
        // Özel istekler alanını brief'e ekliyoruz
        if (!empty($_POST['logo_special_requests'])) {
            $description .= "\n\n**Özel İstekler ve Ek Notlar:**\n" . e($_POST['logo_special_requests']);
        }
    } else { // Diğer şablonlar veya özel brief için
        $description = trim($_POST['custom_description']);
    }

    if (empty($title)) $errors[] = "Yarışma başlığı zorunludur.";
    if (empty($description)) $errors[] = "Yarışma detayları (brief) boş bırakılamaz.";
    if ($budget <= 0) $errors[] = "Ödül tutarı 0'dan büyük olmalıdır.";
    // ... (diğer doğrulamalar) ...

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO competitions (user_id, title, description, budget, category, end_date, privacy_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdsss", $user_id, $title, $description, $budget, $category, $end_date, $privacy_level);
        if ($stmt->execute()) {
            $new_competition_id = $stmt->insert_id;
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Yarışmanız başarıyla başlatıldı!'];
            header("Location: competition_view.php?id=" . $new_competition_id);
            exit();
        } else {
            $errors[] = "Yarışma başlatılırken bir veritabanı hatası oluştu.";
        }
    }
}
?>
<style>
    .template-card { border: 2px solid #eee; transition: all 0.2s ease-in-out; cursor: pointer; }
    .template-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-color: #ccc; }
    .template-card.selected { border-color: var(--bs-primary); background-color: #e7f0fe; transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .template-fields { display: none; }
</style>

<div class="card shadow-sm">
    <div class="card-header"><h2 class="h4 mb-0">Yeni Yarışma Başlat</h2></div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>" . e($error) . "</p>"; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="form-label fw-bold fs-5">1. Adım: Yarışma türünü seçin</label>
                <div class="row g-3" id="template-selector">
                    <div class="col-6 col-md-3"><div class="card template-card h-100 text-center" data-template="logo"><div class="card-body d-flex flex-column justify-content-center align-items-center"><i class="bi bi-bounding-box fs-1 text-primary"></i><p class="mt-2 mb-0 fw-bold">Logo Tasarımı</p></div></div></div>
                    <div class="col-6 col-md-3"><div class="card template-card h-100 text-center" data-template="custom"><div class="card-body d-flex flex-column justify-content-center align-items-center"><i class="bi bi-pencil-square fs-1 text-secondary"></i><p class="mt-2 mb-0 fw-bold">Diğer (Boş)</p></div></div></div>
                    </div>
                <input type="hidden" name="template_type" id="template_type" value="">
            </div>
            
            <div id="form-fields" style="display: none;">
                <hr class="my-4">
                <p class="fw-bold fs-5">2. Adım: Yarışma detaylarını doldurun</p>
                <div class="row">
                    <div class="col-md-8 mb-3"><label for="title" class="form-label">Yarışma Başlığı</label><input type="text" name="title" id="title" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label for="category" class="form-label">Kategori</label><input type="text" name="category" id="category" class="form-control" required></div>
                </div>

                <div id="template-fields-logo" class="template-fields p-3 bg-light border rounded mb-3">
                    <h5 class="mb-3">Logo Tasarım Brief'i</h5>
                    <div class="mb-2"><label for="logo_company_name" class="form-label small fw-bold">**Şirket/Marka Adı:**</label><input type="text" id="logo_company_name" name="logo_company_name" class="form-control"></div>
                    <div class="mb-2"><label for="logo_slogan" class="form-label small fw-bold">**Slogan (varsa):**</label><input type="text" id="logo_slogan" name="logo_slogan" class="form-control"></div>
                    <div class="mb-2"><label for="logo_audience" class="form-label small fw-bold">**Hedef Kitleniz:**</label><textarea id="logo_audience" name="logo_audience" class="form-control" rows="2"></textarea></div>
                    <div class="mb-2"><label for="logo_colors" class="form-label small fw-bold">**İstenen Renkler:**</label><input type="text" id="logo_colors" name="logo_colors" class="form-control"></div>
                    <div class="mt-3"><label for="logo_special_requests" class="form-label small fw-bold">**Özel İstekler ve Ek Notlar:**</label><textarea id="logo_special_requests" name="logo_special_requests" class="form-control" rows="3" placeholder="Şablona ek olarak belirtmek istediğiniz her şeyi buraya yazın..."></textarea></div>
                </div>

                <div id="template-fields-custom" class="template-fields mb-3">
                    <label for="custom_description" class="form-label">Yarışma Detayları (Brief)</label>
                    <textarea name="custom_description" id="custom_description" rows="10" class="form-control"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="budget" class="form-label">Ödül Tutarı (TL)</label><input type="number" step="0.01" name="budget" id="budget" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label for="end_date" class="form-label">Bitiş Tarihi</label><input type="date" name="end_date" id="end_date" class="form-control" required></div>
                </div>
                <div class="mb-3"><label class="form-label">Yarışma Türü:</label>
                    <div class="form-check"><input class="form-check-input" type="radio" name="privacy_level" id="privacy_open" value="open" checked><label class="form-check-label" for="privacy_open">Açık Yarışma</label></div>
                    <div class="form-check"><input class="form-check-input" type="radio" name="privacy_level" id="privacy_blind" value="blind"><label class="form-check-label" for="privacy_blind">Gizli Yarışma</label></div>
                </div>
                <button type="submit" name="post_competition" class="btn btn-primary w-100 btn-lg">Yarışmayı Başlat</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('template-selector');
    const cards = selector.querySelectorAll('.template-card');
    const formFields = document.getElementById('form-fields');
    const hiddenTemplateType = document.getElementById('template_type');
    const categoryInput = document.getElementById('category');

    cards.forEach(card => {
        card.addEventListener('click', function() {
            cards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            
            const template = this.dataset.template;
            hiddenTemplateType.value = template;
            formFields.style.display = 'block';

            document.querySelectorAll('.template-fields').forEach(fieldSet => {
                fieldSet.style.display = 'none';
                fieldSet.querySelectorAll('input, textarea').forEach(input => input.required = false);
            });

            const selectedFields = document.getElementById('template-fields-' + template);
            if (selectedFields) {
                selectedFields.style.display = 'block';
                selectedFields.querySelectorAll('input, textarea').forEach(input => {
                    // Sadece özel brief'teki ana kutuyu zorunlu yap
                    if (input.id === 'custom_description') {
                        input.required = true;
                    }
                });
            }
            
            if (template === 'logo') categoryInput.value = 'Logo Tasarımı';
            else if (template === 'custom') categoryInput.value = '';
            // Diğer şablonlar için de kategori ayarlanabilir
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>