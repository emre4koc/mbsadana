<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Lig Yönetimi";
$mesaj = '';

// Yeni lig ekleme
if (isset($_POST['lig_ekle']) && !empty(trim($_POST['ad']))) {
    $stmt = $pdo->prepare("INSERT INTO ligler (ad) VALUES (?)");
    if ($stmt->execute([trim($_POST['ad'])])) {
        $mesaj = ['tip' => 'success', 'icerik' => 'Yeni lig başarıyla eklendi.'];
    } else {
        $mesaj = ['tip' => 'error', 'icerik' => 'Lig eklenirken bir hata oluştu.'];
    }
}

// Lig silme
if (isset($_GET['action']) && $_GET['action'] == 'sil' && isset($_GET['id'])) {
    $lig_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM musabakalar WHERE lig_id = ?");
    $stmt->execute([$lig_id]);
    if ($stmt->fetchColumn() > 0) {
        $mesaj = ['tip' => 'error', 'icerik' => 'Bu lig bir müsabakada kullanıldığı için silinemez.'];
    } else {
        $stmt = $pdo->prepare("DELETE FROM ligler WHERE id = ?");
        $stmt->execute([$lig_id]);
        $mesaj = ['tip' => 'success', 'icerik' => 'Lig başarıyla silindi.'];
    }
}

$ligler = $pdo->query("SELECT * FROM ligler ORDER BY ad ASC")->fetchAll();

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Yeni Lig Ekle</h2>
                <?php if ($mesaj): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($mesaj['icerik']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <label for="ad" class="block text-sm font-medium text-gray-700">Lig Adı</label>
                    <input type="text" name="ad" id="ad" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    <button type="submit" name="lig_ekle" class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Ekle</button>
                </form>
            </div>
        </div>
        <div class="md:col-span-2">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Mevcut Ligler</h2>
                <div class="overflow-y-auto max-h-96">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-4 text-left">Lig Adı</th>
                                <th class="py-2 px-4 text-left">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ligler as $lig): ?>
                            <tr class="border-b">
                                <td class="py-2 px-4"><?php echo htmlspecialchars($lig->ad); ?></td>
                                <td class="py-2 px-4">
                                    <a href="?action=sil&id=<?php echo $lig->id; ?>" onclick="return confirm('Bu ligi silmek istediğinizden emin misiniz?')" class="text-red-600 hover:text-red-800" title="Sil"><i class="fas fa-trash"></i></a>
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
