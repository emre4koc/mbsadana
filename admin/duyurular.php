<?php
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

$sayfa_baslik = "Duyuru Yönetimi";
$mesaj = '';

// Sayfa yüklendiğinde session'daki mesajı al ve temizle
if (isset($_SESSION['mesaj'])) {
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
}

// --- İŞLEM BLOKLARI ---

// Duyuru ekleme/güncelleme
if (isset($_POST['duyuru_kaydet'])) {
    $id = $_POST['id'];
    $baslik = trim($_POST['baslik']);
    $icerik = trim($_POST['icerik']);
    $tarih = !empty($_POST['tarih']) ? $_POST['tarih'] : null;

    if (empty($id)) { // Yeni duyuru ekleme
        $stmt = $pdo->prepare("INSERT INTO duyurular (baslik, icerik, tarih) VALUES (?, ?, ?)");
        if ($stmt->execute([$baslik, $icerik, $tarih])) {
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Duyuru başarıyla eklendi ve bildirimler gönderildi.'];
            
            // Tüm kullanıcılara bildirim gönder (yöneticiler hariç)
            $users_stmt = $pdo->query("SELECT id FROM users WHERE rol != 1");
            $user_ids = $users_stmt->fetchAll(PDO::FETCH_COLUMN);

            if ($user_ids) {
                $bildirim_mesaji = "Yeni duyuru: " . (strlen($baslik) > 50 ? substr($baslik, 0, 47) . '...' : $baslik);
                $bildirim_linki = "/anasayfa.php";
                
                $bildirim_stmt = $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)");
                foreach ($user_ids as $user_id) {
                    $bildirim_stmt->execute([$user_id, $bildirim_mesaji, $bildirim_linki]);
                }
            }
        } else {
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Duyuru eklenirken bir hata oluştu.'];
        }
    } else { // Mevcut duyuru güncelleme
        $stmt = $pdo->prepare("UPDATE duyurular SET baslik = ?, icerik = ?, tarih = ? WHERE id = ?");
        if ($stmt->execute([$baslik, $icerik, $tarih, $id])) {
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Duyuru başarıyla güncellendi.'];
        } else {
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Duyuru güncellenirken bir hata oluştu.'];
        }
    }
    header("Location: duyurular.php");
    exit();
}

// Arşivleme, Arşivden Çıkarma ve Silme İşlemleri
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'arsivle') {
        $pdo->prepare("UPDATE duyurular SET arsiv = 1 WHERE id = ?")->execute([$id]);
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Duyuru başarıyla arşivlendi.'];
    } elseif ($action == 'arsivden_cikar') {
        $pdo->prepare("UPDATE duyurular SET arsiv = 0 WHERE id = ?")->execute([$id]);
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Duyuru arşivden çıkarıldı.'];
    } elseif ($action == 'sil') {
        $pdo->prepare("DELETE FROM duyurular WHERE id = ?")->execute([$id]);
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Duyuru kalıcı olarak silindi.'];
    }
    header("Location: duyurular.php");
    exit();
}

// Düzenlenecek duyuruyu çek
$duzenlenecek_duyuru = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM duyurular WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_id']]);
    $duzenlenecek_duyuru = $stmt->fetch();
}

// Tüm duyuruları çek
$duyurular = $pdo->query("SELECT * FROM duyurular ORDER BY olusturma_tarihi DESC")->fetchAll();

include '../templates/header.php';
?>
<div class="container mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit">
            <h2 class="text-xl font-semibold mb-4 text-gray-800"><?php echo $duzenlenecek_duyuru ? 'Duyuruyu Düzenle' : 'Yeni Duyuru Ekle'; ?></h2>
            <?php if ($mesaj && (isset($_POST['duyuru_kaydet']) || isset($_SESSION['mesaj']))): ?>
                <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($mesaj['icerik']); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="duyurular.php">
                <input type="hidden" name="id" value="<?php echo $duzenlenecek_duyuru->id ?? ''; ?>">
                <div class="mb-4">
                    <label for="baslik" class="block text-sm font-medium text-gray-700">Başlık</label>
                    <input type="text" name="baslik" id="baslik" value="<?php echo htmlspecialchars($duzenlenecek_duyuru->baslik ?? ''); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                </div>
                <div class="mb-4">
                    <label for="icerik" class="block text-sm font-medium text-gray-700">İçerik</label>
                    <textarea name="icerik" id="icerik" rows="5" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required><?php echo htmlspecialchars($duzenlenecek_duyuru->icerik ?? ''); ?></textarea>
                </div>
                <div class="mb-4">
                    <label for="tarih" class="block text-sm font-medium text-gray-700">İlişkili Tarih (İsteğe Bağlı)</label>
                    <input type="date" name="tarih" id="tarih" value="<?php echo htmlspecialchars($duzenlenecek_duyuru->tarih ?? ''); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <button type="submit" name="duyuru_kaydet" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Kaydet</button>
                <?php if ($duzenlenecek_duyuru): ?>
                    <a href="duyurular.php" class="block text-center w-full bg-gray-200 text-gray-800 mt-2 py-2 px-4 rounded-md hover:bg-gray-300">İptal</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Tüm Duyurular</h2>
             <?php if ($mesaj && !isset($_POST['duyuru_kaydet'])): ?>
                <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($mesaj['icerik']); ?>
                </div>
            <?php endif; ?>
            <div class="overflow-y-auto max-h-[600px]">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($duyurular as $duyuru): ?>
                    <li class="py-3 <?php echo $duyuru->arsiv ? 'bg-gray-100 opacity-60' : ''; ?>">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($duyuru->baslik); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($duyuru->icerik); ?></p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?php echo date('d.m.Y', strtotime($duyuru->olusturma_tarihi)); ?>
                                    <?php if ($duyuru->tarih): ?> - İlgili Tarih: <?php echo date('d.m.Y', strtotime($duyuru->tarih)); ?><?php endif; ?>
                                </p>
                            </div>
                            <div class="flex-shrink-0 ml-4 flex items-center space-x-3">
                                <a href="?edit_id=<?php echo $duyuru->id; ?>" class="text-blue-500 hover:text-blue-700" title="Düzenle"><i class="fas fa-edit"></i></a>
                                <?php if ($duyuru->arsiv): ?>
                                    <a href="?action=arsivden_cikar&id=<?php echo $duyuru->id; ?>" class="text-green-500 hover:text-green-700" title="Arşivden Çıkar"><i class="fas fa-box-open"></i></a>
                                <?php else: ?>
                                    <a href="?action=arsivle&id=<?php echo $duyuru->id; ?>" class="text-yellow-500 hover:text-yellow-700" title="Arşivle"><i class="fas fa-archive"></i></a>
                                <?php endif; ?>
                                <a href="?action=sil&id=<?php echo $duyuru->id; ?>" class="text-red-500 hover:text-red-700" title="Sil" onclick="return confirm('Bu duyuruyu kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>
