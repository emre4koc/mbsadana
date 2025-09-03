<?php
require_once '../config/db.php'; // Veritabanı ve oturum başlatma

if (!isset($_SESSION['user_id'])) {
    // Giriş yapmamış kullanıcılar için işlemi sonlandır
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Kullanıcının okunmamış tüm bildirimlerini 'okundu' olarak işaretle
    $stmt = $pdo->prepare("UPDATE bildirimler SET okundu = 1 WHERE user_id = ? AND okundu = 0");
    $stmt->execute([$user_id]);

    // Başarılı yanıt gönder
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    // Hata durumunda sunucu hatası yanıtı gönder
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Veritabanı hatası.']);
}
?>
