<?php
require_once 'config/session_check.php';
require_once 'config/db.php';

$sayfa_baslik = "Takvim";
$user_id = $_SESSION['user_id'];

// Takvim için ay ve yıl belirleme
// DÜZELTME: date('m') fonksiyonundan gelen değeri (int) ile tam sayıya çeviriyoruz.
$ay = isset($_GET['ay']) ? (int)$_GET['ay'] : (int)date('m');
$yil = isset($_GET['yil']) ? (int)$_GET['yil'] : date('Y');

// Ayın ilk günü ve gün sayısı
$ilk_gun_timestamp = mktime(0, 0, 0, $ay, 1, $yil);
$gun_sayisi = date('t', $ilk_gun_timestamp);
$ilk_gun_no = date('w', $ilk_gun_timestamp); // 0:Pazar, 1:Pzt...
if ($ilk_gun_no == 0) $ilk_gun_no = 7; // Pazartesi 1 olsun

// Önceki ve sonraki ay linkleri
$onceki_ay = $ay - 1;
$onceki_yil = $yil;
if ($onceki_ay == 0) {
    $onceki_ay = 12;
    $onceki_yil--;
}
$sonraki_ay = $ay + 1;
$sonraki_yil = $yil;
if ($sonraki_ay == 13) {
    $sonraki_ay = 1;
    $sonraki_yil++;
}

// Kullanıcının o aydaki görevlerini çek
$stmt = $pdo->prepare("
    SELECT DAY(tarih) as gun, GROUP_CONCAT(CONCAT(t1.ad, ' vs ', t2.ad) SEPARATOR '<br>') as maclar
    FROM musabakalar m
    JOIN takimlar t1 ON m.ev_sahibi_id = t1.id
    JOIN takimlar t2 ON m.misafir_id = t2.id
    WHERE 
        (m.hakem_id = :user_id OR m.yardimci_1_id = :user_id OR m.yardimci_2_id = :user_id OR m.dorduncu_hakem_id = :user_id OR m.gozlemci_id = :user_id)
        AND YEAR(tarih) = :yil AND MONTH(tarih) = :ay
    GROUP BY DAY(tarih)
");
$stmt->execute(['user_id' => $user_id, 'yil' => $yil, 'ay' => $ay]);
$gorevler_raw = $stmt->fetchAll();
$gorevler = [];
foreach ($gorevler_raw as $gorev) {
    $gorevler[$gorev->gun] = $gorev->maclar;
}

// DİZİ ANAHTARLARI 1'DEN BAŞLADIĞI İÇİN 0. İNDEKSİ BOŞ BIRAKIYORUZ
$aylar = [1 => "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];

include 'templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <a href="?ay=<?php echo $onceki_ay; ?>&yil=<?php echo $onceki_yil; ?>" class="text-blue-600 font-bold">&lt; Önceki Ay</a>
            <h2 class="text-xl font-semibold text-gray-800"><?php echo $aylar[$ay] . ' ' . $yil; ?></h2>
            <a href="?ay=<?php echo $sonraki_ay; ?>&yil=<?php echo $sonraki_yil; ?>" class="text-blue-600 font-bold">Sonraki Ay &gt;</a>
        </div>

        <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200">
            <!-- Gün Başlıkları -->
            <div class="text-center font-semibold py-2 bg-gray-100">Pzt</div>
            <div class="text-center font-semibold py-2 bg-gray-100">Sal</div>
            <div class="text-center font-semibold py-2 bg-gray-100">Çar</div>
            <div class="text-center font-semibold py-2 bg-gray-100">Per</div>
            <div class="text-center font-semibold py-2 bg-gray-100">Cum</div>
            <div class="text-center font-semibold py-2 bg-gray-100">Cmt</div>
            <div class="text-center font-semibold py-2 bg-gray-100">Paz</div>

            <?php
            // Ayın ilk gününden önceki boş kutular
            for ($i = 1; $i < $ilk_gun_no; $i++) {
                echo '<div class="bg-gray-50 h-32"></div>';
            }

            // Ayın günleri
            for ($gun = 1; $gun <= $gun_sayisi; $gun++) {
                $bugun_class = (date('Y-n-j') == "$yil-$ay-$gun") ? 'bg-blue-100' : 'bg-white';
                $gorev_class = isset($gorevler[$gun]) ? 'border-2 border-green-500' : '';
                echo "<div class='p-2 h-32 overflow-y-auto {$bugun_class} {$gorev_class}'>";
                echo "<div class='font-bold text-gray-800'>{$gun}</div>";
                if (isset($gorevler[$gun])) {
                    echo "<div class='text-xs text-gray-700 mt-1'>{$gorevler[$gun]}</div>";
                }
                echo "</div>";
            }
            
            // Ayın son gününden sonraki boş kutular
            $toplam_kutu = $ilk_gun_no - 1 + $gun_sayisi;
            $kalan_kutu = (7 - ($toplam_kutu % 7)) % 7;
            for ($i = 0; $i < $kalan_kutu; $i++) {
                echo '<div class="bg-gray-50 h-32"></div>';
            }
            ?>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
