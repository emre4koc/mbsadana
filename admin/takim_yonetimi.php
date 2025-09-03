<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Takım Yönetimi";
$mesaj = '';

// Tekli takım ekleme
if (isset($_POST['takim_ekle']) && !empty(trim($_POST['ad']))) {
    $stmt = $pdo->prepare("INSERT INTO takimlar (ad) VALUES (?)");
    if ($stmt->execute([trim($_POST['ad'])])) {
        $mesaj = ['tip' => 'success', 'icerik' => 'Yeni takım başarıyla eklendi.'];
    } else {
        $mesaj = ['tip' => 'error', 'icerik' => 'Takım eklenirken bir hata oluştu.'];
    }
}

// Toplu takım ekleme
if (isset($_POST['toplu_takim_ekle']) && !empty(trim($_POST['takim_listesi']))) {
    $takim_listesi = trim($_POST['takim_listesi']);
    $takimlar_arr = explode("\n", str_replace("\r", "", $takim_listesi));
    $eklenen_sayisi = 0;
    $stmt = $pdo->prepare("INSERT INTO takimlar (ad) VALUES (?)");
    foreach ($takimlar_arr as $takim_adi) {
        $takim_adi = trim($takim_adi);
        if (!empty($takim_adi)) {
            try {
                if ($stmt->execute([$takim_adi])) {
                    $eklenen_sayisi++;
                }
            } catch (PDOException $e) {
                // Aynı takım tekrar eklenmeye çalışılırsa hatayı yoksay
            }
        }
    }
    $mesaj = ['tip' => 'success', 'icerik' => "{$eklenen_sayisi} adet takım başarıyla eklendi."];
}


// Takım silme
if (isset($_GET['action']) && $_GET['action'] == 'sil' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM musabakalar WHERE ev_sahibi_id = ? OR misafir_id = ?");
    $stmt->execute([$_GET['id'], $_GET['id']]);
    if ($stmt->fetchColumn() > 0) {
        $mesaj = ['tip' => 'error', 'icerik' => 'Bu takım bir müsabakada kullanıldığı için silinemez.'];
    } else {
        $stmt = $pdo->prepare("DELETE FROM takimlar WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $mesaj = ['tip' => 'success', 'icerik' => 'Takım başarıyla silindi.'];
    }
}

$takimlar = $pdo->query("SELECT * FROM takimlar ORDER BY ad ASC")->fetchAll();

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Yeni Takım Ekle</h2>
                <?php if ($mesaj && (isset($_POST['takim_ekle']) || isset($_GET['action']))): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($mesaj['icerik']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <label for="ad" class="block text-sm font-medium text-gray-700">Takım Adı</label>
                    <input type="text" name="ad" id="ad" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    <button type="submit" name="takim_ekle" class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Ekle</button>
                </form>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Toplu Takım Ekle</h2>
                 <?php if ($mesaj && isset($_POST['toplu_takim_ekle'])): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($mesaj['icerik']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <label for="takim_listesi" class="block text-sm font-medium text-gray-700">Takım Adları (Her satıra bir takım)</label>
                    <textarea name="takim_listesi" id="takim_listesi" rows="5" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"></textarea>
                    <button type="submit" name="toplu_takim_ekle" class="mt-4 w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">Toplu Ekle</button>
                </form>
            </div>
        </div>
        <div class="md:col-span-2">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Mevcut Takımlar</h2>
                <div class="overflow-y-auto max-h-[500px]">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-4 text-left">Takım Adı</th>
                                <th class="py-2 px-4 text-left">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($takimlar as $takim): ?>
                            <tr class="border-b">
                                <td class="py-2 px-4"><?php echo htmlspecialchars($takim->ad); ?></td>
                                <td class="py-2 px-4">
                                    <a href="?action=sil&id=<?php echo $takim->id; ?>" onclick="return confirm('Bu takımı silmek istediğinizden emin misiniz?')" class="text-red-600 hover:text-red-800" title="Sil"><i class="fas fa-trash"></i></a>
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
