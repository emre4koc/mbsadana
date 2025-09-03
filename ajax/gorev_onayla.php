<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$musabaka_id = isset($_POST['musabaka_id']) ? (int)$_POST['musabaka_id'] : 0;

header('Content-Type: application/json');

if ($musabaka_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz müsabaka ID.']);
    exit();
}

try {
    // Müsabakadaki kullanıcının rolünü bul
    $stmt = $pdo->prepare("SELECT * FROM musabakalar WHERE id = ?");
    $stmt->execute([$musabaka_id]);
    $musabaka = $stmt->fetch();

    if (!$musabaka) {
        echo json_encode(['status' => 'error', 'message' => 'Müsabaka bulunamadı.']);
        exit();
    }
    
    $kolon_adi = '';
    if ($musabaka->hakem_id == $user_id) $kolon_adi = 'hakem_onay';
    elseif ($musabaka->yardimci_1_id == $user_id) $kolon_adi = 'yardimci_1_onay';
    elseif ($musabaka->yardimci_2_id == $user_id) $kolon_adi = 'yardimci_2_onay';
    elseif ($musabaka->dorduncu_hakem_id == $user_id) $kolon_adi = 'dorduncu_hakem_onay';
    elseif ($musabaka->gozlemci_id == $user_id) $kolon_adi = 'gozlemci_onay';

    if (!empty($kolon_adi)) {
        // Onay durumunu güncelle
        $update_stmt = $pdo->prepare("UPDATE musabakalar SET {$kolon_adi} = 1 WHERE id = ?");
        $update_stmt->execute([$musabaka_id]);
        
        // Yöneticiye bildirim gönder
        $admin_id = $pdo->query("SELECT id FROM users WHERE rol = 1 LIMIT 1")->fetchColumn();
        if ($admin_id) {
            $kullanici_adi = $_SESSION['user_ad'] . ' ' . $_SESSION['user_soyad'];
            $musabaka_bilgi_stmt = $pdo->prepare("SELECT t1.ad as ev_sahibi, t2.ad as misafir FROM musabakalar m JOIN takimlar t1 ON m.ev_sahibi_id=t1.id JOIN takimlar t2 ON m.misafir_id=t2.id WHERE m.id = ?");
            $musabaka_bilgi_stmt->execute([$musabaka_id]);
            $musabaka_bilgi = $musabaka_bilgi_stmt->fetch();
            $mac_adi = $musabaka_bilgi ? "{$musabaka_bilgi->ev_sahibi} - {$musabaka_bilgi->misafir}" : "ilgili müsabaka";
            
            $bildirim_mesaji = "{$kullanici_adi}, {$mac_adi} görevini onayladı.";
            $bildirim_linki = "/musabaka_detay.php?id={$musabaka_id}";
            $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)")->execute([$admin_id, $bildirim_mesaji, $bildirim_linki]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Görev başarıyla onaylandı.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Bu müsabakada göreviniz bulunmamaktadır.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Bir veritabanı hatası oluştu.']);
}
?>
