<?php
require_once 'includes/header.php';
require_login(); 

$errors = [];
if (isset($_POST['post_competition'])) {
    // Formdan gelen verileri al ve işle (Bu kısım aynı kalıyor)
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = (float)($_POST['budget'] ?? 0);
    $category = trim($_POST['category']);
    $end_date = $_POST['end_date'];
    $privacy_level = $_POST['privacy_level'];
    $user_id = $_SESSION['user_id'];

    if (empty($title)) $errors[] = "Yarışma başlığı zorunludur.";
    if ($budget <= 0) $errors[] = "Ödül tutarı 0'dan büyük olmalıdır.";
    if (empty($end_date)) $errors[] = "Bitiş tarihi zorunludur.";
    if (empty($description)) $errors[] = "Yarışma detayları (brief) boş bırakılamaz.";
    if (!in_array($privacy_level, ['open', 'blind'])) $errors[] = "Geçersiz yarışma türü.";

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO competitions (user_id, title, description, budget, category, end_date, privacy_level) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
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

<div class="card shadow-sm">
    <div class="card-header"><h2 class="h4 mb-0">Yeni Yarışma Başlat</h2></div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $error) echo "<p class='mb-0'>" . e($error) . "</p>"; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4 p-3 bg-light border rounded">
                <label for="brief_template" class="form-label fw-bold">1. Adım: Yarışma Türünü Seçin</label>
                <select id="brief_template" class="form-select">
                    <option value="">-- Bir şablon seçin --</option>
                    <option value="logo">Logo Tasarımı</option>
                    <option value="business_card">Kartvizit Tasarımı</option>
                    <option value="web_interface">Web Arayüz Tasarımı</option>
                    <option value="tshirt">T-shirt Tasarımı</option>
                    <option value="custom">Diğer (Boş Brief)</option>
                </select>
                <div class="form-text">Seçiminiz, aşağıdaki "Yarışma Detayları" kutusunu size yol gösterecek bir şablonla dolduracaktır.</div>
            </div>

            <hr>
            <p class="fw-bold">2. Adım: Yarışma Bilgilerini Doldurun</p>

            <div class="mb-3">
                <label for="title" class="form-label">Yarışma Başlığı</label>
                <input type="text" name="title" id="title" class="form-control" placeholder="Örn: E-ticaret sitem için modern logo tasarımı" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Kategori</label>
                <input type="text" name="category" id="category" class="form-control" placeholder="Örn: Logo & Marka Kimliği" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Yarışma Detayları (Brief)</label>
                <textarea name="description" id="description" rows="10" class="form-control" placeholder="Tasarımcılara yol göstermek için lütfen tüm detayları eksiksiz doldurun..." required></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label for="budget" class="form-label">Ödül Tutarı (TL)</label><input type="number" step="0.01" name="budget" id="budget" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label for="end_date" class="form-label">Bitiş Tarihi</label><input type="date" name="end_date" id="end_date" class="form-control" required></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Yarışma Türü:</label>
                <div class="form-check"><input class="form-check-input" type="radio" name="privacy_level" id="privacy_open" value="open" checked><label class="form-check-label" for="privacy_open">Açık Yarışma (Tüm sunumları herkes görebilir)</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="privacy_level" id="privacy_blind" value="blind"><label class="form-check-label" for="privacy_blind">Gizli Yarışma (Sunumları sadece siz görürsünüz)</label></div>
            </div>
            <button type="submit" name="post_competition" class="btn btn-primary w-100 btn-lg">Yarışmayı Başlat</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const briefTemplates = {
        logo: `**Şirket/Marka Adı:**\n[Buraya yazın]\n\n**Slogan (varsa):**\n[Buraya yazın]\n\n**Sektörünüz Nedir?**\n[Buraya yazın]\n\n**Hedef Kitleniz Kimlerdir?**\n[Buraya yazın]\n\n**Logoda Görmek İstediğiniz Renkler:**\n[Buraya yazın]\n\n**Beğendiğiniz Logo Stilleri (Modern, minimalist, klasik, eğlenceli vb.):**\n[Buraya yazın]\n\n**Logoda Kesinlikle Olmasını veya Olmamasını İstediğiniz Şeyler:**\n[Buraya yazın]\n\n**Referans Beğendiğiniz Logo Örnekleri (Link veya açıklama):**\n[Buraya yazın]`,
        business_card: `**Kartvizitte Yer Alacak Bilgiler:**\n- Ad Soyad:\n- Unvan:\n- Şirket Adı:\n- Telefon:\n- E-posta:\n- Web Sitesi:\n- Adres:\n\n**Mevcut Logonuz Var Mı? (Varsa linkini ekleyin):**\n[Buraya yazın]\n\n**İstenen Kartvizit Boyutları (Standart mı, özel mi?):**\n[Buraya yazın]\n\n**Tasarım Stili (Kurumsal, modern, minimalist, yaratıcı vb.):**\n[Buraya yazın]\n\n**İstenen Renk Paleti:**\n[Buraya yazın]`,
        web_interface: `**Projenin Amacı Nedir? (E-ticaret, blog, kurumsal site vb.):**\n[Buraya yazın]\n\n**Hangi Sayfaların Tasarlanmasını İstiyorsunuz?**\n- Anasayfa\n- Hakkımızda\n- Ürünler Sayfası\n- İletişim\n- (Diğer...)\n\n**Hedef Kitleniz Kimlerdir?**\n[Buraya yazın]\n\n**Marka Kimliğiniz ve Renkleriniz:**\n[Buraya yazın]\n\n**Beğendiğiniz Rakip veya Örnek Web Siteleri (Linkler):**\n[Buraya yazın]\n\n**İstediğiniz Genel Hava (Profesyonel, enerjik, sakin, lüks vb.):**\n[Buraya yazın]`,
        tshirt: `**T-shirt Üzerinde Ne Yazmasını/Görünmesini İstiyorsunuz?**\n[Buraya yazın]\n\n**Tasarım Hangi Kitleye Hitap Edecek?**\n[Buraya yazın]\n\n**İstenen Tasarım Stili (Komik, vintage, tipografik, minimalist vb.):**\n[Buraya yazın]\n\n**İstenen Renkler (Hem t-shirt rengi hem de tasarım renkleri):**\n[Buraya yazın]\n\n**Referans Olarak Beğendiğiniz Tasarım Örnekleri:**\n[Buraya yazın]`,
        custom: ''
    };

    const templateSelect = document.getElementById('brief_template');
    const descriptionTextarea = document.getElementById('description');
    const categoryInput = document.getElementById('category');

    templateSelect.addEventListener('change', function() {
        const selectedTemplate = this.value;
        if (briefTemplates.hasOwnProperty(selectedTemplate)) {
            descriptionTextarea.value = briefTemplates[selectedTemplate];
            
            // Kategori alanını da otomatik dolduralım
            if(selectedTemplate && selectedTemplate !== 'custom') {
                categoryInput.value = this.options[this.selectedIndex].text;
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>