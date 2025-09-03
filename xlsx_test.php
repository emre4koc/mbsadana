<?php
// Hata raporlamayı en üst seviyeye çıkararak sorunu görelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gerekli dosyalar
require_once 'config/db.php';
$library_path = __DIR__ . '/lib/SimpleXLSX.php';

// Kütüphaneyi kontrol et ve yükle
if (file_exists($library_path)) {
    require_once $library_path;
    
    // Kütüphane yüklendikten sonra sınıfın varlığını kontrol et
    if (!class_exists('SimpleXLSX')) {
        // Alternatif sınıf isimlerini dene
        if (class_exists('Shuchkin\SimpleXLSX')) {
            class_alias('Shuchkin\SimpleXLSX', 'SimpleXLSX');
        } elseif (class_exists('SimpleXLSX')) {
            // Zaten yüklü
        } else {
            die("SimpleXLSX sınıfı bulunamadı. Kütüphane farklı bir isim alanı kullanıyor olabilir.");
        }
    }
} else {
    die("SimpleXLSX kütüphanesi bulunamadı: " . $library_path);
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>XLSX Yükleme Test Aracı</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8 font-sans">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-4">Excel (.xlsx) Yükleme Test Aracı</h1>
        
        <div class="bg-blue-50 border border-blue-200 p-4 rounded-md mb-6">
            <h2 class="font-semibold text-lg text-blue-800">Test Adımları</h2>
            <ol class="list-decimal list-inside text-sm text-blue-700 mt-2 space-y-1">
                <li>Aşağıdaki "Dosya Seç" butonu ile, sisteme yüklemeye çalıştığınız `.xlsx` dosyasını seçin.</li>
                <li>"Test Et" butonuna tıklayın.</li>
                <li>Aşağıda çıkan sonuçları inceleyin. Eğer bir hata varsa, bu ekranda görünecektir.</li>
            </ol>
        </div>

        <form method="POST" enctype="multipart/form-data" class="mb-6">
            <label for="xlsx_file" class="block text-sm font-medium text-gray-700">Test Edilecek Excel Dosyası:</label>
            <input type="file" name="xlsx_file" id="xlsx_file" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50" accept=".xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            <button type="submit" class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Test Et</button>
        </form>

        <div class="bg-gray-50 p-4 rounded-lg border">
            <h2 class="font-semibold text-lg mb-2">Test Sonuçları</h2>
            <pre class="bg-gray-900 text-white p-4 rounded-md overflow-auto text-xs"><?php

// --- TEST BAŞLIYOR ---

echo "Test Başlatıldı...\n\n";

// 1. Kütüphane Kontrolü
echo "1. Kütüphane Kontrolü:\n";
if (file_exists($library_path)) {
    echo "   [BAŞARILI] SimpleXLSX.php kütüphanesi '{$library_path}' yolunda bulundu.\n";
    
    // Kütüphane yüklendikten sonra sınıfın varlığını kontrol et
    if (class_exists('SimpleXLSX')) {
        echo "   [BAŞARILI] SimpleXLSX sınıfı mevcut.\n\n";
    } elseif (class_exists('Shuchkin\SimpleXLSX')) {
        echo "   [BAŞARILI] Shuchkin\\SimpleXLSX sınıfı mevcut.\n\n";
        class_alias('Shuchkin\SimpleXLSX', 'SimpleXLSX');
    } else {
        echo "   [HATA] SimpleXLSX sınıfı bulunamadı. Mevcut sınıflar: \n";
        foreach (get_declared_classes() as $class) {
            if (strpos($class, 'XLSX') !== false || strpos($class, 'Excel') !== false) {
                echo "        - $class\n";
            }
        }
        die();
    }
} else {
    echo "   [HATA] SimpleXLSX.php kütüphanesi '{$library_path}' yolunda BULUNAMADI!\n";
    die();
}

// 2. Form Gönderimi Kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "2. Dosya Yükleme Kontrolü:\n";
    if (isset($_FILES['xlsx_file']) && $_FILES['xlsx_file']['error'] == 0) {
        echo "   [BAŞARILI] '" . htmlspecialchars($_FILES['xlsx_file']['name']) . "' adlı dosya sunucuya başarıyla yüklendi.\n\n";
        
        // 3. Dosya Okuma (Parse) Testi
        echo "3. Excel Okuma Testi:\n";
        
        // Farklı sınıf isimlerini dene
        $success = false;
        $error_msg = "";
        
        if (class_exists('SimpleXLSX')) {
            if ($xlsx = SimpleXLSX::parse($_FILES['xlsx_file']['tmp_name'])) {
                $success = true;
            } else {
                $error_msg = SimpleXLSX::parseError();
            }
        } elseif (class_exists('Shuchkin\SimpleXLSX')) {
            if ($xlsx = Shuchkin\SimpleXLSX::parse($_FILES['xlsx_file']['tmp_name'])) {
                $success = true;
            } else {
                $error_msg = Shuchkin\SimpleXLSX::parseError();
            }
        }
        
        if ($success) {
            echo "   [BAŞARILI] Excel dosyası başarıyla okundu ve çözümlendi.\n\n";
            echo "4. Dosya İçeriği:\n";
            echo "--------------------------------------------------\n";
            
            $rows = $xlsx->rows();
            if (empty($rows)) {
                echo "   Excel dosyasının içi boş.\n";
            } else {
                // Sadece ilk birkaç satırı göster (çok büyük dosyalar için)
                $max_rows_to_display = min(10, count($rows));
                for ($i = 0; $i < $max_rows_to_display; $i++) {
                    echo "   Satır " . ($i+1) . ": " . implode(" | ", array_map('htmlspecialchars', $rows[$i])) . "\n";
                }
                if (count($rows) > $max_rows_to_display) {
                    echo "   ... ve " . (count($rows) - $max_rows_to_display) . " satır daha.\n";
                }
            }
        } else {
            echo "   [HATA] Excel dosyası OKUNAMADI. Dosya formatı bozuk olabilir veya desteklenmiyor olabilir.\n";
            echo "   Hata Detayı: " . $error_msg . "\n";
        }
    } else {
        $error_msg = "Dosya yüklenemedi. ";
        if (isset($_FILES['xlsx_file'])) {
            switch ($_FILES['xlsx_file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_msg .= "Dosya boyutu çok büyük.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_msg .= "Dosyanın sadece bir kısmı yüklendi.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_msg .= "Hiçbir dosya yüklenmedi.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_msg .= "Geçici dizin bulunamadı.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_msg .= "Dosya diske yazılamadı.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_msg .= "Bir PHP eklentisi dosya yüklemeyi durdurdu.";
                    break;
                default:
                    $error_msg .= "Bilinmeyen bir hata oluştu.";
                    break;
            }
        } else {
            $error_msg .= "Dosya seçilmedi.";
        }
        echo "   [HATA] " . $error_msg . "\n";
    }
} else {
    echo "Testi başlatmak için lütfen bir .xlsx dosyası yükleyin.\n";
}

?></pre>
        </div>
    </div>
</body>
</html>