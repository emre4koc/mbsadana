<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Mazeret Talepleri";
$mesaj = '';

// Sayfa yüklendiğinde session'daki mesajı al ve temizle
if (isset($_SESSION['mesaj'])) {
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
}

// Mazeret durumu güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mazeret_guncelle'])) {
    $mazeret_id = (int)$_POST['mazeret_id'];
    $yeni_durum = $_POST['durum'];
    $red_gerekcesi = ($yeni_durum === 'Reddedildi') ? trim($_POST['red_gerekcesi']) : null;

    // Gerekçeyle reddetme kontrolü
    if ($yeni_durum === 'Reddedildi' && empty($red_gerekcesi)) {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Bir mazereti reddetmek için gerekçe belirtmelisiniz.'];
    } else {
        $stmt = $pdo->prepare("UPDATE mazeretler SET durum = ?, red_gerekcesi = ? WHERE id = ?");
        if ($stmt->execute([$yeni_durum, $red_gerekcesi, $mazeret_id])) {
            
            // Kullanıcıya bildirim gönder
            $talep_sahibi_stmt = $pdo->prepare("SELECT user_id FROM mazeretler WHERE id = ?");
            $talep_sahibi_stmt->execute([$mazeret_id]);
            $user_id = $talep_sahibi_stmt->fetchColumn();

            if ($user_id) {
                if ($yeni_durum === 'Onaylandı') {
                    $bildirim_mesaji = "Mazeret talebiniz onaylandı.";
                } else if ($yeni_durum === 'Reddedildi') {
                    $bildirim_mesaji = "Mazeret talebiniz reddedildi. Gerekçe: " . $red_gerekcesi;
                } else {
                    $bildirim_mesaji = "Mazeret talebinizin durumu 'Beklemede' olarak güncellendi.";
                }
                
                $bildirim_linki = "/mazeret_bildir.php";
                $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)")->execute([$user_id, $bildirim_mesaji, $bildirim_linki]);
            }
            
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Mazeret durumu başarıyla güncellendi.'];
        } else {
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Güncelleme sırasında bir hata oluştu.'];
        }
    }
    header("Location: mazeretler.php");
    exit();
}

// Tüm mazeretleri çek
$mazeretler = $pdo->query("
    SELECT m.*, CONCAT(u.ad, ' ', u.soyad) as kullanici_adi
    FROM mazeretler m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.durum = 'Beklemede' DESC, m.olusturma_tarihi DESC
")->fetchAll();

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Tüm Mazeret Talepleri</h2>
        <?php if ($mesaj): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($mesaj['icerik']); ?>
            </div>
        <?php endif; ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left">Kullanıcı</th>
                        <th class="py-2 px-4 text-left">Tarih Aralığı</th>
                        <th class="py-2 px-4 text-left">Açıklama</th>
                        <th class="py-2 px-4 text-left">Durum / Gerekçe</th>
                        <th class="py-2 px-4 text-left">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mazeretler)): ?>
                        <tr><td colspan="5" class="text-center py-4">Gösterilecek mazeret talebi bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach($mazeretler as $mazeret): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4 font-medium"><?php echo htmlspecialchars($mazeret->kullanici_adi); ?></td>
                            <td class="py-2 px-4"><?php echo date('d.m.Y', strtotime($mazeret->baslangic_tarihi)); ?> - <?php echo date('d.m.Y', strtotime($mazeret->bitis_tarihi)); ?></td>
                            <td class="py-2 px-4 max-w-xs break-words"><?php echo htmlspecialchars($mazeret->aciklama); ?></td>
                            <td class="py-2 px-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $mazeret->durum == 'Onaylandı' ? 'bg-green-100 text-green-800' : ($mazeret->durum == 'Reddedildi' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                    <?php echo htmlspecialchars($mazeret->durum); ?>
                                </span>
                                <?php if ($mazeret->durum == 'Reddedildi' && !empty($mazeret->red_gerekcesi)): ?>
                                    <p class="text-xs text-gray-500 mt-1"><strong>Gerekçe:</strong> <?php echo htmlspecialchars($mazeret->red_gerekcesi); ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4">
                                <form method="POST" class="space-y-2">
                                    <input type="hidden" name="mazeret_id" value="<?php echo $mazeret->id; ?>">
                                    <select name="durum" class="w-full border p-1 rounded-md text-sm" onchange="toggleGerekce(this)">
                                        <option value="Beklemede" <?php if($mazeret->durum == 'Beklemede') echo 'selected'; ?>>Beklemede</option>
                                        <option value="Onaylandı" <?php if($mazeret->durum == 'Onaylandı') echo 'selected'; ?>>Onayla</option>
                                        <option value="Reddedildi" <?php if($mazeret->durum == 'Reddedildi') echo 'selected'; ?>>Reddet</option>
                                    </select>
                                    <textarea name="red_gerekcesi" rows="2" class="w-full border p-1 rounded-md text-sm <?php if($mazeret->durum != 'Reddedildi') echo 'hidden'; ?>" placeholder="Reddetme gerekçesi..."></textarea>
                                    <button type="submit" name="mazeret_guncelle" class="w-full bg-gray-800 text-white py-1 px-2 text-xs rounded-md hover:bg-gray-700">Güncelle</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function toggleGerekce(selectElement) {
    const gerekceTextarea = selectElement.nextElementSibling;
    if (selectElement.value === 'Reddedildi') {
        gerekceTextarea.classList.remove('hidden');
    } else {
        gerekceTextarea.classList.add('hidden');
    }
}
</script>
<?php include '../templates/footer.php'; ?>
