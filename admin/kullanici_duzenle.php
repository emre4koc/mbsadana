<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Kullanıcı Detayları";
$mesaj = '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_admin_id = $_SESSION['user_id'];

if ($user_id === 0) { header('Location: kullanicilar.php'); exit(); }

// Form gönderildi mi?
if (isset($_POST['bilgi_guncelle'])) {
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];
    $klasman = $_POST['klasman'];
    $telefon = preg_replace('/[^0-9]/', '', $_POST['telefon']);
    $lisans_no = trim($_POST['lisans_no']);
    $dogum_tarihi = !empty($_POST['dogum_tarihi']) ? $_POST['dogum_tarihi'] : null;
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $sifre = $_POST['sifre'];

    // E-posta benzersizlik kontrolü
    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check->execute([$email, $user_id]);
    if ($stmt_check->fetch()) {
        $mesaj = ['tip' => 'error', 'icerik' => 'Bu e-posta adresi zaten başka bir kullanıcı tarafından kullanılıyor.'];
    }
    // Telefon format kontrolü
    elseif (!empty($telefon) && (strlen($telefon) != 11 || substr($telefon, 0, 1) !== '0')) {
        $mesaj = ['tip' => 'error', 'icerik' => 'Telefon numarası 11 hane olmalı ve 0 ile başlamalıdır.'];
    } else {
        $sql = "UPDATE users SET ad=?, soyad=?, email=?, rol=?, klasman=?, telefon=?, lisans_no=?, dogum_tarihi=?, aktif=?";
        $params = [$ad, $soyad, $email, $rol, $klasman, $telefon, $lisans_no, $dogum_tarihi, $aktif];
        
        if (!empty($sifre)) {
            $sql .= ", sifre=?";
            $params[] = password_hash($sifre, PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id=?";
        $params[] = $user_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $mesaj = ['tip' => 'success', 'icerik' => 'Kullanıcı bilgileri başarıyla güncellendi.'];
        } else {
            $mesaj = ['tip' => 'error', 'icerik' => 'Güncelleme sırasında bir hata oluştu.'];
        }
    }
}

// Düzenlenecek kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$kullanici = $stmt->fetch();

if (!$kullanici) { header('Location: kullanicilar.php'); exit(); }

// İstatistikleri çek
$ortalama_puan_stmt = $pdo->prepare("SELECT AVG(puan) as ort_puan FROM rapor_detaylari WHERE hakem_id = ?");
$ortalama_puan_stmt->execute([$user_id]);
$ortalama_puan = $ortalama_puan_stmt->fetchColumn();

