<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Kullanıcı Yönetimi";
$mesaj = '';
$current_admin_id = $_SESSION['user_id'];

// --- İŞLEM BLOKLARI ---

// KULLANICI SİLME İŞLEMİ
if (isset($_GET['action']) && $_GET['action'] == 'sil' && isset($_GET['id'])) {
    $user_to_delete_id = (int)$_GET['id'];
    if ($user_to_delete_id === $current_admin_id) {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Kendi hesabınızı silemezsiniz.'];
    } else {
        try {
            $pdo->beginTransaction();
            // Kullanıcının görevlerini null yap
            $pdo->prepare("UPDATE musabakalar SET hakem_id = NULL WHERE hakem_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("UPDATE musabakalar SET yardimci_1_id = NULL WHERE yardimci_1_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("UPDATE musabakalar SET yardimci_2_id = NULL WHERE yardimci_2_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("UPDATE musabakalar SET dorduncu_hakem_id = NULL WHERE dorduncu_hakem_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("UPDATE musabakalar SET gozlemci_id = NULL WHERE gozlemci_id = ?")->execute([$user_to_delete_id]);
            // Kullanıcıya ait diğer verileri sil
            $pdo->prepare("DELETE FROM bildirimler WHERE user_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("DELETE FROM mazeretler WHERE user_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("DELETE FROM musaitlik WHERE user_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("DELETE FROM musaitlik_notlari WHERE user_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("DELETE FROM musaitlik_talepleri WHERE user_id = ?")->execute([$user_to_delete_id]);
            $pdo->prepare("DELETE FROM rapor_detaylari WHERE hakem_id = ?")->execute([$user_to_delete_id]);
            // Son olarak kullanıcıyı sil
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_to_delete_id]);
            $pdo->commit();
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Kullanıcı ve ilişkili tüm verileri başarıyla silindi.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Silme işlemi sırasında bir hata oluştu: ' . $e->getMessage()];
        }
    }
    header("Location: kullanicilar.php");
    exit();
}


// Yetki değiştirme
if (isset($_GET['action']) && in_array($_GET['action'], ['yetki_ver', 'yetki_al']) && isset($_GET['id'])) {
    $user_to_change_id = (int)$_GET['id'];
    if ($user_to_change_id === $current_admin_id) {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Kendi yetkinizi değiştiremezsiniz.'];
    } else {
        $new_rol = null;
        if ($_GET['action'] === 'yetki_ver') {
            $new_rol = 1;
            $mesaj_text = 'Kullanıcıya yönetici yetkisi verildi.';
        } elseif ($_GET['action'] === 'yetki_al') {
            $new_rol = 2;
            $mesaj_text = 'Kullanıcının yönetici yetkisi alındı.';
        }

        if ($new_rol) {
            $stmt = $pdo->prepare("UPDATE users SET rol = ? WHERE id = ?");
            if ($stmt->execute([$new_rol, $user_to_change_id])) {
                $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => $mesaj_text];
            } else {
                $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'İşlem sırasında bir hata oluştu.'];
            }
        }
    }
    header("Location: kullanicilar.php");
    exit();
}

if (isset($_SESSION['mesaj'])) {
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
}

// Manuel kullanıcı ekleme
if(isset($_POST['kullanici_ekle'])) {
    $ad = trim($_POST['ad']);
    $soyad = trim($_POST['soyad']);
    $email = trim($_POST['email']);
    $sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $klasman = trim($_POST['klasman']);
    $telefon = trim($_POST['telefon']);
    $lisans_no = trim($_POST['lisans_no']);
    $dogum_tarihi = !empty($_POST['dogum_tarihi']) ? $_POST['dogum_tarihi'] : null;

    $stmt = $pdo->prepare("INSERT INTO users (ad, soyad, email, sifre, rol, klasman, telefon, lisans_no, dogum_tarihi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if($stmt->execute([$ad, $soyad, $email, $sifre, $rol, $klasman, $telefon, $lisans_no, $dogum_tarihi])) {
        $mesaj = ['tip' => 'success', 'icerik' => 'Yeni kullanıcı başarıyla eklendi.'];
    } else {
        $mesaj = ['tip' => 'error', 'icerik' => 'Kullanıcı eklenirken bir hata oluştu. E-posta adresi daha önce kullanılmış olabilir.'];
    }
}

