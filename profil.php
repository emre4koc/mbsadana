<?php
require_once 'config/session_check.php';
require_once 'config/db.php';

$sayfa_baslik = "Profilim";
$user_id = $_SESSION['user_id'];
$mesaj_bilgi = '';
$mesaj_sifre = '';
$mesaj_email = '';

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Bilgi güncelleme formu
if (isset($_POST['bilgi_guncelle'])) {
    $telefon = preg_replace('/[^0-9]/', '', $_POST['telefon']);
    $dogum_tarihi = !empty($_POST['dogum_tarihi']) ? $_POST['dogum_tarihi'] : null;

    if (!empty($telefon) && (strlen($telefon) != 11 || substr($telefon, 0, 1) !== '0')) {
        $mesaj_bilgi = ['tip' => 'error', 'icerik' => 'Telefon numarası 11 hane olmalı ve 0 ile başlamalıdır.'];
    } else {
        // Sadece telefon ve doğum tarihi güncellenebilir, diğer alanlar sabit kalır
        $stmt = $pdo->prepare("UPDATE users SET telefon = ?, dogum_tarihi = ? WHERE id = ?");
        if ($stmt->execute([$telefon, $dogum_tarihi, $user_id])) {
            $mesaj_bilgi = ['tip' => 'success', 'icerik' => 'Bilgileriniz başarıyla güncellendi.'];
            header("Refresh: 2; url=profil.php");
        } else {
            $mesaj_bilgi = ['tip' => 'error', 'icerik' => 'Güncelleme sırasında bir hata oluştu.'];
        }
    }
}

// E-posta güncelleme formu
if (isset($_POST['email_guncelle'])) {
    $yeni_email = trim($_POST['yeni_email']);
    $yeni_email_tekrar = trim($_POST['yeni_email_tekrar']);
    $mevcut_sifre_email = $_POST['mevcut_sifre_email'];

    if (!password_verify($mevcut_sifre_email, $user->sifre)) {
        $mesaj_email = ['tip' => 'error', 'icerik' => 'Mevcut şifreniz hatalı.'];
    }
    elseif ($yeni_email !== $yeni_email_tekrar) {
        $mesaj_email = ['tip' => 'error', 'icerik' => 'Yeni e-posta adresleri uyuşmuyor.'];
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check->execute([$yeni_email, $user_id]);
        if ($stmt_check->fetch()) {
            $mesaj_email = ['tip' => 'error', 'icerik' => 'Bu e-posta adresi zaten başka bir kullanıcı tarafından kullanılıyor.'];
        } else {
            $stmt_update = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            if ($stmt_update->execute([$yeni_email, $user_id])) {
                $mesaj_email = ['tip' => 'success', 'icerik' => 'E-posta adresiniz başarıyla güncellendi.'];
                header("Refresh: 2; url=profil.php");
            } else {
                $mesaj_email = ['tip' => 'error', 'icerik' => 'E-posta güncellenirken bir hata oluştu.'];
            }
        }
    }
}


