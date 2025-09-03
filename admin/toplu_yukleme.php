<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Toplu Veri Yükleme";
$mesaj = '';

// Bu kısım CSV dosyasını işlemek için daha detaylı kod gerektirir.
// Örnek olarak sadece dosya yükleme mantığı eklenmiştir.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_dosyasi'])) {
    if ($_FILES['csv_dosyasi']['error'] == 0) {
        $dosya_adi = $_FILES['csv_dosyasi']['name'];
        $dosya_tipi = $_FILES['csv_dosyasi']['type'];
        $tmp_name = $_FILES['csv_dosyasi']['tmp_name'];
        $dosya_uzantisi = pathinfo($dosya_adi, PATHINFO_EXTENSION);
        
        if (strtolower($dosya_uzantisi) === 'csv') {
            // CSV dosyasını işleme mantığı burada yer alacak
            // Örnek:
            // $file = fopen($tmp_name, 'r');
            // while (($line = fgetcsv($file)) !== FALSE) {
            //   // $line bir dizidir, veritabanına ekleme işlemleri yapılır
            // }
            // fclose($file);
            $mesaj = ['tip' => 'success', 'icerik' => "CSV dosyası '$dosya_adi' başarıyla yüklendi ve işlenmeye hazır."];
        } else {
            $mesaj = ['tip' => 'error', 'icerik' => 'Lütfen sadece CSV formatında bir dosya yükleyin.'];
        }
    } else {
        $mesaj = ['tip' => 'error', 'icerik' => 'Dosya yüklenirken bir hata oluştu.'];
    }
}

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Toplu Yükleme</h2>
        
        <?php if ($mesaj): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($mesaj['icerik']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Toplu Müsabaka Yükleme -->
            <div class="border p-4 rounded-md">
                <h3 class="font-semibold mb-2">Toplu Müsabaka Yükle</h3>
                <p class="text-sm text-gray-600 mb-4">CSV dosyanızın sütunları şu sırada olmalıdır: Hafta No, Tarih (YYYY-AA-GG), Saat (SS:DD), Lig Adı, Stadyum Adı, Ev Sahibi, Misafir, Hakem E-posta, 1. Yrd. E-posta, 2. Yrd. E-posta, 4. Hakem E-posta, Gözlemci E-posta</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="yukleme_tipi" value="musabaka">
                    <input type="file" name="csv_dosyasi" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50" required>
                    <button type="submit" class="mt-4 w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Müsabakaları Yükle</button>
                </form>
            </div>

            <!-- Toplu Kullanıcı Yükleme -->
            <div class="border p-4 rounded-md">
                <h3 class="font-semibold mb-2">Toplu Kullanıcı Yükle</h3>
                <p class="text-sm text-gray-600 mb-4">CSV dosyanızın sütunları şu sırada olmalıdır: Ad, Soyad, E-posta, Şifre, Rol (2:Hakem, 3:Gözlemci), Klasman, Telefon</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="yukleme_tipi" value="kullanici">
                    <input type="file" name="csv_dosyasi" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50" required>
                    <button type="submit" class="mt-4 w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700">Kullanıcıları Yükle</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>
