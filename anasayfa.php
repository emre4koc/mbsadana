<?php
require_once 'config/session_check.php';
require_once 'config/db.php';

date_default_timezone_set('Europe/Istanbul');

$sayfa_baslik = "Anasayfa";

// Duyuruları çek
$duyurular = $pdo->query("SELECT * FROM duyurular WHERE arsiv = 0 ORDER BY olusturma_tarihi DESC LIMIT 10")->fetchAll();

// Haftanın başlangıç ve bitişini hesapla
if (date('w') == 6) { $hafta_baslangic = date('Y-m-d'); } 
else { $hafta_baslangic = date('Y-m-d', strtotime('last Saturday')); }
$hafta_bitis = date('Y-m-d', strtotime($hafta_baslangic . ' +6 days'));

// Haftalık Müsabaka Tebligatı için SADECE AKTİF müsabakaları çek
$stmt = $pdo->prepare("
    SELECT m.*, l.ad as lig_adi, s.ad as stadyum_adi, 
           t1.ad as ev_sahibi, t2.ad as misafir,
           CONCAT(h.ad, ' ', h.soyad) as hakem,
           CONCAT(y1.ad, ' ', y1.soyad) as yardimci_1,
           CONCAT(y2.ad, ' ', y2.soyad) as yardimci_2,
           CONCAT(d4.ad, ' ', d4.soyad) as dorduncu_hakem,
           CONCAT(g.ad, ' ', g.soyad) as gozlemci
    FROM musabakalar m
    LEFT JOIN ligler l ON m.lig_id = l.id
    LEFT JOIN stadyumlar s ON m.stadyum_id = s.id
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
$haftalik_musabakalar = $stmt->fetchAll();

// Kullanıcı istatistikleri (arşivlenmiş maçlar dahil)
$user_id = $_SESSION['user_id'];
$haftalik_gorev_sayisi_stmt = $pdo->prepare("SELECT COUNT(*) FROM musabakalar WHERE (hakem_id = :user_id OR yardimci_1_id = :user_id OR yardimci_2_id = :user_id OR dorduncu_hakem_id = :user_id OR gozlemci_id = :user_id) AND tarih BETWEEN :start_date AND :end_date");
$haftalik_gorev_sayisi_stmt->execute(['user_id' => $user_id, 'start_date' => $hafta_baslangic, 'end_date' => $hafta_bitis]);
$haftalik_gorev_sayisi = $haftalik_gorev_sayisi_stmt->fetchColumn();

$toplam_gorev_sayisi_stmt = $pdo->prepare("SELECT COUNT(*) FROM musabakalar WHERE (hakem_id = :user_id OR yardimci_1_id = :user_id OR yardimci_2_id = :user_id OR dorduncu_hakem_id = :user_id OR gozlemci_id = :user_id)");
$toplam_gorev_sayisi_stmt->execute(['user_id' => $user_id]);
$toplam_gorev_sayisi = $toplam_gorev_sayisi_stmt->fetchColumn();

// Türkçe ay isimleri için dizi
$turkce_aylar = ["", "Oca", "Şub", "Mar", "Nis", "May", "Haz", "Tem", "Ağu", "Eyl", "Eki", "Kas", "Ara"];
$baslangic_gun = date('d', strtotime($hafta_baslangic));
$baslangic_ay = $turkce_aylar[date('n', strtotime($hafta_baslangic))];
$bitis_gun = date('d', strtotime($hafta_bitis));
$bitis_ay = $turkce_aylar[date('n', strtotime($hafta_bitis))];
$haftalik_tarih_araligi = "$baslangic_gun $baslangic_ay - $bitis_gun $bitis_ay";

include 'templates/header.php';
?>
<div class="container mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <a href="<?php echo BASE_URL; ?>/gorevlerim.php?filtre=haftalik" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl hover:scale-105 transition-transform duration-200 ease-in-out">
            <h3 class="text-gray-500 text-sm font-medium">Bu Haftaki Görev Sayınız</h3>
            <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $haftalik_gorev_sayisi; ?></p>
        </a>
        <a href="<?php echo BASE_URL; ?>/gorevlerim.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl hover:scale-105 transition-transform duration-200 ease-in-out">
            <h3 class="text-gray-500 text-sm font-medium">Bu Sezonki Toplam Görevleriniz</h3>
            <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $toplam_gorev_sayisi; ?></p>
        </a>
        <div class="bg-white p-6 rounded-lg shadow-md flex flex-col h-48">
            <h3 class="text-gray-500 text-sm font-medium flex-shrink-0">Duyuru Panosu</h3>
            <div class="flex-grow overflow-y-auto mt-2 pr-2">
                <ul class="space-y-2 text-sm">
                    <?php if (empty($duyurular)): ?>
                        <li class="text-gray-500">Gösterilecek duyuru yok.</li>
                    <?php else: ?>
                        <?php foreach($duyurular as $duyuru): ?>
                        <li class="p-2 bg-gray-50 rounded hover:bg-gray-100 cursor-pointer duyuru-item" 
                            data-baslik="<?php echo htmlspecialchars($duyuru->baslik); ?>" 
                            data-icerik="<?php echo nl2br(htmlspecialchars($duyuru->icerik)); ?>"
                            data-tarih="<?php echo date('d.m.Y', strtotime($duyuru->olusturma_tarihi)); ?>"
                            data-ilgili-tarih="<?php echo !empty($duyuru->tarih) ? date('d.m.Y', strtotime($duyuru->tarih)) : ''; ?>">
                            <p class="font-semibold text-gray-800 pointer-events-none"><?php echo htmlspecialchars($duyuru->baslik); ?></p>
                            <p class="text-gray-600 truncate pointer-events-none"><?php echo htmlspecialchars($duyuru->icerik); ?></p>
                            <?php if (!empty($duyuru->tarih)): ?>
                                <p class="text-xs text-red-600 mt-1 pointer-events-none">İlgili Tarih: <?php echo date('d.m.Y', strtotime($duyuru->tarih)); ?></p>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Haftalık Müsabaka Tebligatı (<?php echo $haftalik_tarih_araligi; ?>)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="py-2 px-4 text-left text-sm font-semibold text-gray-600">Tarih - Saat</th>
                        <th class="py-2 px-4 text-left text-sm font-semibold text-gray-600">Lig</th>
                        <th class="py-2 px-4 text-left text-sm font-semibold text-gray-600">Müsabaka</th>
                        <th class="py-2 px-4 text-left text-sm font-semibold text-gray-600">Stadyum</th>
                        <th class="py-2 px-4 text-left text-sm font-semibold text-gray-600">Hakem Ekibi</th>
                        <th class="py-2 px-4 text-left text-sm font-semibold text-gray-600">Gözlemci</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php if (empty($haftalik_musabakalar)): ?>
                        <tr><td colspan="6" class="text-center py-4">Bu hafta için gösterilecek müsabaka bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach($haftalik_musabakalar as $musabaka): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4"><?php echo date('d.m.Y', strtotime($musabaka->tarih)); ?> - <?php echo date('H:i', strtotime($musabaka->saat)); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($musabaka->lig_adi); ?></td>
                            <td class="py-2 px-4 font-medium"><?php echo htmlspecialchars($musabaka->ev_sahibi); ?> - <?php echo htmlspecialchars($musabaka->misafir); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($musabaka->stadyum_adi); ?></td>
                            <td class="py-2 px-4 text-xs leading-tight">
                                <div><span class="font-semibold text-gray-800">H:</span> <?php echo htmlspecialchars($musabaka->hakem ?? '-'); ?></div>
                                <div><span class="font-semibold text-gray-800">Y1:</span> <?php echo htmlspecialchars($musabaka->yardimci_1 ?? '-'); ?></div>
                                <div><span class="font-semibold text-gray-800">Y2:</span> <?php echo htmlspecialchars($musabaka->yardimci_2 ?? '-'); ?></div>
                                <div><span class="font-semibold text-gray-800">4:</span> <?php echo htmlspecialchars($musabaka->dorduncu_hakem ?? '-'); ?></div>
                            </td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($musabaka->gozlemci ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="duyuru-modal" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative">
        <button id="duyuru-modal-close" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        <h3 id="duyuru-modal-baslik" class="text-xl font-bold text-gray-800 mb-2"></h3>
        <p id="duyuru-modal-tarih" class="text-xs text-gray-500 mb-4"></p>
        <div id="duyuru-modal-icerik" class="text-gray-700 max-h-96 overflow-y-auto"></div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>