<?php
require_once 'config/session_check.php';
require_once 'config/db.php';

date_default_timezone_set('Europe/Istanbul');

$user_id = $_SESSION['user_id'];
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'tumu';

// Sorgu ve parametreler için başlangıç değerleri
$sql_base = "
    SELECT m.id, m.tarih, m.saat, m.durum, m.arsiv, l.ad as lig_adi, s.ad as stadyum_adi,
           t1.ad as ev_sahibi, t2.ad as misafir, r.rapor_dosya_yolu,
           CONCAT(h.ad, ' ', h.soyad) as hakem,
           CONCAT(y1.ad, ' ', y1.soyad) as yardimci_1,
           CONCAT(y2.ad, ' ', y2.soyad) as yardimci_2,
           CONCAT(d4.ad, ' ', d4.soyad) as dorduncu_hakem,
           CONCAT(g.ad, ' ', g.soyad) as gozlemci,
           (CASE 
                WHEN m.hakem_id = :user_id THEN 'Hakem'
                WHEN m.yardimci_1_id = :user_id THEN '1. Yardımcı Hakem'
                WHEN m.yardimci_2_id = :user_id THEN '2. Yardımcı Hakem'
                WHEN m.dorduncu_hakem_id = :user_id THEN '4. Hakem'
                WHEN m.gozlemci_id = :user_id THEN 'Gözlemci'
                ELSE ''
           END) as gorev,
           (CASE
                WHEN m.hakem_id = :user_id THEN m.hakem_onay
                WHEN m.yardimci_1_id = :user_id THEN m.yardimci_1_onay
                WHEN m.yardimci_2_id = :user_id THEN m.yardimci_2_onay
                WHEN m.dorduncu_hakem_id = :user_id THEN m.dorduncu_hakem_onay
                WHEN m.gozlemci_id = :user_id THEN m.gozlemci_onay
           END) as onay_durumu
    FROM musabakalar m
    JOIN ligler l ON m.lig_id = l.id
    LEFT JOIN stadyumlar s ON m.stadyum_id = s.id
    JOIN takimlar t1 ON m.ev_sahibi_id = t1.id
    JOIN takimlar t2 ON m.misafir_id = t2.id
    LEFT JOIN users h ON m.hakem_id = h.id
    LEFT JOIN users y1 ON m.yardimci_1_id = y1.id
    LEFT JOIN users y2 ON m.yardimci_2_id = y2.id
    LEFT JOIN users d4 ON m.dorduncu_hakem_id = d4.id
    LEFT JOIN users g ON m.gozlemci_id = g.id
    LEFT JOIN raporlar r ON m.id = r.musabaka_id
    WHERE (m.hakem_id = :user_id OR m.yardimci_1_id = :user_id OR m.yardimci_2_id = :user_id OR m.dorduncu_hakem_id = :user_id OR m.gozlemci_id = :user_id)
";
$params = [':user_id' => $user_id];
$sayfa_aciklama = "";

// Filtreye göre sorguyu ve başlığı ayarla
if ($filtre == 'haftalik') {
    $sayfa_baslik = "Bu Haftaki Görevlerim";
    
    if (date('w') == 6) { // Cumartesi
        $hafta_baslangic = date('Y-m-d');
    } else {
        $hafta_baslangic = date('Y-m-d', strtotime('last Saturday'));
    }
    $hafta_bitis = date('Y-m-d', strtotime($hafta_baslangic . ' +6 days'));
    
    $sql_base .= " AND m.tarih BETWEEN :hafta_baslangic AND :hafta_bitis";
    $params[':hafta_baslangic'] = $hafta_baslangic;
    $params[':hafta_bitis'] = $hafta_bitis;
    
    $sayfa_aciklama = "(" . date('d M', strtotime($hafta_baslangic)) . ' - ' . date('d M', strtotime($hafta_bitis)) . ")";
} else {
    $sayfa_baslik = "Tüm Görevlerim";
}

$sql_final = $sql_base . " ORDER BY m.tarih DESC, m.saat DESC";

$stmt = $pdo->prepare($sql_final);
$stmt->execute($params);
$gorevler = $stmt->fetchAll();

