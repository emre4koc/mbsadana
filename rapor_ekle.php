<?php
require_once 'config/session_check.php';
require_once 'config/db.php';

if ($_SESSION['user_rol'] != 3) { header("Location: anasayfa.php"); exit(); }

$sayfa_baslik = "Gözlemci Raporu Ekle";
$musabaka_id = isset($_GET['musabaka_id']) ? (int)$_GET['musabaka_id'] : 0;
$gozlemci_id = $_SESSION['user_id'];
$mesaj = '';

// Müsabaka bilgilerini çek
$stmt = $pdo->prepare("
    SELECT m.*, 
           CONCAT(h.ad, ' ', h.soyad) as hakem, h.id as hakem_id,
           CONCAT(y1.ad, ' ', y1.soyad) as yardimci_1, y1.id as yardimci_1_id,
           CONCAT(y2.ad, ' ', y2.soyad) as yardimci_2, y2.id as yardimci_2_id,
           CONCAT(d4.ad, ' ', d4.soyad) as dorduncu_hakem, d4.id as dorduncu_hakem_id
    FROM musabakalar m
    LEFT JOIN users h ON m.hakem_id = h.id
    LEFT JOIN users y1 ON m.yardimci_1_id = y1.id
    LEFT JOIN users y2 ON m.yardimci_2_id = y2.id
    LEFT JOIN users d4 ON m.dorduncu_hakem_id = d4.id
    WHERE m.id = ? AND m.gozlemci_id = ?
");
$stmt->execute([$musabaka_id, $gozlemci_id]);
$musabaka = $stmt->fetch();

if (!$musabaka) {
    die("Bu müsabaka için yetkiniz yok veya müsabaka bulunamadı.");
}

// ARŞİV KONTROLÜ
if ($musabaka->arsiv == 1) {
    include 'templates/header.php';
    echo '<div class="container mx-auto"><div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert"><p class="font-bold">Uyarı</p><p>Bu müsabaka arşivlendiği için rapor üzerinde değişiklik yapamazsınız.</p><a href="/mbs/gorevlerim.php" class="mt-2 inline-block text-blue-600 hover:underline">Görevlerime Geri Dön</a></div></div>';
    include 'templates/footer.php';
    exit();
}

// Form gönderildiyse raporu kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $rapor_dosya_yolu = null;
        if (isset($_FILES['rapor_dosyasi']) && $_FILES['rapor_dosyasi']['error'] == 0) {
            $hedef_klasor = 'public/uploads/raporlar/';
            if (!is_dir($hedef_klasor)) { mkdir($hedef_klasor, 0777, true); }
            $dosya_adi = time() . '_' . basename($_FILES['rapor_dosyasi']['name']);
            $rapor_dosya_yolu = $hedef_klasor . $dosya_adi;
            move_uploaded_file($_FILES['rapor_dosyasi']['tmp_name'], $rapor_dosya_yolu);
        }

        $stmt = $pdo->prepare("SELECT id FROM raporlar WHERE musabaka_id = ?");
        $stmt->execute([$musabaka_id]);
        $rapor = $stmt->fetch();

        if ($rapor) {
            $rapor_id = $rapor->id;
            if ($rapor_dosya_yolu) {
                $stmt = $pdo->prepare("UPDATE raporlar SET rapor_dosya_yolu = ? WHERE id = ?");
                $stmt->execute([$rapor_dosya_yolu, $rapor_id]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO raporlar (musabaka_id, gozlemci_id, rapor_dosya_yolu) VALUES (?, ?, ?)");
            $stmt->execute([$musabaka_id, $gozlemci_id, $rapor_dosya_yolu]);
            $rapor_id = $pdo->lastInsertId();
        }

        $hakemler_dizisi = [];
        if ($musabaka->hakem_id) $hakemler_dizisi[] = ['id' => $musabaka->hakem_id];
        if ($musabaka->yardimci_1_id) $hakemler_dizisi[] = ['id' => $musabaka->yardimci_1_id];
        if ($musabaka->yardimci_2_id) $hakemler_dizisi[] = ['id' => $musabaka->yardimci_2_id];
        if ($musabaka->dorduncu_hakem_id) $hakemler_dizisi[] = ['id' => $musabaka->dorduncu_hakem_id];

        foreach ($hakemler_dizisi as $hakem) {
            $hakem_id = $hakem['id'];
            $puan = str_replace(',', '.', $_POST['puan'][$hakem_id]);
            $not = $_POST['not'][$hakem_id];
            if ($puan > 10.0) $puan = 10.0;
            if ($puan < 0) $puan = 0.0;

            $stmt = $pdo->prepare("SELECT id FROM rapor_detaylari WHERE rapor_id = ? AND hakem_id = ?");
            $stmt->execute([$rapor_id, $hakem_id]);
            $detay = $stmt->fetch();

            if ($detay) {
                $stmt = $pdo->prepare("UPDATE rapor_detaylari SET puan = ?, `not` = ? WHERE id = ?");
                $stmt->execute([$puan, $not, $detay->id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO rapor_detaylari (rapor_id, hakem_id, puan, `not`) VALUES (?, ?, ?, ?)");
                $stmt->execute([$rapor_id, $hakem_id, $puan, $not]);
            }
        }
        $pdo->commit();
        $mesaj = ['tip' => 'success', 'icerik' => 'Rapor başarıyla kaydedildi.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $mesaj = ['tip' => 'error', 'icerik' => 'Hata: ' . $e->getMessage()];
    }
}

// Hakemleri ve mevcut puanları çek
$hakemler = [];
if ($musabaka->hakem_id) $hakemler[] = ['id' => $musabaka->hakem_id, 'ad' => $musabaka->hakem, 'gorev' => 'Hakem'];
if ($musabaka->yardimci_1_id) $hakemler[] = ['id' => $musabaka->yardimci_1_id, 'ad' => $musabaka->yardimci_1, 'gorev' => '1. Yardımcı Hakem'];
if ($musabaka->yardimci_2_id) $hakemler[] = ['id' => $musabaka->yardimci_2_id, 'ad' => $musabaka->yardimci_2, 'gorev' => '2. Yardımcı Hakem'];
if ($musabaka->dorduncu_hakem_id) $hakemler[] = ['id' => $musabaka->dorduncu_hakem_id, 'ad' => $musabaka->dorduncu_hakem, 'gorev' => '4. Hakem'];

$mevcut_puanlar = [];
$stmt = $pdo->prepare("SELECT rd.* FROM rapor_detaylari rd JOIN raporlar r ON rd.rapor_id = r.id WHERE r.musabaka_id = ?");
$stmt->execute([$musabaka_id]);
$detaylar = $stmt->fetchAll();
foreach($detaylar as $detay) {
    $mevcut_puanlar[$detay->hakem_id] = ['puan' => $detay->puan, 'not' => $detay->not];
}

include 'templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Gözlemci Raporu</h2>
        <p class="text-gray-600 mb-4">Müsabaka: <?php echo $musabaka->skor ?? 'Sonuç Girilmedi'; ?></p>

        <?php if ($mesaj): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($mesaj['icerik']); ?>
            </div>
        <?php endif; ?>

        <form action="rapor_ekle.php?musabaka_id=<?php echo $musabaka_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-6 border p-4 rounded-md">
                <label for="rapor_dosyasi" class="block text-gray-700 text-sm font-bold mb-2">Rapor Dosyası (Excel)</label>
                <input type="file" name="rapor_dosyasi" id="rapor_dosyasi" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                <p class="mt-1 text-sm text-gray-500">Sadece .xls veya .xlsx dosyaları yükleyebilirsiniz.</p>
            </div>

            <h3 class="text-lg font-semibold mb-4 text-gray-700">Hakem Değerlendirmesi</h3>
            <div class="space-y-4">
                <?php foreach ($hakemler as $hakem): ?>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center border p-4 rounded-md">
                    <div class="md:col-span-4">
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($hakem['ad']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($hakem['gorev']); ?></p>
                    </div>
                    <div class="md:col-span-2">
                         <label class="block text-gray-700 text-sm font-bold mb-1">Puan (0.0 - 10.0)</label>
                         <input type="text" inputmode="decimal" maxlength="4" name="puan[<?php echo $hakem['id']; ?>]" value="<?php echo htmlspecialchars($mevcut_puanlar[$hakem['id']]['puan'] ?? '8.4'); ?>" class="puan-input shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="md:col-span-6">
                        <label class="block text-gray-700 text-sm font-bold mb-1">Kısa Bilgi Notu</label>
                        <textarea name="not[<?php echo $hakem['id']; ?>]" rows="2" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($mevcut_puanlar[$hakem['id']]['not'] ?? ''); ?></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Raporu Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
