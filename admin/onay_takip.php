<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Haftalık Onay Takip";

// Haftanın başlangıç (Cumartesi) ve bitişini (Cuma) hesapla
if (date('w') == 6) { 
    $hafta_baslangic = date('Y-m-d');
} else {
    $hafta_baslangic = date('Y-m-d', strtotime('last Saturday'));
}
$hafta_bitis = date('Y-m-d', strtotime($hafta_baslangic . ' +6 days'));


// Haftalık Müsabakaları ve görevli onay durumlarını çek
$stmt = $pdo->prepare("
    SELECT 
        m.id, m.tarih, m.saat, 
        l.ad as lig_adi, 
        t1.ad as ev_sahibi, 
        t2.ad as misafir,
        CONCAT(h.ad, ' ', h.soyad) as hakem, m.hakem_onay,
        CONCAT(y1.ad, ' ', y1.soyad) as yardimci_1, m.yardimci_1_onay,
        CONCAT(y2.ad, ' ', y2.soyad) as yardimci_2, m.yardimci_2_onay,
        CONCAT(d4.ad, ' ', d4.soyad) as dorduncu_hakem, m.dorduncu_hakem_onay,
        CONCAT(g.ad, ' ', g.soyad) as gozlemci, m.gozlemci_onay
    FROM musabakalar m
    LEFT JOIN ligler l ON m.lig_id = l.id
    LEFT JOIN takimlar t1 ON m.ev_sahibi_id = t1.id
    LEFT JOIN takimlar t2 ON m.misafir_id = t2.id
    LEFT JOIN users h ON m.hakem_id = h.id
    LEFT JOIN users y1 ON m.yardimci_1_id = y1.id
    LEFT JOIN users y2 ON m.yardimci_2_id = y2.id
    LEFT JOIN users d4 ON m.dorduncu_hakem_id = d4.id
    LEFT JOIN users g ON m.gozlemci_id = g.id
    WHERE m.tarih BETWEEN ? AND ? AND m.arsiv = 0
    ORDER BY m.tarih, m.saat
");
$stmt->execute([$hafta_baslangic, $hafta_bitis]);
$musabakalar = $stmt->fetchAll();

// Onay durumunu renklendirmek için yardımcı fonksiyon
function render_onay_durumu($isim, $onay_durumu) {
    if (empty($isim) || $isim == ' ') {
        return '<span class="text-gray-400">-</span>';
    }
    $etiket = $onay_durumu 
        ? '<span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Onaylandı</span>' 
        : '<span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Onay Bekliyor</span>';
    
    return htmlspecialchars($isim) . $etiket;
}

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-1 text-gray-800">Haftalık Görev Onay Takibi</h2>
        <p class="text-sm text-gray-500 mb-4"><?php echo date('d M Y', strtotime($hafta_baslangic)); ?> - <?php echo date('d M Y', strtotime($hafta_bitis)); ?></p>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-3 text-left">Müsabaka</th>
                        <th class="py-2 px-3 text-left">Hakem</th>
                        <th class="py-2 px-3 text-left">1. Yrd.</th>
                        <th class="py-2 px-3 text-left">2. Yrd.</th>
                        <th class="py-2 px-3 text-left">4. Hakem</th>
                        <th class="py-2 px-3 text-left">Gözlemci</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($musabakalar)): ?>
                        <tr><td colspan="6" class="text-center py-4">Bu hafta için görevlendirilmiş müsabaka bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach($musabakalar as $musabaka): ?>
                        <tr>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($musabaka->ev_sahibi . ' - ' . $musabaka->misafir); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('d.m.Y H:i', strtotime($musabaka->tarih . ' ' . $musabaka->saat)); ?></div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap"><?php echo render_onay_durumu($musabaka->hakem, $musabaka->hakem_onay); ?></td>
                            <td class="py-3 px-3 whitespace-nowrap"><?php echo render_onay_durumu($musabaka->yardimci_1, $musabaka->yardimci_1_onay); ?></td>
                            <td class="py-3 px-3 whitespace-nowrap"><?php echo render_onay_durumu($musabaka->yardimci_2, $musabaka->yardimci_2_onay); ?></td>
                            <td class="py-3 px-3 whitespace-nowrap"><?php echo render_onay_durumu($musabaka->dorduncu_hakem, $musabaka->dorduncu_hakem_onay); ?></td>
                            <td class="py-3 px-3 whitespace-nowrap"><?php echo render_onay_durumu($musabaka->gozlemci, $musabaka->gozlemci_onay); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>
