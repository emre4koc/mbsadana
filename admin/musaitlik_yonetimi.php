<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';
@include_once '../lib/SimpleXLSXGen.php'; // Excel oluşturucu kütüphanesi

$sezon = date('Y');
$sayfa_baslik = "Müsaitlik Yönetimi";
$gunler = ["Cumartesi", "Pazar", "Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma"];
$zamanlar = ["Sabah", "Ogle", "Aksam"];

// Filtreleme için klasmanları al
$secili_klasmanlar = isset($_GET['klasmanlar']) && is_array($_GET['klasmanlar']) ? $_GET['klasmanlar'] : [];
// Filtre seçeneklerini doğrudan users tablosundaki mevcut klasmanlardan çek
$klasmanlar_listesi = $pdo->query("SELECT DISTINCT klasman FROM users WHERE klasman IS NOT NULL AND klasman != '' ORDER BY klasman ASC")->fetchAll(PDO::FETCH_COLUMN);


// --- VERİ HAZIRLAMA FONKSİYONU (YENİ VE DAHA VERİMLİ VERSİYON) ---
function hazirlaMusaitlikRaporu($pdo, $sezon, $secili_klasmanlar) {
    // 1. Adım: Filtreye uygun kullanıcıları ve ID'lerini al
    $user_query_sql = "SELECT id, ad, soyad FROM users";
    $params = [];
    if (!empty($secili_klasmanlar)) {
        $placeholders = implode(',', array_fill(0, count($secili_klasmanlar), '?'));
        // DÜZELTME: 'klasman_id' yerine 'klasman' sütununa göre filtrele
        $user_query_sql .= " WHERE klasman IN ($placeholders)";
        $params = $secili_klasmanlar;
    }
    $user_query_sql .= " ORDER BY ad, soyad";
    
    $users_stmt = $pdo->prepare($user_query_sql);
    $users_stmt->execute($params);
    $users = $users_stmt->fetchAll();

    if (empty($users)) {
        return []; // Filtreye uygun kullanıcı yoksa boş rapor döndür
    }

    $user_ids = array_column($users, 'id');
    $user_ids_placeholders = implode(',', array_fill(0, count($user_ids), '?'));

    // 2. Adım: Sadece bu kullanıcılara ait verileri çek
    $musaitlik_stmt = $pdo->prepare("SELECT user_id, gun, zaman_dilimi, musait FROM musaitlik WHERE sezon = ? AND user_id IN ($user_ids_placeholders)");
    $musaitlik_stmt->execute(array_merge([$sezon], $user_ids));
    $musaitlik_raw = $musaitlik_stmt->fetchAll();

    $notlar_stmt = $pdo->prepare("SELECT user_id, gun, `not` FROM musaitlik_notlari WHERE sezon = ? AND user_id IN ($user_ids_placeholders)");
    $notlar_stmt->execute(array_merge([$sezon], $user_ids));
    $notlar_raw = $notlar_stmt->fetchAll();

    $mazeret_stmt = $pdo->prepare("SELECT user_id, baslangic_tarihi, bitis_tarihi FROM mazeretler WHERE durum = 'Onaylandı' AND bitis_tarihi >= CURDATE() AND user_id IN ($user_ids_placeholders)");
    $mazeret_stmt->execute($user_ids);
    $mazeretler_raw = $mazeret_stmt->fetchAll();

    // 3. Adım: Verileri daha kolay işlemek için yeniden yapılandır
    $musaitlikler = [];
    foreach ($musaitlik_raw as $item) { $musaitlikler[$item->user_id][$item->gun][$item->zaman_dilimi] = $item->musait; }
    $notlar = [];
    foreach ($notlar_raw as $item) { $notlar[$item->user_id][$item->gun] = $item->not; }
    $mazeretler = [];
    foreach ($mazeretler_raw as $item) {
        $baslangic = new DateTime($item->baslangic_tarihi);
        $bitis = new DateTime($item->bitis_tarihi);
        $fark = $bitis->diff($baslangic)->days + 1;
        $mazeretler[$item->user_id][] = date('d.m.Y', strtotime($item->baslangic_tarihi)) . ' - ' . date('d.m.Y', strtotime($item->bitis_tarihi)) . " ({$fark} gün)";
    }

    // 4. Adım: Nihai rapor dizisini oluştur
    $rapor = [];
    foreach ($users as $user) {
        $kullanici_adi = $user->ad . ' ' . $user->soyad;
        $rapor[$kullanici_adi] = [
            'gunler' => [],
            'mazeretler' => implode("\n", $mazeretler[$user->id] ?? [])
        ];
        foreach ($GLOBALS['gunler'] as $gun) {
            $durum_str = '';
            foreach ($GLOBALS['zamanlar'] as $zaman) {
                $durum = $musaitlikler[$user->id][$gun][$zaman] ?? 0;
                $durum_str .= ($durum ? 'E' : 'H') . '-';
            }
            $durum_str = rtrim($durum_str, '-');
            
            $not = $notlar[$user->id][$gun] ?? '';
            $hucre_icerigi = $durum_str;
            if (!empty($not)) { $hucre_icerigi .= " (" . $not . ")"; }
            $rapor[$kullanici_adi]['gunler'][$gun] = $hucre_icerigi;
        }
    }
    return $rapor;
}

