<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Yönetim Paneli";

// İstatistikleri çek
$toplam_kullanici = $pdo->query("SELECT COUNT(*) FROM users WHERE rol != 1")->fetchColumn();
$aktif_musabaka = $pdo->query("SELECT COUNT(*) FROM musabakalar WHERE arsiv = 0")->fetchColumn();
$bekleyen_mazeret = $pdo->query("SELECT COUNT(*) FROM mazeretler WHERE durum = 'Beklemede'")->fetchColumn();

?>
<?php include '../templates/header.php'; ?>

<div class="container mx-auto">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-gray-500 text-sm font-medium">Toplam Kullanıcı</h3>
            <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $toplam_kullanici; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-gray-500 text-sm font-medium">Aktif Müsabaka</h3>
            <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $aktif_musabaka; ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-gray-500 text-sm font-medium">Bekleyen Mazeret Talebi</h3>
            <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $bekleyen_mazeret; ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="musabaka_yonetimi.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <i class="fas fa-futbol text-3xl text-green-500"></i>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-800">Müsabaka Yönetimi</h2>
                    <p class="text-gray-500 text-sm">Yeni müsabaka ekle, düzenle ve yönet.</p>
                </div>
            </div>
        </a>
        <a href="kullanicilar.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <i class="fas fa-users-cog text-3xl text-purple-500"></i>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-800">Kullanıcı Yönetimi</h2>
                    <p class="text-gray-500 text-sm">Kullanıcıları yönet ve yetkilerini düzenle.</p>
                </div>
            </div>
        </a>
        <a href="duyurular.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <i class="fas fa-bullhorn text-3xl text-yellow-500"></i>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-800">Duyuru Yönetimi</h2>
                    <p class="text-gray-500 text-sm">Tüm kullanıcılara duyuru ve bildirim gönder.</p>
                </div>
            </div>
        </a>
        <a href="mazeretler.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <i class="fas fa-file-medical-alt text-3xl text-red-500"></i>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-800">Mazeret Talepleri</h2>
                    <p class="text-gray-500 text-sm">Kullanıcıların mazeret taleplerini onayla/reddet.</p>
                </div>
            </div>
        </a>
        <a href="musaitlik_talepleri.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <i class="fas fa-user-check text-3xl text-indigo-500"></i>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-800">Müsaitlik Talepleri</h2>
                    <p class="text-gray-500 text-sm">Müsaitlik değişikliği taleplerini yönet.</p>
                </div>
            </div>
        </a>
        <a href="musaitlik_yonetimi.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <i class="fas fa-users-between-lines text-3xl text-indigo-500"></i>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-800">Müsaitlik Durumları</h2>
                    <p class="text-gray-500 text-sm">Kullanıcıların müsaitlik durumlarını gör/yönet.</p>
                </div>
            </div>
        </a>
        <a href="onay_takip.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="flex items-center">
                <i class="fas fa-check-double text-3xl text-blue-500"></i>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-800">Onay Takip</h2>
                    <p class="text-gray-500 text-sm">Haftalık görev onay durumlarını görüntüle.</p>
                </div>
            </div>
        </a>
    </div>
</div>

<?php include '../templates/footer.php'; ?>