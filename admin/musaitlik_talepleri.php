<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Müsaitlik Değişiklik Talepleri";
$mesaj = '';

// Sayfa yüklendiğinde session'daki mesajı al ve temizle
if (isset($_SESSION['mesaj'])) {
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
}

// Talepleri onayla/reddet (Toplu veya Tekli)
if ((isset($_POST['action']) && isset($_POST['talep_ids'])) || (isset($_GET['action']) && isset($_GET['id']))) {
    $islem = $_POST['action'] ?? $_GET['action'];
    $talep_ids = $_POST['talep_ids'] ?? [$_GET['id']];
    if (!is_array($talep_ids)) $talep_ids = [$talep_ids];
    
    $islenen_talep_sayisi = 0;
    
    if (!empty($talep_ids)) {
        foreach ($talep_ids as $talep_id) {
            $talep_id = (int)$talep_id;
            $talep_stmt = $pdo->prepare("SELECT * FROM musaitlik_talepleri WHERE id = ? AND durum = 'Beklemede'");
            $talep_stmt->execute([$talep_id]);
            $talep = $talep_stmt->fetch();

            if (!$talep) continue;

            if ($islem === 'onayla') {
                $yeni_data = json_decode($talep->yeni_musaitlik_data, true);
                if ($yeni_data) {
                    $pdo->prepare("DELETE FROM musaitlik WHERE user_id = ? AND sezon = ?")->execute([$talep->user_id, $talep->sezon]);
                    $stmt_musaitlik = $pdo->prepare("INSERT INTO musaitlik (user_id, gun, zaman_dilimi, musait, sezon) VALUES (?, ?, ?, ?, ?)");
                    if(isset($yeni_data['musaitlik'])) {
                        foreach ($yeni_data['musaitlik'] as $gun => $zamanlar) {
                            foreach ($zamanlar as $zaman => $musait) {
                                $stmt_musaitlik->execute([$talep->user_id, $gun, $zaman, $musait, $talep->sezon]);
                            }
                        }
                    }
                    
                    $pdo->prepare("DELETE FROM musaitlik_notlari WHERE user_id = ? AND sezon = ?")->execute([$talep->user_id, $talep->sezon]);
                    $stmt_not = $pdo->prepare("INSERT INTO musaitlik_notlari (user_id, sezon, gun, `not`) VALUES (?, ?, ?, ?)");
                     if (!empty($yeni_data['notlar'])) {
                        foreach ($yeni_data['notlar'] as $gun => $not) {
                            $stmt_not->execute([$talep->user_id, $talep->sezon, $gun, $not]);
                        }
                    }
                }
                $pdo->prepare("UPDATE musaitlik_talepleri SET durum = 'Onaylandı', yanit_tarihi = NOW() WHERE id = ?")->execute([$talep_id]);
                $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)")->execute([$talep->user_id, "Müsaitlik değişikliği talebiniz onaylandı.", "/musaitlik.php"]);
                $islenen_talep_sayisi++;

            } elseif ($islem === 'reddet') {
                 $pdo->prepare("UPDATE musaitlik_talepleri SET durum = 'Reddedildi', yanit_tarihi = NOW() WHERE id = ?")->execute([$talep_id]);
                 $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)")->execute([$talep->user_id, "Müsaitlik değişikliği talebiniz reddedildi.", "/musaitlik.php"]);
                 $islenen_talep_sayisi++;
            }
        }
        if ($islenen_talep_sayisi > 0) {
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => $islenen_talep_sayisi . ' adet talep başarıyla işlendi.'];
        }
    }
    header("Location: musaitlik_talepleri.php");
    exit();
}


$talepler = $pdo->query("
    SELECT t.*, CONCAT(u.ad, ' ', u.soyad) as kullanici_adi
    FROM musaitlik_talepleri t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.durum = 'Beklemede' DESC, t.talep_tarihi DESC
")->fetchAll();

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Tüm Talepler</h2>
        <?php if ($mesaj): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($mesaj['icerik']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <button type="submit" name="action" value="onayla" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm" onclick="return confirm('Seçilen talepleri onaylamak istediğinizden emin misiniz?');">
                    <i class="fas fa-check-double mr-2"></i>Seçilenleri Onayla
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 w-10"><input type="checkbox" id="select-all"></th>
                            <th class="py-2 px-4 text-left">Kullanıcı</th>
                            <th class="py-2 px-4 text-left">Talep Zamanı</th>
                            <th class="py-2 px-4 text-left">Gerekçe</th>
                            <th class="py-2 px-4 text-left">Durum</th>
                            <th class="py-2 px-4 text-left">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($talepler)): ?>
                            <tr><td colspan="6" class="text-center py-4">Gösterilecek talep bulunmamaktadır.</td></tr>
                        <?php else: ?>
                            <?php foreach($talepler as $talep): ?>
                            <tr class="border-b">
                                <td class="p-2">
                                    <?php if ($talep->durum == 'Beklemede'): ?>
                                    <input type="checkbox" name="talep_ids[]" value="<?php echo $talep->id; ?>" class="talep-checkbox">
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 font-medium"><?php echo htmlspecialchars($talep->kullanici_adi); ?></td>
                                <td class="py-2 px-4 text-sm text-gray-500"><?php echo date('d.m.Y H:i', strtotime($talep->talep_tarihi)); ?></td>
                                <td class="py-2 px-4 max-w-sm break-words"><?php echo htmlspecialchars($talep->gerekce); ?></td>
                                <td class="py-2 px-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $talep->durum == 'Onaylandı' ? 'bg-green-100 text-green-800' : ($talep->durum == 'Reddedildi' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo htmlspecialchars($talep->durum); ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4 whitespace-nowrap">
                                    <?php if ($talep->durum == 'Beklemede'): ?>
                                        <a href="?action=onayla&id=<?php echo $talep->id; ?>" onclick="return confirm('Bu talebi onaylamak istediğinizden emin misiniz?');" class="text-green-600 hover:text-green-800 mr-3" title="Onayla"><i class="fas fa-check-circle fa-lg"></i></a>
                                        <a href="?action=reddet&id=<?php echo $talep->id; ?>" onclick="return confirm('Bu talebi reddetmek istediğinizden emin misiniz?');" class="text-red-600 hover:text-red-800" title="Reddet"><i class="fas fa-times-circle fa-lg"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('select-all').addEventListener('click', function(event) {
    const checkboxes = document.querySelectorAll('.talep-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = event.target.checked;
    });
});
</script>
<?php include '../templates/footer.php'; ?>
