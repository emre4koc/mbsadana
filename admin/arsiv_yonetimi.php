<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Arşiv Yönetimi";
$mesaj = '';

// Arşivden çıkarma işlemi
if (isset($_GET['action']) && $_GET['action'] == 'arsivden_cikar' && isset($_GET['id'])) {
    $musabaka_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE musabakalar SET arsiv = 0 WHERE id = ?");
    if ($stmt->execute([$musabaka_id])) {
        $mesaj = ['tip' => 'success', 'icerik' => 'Müsabaka başarıyla arşivden çıkarıldı.'];
    } else {
        $mesaj = ['tip' => 'error', 'icerik' => 'İşlem sırasında bir hata oluştu.'];
    }
}


// Arşivlenmiş müsabakaları listele
$arsivlenmis_musabakalar = $pdo->query("
    SELECT m.*, l.ad as lig_adi, t1.ad as ev_sahibi, t2.ad as misafir
    FROM musabakalar m
    JOIN ligler l ON m.lig_id = l.id
    JOIN takimlar t1 ON m.ev_sahibi_id = t1.id
    JOIN takimlar t2 ON m.misafir_id = t2.id
    WHERE m.arsiv = 1
    ORDER BY m.tarih DESC, m.saat DESC
")->fetchAll();

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Arşivlenmiş Müsabakalar</h2>
        <?php if ($mesaj): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($mesaj['icerik']); ?>
            </div>
        <?php endif; ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left">Tarih</th>
                        <th class="py-2 px-4 text-left">Müsabaka</th>
                        <th class="py-2 px-4 text-left">Lig</th>
                        <th class="py-2 px-4 text-left">Durum</th>
                        <th class="py-2 px-4 text-left">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($arsivlenmis_musabakalar)): ?>
                        <tr><td colspan="5" class="text-center py-4">Arşivde müsabaka bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach($arsivlenmis_musabakalar as $musabaka): ?>
                        <tr class="border-b bg-gray-50">
                            <td class="py-2 px-4"><?php echo date('d.m.Y H:i', strtotime("$musabaka->tarih $musabaka->saat")); ?></td>
                            <td class="py-2 px-4 font-medium"><?php echo htmlspecialchars($musabaka->ev_sahibi . ' - ' . $musabaka->misafir); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($musabaka->lig_adi); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($musabaka->durum); ?></td>
                            <td class="py-2 px-4">
                                <a href="?action=arsivden_cikar&id=<?php echo $musabaka->id; ?>" class="text-green-600 hover:text-green-800" title="Arşivden Çıkar" onclick="return confirm('Bu müsabakayı arşivden çıkarmak istediğinizden emin misiniz?')">
                                    <i class="fas fa-box-open"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>