// Toplu Kullanıcı Yükleme (CSV)
if (isset($_POST['toplu_kullanici_yukle']) && isset($_FILES['csv_dosyasi']) && $_FILES['csv_dosyasi']['error'] == 0) {
    $file = fopen($_FILES['csv_dosyasi']['tmp_name'], 'r');
    fgetcsv($file); // Başlık satırını atla
    
    $eklenen_sayisi = 0;
    $stmt = $pdo->prepare("INSERT INTO users (ad, soyad, email, sifre, rol, klasman, telefon, lisans_no, dogum_tarihi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    while (($line = fgetcsv($file, 1000, ";")) !== FALSE) {
        try {
            $dogum_tarihi_csv = !empty(trim($line[8])) ? date('Y-m-d', strtotime(trim($line[8]))) : null;
            $stmt->execute([
                trim($line[0]), trim($line[1]), trim($line[2]),
                password_hash(trim($line[3]), PASSWORD_DEFAULT),
                trim($line[4]), trim($line[5]), trim($line[6]),
                trim($line[7]), $dogum_tarihi_csv
            ]);
            $eklenen_sayisi++;
        } catch (Exception $e) { /* Hata olursa o satırı atla */ }
    }
    fclose($file);
    $mesaj = ['tip' => 'success', 'icerik' => "{$eklenen_sayisi} adet kullanıcı CSV dosyasından başarıyla eklendi."];
}


// --- FİLTRELEME ve ARAMA ---
$arama_terimi = isset($_GET['arama']) ? trim($_GET['arama']) : '';
$filtre_klasman = isset($_GET['filtre_klasman']) ? trim($_GET['filtre_klasman']) : '';

// Klasmanları çek (filtre dropdown için)
$klasmanlar = $pdo->query("SELECT ad FROM klasmanlar ORDER BY ad ASC")->fetchAll(PDO::FETCH_COLUMN);

// SQL sorgusu
$sql = "
    SELECT u.*,
        (SELECT COUNT(m.id) FROM musabakalar m WHERE m.hakem_id = u.id OR m.yardimci_1_id = u.id OR m.yardimci_2_id = u.id OR m.dorduncu_hakem_id = u.id OR m.gozlemci_id = u.id) AS toplam_gorev,
        (SELECT AVG(rd.puan) FROM rapor_detaylari rd WHERE rd.hakem_id = u.id) AS puan_ortalamasi
    FROM users u
    WHERE 1=1
";

$params = [];
if (!empty($arama_terimi)) {
    $sql .= " AND CONCAT(u.ad, ' ', u.soyad) LIKE :arama";
    $params[':arama'] = "%$arama_terimi%";
}
if (!empty($filtre_klasman)) {
    $sql .= " AND u.klasman = :klasman";
    $params[':klasman'] = $filtre_klasman;
}

// Özel sıralama mantığı
$sql .= " ORDER BY 
    FIELD(u.klasman, 
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
    ), u.ad, u.soyad";
    
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kullanicilar = $stmt->fetchAll();


