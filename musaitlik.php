<?php
require_once 'config/session_check.php';
require_once 'config/db.php';

$sayfa_baslik = "Müsaitlik Bildirimi";
$user_id = $_SESSION['user_id'];
$sezon = date('Y');
$mesaj = '';

// Sayfa yüklendiğinde session'daki mesajı al ve temizle
if (isset($_SESSION['mesaj'])) {
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
}

// Talebi geri çekme
if (isset($_GET['action']) && $_GET['action'] == 'geri_cek' && isset($_GET['id'])) {
    $talep_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM musaitlik_talepleri WHERE id = ? AND user_id = ? AND durum = 'Beklemede'");
    if ($stmt->execute([$talep_id, $user_id])) {
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Değişiklik talebiniz başarıyla geri çekildi.'];
    } else {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'İşlem sırasında bir hata oluştu.'];
    }
    header("Location: musaitlik.php");
    exit();
}

// Değişiklik talebi oluşturma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['talep_gonder'])) {
    $gerekce = trim($_POST['gerekce']);
    $yeni_musaitlik_data = $_POST['yeni_musaitlik_data'];
    $admin_id = $pdo->query("SELECT id FROM users WHERE rol = 1 LIMIT 1")->fetchColumn();

    $pdo->prepare("DELETE FROM musaitlik_talepleri WHERE user_id = ? AND sezon = ? AND durum = 'Beklemede'")->execute([$user_id, $sezon]);

    $stmt = $pdo->prepare("INSERT INTO musaitlik_talepleri (user_id, sezon, gerekce, yeni_musaitlik_data) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $sezon, $gerekce, $yeni_musaitlik_data])) {
        if ($admin_id) {
            $bildirim_mesaji = $_SESSION['user_ad'] . " " . $_SESSION['user_soyad'] . " müsaitlik değişikliği talebinde bulundu.";
            $bildirim_linki = "/admin/musaitlik_talepleri.php";
            $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)")->execute([$admin_id, $bildirim_mesaji, $bildirim_linki]);
        }
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Değişiklik talebiniz başarıyla iletildi.'];
    } else {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Talep gönderilirken bir hata oluştu.'];
    }
    header("Location: musaitlik.php");
    exit();
}

$mevcut_musaitlik_stmt = $pdo->prepare("SELECT * FROM musaitlik WHERE user_id = ? AND sezon = ?");
$mevcut_musaitlik_stmt->execute([$user_id, $sezon]);
$mevcut_musaitlik_raw = $mevcut_musaitlik_stmt->fetchAll();

$mevcut_musaitlik = [];
foreach ($mevcut_musaitlik_raw as $item) {
    $mevcut_musaitlik[$item->gun][$item->zaman_dilimi] = $item->musait;
}

$mevcut_notlar_stmt = $pdo->prepare("SELECT gun, `not` FROM musaitlik_notlari WHERE user_id = ? AND sezon = ?");
$mevcut_notlar_stmt->execute([$user_id, $sezon]);
$mevcut_notlar = $mevcut_notlar_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$bekleyen_talep_stmt = $pdo->prepare("SELECT * FROM musaitlik_talepleri WHERE user_id = ? AND sezon = ? AND durum = 'Beklemede'");
$bekleyen_talep_stmt->execute([$user_id, $sezon]);
$talep = $bekleyen_talep_stmt->fetch();

$gunler = ["Cumartesi", "Pazar", "Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma"];
$zamanlar = ["Sabah" => "08:00-12:00", "Ogle" => "12:00-18:00", "Aksam" => "18:00-22:00"];