include 'templates/header.php';
?>

<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-800"><?php echo $sayfa_baslik; ?> <span class="text-gray-500 font-normal"><?php echo $sayfa_aciklama; ?></span></h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">Tarih - Saat</th>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">Lig</th>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">Müsabaka</th>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">Stadyum</th>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">Göreviniz</th>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">Hakem Ekibi / Gözlemci</th>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">Durum</th>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">Onay Durumu</th>
                        <th class="py-1 px-2 text-left text-sm font-semibold text-gray-600">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php if (empty($gorevler)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">Bu kritere uygun görev bulunmamaktadır.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($gorevler as $gorev): ?>
                        <?php
                            $musabaka_zamani = new DateTime($gorev->tarih . ' ' . $gorev->saat);
                            $su_an = new DateTime();
                            $mac_basladi_mi = $su_an >= $musabaka_zamani;
                            $is_archived = $gorev->arsiv == 1;
                        ?>
                        <tr class="border-b hover:bg-gray-50 <?php if ($is_archived) echo 'bg-gray-100 opacity-70'; ?>">
                            <td class="py-1 px-2"><?php echo date('d.m.Y', strtotime($gorev->tarih)); ?> - <?php echo date('H:i', strtotime($gorev->saat)); ?></td>
                            <td class="py-1 px-2"><?php echo htmlspecialchars($gorev->lig_adi); ?></td>
                            <td class="py-1 px-2 font-medium"><?php echo htmlspecialchars($gorev->ev_sahibi); ?> - <?php echo htmlspecialchars($gorev->misafir); ?></td>
                            <td class="py-1 px-2"><?php echo htmlspecialchars($gorev->stadyum_adi ?? '-'); ?></td>
                            <td class="py-1 px-2"><span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded"><?php echo htmlspecialchars($gorev->gorev); ?></span></td>
                            <td class="py-1 px-2 text-xs leading-tight">
                                <div><span class="font-semibold text-gray-800">H:</span> <?php echo htmlspecialchars($gorev->hakem ?? '-'); ?></div>
                                <div><span class="font-semibold text-gray-800">Y1:</span> <?php echo htmlspecialchars($gorev->yardimci_1 ?? '-'); ?></div>
                                <div><span class="font-semibold text-gray-800">Y2:</span> <?php echo htmlspecialchars($gorev->yardimci_2 ?? '-'); ?></div>
                                <div><span class="font-semibold text-gray-800">4:</span> <?php echo htmlspecialchars($gorev->dorduncu_hakem ?? '-'); ?></div>
                                <div class="border-t mt-1 pt-1"><span class="font-semibold text-gray-800">G:</span> <?php echo htmlspecialchars($gorev->gozlemci ?? '-'); ?></div>
                            </td>
                            <td class="py-1 px-2">
                                <?php echo htmlspecialchars($gorev->durum); ?>
                                <?php if ($is_archived): ?>
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium ml-2 px-2.5 py-0.5 rounded">Arşivlendi</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-1 px-2">
                                <?php if ($gorev->onay_durumu): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Onaylandı</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Onay Bekliyor</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-1 px-2">
                                <a href="musabaka_detay.php?id=<?php echo $gorev->id; ?>" class="text-blue-600 hover:text-blue-800 mr-2" title="Detayları Gör"><i class="fas fa-eye"></i>İncele</a>
                                <?php if (!$is_archived): ?>
                                    <?php if ($gorev->gorev === 'Gözlemci' && $mac_basladi_mi): ?>
                                        <a href="rapor_ekle.php?musabaka_id=<?php echo $gorev->id; ?>" class="text-green-600 hover:text-green-800" title="Rapor Ekle/Düzenle"><i class="fas fa-file-alt"></i>Rapor</a>
                                    <?php elseif ($gorev->gorev === 'Hakem' && $mac_basladi_mi): ?>
                                         <a href="musabaka_detay.php?id=<?php echo $gorev->id; ?>&action=skor_gir" class="text-purple-600 hover:text-purple-800" title="Sonuç Gir"><i class="fas fa-futbol"></i>Durum</a>
                                    <?php endif; ?>
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

<?php include 'templates/footer.php'; ?>