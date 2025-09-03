<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Stadyum Yönetimi";
$mesaj = '';

// Tekli stadyum ekleme
if (isset($_POST['stadyum_ekle']) && !empty(trim($_POST['ad']))) {
    $stmt = $pdo->prepare("INSERT INTO stadyumlar (ad) VALUES (?)");
    if ($stmt->execute([trim($_POST['ad'])])) {
        $mesaj = ['tip' => 'success', 'icerik' => 'Yeni stadyum başarıyla eklendi.'];
    } else {
        $mesaj = ['tip' => 'error', 'icerik' => 'Stadyum eklenirken bir hata oluştu.'];
    }
}

// Toplu stadyum ekleme
if (isset($_POST['toplu_stadyum_ekle']) && !empty(trim($_POST['stadyum_listesi']))) {
    $stadyum_listesi = trim($_POST['stadyum_listesi']);
    $stadyumlar_arr = explode("\n", str_replace("\r", "", $stadyum_listesi));
    $eklenen_sayisi = 0;
    $stmt = $pdo->prepare("INSERT INTO stadyumlar (ad) VALUES (?)");
    foreach ($stadyumlar_arr as $stadyum_adi) {
        $stadyum_adi = trim($stadyum_adi);
        if (!empty($stadyum_adi)) {
            try {
                if ($stmt->execute([$stadyum_adi])) {
                    $eklenen_sayisi++;
                }
            } catch (PDOException $e) {
                // Hata olursa yoksay, muhtemelen zaten var.
            }
        }
    }
    $mesaj = ['tip' => 'success', 'icerik' => "{$eklenen_sayisi} adet stadyum başarıyla eklendi."];
}


// Stadyum silme
if (isset($_GET['action']) && $_GET['action'] == 'sil' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM musabakalar WHERE stadyum_id = ?");
    $stmt->execute([$_GET['id']]);
    if ($stmt->fetchColumn() > 0) {
        $mesaj = ['tip' => 'error', 'icerik' => 'Bu stadyum bir müsabakada kullanıldığı için silinemez.'];
    } else {
        $stmt = $pdo->prepare("DELETE FROM stadyumlar WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $mesaj = ['tip' => 'success', 'icerik' => 'Stadyum başarıyla silindi.'];
    }
}

$stadyumlar = $pdo->query("SELECT * FROM stadyumlar ORDER BY ad ASC")->fetchAll();

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Yeni Stadyum Ekle</h2>
                 <?php if ($mesaj && (isset($_POST['stadyum_ekle']) || (isset($_GET['action']) && $_GET['action'] == 'sil'))): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($mesaj['icerik']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <label for="ad" class="block text-sm font-medium text-gray-700">Stadyum Adı</label>
                    <input type="text" name="ad" id="ad" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    <button type="submit" name="stadyum_ekle" class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Ekle</button>
                </form>
            </div>
             <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Toplu Stadyum Ekle</h2>
                 <?php if ($mesaj && isset($_POST['toplu_stadyum_ekle'])): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($mesaj['icerik']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <label for="stadyum_listesi" class="block text-sm font-medium text-gray-700">Stadyum Adları (Her satıra bir stadyum)</label>
                    <textarea name="stadyum_listesi" id="stadyum_listesi" rows="5" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"></textarea>
                    <button type="submit" name="toplu_stadyum_ekle" class="mt-4 w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">Toplu Ekle</button>
                </form>
            </div>
        </div>
        <div class="md:col-span-2">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Mevcut Stadyumlar</h2>
                <div class="overflow-y-auto max-h-[500px]">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-4 text-left">Stadyum Adı</th>
                                <th class="py-2 px-4 text-left">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stadyumlar as $stadyum): ?>
                            <tr class="border-b">
                                <td class="py-2 px-4"><?php echo htmlspecialchars($stadyum->ad); ?></td>
                                <td class="py-2 px-4">
                                    <a href="?action=sil&id=<?php echo $stadyum->id; ?>" onclick="return confirm('Bu stadyumu silmek istediğinizden emin misiniz?')" class="text-red-600 hover:text-red-800" title="Sil"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>
