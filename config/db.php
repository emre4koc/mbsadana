<?php
// Veritabanı Bağlantı Bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'mbsadana');
define('DB_PASS', 'mbsadana01'); // XAMPP varsayılan şifresi boştur
define('DB_NAME', 'mbsadana');

// Sitenin temel URL'sini bir kereye mahsus tanımla (linkler için)
if (!defined('BASE_URL')) {
    // Siteniz ana dizinde olduğu için bu değer boş ('') olmalıdır.
    define('BASE_URL', '');
}

// Sitenin sunucudaki kök dizin yolunu tanımla (dosya 'include' ve 'require' işlemleri için)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__)); // Bu satır, projenin ana klasörünün yolunu otomatik olarak bulur.
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die("Veritabanı bağlantısı başarısız: " . $e->getMessage());
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
