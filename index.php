<?php
// Oturumu başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı oturum açmış mı diye kontrol et
if (isset($_SESSION['user_id'])) {
    // Oturum açıksa, ana sayfaya yönlendir
    header("Location: anasayfa.php");
    exit();
} else {
    // Oturum açık değilse, giriş sayfasına yönlendir
    header("Location: login.php");
    exit();
}
?>