$lig_gorevleri_stmt = $pdo->prepare("
    SELECT lig_adi, gorev, COUNT(*) as sayi FROM (
        SELECT l.ad as lig_adi, 'Hakem' as gorev FROM musabakalar m JOIN ligler l ON m.lig_id=l.id WHERE m.hakem_id = :id
        UNION ALL SELECT l.ad as lig_adi, '1. Yardımcı' as gorev FROM musabakalar m JOIN ligler l ON m.lig_id=l.id WHERE m.yardimci_1_id = :id
        UNION ALL SELECT l.ad as lig_adi, '2. Yardımcı' as gorev FROM musabakalar m JOIN ligler l ON m.lig_id=l.id WHERE m.yardimci_2_id = :id
        UNION ALL SELECT l.ad as lig_adi, '4. Hakem' as gorev FROM musabakalar m JOIN ligler l ON m.lig_id=l.id WHERE m.dorduncu_hakem_id = :id
        UNION ALL SELECT l.ad as lig_adi, 'Gözlemci' as gorev FROM musabakalar m JOIN ligler l ON m.lig_id=l.id WHERE m.gozlemci_id = :id
    ) as gorevler GROUP BY lig_adi, gorev ORDER BY lig_adi, gorev
");
$lig_gorevleri_stmt->execute(['id' => $user_id]);
$lig_gorevleri = $lig_gorevleri_stmt->fetchAll();

$gorev_gecmisi_stmt = $pdo->prepare("
    SELECT m.id as musabaka_id, m.tarih, m.saat, l.ad as lig_adi, t1.ad as ev_sahibi, t2.ad as misafir,
    CASE
        WHEN m.hakem_id = :user_id THEN 'Hakem'
        WHEN m.yardimci_1_id = :user_id THEN '1. Yardımcı'
        WHEN m.yardimci_2_id = :user_id THEN '2. Yardımcı'
        WHEN m.dorduncu_hakem_id = :user_id THEN '4. Hakem'
        WHEN m.gozlemci_id = :user_id THEN 'Gözlemci'
    END as gorev,
    rd.puan, r.rapor_dosya_yolu
    FROM musabakalar m
    JOIN ligler l ON m.lig_id = l.id
    JOIN takimlar t1 ON m.ev_sahibi_id = t1.id
    JOIN takimlar t2 ON m.misafir_id = t2.id
    LEFT JOIN raporlar r ON m.id = r.musabaka_id
    LEFT JOIN rapor_detaylari rd ON r.id = rd.rapor_id AND rd.hakem_id = :user_id
    WHERE m.hakem_id = :user_id OR m.yardimci_1_id = :user_id OR m.yardimci_2_id = :user_id OR m.dorduncu_hakem_id = :user_id OR m.gozlemci_id = :user_id
    ORDER BY m.tarih DESC, m.saat DESC
");
$gorev_gecmisi_stmt->execute(['user_id' => $user_id]);
$gorev_gecmisi = $gorev_gecmisi_stmt->fetchAll();

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Kullanıcı Bilgilerini Düzenle</h2>
                <?php if ($mesaj): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($mesaj['icerik']); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="bilgi_guncelle" value="1">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">Ad</label>
                            <input type="text" name="ad" value="<?php echo htmlspecialchars($kullanici->ad); ?>" class="w-full mt-1 p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">Soyad</label>
                            <input type="text" name="soyad" value="<?php echo htmlspecialchars($kullanici->soyad); ?>" class="w-full mt-1 p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">E-posta</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($kullanici->email); ?>" class="w-full mt-1 p-2 border rounded" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">Telefon (05xx...)</label>
                            <input type="tel" name="telefon" value="<?php echo htmlspecialchars($kullanici->telefon); ?>" class="w-full mt-1 p-2 border rounded" pattern="0[0-9]{10}" maxlength="11">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">Klasman</label>
                            <input type="text" name="klasman" value="<?php echo htmlspecialchars($kullanici->klasman); ?>" class="w-full mt-1 p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">Lisans No</label>
                            <input type="text" name="lisans_no" value="<?php echo htmlspecialchars($kullanici->lisans_no); ?>" class="w-full mt-1 p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">Doğum Tarihi</label>
                            <input type="date" name="dogum_tarihi" value="<?php echo htmlspecialchars($kullanici->dogum_tarihi); ?>" class="w-full mt-1 p-2 border rounded">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">Rol</label>
                            <select name="rol" class="w-full mt-1 p-2 border rounded">
                                <option value="1" <?php if($kullanici->rol == 1) echo 'selected'; ?>>Yönetici</option>
                                <option value="2" <?php if($kullanici->rol == 2) echo 'selected'; ?>>Hakem</option>
                                <option value="3" <?php if($kullanici->rol == 3) echo 'selected'; ?>>Gözlemci</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium">Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
                            <input type="password" name="sifre" class="w-full mt-1 p-2 border rounded">
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="aktif" value="1" class="form-checkbox" <?php if($kullanici->aktif) echo 'checked'; ?>>
                                <span class="ml-2 text-gray-700">Hesap Aktif</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-between items-center">
                        <a href="kullanicilar.php" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300">Geri</a>
                        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Kaydet</button>
                    </div>
                </form>
            </div>
            
            <?php if ($kullanici->id !== $current_admin_id): ?>
            <div class="bg-white p-6 rounded-lg shadow-md border-t-4 border-red-500">
                <h2 class="text-lg font-semibold text-red-700 mb-2">Tehlikeli Alan</h2>
                <p class="text-sm text-gray-600 mb-4">Bu kullanıcıyı silmek, kullanıcıyla ilişkili tüm verileri (görevler, mazeretler, bildirimler vb.) kalıcı olarak kaldırır. Bu işlem geri alınamaz.</p>
                <a href="kullanicilar.php?action=sil&id=<?php echo $kullanici->id; ?>" 
                   class="w-full block text-center bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700"
                   onclick="return confirm('Bu kullanıcıyı ve tüm verilerini kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                   Hesabı Kalıcı Olarak Sil
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Genel İstatistikler</h2>
                <div class="text-center">
                    <p class="text-gray-500 text-sm font-medium">Gözlemci Puan Ortalaması</p>
                    <p class="text-4xl font-bold text-blue-600 mt-2"><?php echo $ortalama_puan ? number_format($ortalama_puan, 2) : 'N/A'; ?></p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Lig Bazında Görev Dağılımı</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-4 text-left">Lig</th><th class="py-2 px-4 text-left">Görev</th><th class="py-2 px-4 text-left">Maç Sayısı</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($lig_gorevleri)): ?>
                                <tr><td colspan="3" class="text-center py-4">Henüz görev alınmamış.</td></tr>
                            <?php else: ?>
                                <?php foreach($lig_gorevleri as $gorev): ?>
                                <tr class="border-b">
                                    <td class="py-2 px-4 font-medium"><?php echo htmlspecialchars($gorev->lig_adi); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($gorev->gorev); ?></td>
                                    <td class="py-2 px-4 font-bold"><?php echo htmlspecialchars($gorev->sayi); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Görev Geçmişi</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-4 text-left">Tarih</th><th class="py-2 px-4 text-left">Lig</th><th class="py-2 px-4 text-left">Müsabaka</th><th class="py-2 px-4 text-left">Görevi</th><th class="py-2 px-4 text-left">Puan</th><th class="py-2 px-4 text-left">Rapor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($gorev_gecmisi)): ?>
                                <tr><td colspan="6" class="text-center py-4">Bu kullanıcının görev geçmişi bulunmamaktadır.</td></tr>
                            <?php else: ?>
                                <?php foreach($gorev_gecmisi as $gorev): ?>
                                <tr class="border-b">
                                    <td class="py-2 px-4"><?php echo date('d.m.Y', strtotime($gorev->tarih)); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($gorev->lig_adi); ?></td>
                                    <td class="py-2 px-4"><a href="../musabaka_detay.php?id=<?php echo $gorev->musabaka_id; ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($gorev->ev_sahibi . ' - ' . $gorev->misafir); ?></a></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($gorev->gorev); ?></td>
                                    <td class="py-2 px-4 font-bold"><?php echo htmlspecialchars($gorev->puan ?? '-'); ?></td>
                                    <td class="py-2 px-4">
                                        <?php if(!empty($gorev->rapor_dosya_yolu)): ?>
                                            <a href="../<?php echo htmlspecialchars($gorev->rapor_dosya_yolu); ?>" target="_blank" class="text-green-600 hover:text-green-800" title="Raporu İndir"><i class="fas fa-file-excel fa-lg"></i></a>
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>
