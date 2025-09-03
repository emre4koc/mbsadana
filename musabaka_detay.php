<?php
/* Entegrasyon notu: Bu dosyada 1.txt içindeki "MÜSABAKA GÜNCELLEME" (tüm alanları düzenleme + bildirim akışı) bloğu, ana kod olan 2.txt'ye eklendi.  Kaynaklar: :contentReference[oaicite:0]{index=0} ve :contentReference[oaicite:1]{index=1} */

require_once 'config/session_check.php';
require_once 'config/db.php';

$sayfa_baslik = "Müsabaka Detayları";
$musabaka_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['user_rol'];
$mesaj = '';

// Sayfa yüklendiğinde session'daki mesajı al ve temizle
if (isset($_SESSION['mesaj'])) {
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
}

// --- YARDIMCI FONKSIYONLAR ---
function format_turkish_date($date_string) {
    if (empty($date_string) || $date_string === '0000-00-00 00:00:00') { return 'Tarih Belirtilmemiş'; }
    $timestamp = strtotime($date_string);
    $aylar = ["", "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
    $gunler = ["Pazar", "Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi"];
    $gun = $gunler[date('w', $timestamp)];
    $ay = $aylar[date('n', $timestamp)];
    return date('d ', $timestamp) . $ay . date(' Y, ', $timestamp) . $gun . date(' - H:i', $timestamp);
}

function render_gorevli($gorev, $isim, $telefon, $onay_durumu = null, $kullanici_rol = null) {
    echo "<li class='flex items-center justify-between py-1'>";
    echo "<span><strong>{$gorev}:</strong> " . htmlspecialchars($isim ?? '-');
    
    // Onay durumunu göster (sadece yöneticiler için)
    if ($kullanici_rol == 1 && $onay_durumu !== null) {
        $onay_durumu_text = $onay_durumu ? 'Onaylandı' : 'Onay Bekliyor';
        $onay_renk = $onay_durumu ? 'text-green-600' : 'text-yellow-600';
        echo " <span class='text-xs {$onay_renk}'>({$onay_durumu_text})</span>";
    }
    
    echo "</span>";
    if (!empty($telefon)) {
        echo "<a href='tel:" . htmlspecialchars($telefon) . "' class='text-blue-600 hover:text-blue-800' title='" . htmlspecialchars($telefon) . "'><i class='fas fa-phone-alt'></i></a>";
    }
    echo "</li>";
}

function sendSingleNotification($pdo, $user_id, $message, $link) {
    if (empty($user_id)) return;
    $stmt = $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $message, $link]);
}

