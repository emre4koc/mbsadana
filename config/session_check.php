<?php
// Veritabanı bağlantısını dahil et. Veritabanı dosyası oturumu başlatmayı zaten yönetiyor.
// Bu dosyanın diğer dosyalardan çağrıldığında doğru yolu bulabilmesi için __DIR__ kullanıyoruz.
require_once __DIR__ . '/db.php';

// Eğer kullanıcı ID'si session'da yoksa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Eğer ad, soyad gibi kritik oturum bilgileri eksikse,
// veritabanından bu bilgileri çekerek oturumu tamamla.
if (!isset($_SESSION['user_ad']) || !isset($_SESSION['user_soyad'])) {
    try {
        $stmt = $pdo->prepare("SELECT ad, soyad, rol FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            // Eksik oturum değişkenlerini doldur
            $_SESSION['user_ad'] = $user->ad;
            $_SESSION['user_soyad'] = $user->soyad;
            $_SESSION['user_rol'] = $user->rol;
        } else {
            // Eğer session'daki kullanıcı ID'si veritabanında yoksa (geçersizse),
            // oturumu sonlandır ve giriş sayfasına yönlendir.
            session_unset();
            session_destroy();
            header("Location: /login.php");
            exit();
        }
    } catch (PDOException $e) {
        // Olası bir veritabanı hatasında işlemi durdur ve hata mesajı göster.
        die("Oturum bilgileri doğrulanırken bir hata oluştu: " . $e->getMessage());
    }
}
?>
