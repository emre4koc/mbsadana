<?php
require_once 'config/session_check.php';
require_once 'config/db.php';

$sayfa_baslik = "Rehber";

// --- FİLTRELEME ve ARAMA ---
$arama_terimi = isset($_GET['arama']) ? trim($_GET['arama']) : '';
$filtre_klasman = isset($_GET['filtre_klasman']) ? trim($_GET['filtre_klasman']) : '';

// Klasmanları çek (filtre dropdown için)
$klasmanlar = $pdo->query("SELECT ad FROM klasmanlar ORDER BY ad ASC")->fetchAll(PDO::FETCH_COLUMN);

// SQL sorgusunu hazırla
$sql = "
    SELECT ad, soyad, email, telefon, klasman, rol, lisans_no, dogum_tarihi 
    FROM users 
    WHERE aktif = 1 AND rol != 1
";

$params = [];
if (!empty($arama_terimi)) {
    $sql .= " AND CONCAT(ad, ' ', soyad) LIKE :arama";
    $params[':arama'] = "%$arama_terimi%";
}
if (!empty($filtre_klasman)) {
    $sql .= " AND klasman = :klasman";
    $params[':klasman'] = $filtre_klasman;
}

// Özel sıralama mantığı
$sql .= " ORDER BY 
    FIELD(klasman, 
        'Üst Klasman Hakemi', 
        'Üst Klasman Yardımcı Hakemi', 
        'Klasman Hakemi', 
        'Klasman Yardımcı Hakemi', 
        'Bölgesel Hakem', 
        'Bölgesel Yardımcı Hakem', 
        'İl Hakemi', 
        'Aday Hakem', 
        'KLASMAN GÖZLEMCİSİ', 
        'BÖLGESEL GÖZLEMCİ', 
        'İL GÖZLEMCİSİ'
    ), ad, soyad";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kullanicilar = $stmt->fetchAll();


function getRoleName($roleId) {
    return $roleId == 2 ? 'Hakem' : 'Gözlemci';
}

include 'templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Kullanıcı Rehberi</h2>
            <!-- FİLTRELEME FORMU -->
            <form method="GET" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <input type="text" name="arama" placeholder="İsme göre ara..." value="<?php echo htmlspecialchars($arama_terimi); ?>" class="border p-2 rounded-md w-full sm:w-auto">
                <select name="filtre_klasman" class="border p-2 rounded-md w-full sm:w-auto">
                    <option value="">Tüm Klasmanlar</option>
                    <?php foreach ($klasmanlar as $klasman): ?>
                        <option value="<?php echo htmlspecialchars($klasman); ?>" <?php if ($filtre_klasman === $klasman) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($klasman); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md">Filtrele</button>
                <a href="rehber.php" class="bg-gray-200 text-gray-800 text-center py-2 px-4 rounded-md">Temizle</a>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ad Soyad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klasman / Lisans</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-posta</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(empty($kullanicilar)): ?>
                        <tr><td colspan="4" class="text-center py-4">Filtreye uygun kullanıcı bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php foreach ($kullanicilar as $kullanici): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($kullanici->ad . ' ' . $kullanici->soyad); ?></div>
                                <div class="text-sm text-gray-500"><?php echo getRoleName($kullanici->rol); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div><?php echo htmlspecialchars($kullanici->klasman); ?></div>
                                <div class="text-xs">L.No: <?php echo htmlspecialchars($kullanici->lisans_no ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if (!empty($kullanici->telefon)): ?>
                                    <a href="tel:<?php echo htmlspecialchars($kullanici->telefon); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($kullanici->telefon); ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                 <?php if (!empty($kullanici->email)): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($kullanici->email); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($kullanici->email); ?></a>
                                <?php else: ?>
                                    -
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