/* =========================
   1.txt'ten ENTEGRE EDİLEN BLOK: MÜSABAKA GÜNCELLEME (YÖNETİCİ)
   - Lig/hafta/tarih/saat/stadyum/takımlar ve tüm görevliler aynı formda güncellenir
   - Eklenen/çıkarılan/kalan görevlilere bildirim
   - Tarih/Saat/Stadyum değişirse kalanlara "güncellendi" bildirimi
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['musabaka_guncelle']) && $user_rol == 1) {
    // Önceki müsabaka ve görevli bilgilerini çek
    $stmt_onceki = $pdo->prepare("SELECT * FROM musabakalar WHERE id = ?");
    $stmt_onceki->execute([$musabaka_id]);
    $onceki_musabaka = $stmt_onceki->fetch(PDO::FETCH_ASSOC);

    // Formdan yeni bilgileri al
    $yeni_bilgiler = [
        'lig_id' => $_POST['lig_id'],
        'hafta_no' => $_POST['hafta_no'],
        'tarih' => $_POST['tarih'],
        'saat' => $_POST['saat'],
        'stadyum_id' => $_POST['stadyum_id'],
        'ev_sahibi_id' => $_POST['ev_sahibi_id'],
        'misafir_id' => $_POST['misafir_id'],
        'hakem_id' => !empty($_POST['hakem_id']) ? (int)$_POST['hakem_id'] : null,
        'yardimci_1_id' => !empty($_POST['yardimci_1_id']) ? (int)$_POST['yardimci_1_id'] : null,
        'yardimci_2_id' => !empty($_POST['yardimci_2_id']) ? (int)$_POST['yardimci_2_id'] : null,
        'dorduncu_hakem_id' => !empty($_POST['dorduncu_hakem_id']) ? (int)$_POST['dorduncu_hakem_id'] : null,
        'gozlemci_id' => !empty($_POST['gozlemci_id']) ? (int)$_POST['gozlemci_id'] : null,
        'id' => $musabaka_id
    ];

    // Veritabanını güncelle
    $sql = "UPDATE musabakalar 
            SET lig_id=:lig_id, hafta_no=:hafta_no, tarih=:tarih, saat=:saat, stadyum_id=:stadyum_id, 
                ev_sahibi_id=:ev_sahibi_id, misafir_id=:misafir_id, 
                hakem_id=:hakem_id, yardimci_1_id=:yardimci_1_id, yardimci_2_id=:yardimci_2_id, 
                dorduncu_hakem_id=:dorduncu_hakem_id, gozlemci_id=:gozlemci_id 
            WHERE id=:id";
    $stmt_guncelle = $pdo->prepare($sql);

    if ($stmt_guncelle->execute($yeni_bilgiler)) {
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Müsabaka başarıyla güncellendi.'];

        // Bildirimleri gönder
        $onceki_gorevliler_dizi = array_filter([
            $onceki_musabaka['hakem_id'] ?? null, 
            $onceki_musabaka['yardimci_1_id'] ?? null, 
            $onceki_musabaka['yardimci_2_id'] ?? null, 
            $onceki_musabaka['dorduncu_hakem_id'] ?? null, 
            $onceki_musabaka['gozlemci_id'] ?? null
        ]);
        $yeni_gorevliler_dizi = array_filter([
            $yeni_bilgiler['hakem_id'], 
            $yeni_bilgiler['yardimci_1_id'], 
            $yeni_bilgiler['yardimci_2_id'], 
            $yeni_bilgiler['dorduncu_hakem_id'], 
            $yeni_bilgiler['gozlemci_id']
        ]);
        
        $mac_adi = "ilgili müsabaka"; // Fallback
        $musabaka_bilgi_stmt = $pdo->prepare("SELECT t1.ad as ev_sahibi, t2.ad as misafir FROM musabakalar m JOIN takimlar t1 ON m.ev_sahibi_id = t1.id JOIN takimlar t2 ON m.misafir_id = t2.id WHERE m.id = ?");
        $musabaka_bilgi_stmt->execute([$musabaka_id]);
        if($musabaka_bilgi = $musabaka_bilgi_stmt->fetch(PDO::FETCH_OBJ)) {
            $mac_adi = "{$musabaka_bilgi->ev_sahibi} - {$musabaka_bilgi->misafir}";
        }
        $bildirim_linki = "/musabaka_detay.php?id=" . $musabaka_id;

        $eklenenler = array_diff($yeni_gorevliler_dizi, $onceki_gorevliler_dizi);
        $cikarilanlar = array_diff($onceki_gorevliler_dizi, $yeni_gorevliler_dizi);
        $kalanlar = array_intersect($yeni_gorevliler_dizi, $onceki_gorevliler_dizi);
        
        foreach ($eklenenler as $gorevli_id) { sendSingleNotification($pdo, $gorevli_id, "Yeni görev atandı: {$mac_adi}", $bildirim_linki); }
        foreach ($cikarilanlar as $gorevli_id) { sendSingleNotification($pdo, $gorevli_id, "Göreviniz iptal edildi: {$mac_adi}", $bildirim_linki); }

        // Maç bilgileri değiştiyse kalanlara da bildirim gönder
        if (($onceki_musabaka['tarih'] ?? null) != $yeni_bilgiler['tarih'] 
            || ($onceki_musabaka['saat'] ?? null) != $yeni_bilgiler['saat'] 
            || ($onceki_musabaka['stadyum_id'] ?? null) != $yeni_bilgiler['stadyum_id']) {
            foreach ($kalanlar as $gorevli_id) { sendSingleNotification($pdo, $gorevli_id, "Göreviniz güncellendi: {$mac_adi}", $bildirim_linki); }
        }
    } else {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Güncelleme sırasında bir hata oluştu.'];
    }
    header("Location: musabaka_detay.php?id=$musabaka_id");
    exit();
}

/* --- İŞLEM BLOKLARI (ORİJİNAL 2.txt) --- */

