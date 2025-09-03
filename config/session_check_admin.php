<?php
// Önce normal oturum kontrolünü dahil et
require_once __DIR__ . '/session_check.php';

// Kullanıcı rolünün yönetici olup olmadığını kontrol et
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 1) {
    // Yönetici değilse, ana sayfaya yönlendir
    header("Location: /mbs/anasayfa.php");
    exit();
}
?>
