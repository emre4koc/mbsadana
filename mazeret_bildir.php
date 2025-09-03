<?php
require_once 'config/session_check.php';
require_once 'config/db.php';

$sayfa_baslik = "Mazeret Bildir";
$user_id = $_SESSION['user_id'];
$mesaj = '';

if (isset($_SESSION['mesaj'])) {
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
}

if (isset($_GET['action']) && $_GET['action'] == 'geri_cek' && isset($_GET['id'])) {
    $mazeret_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM mazeretler WHERE id = ? AND user_id = ? AND durum = 'Beklemede'");
    if ($stmt->execute([$mazeret_id, $user_id])) {
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Mazeret talebiniz başarıyla geri çekildi.'];
    } else {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'İşlem sırasında bir hata oluştu.'];
    }
    header("Location: mazeret_bildir.php");
    exit();
}

if (isset($_POST['mazeret_gonder'])) {
    $baslangic = $_POST['baslangic_tarihi'];
    $bitis = $_POST['bitis_tarihi'];
    $aciklama = trim($_POST['aciklama']);

    if (empty($baslangic) || empty($bitis) || empty($aciklama)) {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Lütfen tüm alanları doldurun.'];
    } elseif ($bitis < $baslangic) {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Bitiş tarihi, başlangıç tarihinden önce olamaz.'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO mazeretler (user_id, baslangic_tarihi, bitis_tarihi, aciklama) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $baslangic, $bitis, $aciklama])) {
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Mazeretiniz başarıyla iletildi.'];
            
            $admin_stmt = $pdo->query("SELECT id FROM users WHERE rol = 1 ORDER BY id ASC LIMIT 1");
            $admin = $admin_stmt->fetch();

            if ($admin) {
                $admin_id = $admin->id;
                $kullanici_adi = $_SESSION['user_ad'] . ' ' . $_SESSION['user_soyad'];
                $bildirim_mesaji = "{$kullanici_adi} yeni bir mazeret talebinde bulundu.";
                $bildirim_linki = "/admin/mazeretler.php";
                
                $bildirim_stmt = $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)");
                $bildirim_stmt->execute([$admin_id, $bildirim_mesaji, $bildirim_linki]);
            }
        } else {
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Mazeret gönderilirken bir hata oluştu.'];
        }
    }
    header("Location: mazeret_bildir.php");
    exit();
}

$mazeretler = $pdo->prepare("SELECT * FROM mazeretler WHERE user_id = ? ORDER BY olusturma_tarihi DESC");
$mazeretler->execute([$user_id]);
$mazeretler = $mazeretler->fetchAll();

include 'templates/header.php';
?>
<div class="container mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Yeni Mazeret Bildir</h2>
            <?php if ($mesaj): ?>
                <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($mesaj['icerik']); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-4">
                    <label for="baslangic_tarihi" class="block text-sm font-medium text-gray-700">Başlangıç Tarihi</label>
                    <input type="date" name="baslangic_tarihi" id="baslangic_tarihi" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                </div>
                <div class="mb-4">
                    <label for="bitis_tarihi" class="block text-sm font-medium text-gray-700">Bitiş Tarihi</label>
                    <input type="date" name="bitis_tarihi" id="bitis_tarihi" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                </div>
                <div class="mb-4">
                    <label for="aciklama" class="block text-sm font-medium text-gray-700">Açıklama</label>
                    <textarea name="aciklama" id="aciklama" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required></textarea>
                </div>
                <button type="submit" name="mazeret_gonder" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Gönder</button>
            </form>
        </div>
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Geçmiş Mazeretlerim</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-2 px-4 text-left">Tarih Aralığı</th>
                            <th class="py-2 px-4 text-left">Açıklama</th>
                            <th class="py-2 px-4 text-left">Durum</th>
                            <th class="py-2 px-4 text-left">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mazeretler)): ?>
                            <tr><td colspan="4" class="text-center py-4">Daha önce bildirilmiş mazeretiniz bulunmamaktadır.</td></tr>
                        <?php else: ?>
                            <?php foreach ($mazeretler as $mazeret): ?>
                            <tr class="border-b">
                                <td class="py-2 px-4"><?php echo date('d.m.Y', strtotime($mazeret->baslangic_tarihi)); ?> - <?php echo date('d.m.Y', strtotime($mazeret->bitis_tarihi)); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($mazeret->aciklama); ?></td>
                                <td class="py-2 px-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $mazeret->durum == 'Onaylandı' ? 'bg-green-100 text-green-800' : ($mazeret->durum == 'Reddedildi' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo htmlspecialchars($mazeret->durum); ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4">
                                    <?php if ($mazeret->durum == 'Beklemede'): ?>
                                        <a href="?action=geri_cek&id=<?php echo $mazeret->id; ?>" 
                                           class="text-red-600 hover:text-red-800" 
                                           title="Talebi Geri Çek"
                                           onclick="return confirm('Bu mazeret talebini geri çekmek istediğinizden emin misiniz?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