include 'templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-2 text-gray-800">Müsaitlik Tablosu (<?php echo $sezon; ?> Sezonu)</h2>
        <p class="text-sm text-gray-600 mb-4">Görev almak için müsait olduğunuz zaman dilimlerini işaretleyin ve değişiklik talebi gönderin.</p>
        
        <?php if ($mesaj): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($mesaj['icerik']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="musaitlik-formu" onsubmit="return confirm('Değişiklik talebi göndermek istediğinizden emin misiniz?');">
            <input type="hidden" name="yeni_musaitlik_data" id="yeni_musaitlik_data">
            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border">Gün</th>
                            <?php foreach ($zamanlar as $zaman => $saat): ?>
                                <th class="py-2 px-4 border"><?php echo $zaman; ?><br><span class="text-xs font-normal text-gray-500"><?php echo $saat; ?></span></th>
                            <?php endforeach; ?>
                            <th class="py-2 px-4 border">Diğer (Açıklama)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gunler as $gun): ?>
                        <tr class="text-center">
                            <td class="py-2 px-4 border font-semibold"><?php echo $gun; ?></td>
                            <?php foreach (array_keys($zamanlar) as $zaman): ?>
                            <td class="py-2 px-4 border">
                                <input type="checkbox" data-gun="<?php echo $gun; ?>" data-zaman="<?php echo $zaman; ?>" class="h-6 w-6 musaitlik-checkbox" 
                                <?php if (isset($mevcut_musaitlik[$gun][$zaman]) && $mevcut_musaitlik[$gun][$zaman] == 1) echo 'checked'; ?>>
                            </td>
                            <?php endforeach; ?>
                            <td class="py-2 px-4 border">
                                <textarea data-gun="<?php echo $gun; ?>" rows="1" class="w-full border p-1 rounded text-sm musaitlik-not"><?php echo htmlspecialchars($mevcut_notlar[$gun] ?? ''); ?></textarea>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 border-t pt-6">
                <?php if ($talep): ?>
                    <div class="p-4 text-sm text-yellow-800 rounded-lg bg-yellow-100">
                        <strong class="block text-base">Beklemede olan bir değişiklik talebiniz var.</strong>
                        <p class="mt-1">Gerekçeniz: <?php echo htmlspecialchars($talep->gerekce); ?></p>
                        <a href="?action=geri_cek&id=<?php echo $talep->id; ?>" 
                           onclick="return confirm('Bu değişiklik talebini geri çekmek istediğinizden emin misiniz?');"
                           class="inline-block mt-2 bg-red-600 text-white text-xs font-bold py-1 px-3 rounded hover:bg-red-700">
                           Talebi Geri Çek
                        </a>
                    </div>
                <?php else: ?>
                    <h3 class="text-lg font-semibold text-gray-800">Değişiklik Talebi Gönder</h3>
                    <p class="text-sm text-gray-600 mb-4">Yukarıdaki tabloda yaptığınız değişikliklerin geçerli olması için gerekçenizi belirterek talep oluşturun.</p>
                    <textarea name="gerekce" rows="3" class="w-full border p-2 rounded" placeholder="Lütfen değişiklik talebinizin nedenini açıklayın..." required></textarea>
                    <button type="submit" name="talep_gonder" class="mt-2 bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-700">Değişiklik Talebi Gönder</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('musaitlik-formu').addEventListener('submit', function(e) {
    if (e.submitter && e.submitter.name === 'talep_gonder') {
        const musaitlikData = {
            musaitlik: {},
            notlar: {}
        };

        document.querySelectorAll('.musaitlik-checkbox').forEach(function(checkbox) {
            const gun = checkbox.dataset.gun;
            const zaman = checkbox.dataset.zaman;
            if (!musaitlikData.musaitlik[gun]) {
                musaitlikData.musaitlik[gun] = {};
            }
            musaitlikData.musaitlik[gun][zaman] = checkbox.checked ? 1 : 0;
        });

        document.querySelectorAll('.musaitlik-not').forEach(function(textarea) {
            const gun = textarea.dataset.gun;
            if (textarea.value.trim() !== '') {
                musaitlikData.notlar[gun] = textarea.value.trim();
            }
        });

        document.getElementById('yeni_musaitlik_data').value = JSON.stringify(musaitlikData);
    }
});
</script>
<?php include 'templates/footer.php'; ?>