function getRoleName($roleId) {
    switch ($roleId) {
        case 1: return 'Yönetici';
        case 2: return 'Hakem';
        case 3: return 'Gözlemci';
        default: return 'Bilinmiyor';
    }
}

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Toplu Kullanıcı Yükle (CSV)</h2>
        <?php if ($mesaj && isset($_POST['toplu_kullanici_yukle'])): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($mesaj['icerik']); ?></div>
        <?php endif; ?>
        <p class="text-sm text-gray-600">Sütunlar: Ad, Soyad, E-posta, Şifre, Rol (2:Hakem, 3:Gözlemci), Klasman, Telefon, Lisans No, Doğum Tarihi (GG.AA.YYYY)</p>
        <a href="data:text/csv;charset=utf-8,%EF%BB%BFAd;Soyad;E-posta;Şifre;Rol;Klasman;Telefon;Lisans No;Doğum Tarihi%0AAli;Veli;ali.veli@hakem.com;sifre123;2;İl Hakemi;05551234567;12345;15.03.1990%0AZeynep;Güneş;zeynep.gunes@gozlemci.com;sifre456;3;Bölgesel Gözlemci;05559876543;67890;20.07.1985" download="ornek_kullanici_sablonu.csv" class="text-sm text-blue-600 hover:underline my-2 inline-block"><i class="fas fa-download mr-1"></i>Örnek Şablonu İndir</a>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_dosyasi" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50" accept=".csv" required>
            <button type="submit" name="toplu_kullanici_yukle" class="mt-4 w-full bg-purple-600 text-white py-2 rounded-md hover:bg-purple-700">CSV ile Yükle</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Manuel Kullanıcı Ekle</h2>
        <?php if ($mesaj && isset($_POST['kullanici_ekle'])): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($mesaj['icerik']); ?></div>
        <?php endif; ?>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="text" name="ad" placeholder="Ad" class="border p-2 rounded" required>
            <input type="text" name="soyad" placeholder="Soyad" class="border p-2 rounded" required>
            <input type="email" name="email" placeholder="E-posta" class="border p-2 rounded" required>
            <input type="password" name="sifre" placeholder="Şifre" class="border p-2 rounded" required>
            <input type="tel" name="telefon" placeholder="Telefon (05xx...)" class="border p-2 rounded" pattern="0[0-9]{10}" maxlength="11">
            <input type="text" name="klasman" placeholder="Klasman" class="border p-2 rounded">
            <input type="text" name="lisans_no" placeholder="Lisans No" class="border p-2 rounded">
            <input type="date" name="dogum_tarihi" placeholder="Doğum Tarihi" class="border p-2 rounded">
            <select name="rol" class="border p-2 rounded" required>
                <option value="1">Yönetici</option>
                <option value="2" selected>Hakem</option>
                <option value="3">Gözlemci</option>
            </select>
            <button type="submit" name="kullanici_ekle" class="md:col-span-3 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Kullanıcıyı Ekle</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Tüm Kullanıcılar</h2>
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
                <a href="kullanicilar.php" class="bg-gray-200 text-gray-800 text-center py-2 px-4 rounded-md">Temizle</a>
            </form>
        </div>
        
        <?php if ($mesaj && !isset($_POST['kullanici_ekle']) && !isset($_POST['toplu_kullanici_yukle'])): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($mesaj['icerik']); ?></div>
        <?php endif; ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left">Ad Soyad / Klasman</th>
                        <th class="py-2 px-4 text-left">Rol</th>
                        <th class="py-2 px-4 text-left">Toplam Görev</th>
                        <th class="py-2 px-4 text-left">Puan Ort.</th>
                        <th class="py-2 px-4 text-left">Yetki</th>
                        <th class="py-2 px-4 text-left">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($kullanicilar)): ?>
                        <tr><td colspan="6" class="text-center py-4">Filtreye uygun kullanıcı bulunamadı.</td></tr>
                    <?php else: ?>
                        <?php foreach($kullanicilar as $kullanici): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4">
                                <div class="font-medium"><?php echo htmlspecialchars($kullanici->ad . ' ' . $kullanici->soyad); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($kullanici->klasman); ?></div>
                            </td>
                            <td class="py-2 px-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $kullanici->rol == 1 ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo getRoleName($kullanici->rol); ?>
                                </span>
                            </td>
                            <td class="py-2 px-4 text-center font-bold"><?php echo $kullanici->toplam_gorev; ?></td>
                            <td class="py-2 px-4 text-center font-bold text-blue-600">
                                <?php echo $kullanici->puan_ortalamasi ? number_format($kullanici->puan_ortalamasi, 2) : '-'; ?>
                            </td>
                            <td class="py-2 px-4">
                                <?php if ($kullanici->id !== $current_admin_id): ?>
                                    <?php if ($kullanici->rol == 1): ?>
                                        <a href="?action=yetki_al&id=<?php echo $kullanici->id; ?>" class="text-yellow-600 hover:underline text-xs" onclick="return confirm('Bu kullanıcının yönetici yetkisini almak istediğinizden emin misiniz? Kullanıcı varsayılan olarak Hakem rolüne atanacaktır.')">Yetkiyi Al</a>
                                    <?php else: ?>
                                        <a href="?action=yetki_ver&id=<?php echo $kullanici->id; ?>" class="text-green-600 hover:underline text-xs" onclick="return confirm('Bu kullanıcıya yönetici yetkisi vermek istediğinizden emin misiniz?')">Yönetici Yap</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 italic">Mevcut Oturum</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4">
                                <a href="kullanici_duzenle.php?id=<?php echo $kullanici->id; ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Detayları Görüntüle / Düzenle"><i class="fas fa-edit"></i></a>
                                <?php if ($kullanici->id !== $current_admin_id): ?>
                                <a href="?action=sil&id=<?php echo $kullanici->id; ?>" class="text-red-600 hover:text-red-800" title="Kullanıcıyı Sil" onclick="return confirm('Bu kullanıcıyı ve tüm verilerini (mazeret, bildirim, görev vb.) kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');"><i class="fas fa-trash"></i></a>
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
<?php include '../templates/footer.php'; ?>