// GÖREV ONAYLAMA İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gorevi_onayla'])) {
    // Kullanıcının bu maçtaki rolünü bul
    $stmt = $pdo->prepare("SELECT hakem_id, yardimci_1_id, yardimci_2_id, dorduncu_hakem_id, gozlemci_id FROM musabakalar WHERE id = ?");
    $stmt->execute([$musabaka_id]);
    $gorevliler = $stmt->fetch();
    
    $rol_kolonu = '';
    if ($gorevliler->hakem_id == $user_id) $rol_kolonu = 'hakem_onay';
    elseif ($gorevliler->yardimci_1_id == $user_id) $rol_kolonu = 'yardimci_1_onay';
    elseif ($gorevliler->yardimci_2_id == $user_id) $rol_kolonu = 'yardimci_2_onay';
    elseif ($gorevliler->dorduncu_hakem_id == $user_id) $rol_kolonu = 'dorduncu_hakem_onay';
    elseif ($gorevliler->gozlemci_id == $user_id) $rol_kolonu = 'gozlemci_onay';
    
    if (!empty($rol_kolonu)) {
        $stmt = $pdo->prepare("UPDATE musabakalar SET {$rol_kolonu} = 1 WHERE id = ?");
        if ($stmt->execute([$musabaka_id])) {
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Göreviniz başarıyla onaylandı.'];
        } else {
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Onaylama işlemi sırasında bir hata oluştu.'];
        }
    }
    header("Location: musabaka_detay.php?id=$musabaka_id");
    exit();
}

// MÜSABAKA SİLME İŞLEMİ
if (isset($_GET['action']) && $_GET['action'] == 'sil' && $user_rol == 1) {
    try {
        $pdo->beginTransaction();
        // İlişkili raporları ve detaylarını sil
        $rapor_stmt = $pdo->prepare("SELECT id FROM raporlar WHERE musabaka_id = ?");
        $rapor_stmt->execute([$musabaka_id]);
        $rapor = $rapor_stmt->fetch();
        if ($rapor) {
            $pdo->prepare("DELETE FROM rapor_detaylari WHERE rapor_id = ?")->execute([$rapor->id]);
            $pdo->prepare("DELETE FROM raporlar WHERE id = ?")->execute([$rapor->id]);
        }
        // Müsabakayı sil
        $pdo->prepare("DELETE FROM musabakalar WHERE id = ?")->execute([$musabaka_id]);
        $pdo->commit();
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Müsabaka başarıyla silindi.'];
        header("Location: admin/musabaka_yonetimi.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Silme işlemi sırasında bir hata oluştu: ' . $e->getMessage()];
        header("Location: musabaka_detay.php?id=$musabaka_id");
        exit();
    }
}

// Arşivleme / Arşivden Çıkarma İşlemi
if (isset($_GET['action']) && $user_rol == 1 && in_array($_GET['action'], ['arsivle', 'arsivden_cikar'])) {
    $arsiv_durumu = $_GET['action'] == 'arsivle' ? 1 : 0;
    $pdo->prepare("UPDATE musabakalar SET arsiv = ? WHERE id = ?")->execute([$arsiv_durumu, $musabaka_id]);
    $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => $arsiv_durumu ? 'Müsabaka arşivlendi.' : 'Müsabaka arşivden çıkarıldı.'];
    header("Location: musabaka_detay.php?id=$musabaka_id");
    exit();
}

// GÖREVLİ GÜNCELLEME İŞLEMİ (2.txt orijinal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gorevli_guncelle']) && $user_rol == 1) {
    // Önceki görevli listesini çek
    $stmt_onceki = $pdo->prepare("SELECT hakem_id, yardimci_1_id, yardimci_2_id, dorduncu_hakem_id, gozlemci_id FROM musabakalar WHERE id = ?");
    $stmt_onceki->execute([$musabaka_id]);
    $onceki_gorevliler = $stmt_onceki->fetch(PDO::FETCH_ASSOC);

    // Formdan yeni görevli ID'lerini al
    $yeni_gorevliler = [
        'hakem_id' => !empty($_POST['hakem_id']) ? (int)$_POST['hakem_id'] : null,
        'yardimci_1_id' => !empty($_POST['yardimci_1_id']) ? (int)$_POST['yardimci_1_id'] : null,
        'yardimci_2_id' => !empty($_POST['yardimci_2_id']) ? (int)$_POST['yardimci_2_id'] : null,
        'dorduncu_hakem_id' => !empty($_POST['dorduncu_hakem_id']) ? (int)$_POST['dorduncu_hakem_id'] : null,
        'gozlemci_id' => !empty($_POST['gozlemci_id']) ? (int)$_POST['gozlemci_id'] : null
    ];
    
    // Veritabanını güncelle
    $stmt_guncelle = $pdo->prepare("UPDATE musabakalar SET hakem_id = :hakem_id, yardimci_1_id = :yardimci_1_id, yardimci_2_id = :yardimci_2_id, dorduncu_hakem_id = :dorduncu_hakem_id, gozlemci_id = :gozlemci_id WHERE id = :id");
    if ($stmt_guncelle->execute(array_merge($yeni_gorevliler, ['id' => $musabaka_id]))) {
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Görevliler başarıyla güncellendi.'];

        $musabaka_bilgi_stmt = $pdo->prepare("SELECT t1.ad as ev_sahibi, t2.ad as misafir FROM musabakalar m JOIN takimlar t1 ON m.ev_sahibi_id = t1.id JOIN takimlar t2 ON m.misafir_id = t2.id WHERE m.id = ?");
        $musabaka_bilgi_stmt->execute([$musabaka_id]);
        $musabaka_bilgi = $musabaka_bilgi_stmt->fetch();
        $mac_adi = $musabaka_bilgi ? "{$musabaka_bilgi->ev_sahibi} - {$musabaka_bilgi->misafir}" : "ilgili müsabaka";
        $bildirim_linki = "/musabaka_detay.php?id=" . $musabaka_id;

        $eklenenler = array_diff($yeni_gorevliler, $onceki_gorevliler);
        $cikarilanlar = array_diff($onceki_gorevliler, $yeni_gorevliler);

        foreach ($eklenenler as $gorevli_id) {
            if (!empty($gorevli_id)) {
                sendSingleNotification($pdo, $gorevli_id, "Yeni görev atandı: {$mac_adi}", $bildirim_linki);
            }
        }
        foreach ($cikarilanlar as $gorevli_id) {
            if (!empty($gorevli_id)) {
                sendSingleNotification($pdo, $gorevli_id, "Göreviniz iptal edildi: {$mac_adi}", $bildirim_linki);
            }
        }
    } else {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Güncelleme sırasında bir hata oluştu.'];
    }
    header("Location: musabaka_detay.php?id=$musabaka_id");
    exit();
}