// Excel'e Aktarma
if (isset($_GET['export']) && class_exists('Shuchkin\SimpleXLSXGen')) {
    $rapor = hazirlaMusaitlikRaporu($pdo, $sezon, $secili_klasmanlar);
    $excelHeaders = ['Kullanıcı', ...$gunler, 'Onaylanmış Mazeretler'];
    $xlsxData = [$excelHeaders];

    foreach ($rapor as $kullanici_adi => $data) {
        $row = [$kullanici_adi];
        foreach ($gunler as $gun) { $row[] = $data['gunler'][$gun] ?? 'H-H-H'; }
        $row[] = $data['mazeretler'];
        $xlsxData[] = $row;
    }
    $xlsx = Shuchkin\SimpleXLSXGen::fromArray($xlsxData);
    $xlsx->downloadAs('musaitlik_raporu_'.$sezon.'.xlsx');
    exit();
}

// Sayfa Görüntüleme için Verileri Çek
$rapor = hazirlaMusaitlikRaporu($pdo, $sezon, $secili_klasmanlar);
$export_query_string = http_build_query(['export' => 'true', 'klasmanlar' => $secili_klasmanlar]);

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col md:flex-row justify-between md:items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 md:mb-0">Tüm Kullanıcı Müsaitlikleri (<?php echo $sezon; ?>)</h2>
            <a href="?<?php echo $export_query_string; ?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm self-start md:self-center">
                <i class="fas fa-file-excel mr-2"></i>Excel Olarak İndir (.xlsx)
            </a>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg mb-6 border">
            <form method="GET" class="flex flex-col md:flex-row md:items-center md:space-x-4">
                <div>
                    <label for="klasmanlar" class="block text-sm font-medium text-gray-700 mb-1">Klasman Filtresi:</label>
                    <select name="klasmanlar[]" id="klasmanlar" multiple class="border border-gray-300 rounded-md p-2 h-32">
                        <?php foreach($klasmanlar_listesi as $klasman): ?>
                            <option value="<?php echo htmlspecialchars($klasman); ?>" <?php echo in_array($klasman, $secili_klasmanlar) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($klasman); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Birden fazla seçmek için CTRL (Windows) veya Command (Mac) tuşuna basılı tutun.</p>
                </div>
                <div class="mt-4 md:mt-0 self-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">Filtrele</button>
                    <a href="musaitlik_yonetimi.php" class="text-sm text-gray-600 hover:underline ml-2">Filtreyi Temizle</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border text-center text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border">Kullanıcı</th>
                        <?php foreach ($gunler as $gun): ?>
                            <th class="py-2 px-4 border"><?php echo $gun; ?></th>
                        <?php endforeach; ?>
                        <th class="py-2 px-4 border">Onaylanmış Mazeretler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($rapor)): ?>
                        <tr><td colspan="<?php echo count($gunler) + 2; ?>" class="text-center py-4">Bu sezon veya seçilen filtre için girilmiş müsaitlik kaydı bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rapor as $kullanici_adi => $data): ?>
                        <tr class="border-t">
                            <td class="py-2 px-4 border font-semibold text-left"><?php echo htmlspecialchars($kullanici_adi); ?></td>
                            <?php foreach ($gunler as $gun): ?>
                                <td class="py-2 px-4 border">
                                    <?php
                                        $hucre_icerigi = $data['gunler'][$gun] ?? 'H-H-H';
                                        if (preg_match('/^(.*?)\s*\((.*?)\)$/', $hucre_icerigi, $matches)) {
                                            echo '<b>' . htmlspecialchars($matches[1]) . '</b><br><span class="text-xs text-blue-600">' . htmlspecialchars($matches[2]) . '</span>';
                                        } else {
                                            echo '<b>' . htmlspecialchars($hucre_icerigi) . '</b>';
                                        }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="py-2 px-4 border text-left text-xs whitespace-pre-line"><?php echo htmlspecialchars($data['mazeretler']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-xs text-gray-500">
            <p><b>Açıklama:</b> Günlerin altındaki "E" Evet (Müsait), "H" Hayır (Müsait Değil) anlamına gelmektedir. Sıralama S-Ö-A (Sabah-Öğle-Akşam) şeklindedir.</p>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>