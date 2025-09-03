<?php
// Bu dosyanın en üstüne eklenecek PHP kodları
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// BASE_URL tanımı (config/db.php'den gelmiyorsa burada tanımlayalım)
if (!defined('BASE_URL')) {
    // BASE_URL'i doğru şekilde oluştur
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
    define('BASE_URL', rtrim($base_url, '/'));
}

// --- View Mode Toggle Logic ---
// 1. Mevcut modu belirle. Varsayılan 'mobil'.
$current_mode = $_SESSION['view_mode'] ?? 'mobile';

// 2. Değiştirme isteği var mı diye kontrol et
if (isset($_GET['toggle_view'])) {
    // Mevcut moda göre değiştir
    if ($current_mode === 'mobile') {
        $_SESSION['view_mode'] = 'desktop';
    } else {
        $_SESSION['view_mode'] = 'mobile';
    }

    // Sayfa yenilendiğinde tekrar değişmemesi için parametresiz URL'e yönlendir
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $redirect_url);
    exit();
}

// 3. Sayfa yüklemesi için son modu ayarla
$view_mode = $_SESSION['view_mode'] ?? 'mobile';

// 4. Moda göre viewport içeriğini ayarla
$viewport_content = ($view_mode === 'mobile') 
    ? 'width=device-width, initial-scale=1.0' 
    : 'width=1280';

// Kullanıcı giriş yapmışsa bildirimleri yükle
if (isset($_SESSION['user_id'])) {
    // config/db.php dosyasını dahil et (eğer zaten dahil edilmediyse)
    if (!isset($pdo)) {
        require_once __DIR__ . '/../config/db.php';
    }
    
    $user_id_for_notif = $_SESSION['user_id'];
    // Okunmamış bildirim sayısını çek
    $unread_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM bildirimler WHERE user_id = ? AND okundu = 0");
    $unread_count_stmt->execute([$user_id_for_notif]);
    $unread_count = $unread_count_stmt->fetchColumn();

    // Son 5 bildirimi çek
    $notifications_stmt = $pdo->prepare("SELECT * FROM bildirimler WHERE user_id = ? ORDER BY olusturma_tarihi DESC LIMIT 5");
    $notifications_stmt->execute([$user_id_for_notif]);
    $notifications = $notifications_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="<?php echo $viewport_content; ?>">
    <title>Müsabaka Bilgi Sistemi</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome (ikonlar için) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Özel CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
    <!-- JavaScript için temel URL değişkeni -->
    <script>
        const baseURL = '<?php echo BASE_URL; ?>';
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Başlık (Header) - Sabit -->
            <header class="flex justify-between items-center p-4 bg-white border-b-2 border-gray-200 shadow-sm z-20 flex-shrink-0">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="text-gray-500 focus:outline-none md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                    <h1 class="text-xl font-semibold text-gray-700 ml-4"><?php echo $sayfa_baslik ?? 'Anasayfa'; ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- YENİ EKLENDİ: Görünüm Değiştirme Butonu -->
                    <a href="?toggle_view=1" class="p-2 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200" title="<?php echo $view_mode === 'mobile' ? 'Masaüstü Görünümüne Geç' : 'Mobil Görünüme Geç'; ?>">
                        <?php if ($view_mode === 'mobile'): ?>
                            <i class="fas fa-desktop"></i>
                        <?php else: ?>
                            <i class="fas fa-mobile-alt"></i>
                        <?php endif; ?>
                    </a>

                    <div class="relative">
                        <button id="notification-bell" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 focus:outline-none">
                            <i class="fas fa-bell text-gray-600"></i>
                            <?php if (isset($unread_count) && $unread_count > 0): ?>
                                <span id="notification-badge" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="notification-panel" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl overflow-hidden z-30 hidden">
                            <div class="py-2 px-4 text-sm font-semibold text-gray-700 border-b">Bildirimler</div>
                            <div id="notification-list" class="divide-y max-h-80 overflow-y-auto">
                                <?php if (isset($notifications) && !empty($notifications)): ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <a href="<?php echo BASE_URL . htmlspecialchars($notification->link ?? '#'); ?>" class="block py-3 px-4 hover:bg-gray-100 <?php echo $notification->okundu == 0 ? 'font-bold' : 'font-normal'; ?>">
                                            <p class="text-sm text-gray-800"><?php echo htmlspecialchars($notification->mesaj); ?></p>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo date('d.m.Y H:i', strtotime($notification->olusturma_tarihi)); ?></p>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="py-4 px-4 text-sm text-gray-500 text-center">Yeni bildiriminiz yok.</p>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($notifications) && !empty($notifications)): ?>
                            <div id="notification-footer" class="py-2 px-4 border-t text-center bg-gray-50">
                                <button id="clear-notifications-btn" class="text-sm text-red-600 hover:underline focus:outline-none">Tümünü Sil</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="font-semibold text-gray-600 hidden sm:block">
                        <?php echo htmlspecialchars($_SESSION['user_ad'] . ' ' . $_SESSION['user_soyad']); ?>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200" title="Çıkış Yap"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </header>
            <main class="flex-1 overflow-auto p-6">
                <!-- Sayfa içeriği burada başlayacak -->