// Şifre güncelleme formu
if (isset($_POST['sifre_guncelle'])) {
    $mevcut_sifre = $_POST['mevcut_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];

    if (!password_verify($mevcut_sifre, $user->sifre)) {
        $mesaj_sifre = ['tip' => 'error', 'icerik' => 'Mevcut şifreniz hatalı.'];
    } elseif ($yeni_sifre !== $yeni_sifre_tekrar) {
        $mesaj_sifre = ['tip' => 'error', 'icerik' => 'Yeni şifreler uyuşmuyor.'];
    } elseif (strlen($yeni_sifre) < 6) {
        $mesaj_sifre = ['tip' => 'error', 'icerik' => 'Yeni şifre en az 6 karakter olmalıdır.'];
    } else {
        $hashed_password = password_hash($yeni_sifre, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET sifre = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $mesaj_sifre = ['tip' => 'success', 'icerik' => 'Şifreniz başarıyla değiştirildi.'];
        } else {
            $mesaj_sifre = ['tip' => 'error', 'icerik' => 'Şifre güncellenirken bir hata oluştu.'];
        }
    }
}

include 'templates/header.php';
?>
<div class="container mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Kişisel Bilgiler -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Kişisel Bilgiler</h2>
            <?php if ($mesaj_bilgi): ?>
                <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj_bilgi['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($mesaj_bilgi['icerik']); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-4">
                    <label for="ad" class="block text-sm font-medium text-gray-700">Ad</label>
                    <input type="text" name="ad" id="ad" value="<?php echo htmlspecialchars($user->ad); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-100" readonly>
                </div>
                <div class="mb-4">
                    <label for="soyad" class="block text-sm font-medium text-gray-700">Soyad</label>
                    <input type="text" name="soyad" id="soyad" value="<?php echo htmlspecialchars($user->soyad); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-100" readonly>
                </div>
                 <div class="mb-4">
                    <label for="telefon" class="block text-sm font-medium text-gray-700">Telefon (05xx xxx xx xx)</label>
                    <input type="tel" name="telefon" id="telefon" value="<?php echo htmlspecialchars($user->telefon); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" pattern="0[0-9]{10}" maxlength="11" placeholder="05321234567">
                </div>
                <div class="mb-4">
                    <label for="lisans_no" class="block text-sm font-medium text-gray-700">Lisans Numarası</label>
                    <input type="text" name="lisans_no" id="lisans_no" value="<?php echo htmlspecialchars($user->lisans_no); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-100" readonly>
                </div>
                <div class="mb-4">
                    <label for="dogum_tarihi" class="block text-sm font-medium text-gray-700">Doğum Tarihi</label>
                    <input type="date" name="dogum_tarihi" id="dogum_tarihi" value="<?php echo htmlspecialchars($user->dogum_tarihi); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <button type="submit" name="bilgi_guncelle" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Bilgileri Güncelle</button>
            </form>
        </div>

        <div class="space-y-6">
            <!-- E-posta Değiştirme -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">E-posta Değiştir</h2>
                <p class="text-sm text-gray-600 mb-4">Mevcut E-posta: <strong><?php echo htmlspecialchars($user->email); ?></strong></p>
                <?php if ($mesaj_email): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj_email['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($mesaj_email['icerik']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-4">
                        <label for="yeni_email" class="block text-sm font-medium text-gray-700">Yeni E-posta Adresi</label>
                        <input type="email" name="yeni_email" id="yeni_email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <div class="mb-4">
                        <label for="yeni_email_tekrar" class="block text-sm font-medium text-gray-700">Yeni E-posta Adresi (Tekrar)</label>
                        <input type="email" name="yeni_email_tekrar" id="yeni_email_tekrar" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <div class="mb-4">
                        <label for="mevcut_sifre_email" class="block text-sm font-medium text-gray-700">Değişikliği Onaylamak İçin Mevcut Şifreniz</label>
                        <input type="password" name="mevcut_sifre_email" id="mevcut_sifre_email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <button type="submit" name="email_guncelle" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">E-postayı Güncelle</button>
                </form>
            </div>
            <!-- Şifre Değiştirme -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Şifre Değiştir</h2>
                 <?php if ($mesaj_sifre): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj_sifre['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($mesaj_sifre['icerik']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-4">
                        <label for="mevcut_sifre" class="block text-sm font-medium text-gray-700">Mevcut Şifre</label>
                        <input type="password" name="mevcut_sifre" id="mevcut_sifre" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <div class="mb-4">
                        <label for="yeni_sifre" class="block text-sm font-medium text-gray-700">Yeni Şifre</label>
                        <input type="password" name="yeni_sifre" id="yeni_sifre" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <div class="mb-4">
                        <label for="yeni_sifre_tekrar" class="block text-sm font-medium text-gray-700">Yeni Şifre (Tekrar)</label>
                        <input type="password" name="yeni_sifre_tekrar" id="yeni_sifre_tekrar" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                    </div>
                    <button type="submit" name="sifre_guncelle" class="w-full bg-gray-800 text-white py-2 px-4 rounded-md hover:bg-gray-700">Şifreyi Değiştir</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>