// Müsabaka sonucu girme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skor_kaydet'])) {
    $durum = $_POST['durum'];
    $skor = (int)$_POST['ev_skor'] . '-' . (int)$_POST['misafir_skor'];
    $ihraclar = trim($_POST['ihraclar']);

    $stmt = $pdo->prepare("UPDATE musabakalar SET durum = ?, skor = ?, ihraclar = ? WHERE id = ?");
    if ($stmt->execute([$durum, $skor, $ihraclar, $musabaka_id])) {
        $mesaj = ['tip' => 'success', 'icerik' => 'Müsabaka bilgileri başarıyla güncellendi.'];
    } else {
        $mesaj = ['tip' => 'error', 'icerik' => 'Güncelleme sırasında bir hata oluştu.'];
    }
}

// Müsabaka detaylarını ve görevli onay durumlarını çek
$stmt = $pdo->prepare("
    SELECT 
        m.*, 
        l.ad as lig_adi, s.ad as stadyum_adi, t1.ad as ev_sahibi, t2.ad as misafir,
        CONCAT(h.ad, ' ', h.soyad) as hakem, h.telefon as hakem_tel,
        CONCAT(y1.ad, ' ', y1.soyad) as yardimci_1, y1.telefon as yardimci_1_tel,
        CONCAT(y2.ad, ' ', y2.soyad) as yardimci_2, y2.telefon as yardimci_2_tel,
        CONCAT(d4.ad, ' ', d4.soyad) as dorduncu_hakem, d4.telefon as dorduncu_hakem_tel,
        CONCAT(g.ad, ' ', g.soyad) as gozlemci, g.telefon as gozlemci_tel,
        r.rapor_dosya_yolu
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
    LEFT JOIN raporlar r ON r.musabaka_id = m.id
    WHERE m.id = ?
");
$stmt->execute([$musabaka_id]);
$musabaka = $stmt->fetch();

if (!$musabaka) { die("Müsabaka bulunamadı."); }

// Kullanıcının bu maçtaki rolünü ve onay durumunu bul
$kullanici_rolu = '';
$kullanici_onay_durumu = false;
if ($musabaka->hakem_id == $user_id) { $kullanici_rolu = 'hakem'; $kullanici_onay_durumu = $musabaka->hakem_onay; }
elseif ($musabaka->yardimci_1_id == $user_id) { $kullanici_rolu = 'yardimci_1'; $kullanici_onay_durumu = $musabaka->yardimci_1_onay; }
elseif ($musabaka->yardimci_2_id == $user_id) { $kullanici_rolu = 'yardimci_2'; $kullanici_onay_durumu = $musabaka->yardimci_2_onay; }
elseif ($musabaka->dorduncu_hakem_id == $user_id) { $kullanici_rolu = 'dorduncu_hakem'; $kullanici_onay_durumu = $musabaka->dorduncu_hakem_onay; }
elseif ($musabaka->gozlemci_id == $user_id) { $kullanici_rolu = 'gozlemci'; $kullanici_onay_durumu = $musabaka->gozlemci_onay; }

// Form için listeleri çek (yönetici ise)
if ($user_rol == 1) {
    // Orijinal 2.txt listeleri
    $hakemler = $pdo->query("SELECT id, ad, soyad FROM users WHERE rol = 2 ORDER BY ad, soyad")->fetchAll();
    $gozlemciler = $pdo->query("SELECT id, ad, soyad FROM users WHERE rol = 3 ORDER BY ad, soyad")->fetchAll();
    // 1.txt güncelleme formu için ek listeler
    $ligler = $pdo->query("SELECT * FROM ligler ORDER BY ad")->fetchAll();
    $stadyumlar = $pdo->query("SELECT * FROM stadyumlar ORDER BY ad")->fetchAll();
    $takimlar = $pdo->query("SELECT * FROM takimlar ORDER BY ad")->fetchAll();
}

$gorevli_idler = array_filter([$musabaka->hakem_id, $musabaka->yardimci_1_id, $musabaka->yardimci_2_id, $musabaka->dorduncu_hakem_id, $musabaka->gozlemci_id]);
$kullanici_gorevli_mi = in_array($user_id, $gorevli_idler);

$puanlar = [];
if ($user_rol == 1 || $kullanici_gorevli_mi) {
    $stmt = $pdo->prepare("SELECT rd.puan, rd.not, rd.hakem_id, CONCAT(u.ad, ' ', u.soyad) as hakem_adi FROM rapor_detaylari rd JOIN raporlar r ON rd.rapor_id = r.id JOIN users u ON rd.hakem_id = u.id WHERE r.musabaka_id = ?");
    $stmt->execute([$musabaka_id]);
    $puanlar = $stmt->fetchAll();
}

include 'templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <?php if (!empty($kullanici_rolu) && !$kullanici_onay_durumu): ?>
            <div id="onay-kutusu" class="my-4 p-4 bg-blue-50 border-l-4 border-blue-500">
                <p class="font-semibold text-blue-800">Bu müsabakadaki göreviniz için onayınız bekleniyor.</p>
                <form method="POST" class="mt-2">
                    <button type="submit" name="gorevi_onayla" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Görevi Onayla</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($musabaka->ev_sahibi); ?> vs <?php echo htmlspecialchars($musabaka->misafir); ?></h2>
                <p class="text-gray-600"><?php echo htmlspecialchars($musabaka->lig_adi); ?> - <?php echo $musabaka->hafta_no; ?>. Hafta</p>
                <p class="text-gray-500 text-sm"><?php echo format_turkish_date("$musabaka->tarih $musabaka->saat"); ?></p>
            </div>
            <div class="text-right">
                <?php if ($user_rol == 1): ?>
                    <!-- 1.txt tarzı genel düzenleme butonu (orijinal 'duzenle-btn' ile karışmasın diye farklı ID) -->
                    <button id="mac-duzenle-btn" class="mb-2 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 text-sm">
                        <i class="fas fa-edit mr-2"></i>Müsabakayı Düzenle
                    </button>
                <?php endif; ?>
                <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($musabaka->skor ?? '-'); ?></p>
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $musabaka->durum == 'Oynandı' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>"><?php echo htmlspecialchars($musabaka->durum); ?></span>
            </div>
        </div>

        <?php if ($mesaj): ?>
            <div class="my-4 p-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($mesaj['icerik']); ?>
            </div>
        <?php endif; ?>

        <?php if ($user_rol == 1): ?>
        <div class="my-4 border-t pt-4 flex items-center gap-4">
            <?php if ($musabaka->arsiv == 0): ?>
                <a href="?action=arsivle&id=<?php echo $musabaka->id; ?>" class="bg-yellow-500 text-white py-2 px-4 rounded-md hover:bg-yellow-600 text-sm" onclick="return confirm('Bu müsabakayı arşivlemek istediğinizden emin misiniz?')">
                    <i class="fas fa-archive mr-2"></i>Müsabakayı Arşivle
                </a>
            <?php else: ?>
                <p class="text-sm text-yellow-700 bg-yellow-100 p-3 rounded-md mb-4">Bu müsabaka arşivlenmiştir.</p>
                <a href="?action=arsivden_cikar&id=<?php echo $musabaka->id; ?>" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 text-sm" onclick="return confirm('Bu müsabakayı arşivden çıkarmak istediğinizden emin misiniz?')">
                    <i class="fas fa-box-open mr-2"></i>Arşivden Çıkar
                </a>
            <?php endif; ?>
            <a href="?action=sil&id=<?php echo $musabaka->id; ?>" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 text-sm" onclick="return confirm('Bu müsabakayı ve ilişkili tüm verileri (raporlar vb.) kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                <i class="fas fa-trash mr-2"></i>Müsabakayı Sil
            </a>
        </div>
        <?php endif; ?>
        
        <div class="border-t my-4"></div>

        <?php if ($user_rol == 1): ?>
        <!-- 1.txt tarzı GENEL DÜZENLEME PANELİ (tam form) -->
        <div id="mac-duzenleme-paneli" class="hidden border mt-4 p-4 rounded-md">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Müsabakayı Düzenle</h2>
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Lig</label>
                        <select name="lig_id" class="w-full mt-1 p-2 border rounded">
                            <?php foreach($ligler as $lig) echo "<option value='{$lig->id}'".($musabaka->lig_id == $lig->id ? ' selected':'').">{$lig->ad}</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Hafta</label>
                        <input type="number" name="hafta_no" value="<?php echo (int)$musabaka->hafta_no; ?>" class="w-full mt-1 p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Tarih</label>
                        <input type="date" name="tarih" value="<?php echo htmlspecialchars($musabaka->tarih); ?>" class="w-full mt-1 p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Saat</label>
                        <input type="time" name="saat" value="<?php echo htmlspecialchars($musabaka->saat); ?>" class="w-full mt-1 p-2 border rounded">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium">Stadyum</label>
                        <select name="stadyum_id" class="w-full mt-1 p-2 border rounded">
                            <?php foreach($stadyumlar as $stadyum) echo "<option value='{$stadyum->id}'".($musabaka->stadyum_id == $stadyum->id ? ' selected':'').">{$stadyum->ad}</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Ev Sahibi</label>
                        <select name="ev_sahibi_id" class="w-full mt-1 p-2 border rounded">
                            <?php foreach($takimlar as $takim) echo "<option value='{$takim->id}'".($musabaka->ev_sahibi_id == $takim->id ? ' selected':'').">{$takim->ad}</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Misafir</label>
                        <select name="misafir_id" class="w-full mt-1 p-2 border rounded">
                            <?php foreach($takimlar as $takim) echo "<option value='{$takim->id}'".($musabaka->misafir_id == $takim->id ? ' selected':'').">{$takim->ad}</option>"; ?>
                        </select>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Görevli Ekip</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Hakem</label>
                            <select name="hakem_id" class="w-full mt-1 p-2 border rounded">
                                <option value="">Seçiniz</option>
                                <?php foreach($hakemler as $hakem) echo "<option value='{$hakem->id}'".($musabaka->hakem_id == $hakem->id ? ' selected':'').">{$hakem->ad} {$hakem->soyad}</option>"; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">1. Yardımcı</label>
                            <select name="yardimci_1_id" class="w-full mt-1 p-2 border rounded">
                                <option value="">Seçiniz</option>
                                <?php foreach($hakemler as $hakem) echo "<option value='{$hakem->id}'".($musabaka->yardimci_1_id == $hakem->id ? ' selected':'').">{$hakem->ad} {$hakem->soyad}</option>"; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">2. Yardımcı</label>
                            <select name="yardimci_2_id" class="w-full mt-1 p-2 border rounded">
                                <option value="">Seçiniz</option>
                                <?php foreach($hakemler as $hakem) echo "<option value='{$hakem->id}'".($musabaka->yardimci_2_id == $hakem->id ? ' selected':'').">{$hakem->ad} {$hakem->soyad}</option>"; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">4. Hakem</label>
                            <select name="dorduncu_hakem_id" class="w-full mt-1 p-2 border rounded">
                                <option value="">Seçiniz</option>
                                <?php foreach($hakemler as $hakem) echo "<option value='{$hakem->id}'".($musabaka->dorduncu_hakem_id == $hakem->id ? ' selected':'').">{$hakem->ad} {$hakem->soyad}</option>"; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Gözlemci</label>
                            <select name="gozlemci_id" class="w-full mt-1 p-2 border rounded">
                                <option value="">Seçiniz</option>
                                <?php foreach($gozlemciler as $gozlemci) echo "<option value='{$gozlemci->id}'".($musabaka->gozlemci_id == $gozlemci->id ? ' selected':'').">{$gozlemci->ad} {$gozlemci->soyad}</option>"; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button type="button" id="mac-iptal-btn" class="w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300">İptal</button>
                    <button type="submit" name="musabaka_guncelle" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <h3 class="font-semibold text-gray-700 mb-2">Müsabaka Bilgileri</h3>
                <ul class="space-y-1 text-gray-600">
                    <li><strong>Maç No:</strong> <?php echo htmlspecialchars($musabaka->mac_no ?? '-'); ?></li>
                    <li><strong>Stadyum:</strong> <?php echo htmlspecialchars($musabaka->stadyum_adi); ?></li>
                    <li><strong>İhraçlar:</strong> <?php echo nl2br(htmlspecialchars($musabaka->ihraclar ?? 'Yok')); ?></li>
                </ul>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Görevli Ekip</h3>
                    <?php if ($user_rol == 1): ?>
                        <button id="duzenle-btn" class="text-sm bg-gray-200 text-gray-800 px-3 py-1 rounded-md hover:bg-gray-300">
                            <i class="fas fa-edit mr-1"></i> Düzenle
                        </button>
                    <?php endif; ?>
                </div>
                
                <div id="gorevli-listesi">
                    <ul class="space-y-2 text-gray-600">
                        <?php
                            render_gorevli('Hakem', $musabaka->hakem, $musabaka->hakem_tel, $musabaka->hakem_onay, $user_rol);
                            render_gorevli('1. Yardımcı', $musabaka->yardimci_1, $musabaka->yardimci_1_tel, $musabaka->yardimci_1_onay, $user_rol);
                            render_gorevli('2. Yardımcı', $musabaka->yardimci_2, $musabaka->yardimci_2_tel, $musabaka->yardimci_2_onay, $user_rol);
                            render_gorevli('4. Hakem', $musabaka->dorduncu_hakem, $musabaka->dorduncu_hakem_tel, $musabaka->dorduncu_hakem_onay, $user_rol);
                            render_gorevli('Gözlemci', $musabaka->gozlemci, $musabaka->gozlemci_tel, $musabaka->gozlemci_onay, $user_rol);
                        ?>
                    </ul>
                </div>

                <?php if ($user_rol == 1): ?>
                <form id="gorevli-formu" method="POST" class="hidden mt-4 space-y-3 border-t pt-4">
                    <div>
                        <label class="block text-sm font-medium">Hakem</label>
                        <select name="hakem_id" class="w-full mt-1 p-2 border rounded"><option value="">Seçiniz</option><?php foreach($hakemler as $hakem) echo "<option value='{$hakem->id}'".($musabaka->hakem_id == $hakem->id ? ' selected':'').">{$hakem->ad} {$hakem->soyad}</option>"; ?></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">1. Yardımcı</label>
                        <select name="yardimci_1_id" class="w-full mt-1 p-2 border rounded"><option value="">Seçiniz</option><?php foreach($hakemler as $hakem) echo "<option value='{$hakem->id}'".($musabaka->yardimci_1_id == $hakem->id ? ' selected':'').">{$hakem->ad} {$hakem->soyad}</option>"; ?></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">2. Yardımcı</label>
                        <select name="yardimci_2_id" class="w-full mt-1 p-2 border rounded"><option value="">Seçiniz</option><?php foreach($hakemler as $hakem) echo "<option value='{$hakem->id}'".($musabaka->yardimci_2_id == $hakem->id ? ' selected':'').">{$hakem->ad} {$hakem->soyad}</option>"; ?></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">4. Hakem</label>
                        <select name="dorduncu_hakem_id" class="w-full mt-1 p-2 border rounded"><option value="">Seçiniz</option><?php foreach($hakemler as $hakem) echo "<option value='{$hakem->id}'".($musabaka->dorduncu_hakem_id == $hakem->id ? ' selected':'').">{$hakem->ad} {$hakem->soyad}</option>"; ?></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Gözlemci</label>
                        <select name="gozlemci_id" class="w-full mt-1 p-2 border rounded"><option value="">Seçiniz</option><?php foreach($gozlemciler as $gozlemci) echo "<option value='{$gozlemci->id}'".($musabaka->gozlemci_id == $gozlemci->id ? ' selected':'').">{$gozlemci->ad} {$gozlemci->soyad}</option>"; ?></select>
                    </div>
                    <div class="flex gap-2">
                         <button type="button" id="iptal-btn" class="w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300">İptal</button>
                         <button type="submit" name="gorevli_guncelle" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Güncelle</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (($user_rol == 1 || $kullanici_gorevli_mi) && $musabaka->rapor_dosya_yolu): ?>
        <div class="mt-6">
            <h3 class="font-semibold text-gray-700 mb-2">Gözlemci Raporu</h3>
            <a href="/<?php echo htmlspecialchars($musabaka->rapor_dosya_yolu); ?>" target="_blank" class="text-blue-600 hover:underline">
                <i class="fas fa-file-excel"></i> Raporu Görüntüle/İndir
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($puanlar)): ?>
        <div class="mt-6">
            <h3 class="font-semibold text-gray-700 mb-2">Hakem Değerlendirmeleri</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4 text-left">Hakem</th>
                            <th class="py-2 px-4 text-left">Puan</th>
                            <th class="py-2 px-4 text-left">Not</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($puanlar as $puan): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4"><?php echo htmlspecialchars($puan->hakem_adi); ?></td>
                            <td class="py-2 px-4 font-bold"><?php echo htmlspecialchars($puan->puan); ?></td>
                            <td class="py-2 px-4">
                                <?php
                                if ($user_rol == 1 || $user_id == $musabaka->gozlemci_id || $user_id == $puan->hakem_id) {
                                    echo htmlspecialchars($puan->not);
                                } else {
                                    echo '<span class="text-gray-400 italic">Gizli</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['action']) && $_GET['action'] == 'skor_gir' && $user_id == $musabaka->hakem_id): ?>
        <div class="mt-6 border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Müsabaka Sonucu Gir</h3>
            
            <form action="musabaka_detay.php?id=<?php echo $musabaka_id; ?>" method="POST">
                <div class="border p-4 rounded-md space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label for="durum" class="block text-sm font-medium text-gray-700">Durum</label>
                            <select id="durum" name="durum" class="mt-1 block w-full pl-3 pr-10 py-2 border-gray-300 rounded-md">
                                <option <?php if($musabaka->durum == 'Oynandı') echo 'selected'; ?>>Oynandı</option>
                                <option <?php if($musabaka->durum == 'İptal') echo 'selected'; ?>>İptal</option>
                                <option <?php if($musabaka->durum == 'Ertelendi') echo 'selected'; ?>>Ertelendi</option>
                            </select>
                        </div>
                        <?php
                            $skor_parcalari = explode('-', $musabaka->skor ?? '0-0');
                            $ev_skor = $skor_parcalari[0] ?? 0;
                            $misafir_skor = $skor_parcalari[1] ?? 0;
                        ?>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Skor</label>
                            <div class="flex items-center mt-1">
                                <span class="mr-2 text-gray-700"><?php echo htmlspecialchars($musabaka->ev_sahibi); ?></span>
                                <input type="number" name="ev_skor" value="<?php echo (int)$ev_skor; ?>" min="0" class="w-16 p-2 text-center border-gray-300 rounded-md">
                                <span class="mx-2 font-bold">-</span>
                                <input type="number" name="misafir_skor" value="<?php echo (int)$misafir_skor; ?>" min="0" class="w-16 p-2 text-center border-gray-300 rounded-md">
                                <span class="ml-2 text-gray-700"><?php echo htmlspecialchars($musabaka->misafir); ?></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="ihraclar" class="block text-sm font-medium text-gray-700">İhraç Bilgileri</label>
                        <textarea id="ihraclar" name="ihraclar" rows="3" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="Örn: Ali Veli, Ev Sahibi, 78. dk"><?php echo htmlspecialchars($musabaka->ihraclar ?? ''); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Varsa, her ihraç bilgisini yeni bir satıra yazın.</p>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" name="skor_kaydet" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Bilgileri Kaydet
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Orijinal 2.txt görevliler düzenleme toggles
    <?php if ($user_rol == 1): ?>
    document.getElementById('duzenle-btn').addEventListener('click', function() {
        document.getElementById('gorevli-listesi').classList.add('hidden');
        document.getElementById('gorevli-formu').classList.remove('hidden');
    });
    document.getElementById('iptal-btn').addEventListener('click', function() {
        document.getElementById('gorevli-listesi').classList.remove('hidden');
        document.getElementById('gorevli-formu').classList.add('hidden');
    });

    // 1.txt tarzı GENEL düzenleme toggles (yeni ID'ler, çakışma yok)
    const macDuzenleBtn = document.getElementById('mac-duzenle-btn');
    const macDuzenlemePaneli = document.getElementById('mac-duzenleme-paneli');
    const macIptalBtn = document.getElementById('mac-iptal-btn');
    if (macDuzenleBtn && macDuzenlemePaneli) {
        macDuzenleBtn.addEventListener('click', () => {
            macDuzenlemePaneli.classList.toggle('hidden');
        });
    }
    if (macIptalBtn && macDuzenlemePaneli) {
        macIptalBtn.addEventListener('click', () => {
            macDuzenlemePaneli.classList.add('hidden');
        });
    }
    <?php endif; ?>
</script>

<?php include 'templates/footer.php'; ?>
