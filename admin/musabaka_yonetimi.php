<?php
ob_start(); // Çıktı tamponlamasını başlatarak HTTP 500 hatasını engeller.
require_once '../config/session_check_admin.php';
require_once '../config/db.php';

// --- SimpleXLSX KÜTÜPHANE KONTROLÜ VE YÜKLEME (1.txt'den GÜNCELLENDİ) ---
$library_path = '../lib/SimpleXLSX.php';

// Kütüphaneyi kontrol et ve yükle
if (file_exists($library_path)) {
    require_once $library_path;
    // Kütüphane yüklendikten sonra sınıfın varlığını kontrol et
    if (!class_exists('SimpleXLSX')) {
        // Alternatif sınıf isimlerini dene
        if (class_exists('Shuchkin\SimpleXLSX')) {
            class_alias('Shuchkin\SimpleXLSX', 'SimpleXLSX');
        }
    }
}
// --- GÜNCELLEME SONU ---


$sayfa_baslik = "Müsabaka Yönetimi";
$mesaj = '';
// Sayfa yüklendiğinde session'daki mesajı al ve temizle
if (isset($_SESSION['mesaj'])) {
    $mesaj = $_SESSION['mesaj'];
    unset($_SESSION['mesaj']);
}

// --- YARDIMCI FONKSİYONLAR ---
function getUserIdAndEmailByName($pdo_stmt, $name) {
    $name = trim($name);
    if (empty($name)) return null;
    try {
        $pdo_stmt->execute([$name]);
        $result = $pdo_stmt->fetch();
        // Hem ID hem de e-posta adresini döndür
        return $result ? (object)['id' => $result->id, 'email' => $result->email, 'ad_soyad' => $name] : null;
    } catch (PDOException $e) {
        error_log("getUserIdAndEmailByName hatası: " . $e->getMessage());
        return null;
    }
}

function getIdAndCreateIfNeeded($pdo, &$map, $name, $tableName) {
    $name = trim($name);
    if (empty($name)) return null;
    if (isset($map[$name])) return $map[$name];
    try {
        $stmt = $pdo->prepare("INSERT INTO {$tableName} (ad) VALUES (?)");
        $stmt->execute([$name]);
        $newId = $pdo->lastInsertId();
        $map[$name] = $newId;
        return $newId;
    } catch (PDOException $e) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM {$tableName} WHERE ad = ?");
            $stmt->execute([$name]);
            $existingId = $stmt->fetchColumn();
            if ($existingId) { 
                $map[$name] = $existingId;
                return $existingId; 
            }
            return null;
        } catch (PDOException $e2) {
            error_log("getIdAndCreateIfNeeded hatası: " . $e2->getMessage());
            return null;
        }
    }
}

function deleteMatch($pdo, $musabaka_id) {
    try {
        $rapor_stmt = $pdo->prepare("SELECT id FROM raporlar WHERE musabaka_id = ?");
        $rapor_stmt->execute([$musabaka_id]);
        $rapor = $rapor_stmt->fetch();
        if ($rapor) {
            $pdo->prepare("DELETE FROM rapor_detaylari WHERE rapor_id = ?")->execute([$rapor->id]);
            $pdo->prepare("DELETE FROM raporlar WHERE id = ?")->execute([$rapor->id]);
        }
        $pdo->prepare("DELETE FROM musabakalar WHERE id = ?")->execute([$musabaka_id]);
    } catch (PDOException $e) {
        error_log("deleteMatch hatası: " . $e->getMessage());
        throw $e;
    }
}

// Veritabanı bildirimi gönderme
function sendDbNotification($pdo, $gorevli_bilgileri, $musabaka_id) {
    $gorevliler = array_filter($gorevli_bilgileri, function($g) { return !empty($g) && !empty($g->id); });
    if (empty($gorevliler)) return;

    $musabaka_stmt = $pdo->prepare("SELECT t1.ad as ev_sahibi, t2.ad as misafir FROM musabakalar m JOIN takimlar t1 ON m.ev_sahibi_id=t1.id JOIN takimlar t2 ON m.misafir_id=t2.id WHERE m.id = ?");
    $musabaka_stmt->execute([$musabaka_id]);
    $musabaka = $musabaka_stmt->fetch();
    if (!$musabaka) return;

    $bildirim_mesaji = "Yeni görev atandı: {$musabaka->ev_sahibi} - {$musabaka->misafir}";
    $bildirim_linki = "/musabaka_detay.php?id={$musabaka_id}";
    $bildirim_stmt = $pdo->prepare("INSERT INTO bildirimler (user_id, mesaj, link) VALUES (?, ?, ?)");
    foreach ($gorevliler as $gorevli) {
        try {
            $bildirim_stmt->execute([$gorevli->id, $bildirim_mesaji, $bildirim_linki]);
        } catch (PDOException $e) {
            error_log("Bildirim gönderme hatası: " . $e->getMessage());
        }
    }
}

// --- İŞLEM BLOKLARI ---

// MÜSABAKA SİLME İŞLEMİ (Tekli)
if (isset($_GET['action']) && $_GET['action'] == 'sil' && isset($_GET['id'])) {
    $musabaka_id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();
        deleteMatch($pdo, $musabaka_id);
        $pdo->commit();
        $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Müsabaka başarıyla silindi.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Silme işlemi sırasında bir hata oluştu: ' . $e->getMessage()];
    }
    header("Location: musabaka_yonetimi.php");
    exit();
}

// Toplu İşlemler (Arşivleme ve Silme)
if (isset($_POST['toplu_islem_uygula']) && !empty($_POST['musabaka_ids'])) {
    $ids = $_POST['musabaka_ids'];
    $islem = $_POST['toplu_islem'];
    
    // IDs'leri integer'a çevir ve validate et
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function($id) { return $id > 0; });
    if (empty($ids)) {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Geçerli müsabaka seçilmedi.'];
        header("Location: musabaka_yonetimi.php");
        exit();
    }
    
    if ($islem == 'arsivle') {
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE musabakalar SET arsiv = 1 WHERE id IN ($placeholders)");
            if ($stmt->execute($ids)) {
                $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => count($ids) . ' adet müsabaka başarıyla arşivlendi.'];
            } else {
                $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Arşivleme sırasında bir hata oluştu.'];
            }
        } catch (PDOException $e) {
            error_log("Arşivleme hatası: " . $e->getMessage());
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Arşivleme sırasında bir hata oluştu.'];
        }
    } elseif ($islem == 'sil') {
        try {
            $pdo->beginTransaction();
            foreach($ids as $id) {
                deleteMatch($pdo, (int)$id);
            }
            $pdo->commit();
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => count($ids) . ' adet müsabaka başarıyla silindi.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Toplu silme hatası: " . $e->getMessage());
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Toplu silme işlemi sırasında bir hata oluştu.'];
        }
    }
    header("Location: musabaka_yonetimi.php");
    exit();
}

// Manuel Müsabaka Ekleme
if (isset($_POST['manuel_musabaka_ekle'])) {
    $mac_no = trim($_POST['mac_no']);
    $hafta_no = (int)$_POST['hafta_no'];
    $tarih = $_POST['tarih'];
    $saat = $_POST['saat'];
    $lig_id = (int)$_POST['lig_id'];
    $stadyum_id = (int)$_POST['stadyum_id'];
    $ev_sahibi_id = (int)$_POST['ev_sahibi_id'];
    $misafir_id = (int)$_POST['misafir_id'];
    $hakem_id = !empty($_POST['hakem_id']) ? (int)$_POST['hakem_id'] : null;
    $yardimci_1_id = !empty($_POST['yardimci_1_id']) ? (int)$_POST['yardimci_1_id'] : null;
    $yardimci_2_id = !empty($_POST['yardimci_2_id']) ? (int)$_POST['yardimci_2_id'] : null;
    $dorduncu_hakem_id = !empty($_POST['dorduncu_hakem_id']) ? (int)$_POST['dorduncu_hakem_id'] : null;
    $gozlemci_id = !empty($_POST['gozlemci_id']) ? (int)$_POST['gozlemci_id'] : null;
    $durum = 'Atandı';
    
    if ($ev_sahibi_id == $misafir_id) {
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Ev sahibi ve misafir takım aynı olamaz.'];
        header("Location: musabaka_yonetimi.php");
        exit();
    }

    // --- BAŞLANGIÇ: GÜNCELLENEN VE HATASI GİDERİLEN BLOK ---
    try {
        // HATA GİDERİLDİ: INSERT sorgusundaki 14 adet '?' ile execute() içindeki 14 adet değişken eşleştirildi.
        $stmt = $pdo->prepare("INSERT INTO musabakalar (mac_no, hafta_no, tarih, saat, lig_id, stadyum_id, ev_sahibi_id, misafir_id, hakem_id, yardimci_1_id, yardimci_2_id, dorduncu_hakem_id, gozlemci_id, bildirim_gonderildi, arsiv, durum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)");
        
        if ($stmt->execute([$mac_no, $hafta_no, $tarih, $saat, $lig_id, $stadyum_id, $ev_sahibi_id, $misafir_id, $hakem_id, $yardimci_1_id, $yardimci_2_id, $dorduncu_hakem_id, $gozlemci_id, $durum])) {
            $yeni_musabaka_id = $pdo->lastInsertId();
            
            // Otomatik bildirim gönderme (Fazladan DB sorgusu yapmayacak şekilde optimize edildi)
            $gorevli_idler = array_filter([$hakem_id, $yardimci_1_id, $yardimci_2_id, $dorduncu_hakem_id, $gozlemci_id]);
            if (!empty($gorevli_idler)) {
                $gorevli_bilgileri = [];
                foreach ($gorevli_idler as $id) {
                    $gorevli_bilgileri[] = (object)['id' => $id];
                }
                sendDbNotification($pdo, $gorevli_bilgileri, $yeni_musabaka_id);
            }
            
            $_SESSION['mesaj'] = ['tip' => 'success', 'icerik' => 'Müsabaka başarıyla eklendi ve bildirimler gönderildi.'];
        } else {
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Müsabaka eklenirken bir hata oluştu.'];
        }
    } catch (PDOException $e) {
        error_log("Manuel müsabaka ekleme hatası: " . $e->getMessage());
        $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'Müsabaka eklenirken bir veritabanı hatası oluştu: ' . $e->getMessage()];
    }
    // --- BİTİŞ: GÜNCELLENEN VE HATASI GİDERİLEN BLOK ---

    header("Location: musabaka_yonetimi.php");
    exit();
}

// Toplu Müsabaka Yükleme (CSV)
if (isset($_POST['toplu_musabaka_yukle_csv'])) {
    set_time_limit(0);
    $mesaj_icerik = 'Dosya yüklenirken bir hata oluştu.';
    $mesaj_tip = 'error';
    if (isset($_FILES['csv_dosyasi']) && $_FILES['csv_dosyasi']['error'] == 0) {
        $file = fopen($_FILES['csv_dosyasi']['tmp_name'], 'r');
        if ($file === false) {
            $_SESSION['mesaj'] = ['tip' => 'error', 'icerik' => 'CSV dosyası açılamadı.'];
            header("Location: musabaka_yonetimi.php");
            exit();
        }
        
        // İlk satırı (header) atla
        fgetcsv($file, 1000, ";");
        try {
            $pdo->beginTransaction();
            // Cache'leri hazırla
            $ligler_map = $pdo->query("SELECT ad, id FROM ligler")->fetchAll(PDO::FETCH_KEY_PAIR);
            $stadyumlar_map = $pdo->query("SELECT ad, id FROM stadyumlar")->fetchAll(PDO::FETCH_KEY_PAIR);
            $takimlar_map = $pdo->query("SELECT ad, id FROM takimlar")->fetchAll(PDO::FETCH_KEY_PAIR);
            // User statement'ını hazırla
            $user_stmt = $pdo->prepare("SELECT id, email FROM users WHERE CONCAT(ad, ' ', soyad) = ? LIMIT 1");
            $eklenen_sayisi = 0;
            $hata_sayisi = 0;
            $satir_no = 1;
            
            $stmt = $pdo->prepare("INSERT INTO musabakalar (mac_no, hafta_no, tarih, saat, lig_id, stadyum_id, ev_sahibi_id, misafir_id, hakem_id, yardimci_1_id, yardimci_2_id, dorduncu_hakem_id, gozlemci_id, bildirim_gonderildi, arsiv, durum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)");
            while (($line = fgetcsv($file, 1000, ";")) !== FALSE) {
                try {
                    $satir_no++;
                    if (count($line) < 8) {
                        error_log("CSV Satır {$satir_no}: Eksik sütun");
                        $hata_sayisi++;
                        continue;
                    }
                    
                    $tarih_str = trim($line[2]);
                    $tarih_obj = DateTime::createFromFormat('d.m.Y', $tarih_str) ?: DateTime::createFromFormat('Y-m-d', $tarih_str);
                    $formatted_tarih = $tarih_obj ? $tarih_obj->format('Y-m-d') : null;
                    if ($formatted_tarih === null) {
                        error_log("CSV Satır {$satir_no}: Geçersiz tarih formatı: {$tarih_str}");
                        $hata_sayisi++;
                        continue;
                    }

                    $lig_id = getIdAndCreateIfNeeded($pdo, $ligler_map, trim($line[4]), 'ligler');
                    $stadyum_id = getIdAndCreateIfNeeded($pdo, $stadyumlar_map, trim($line[5]), 'stadyumlar');
                    $ev_sahibi_id = getIdAndCreateIfNeeded($pdo, $takimlar_map, trim($line[6]), 'takimlar');
                    $misafir_id = getIdAndCreateIfNeeded($pdo, $takimlar_map, trim($line[7]), 'takimlar');
                    if (!$lig_id || !$stadyum_id || !$ev_sahibi_id || !$misafir_id) {
                        error_log("CSV Satır {$satir_no}: Gerekli ID'ler oluşturulamadı");
                        $hata_sayisi++;
                        continue;
                    }
                    
                    $hakem_info = isset($line[8]) ? getUserIdAndEmailByName($user_stmt, trim($line[8])) : null;
                    $yardimci_1_info = isset($line[9]) ? getUserIdAndEmailByName($user_stmt, trim($line[9])) : null;
                    $yardimci_2_info = isset($line[10]) ? getUserIdAndEmailByName($user_stmt, trim($line[10])) : null;
                    $dorduncu_hakem_info = isset($line[11]) ? getUserIdAndEmailByName($user_stmt, trim($line[11])) : null;
                    $gozlemci_info = isset($line[12]) ? getUserIdAndEmailByName($user_stmt, trim($line[12])) : null;
                    $durum = 'Atandı';
                    
                    if ($stmt->execute([
                        trim($line[0]),
                        (int)trim($line[1]),
                        $formatted_tarih,
                        trim($line[3]),
                        $lig_id,
                        $stadyum_id,
                        $ev_sahibi_id,
                        $misafir_id,
                        $hakem_info ? $hakem_info->id : null,
                        $yardimci_1_info ? $yardimci_1_info->id : null,
                        $yardimci_2_info ? $yardimci_2_info->id : null,
                        $dorduncu_hakem_info ? $dorduncu_hakem_info->id : null,
                        $gozlemci_info ? $gozlemci_info->id : null,
                        $durum
                    ])) {
                        $yeni_musabaka_id = $pdo->lastInsertId();
                        $gorevli_bilgileri = array_filter([
                            $hakem_info, $yardimci_1_info, $yardimci_2_info, $dorduncu_hakem_info, $gozlemci_info
                        ]);
                        if (!empty($gorevli_bilgileri)) {
                            sendDbNotification($pdo, $gorevli_bilgileri, $yeni_musabaka_id);
                        }
                        $eklenen_sayisi++;
                    } else {
                        $hata_sayisi++;
                        error_log("CSV Satır {$satir_no}: Veritabanı ekleme hatası");
                    }
                } catch (Exception $e) {
                    $hata_sayisi++;
                    error_log("CSV Satır {$satir_no} hatası: " . $e->getMessage());
                }
            }
            
            $pdo->commit();
            fclose($file);

            if ($eklenen_sayisi > 0) {
                $mesaj_icerik = "{$eklenen_sayisi} adet müsabaka başarıyla işlendi ve bildirimler gönderildi.";
                if ($hata_sayisi > 0) {
                    $mesaj_icerik .= " {$hata_sayisi} satırda hata oluştu.";
                }
                $mesaj_tip = 'success';
            } else {
                $mesaj_icerik = "Hiçbir müsabaka eklenemedi. {$hata_sayisi} satırda hata oluştu.";
                $mesaj_tip = 'error';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("CSV yükleme genel hatası: " . $e->getMessage());
            $mesaj_icerik = 'CSV yükleme sırasında veritabanı hatası oluştu: ' . $e->getMessage();
            $mesaj_tip = 'error';
            if (isset($file) && $file) fclose($file);
        }
    }
    $_SESSION['mesaj'] = ['tip' => $mesaj_tip, 'icerik' => $mesaj_icerik];
    header("Location: musabaka_yonetimi.php");
    exit();
}

// Toplu Müsabaka Yükleme (Excel)
if (isset($_POST['toplu_musabaka_yukle_excel'])) {
    $mesaj_icerik = 'Dosya yüklenirken bir hata oluştu.';
    $mesaj_tip = 'error';
    if (class_exists('SimpleXLSX') && isset($_FILES['xlsx_dosyasi']) && $_FILES['xlsx_dosyasi']['error'] == 0) {
        set_time_limit(0);
        try {
            $pdo->beginTransaction();
            $xlsx = SimpleXLSX::parse($_FILES['xlsx_dosyasi']['tmp_name']);
            if ($xlsx) {
                $ligler_map = $pdo->query("SELECT ad, id FROM ligler")->fetchAll(PDO::FETCH_KEY_PAIR);
                $stadyumlar_map = $pdo->query("SELECT ad, id FROM stadyumlar")->fetchAll(PDO::FETCH_KEY_PAIR);
                $takimlar_map = $pdo->query("SELECT ad, id FROM takimlar")->fetchAll(PDO::FETCH_KEY_PAIR);
                $user_stmt = $pdo->prepare("SELECT id, email FROM users WHERE CONCAT(ad, ' ', soyad) = ? LIMIT 1");
                
                $eklenen_sayisi = 0;
                $hata_sayisi = 0;
                $satir_no = 0;

                $stmt = $pdo->prepare("INSERT INTO musabakalar (mac_no, hafta_no, tarih, saat, lig_id, stadyum_id, ev_sahibi_id, misafir_id, hakem_id, yardimci_1_id, yardimci_2_id, dorduncu_hakem_id, gozlemci_id, bildirim_gonderildi, arsiv, durum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)");
                foreach ($xlsx->rows() as $i => $row) {
                    $satir_no++;
                    if ($i === 0) continue; // Başlık satırını atla

                    try {
                         if (count($row) < 8) {
                            error_log("Excel Satır {$satir_no}: Eksik sütun");
                            $hata_sayisi++;
                            continue;
                        }

                        $tarih_excel = trim($row[2]);
                        $tarih_obj = DateTime::createFromFormat('d.m.Y', $tarih_excel) ?: DateTime::createFromFormat('Y-m-d', $tarih_excel);
                        $formatted_tarih = $tarih_obj ? $tarih_obj->format('Y-m-d') : null;
                        if ($formatted_tarih === null) {
                             error_log("Excel Satır {$satir_no}: Geçersiz tarih formatı: {$tarih_excel}");
                             $hata_sayisi++;
                             continue;
                        }
                        
                        $lig_id = getIdAndCreateIfNeeded($pdo, $ligler_map, trim($row[4]), 'ligler');
                        $stadyum_id = getIdAndCreateIfNeeded($pdo, $stadyumlar_map, trim($row[5]), 'stadyumlar');
                        $ev_sahibi_id = getIdAndCreateIfNeeded($pdo, $takimlar_map, trim($row[6]), 'takimlar');
                        $misafir_id = getIdAndCreateIfNeeded($pdo, $takimlar_map, trim($row[7]), 'takimlar');
                        if (!$lig_id || !$stadyum_id || !$ev_sahibi_id || !$misafir_id) {
                            error_log("Excel Satır {$satir_no}: Gerekli ID'ler oluşturulamadı");
                            $hata_sayisi++;
                            continue;
                        }

                        $hakem_info = isset($row[8]) ? getUserIdAndEmailByName($user_stmt, trim($row[8])) : null;
                        $yardimci_1_info = isset($row[9]) ? getUserIdAndEmailByName($user_stmt, trim($row[9])) : null;
                        $yardimci_2_info = isset($row[10]) ? getUserIdAndEmailByName($user_stmt, trim($row[10])) : null;
                        $dorduncu_hakem_info = isset($row[11]) ? getUserIdAndEmailByName($user_stmt, trim($row[11])) : null;
                        $gozlemci_info = isset($row[12]) ? getUserIdAndEmailByName($user_stmt, trim($row[12])) : null;
                        $durum = 'Atandı';
                        if ($stmt->execute([ 
                            trim($row[0]),
                            (int)trim($row[1]),
                            $formatted_tarih, 
                            trim($row[3]),
                            $lig_id, 
                            $stadyum_id, 
                            $ev_sahibi_id, 
                            $misafir_id, 
                            $hakem_info->id ?? null, 
                            $yardimci_1_info->id ?? null, 
                            $yardimci_2_info->id ?? null, 
                            $dorduncu_hakem_info->id ?? null, 
                            $gozlemci_info->id ?? null, 
                            $durum
                        ])) {
                            $yeni_musabaka_id = $pdo->lastInsertId();
                            $gorevli_bilgileri = array_filter([
                                $hakem_info, $yardimci_1_info, $yardimci_2_info, $dorduncu_hakem_info, $gozlemci_info
                            ]);
                            if (!empty($gorevli_bilgileri)) {
                                sendDbNotification($pdo, $gorevli_bilgileri, $yeni_musabaka_id);
                            }
                            $eklenen_sayisi++;
                        } else {
                            $hata_sayisi++;
                            error_log("Excel Satır {$satir_no}: Veritabanı ekleme hatası.");
                        }
                    } catch (Exception $e) { 
                        $hata_sayisi++;
                        error_log("Excel Satır {$satir_no} işlenirken hata: " . $e->getMessage());
                    }
                }
                
                $pdo->commit();
                if ($eklenen_sayisi > 0) {
                    $mesaj_icerik = "{$eklenen_sayisi} adet müsabaka Excel dosyasından başarıyla işlendi ve bildirimler gönderildi.";
                    if ($hata_sayisi > 0) {
                        $mesaj_icerik .= " {$hata_sayisi} satırda hata oluştu.";
                    }
                    $mesaj_tip = 'success';
                } else {
                    $mesaj_icerik = "Hiçbir müsabaka eklenemedi. {$hata_sayisi} satırda hata oluştu.";
                    $mesaj_tip = 'error';
                }

            } else {
                $mesaj_icerik = 'Excel dosyası okunamadı veya formatı bozuk. Hata: ' . SimpleXLSX::parseError();
                $mesaj_tip = 'error';
            }
        } catch(PDOException $e) {
            $pdo->rollBack();
            error_log("Excel yükleme genel hatası: " . $e->getMessage());
            $mesaj_icerik = 'Excel yükleme sırasında veritabanı hatası oluştu: ' . $e->getMessage();
            $mesaj_tip = 'error';
        }
    }
    $_SESSION['mesaj'] = ['tip' => $mesaj_tip, 'icerik' => $mesaj_icerik];
    header("Location: musabaka_yonetimi.php");
    exit();
}


// Form için gerekli verileri ve aktif müsabakaları çek
try {
    $ligler = $pdo->query("SELECT * FROM ligler ORDER BY ad")->fetchAll();
    $stadyumlar = $pdo->query("SELECT * FROM stadyumlar ORDER BY ad")->fetchAll();
    $takimlar = $pdo->query("SELECT * FROM takimlar ORDER BY ad")->fetchAll();
    $hakemler = $pdo->query("SELECT id, ad, soyad FROM users WHERE rol = 2 ORDER BY ad, soyad")->fetchAll();
    $gozlemciler = $pdo->query("SELECT id, ad, soyad FROM users WHERE rol = 3 ORDER BY ad, soyad")->fetchAll();
    
    $sql = "SELECT m.id, m.tarih, m.saat, m.mac_no, m.durum, m.skor, 
                   l.ad as lig_adi, 
                   t1.ad as ev_sahibi, 
                   t2.ad as misafir,
                   COALESCE(s.ad, 'Belirtilmemiş') as stadyum_adi
            FROM musabakalar m 
            LEFT JOIN ligler l ON m.lig_id = l.id 
            LEFT JOIN takimlar t1 ON m.ev_sahibi_id = t1.id 
            LEFT JOIN takimlar t2 ON m.misafir_id = t2.id
            LEFT JOIN stadyumlar s ON m.stadyum_id = s.id
            WHERE COALESCE(m.arsiv, 0) = 0 
            ORDER BY m.tarih DESC, m.saat DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $musabakalar = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Veri çekme hatası: " . $e->getMessage());
    $mesaj = ['tip' => 'error', 'icerik' => 'Sayfa yüklenirken bir hata oluştu: ' . $e->getMessage()];
    $ligler = $stadyumlar = $takimlar = $hakemler = $gozlemciler = $musabakalar = [];
}

include '../templates/header.php';
?>
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-800"><?php echo $sayfa_baslik; ?></h1>
    
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Toplu Müsabaka Yükle (.xlsx)</h2>
        <?php if ($mesaj && isset($_POST['toplu_musabaka_yukle_excel'])): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($mesaj['icerik']); ?></div>
        <?php endif; ?>
        <p class="text-sm text-gray-600">Sütunlar: Maç No, Hafta No, Tarih (GG.AA.YYYY), Saat (SS:DD), Lig Adı, Stadyum Adı, Ev Sahibi, Misafir, Hakem Adı Soyadı, 1. Yrd. Adı Soyadı, 2. Yrd. Adı Soyadı, 4. Hakem Adı Soyadı, Gözlemci Adı Soyadı</p>
        <p class="text-xs text-blue-600 mt-1">Not: Excel'de yazan bir lig, stadyum veya takım sistemde yoksa otomatik olarak oluşturulacaktır.</p>
        <a href="/public/sablonlar/ornek_musabaka_sablonu.xlsx" download="ornek_musabaka_sablonu.xlsx" class="text-sm text-blue-600 hover:underline my-2 inline-block"><i class="fas fa-download mr-1"></i>Örnek Excel Şablonu İndir</a>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="xlsx_dosyasi" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50" accept=".xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            <button type="submit" name="toplu_musabaka_yukle_excel" class="mt-4 w-full bg-purple-600 text-white py-2 rounded-md hover:bg-purple-700">Excel ile Yükle</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Toplu Müsabaka Yükle (CSV)</h2>
        <?php if ($mesaj && isset($_POST['toplu_musabaka_yukle_csv'])): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($mesaj['icerik']); ?></div>
        <?php endif; ?>
        <p class="text-sm text-gray-600">Sütunlar: Maç No, Hafta No, Tarih (GG.AA.YYYY), Saat (SS:DD), Lig Adı, Stadyum Adı, Ev Sahibi, Misafir, Hakem Adı Soyadı, 1. Yrd. Adı Soyadı, 2. Yrd. Adı Soyadı, 4. Hakem Adı Soyadı, Gözlemci Adı Soyadı</p>
        <p class="text-xs text-blue-600 mt-1">Not: CSV'de yazan bir lig, stadyum veya takım sistemde yoksa otomatik olarak oluşturulacaktır.</p>
        <a href="data:text/csv;charset=utf-8,%EF%BB%BFMaç No;Hafta No;Tarih;Saat;Lig Adı;Stadyum Adı;Ev Sahibi;Misafir;Hakem Adı Soyadı;1. Yrd. Adı Soyadı;2. Yrd. Adı Soyadı;4. Hakem Adı Soyadı;Gözlemci Adı Soyadı%0A101;2;17.08.2025;21:00;Süper Lig;Tüpraş Stadyumu;Beşiktaş;Fenerbahçe;Ahmet Yılmaz;Mehmet Kaya;Ayşe Demir;Fatma Çelik;Hasan Hüseyin" download="ornek_musabaka_sablonu_isimle.csv" class="text-sm text-blue-600 hover:underline my-2 inline-block"><i class="fas fa-download mr-1"></i>Örnek Şablonu İndir</a>
        <form method="POST" enctype="multipart/form-data">
           <input type="file" name="csv_dosyasi" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50" accept=".csv" required>
            <button type="submit" name="toplu_musabaka_yukle_csv" class="mt-4 w-full bg-purple-600 text-white py-2 rounded-md hover:bg-purple-700">CSV ile Yükle</button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Manuel Müsabaka Ekle</h2>
        <?php if ($mesaj && isset($_POST['manuel_musabaka_ekle'])): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($mesaj['icerik']); ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input type="text" name="mac_no" placeholder="Maç No" class="border p-2 rounded w-full">
                <input type="number" name="hafta_no" placeholder="Hafta No" class="border p-2 rounded w-full" min="1" required>
                <input type="date" name="tarih" class="border p-2 rounded w-full" required>
                <input type="time" name="saat" class="border p-2 rounded w-full" required>
                <select name="lig_id" class="border p-2 rounded w-full" required>
                    <option value="">Lig Seçin</option>
                    <?php foreach($ligler as $lig): ?>
                        <option value="<?php echo $lig->id; ?>"><?php echo htmlspecialchars($lig->ad); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <select name="stadyum_id" class="border p-2 rounded w-full" required>
                    <option value="">Stadyum Seçin</option>
                    <?php foreach($stadyumlar as $stadyum): ?>
                        <option value="<?php echo $stadyum->id; ?>"><?php echo htmlspecialchars($stadyum->ad); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="ev_sahibi_id" class="border p-2 rounded w-full" required>
                    <option value="">Ev Sahibi Seçin</option>
                    <?php foreach($takimlar as $takim): ?>
                        <option value="<?php echo $takim->id; ?>"><?php echo htmlspecialchars($takim->ad); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="misafir_id" class="border p-2 rounded w-full" required>
                    <option value="">Misafir Takım Seçin</option>
                    <?php foreach($takimlar as $takim): ?>
                        <option value="<?php echo $takim->id; ?>"><?php echo htmlspecialchars($takim->ad); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <select name="hakem_id" class="border p-2 rounded w-full">
                    <option value="">Hakem Seçin</option>
                    <?php foreach($hakemler as $hakem): ?>
                        <option value="<?php echo $hakem->id; ?>"><?php echo htmlspecialchars($hakem->ad . ' ' . $hakem->soyad); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="yardimci_1_id" class="border p-2 rounded w-full">
                    <option value="">1. Yardımcı Seçin</option>
                    <?php foreach($hakemler as $hakem): ?>
                        <option value="<?php echo $hakem->id; ?>"><?php echo htmlspecialchars($hakem->ad . ' ' . $hakem->soyad); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="yardimci_2_id" class="border p-2 rounded w-full">
                    <option value="">2. Yardımcı Seçin</option>
                    <?php foreach($hakemler as $hakem): ?>
                        <option value="<?php echo $hakem->id; ?>"><?php echo htmlspecialchars($hakem->ad . ' ' . $hakem->soyad); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="dorduncu_hakem_id" class="border p-2 rounded w-full">
                    <option value="">4. Hakem Seçin</option>
                    <?php foreach($hakemler as $hakem): ?>
                        <option value="<?php echo $hakem->id; ?>"><?php echo htmlspecialchars($hakem->ad . ' ' . $hakem->soyad); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="gozlemci_id" class="border p-2 rounded w-full">
                    <option value="">Gözlemci Seçin</option>
                    <?php foreach($gozlemciler as $gozlemci): ?>
                        <option value="<?php echo $gozlemci->id; ?>"><?php echo htmlspecialchars($gozlemci->ad . ' ' . $gozlemci->soyad); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="manuel_musabaka_ekle" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Müsabakayı Ekle</button>
        </form>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-md mt-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Aktif Müsabakalar (<?php echo count($musabakalar); ?> adet)</h2>
            <a href="arsiv_yonetimi.php" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 text-sm"><i class="fas fa-box-archive mr-2"></i>Arşivi Görüntüle</a>
        </div>
        <?php if ($mesaj && !isset($_POST['manuel_musabaka_ekle']) && !isset($_POST['toplu_musabaka_yukle_csv']) && !isset($_POST['toplu_musabaka_yukle_excel'])): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $mesaj['tip'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars($mesaj['icerik']); ?></div>
        <?php endif; ?>
        
        <form method="POST" id="toplu_islem_form">
            <div class="flex space-x-2 mb-4">
                <select name="toplu_islem" class="border p-2 rounded">
                    <option value="arsivle">Arşivle</option>
                    <option value="sil">Sil</option>
                </select>
                <button type="submit" name="toplu_islem_uygula" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">Uygula</button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><input type="checkbox" id="select_all"></th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarih/Saat</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Maç No</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lig</th>
                             <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ev Sahibi</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Misafir</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stadyum</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durum</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($musabakalar)): ?>
                            <tr><td colspan="9" class="px-4 py-4 text-center text-gray-500">Henüz müsabaka bulunmamaktadır.</td></tr>
                        <?php else: ?>
                            <?php foreach($musabakalar as $musabaka): ?>
                                <tr>
                                     <td class="px-4 py-2"><input type="checkbox" name="musabaka_ids[]" value="<?php echo $musabaka->id; ?>" class="musabaka_checkbox"></td>
                                    <td class="px-4 py-2"><?php echo date('d.m.Y', strtotime($musabaka->tarih)); ?> <?php echo $musabaka->saat; ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($musabaka->mac_no); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($musabaka->lig_adi); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($musabaka->ev_sahibi); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($musabaka->misafir); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($musabaka->stadyum_adi); ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $musabaka->durum === 'Tamamlandı' ? 'bg-green-100 text-green-800' : ($musabaka->durum === 'İptal Edildi' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'); ?>">
                                            <?php echo htmlspecialchars($musabaka->durum); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 space-x-2">
                                        <a href="../musabaka_detay.php?id=<?php echo $musabaka->id; ?>" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i> Detay</a>
                                        <a href="?action=sil&id=<?php echo $musabaka->id; ?>" onclick="return confirm('Bu müsabakayı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tümünü seç checkbox'ı
    document.getElementById('select_all').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.musabaka_checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = this.checked;
        }.bind(this));
    });
    
    // Toplu işlem formu gönderim kontrolü
    document.getElementById('toplu_islem_form').addEventListener('submit', function(e) {
        var checkedBoxes = document.querySelectorAll('.musabaka_checkbox:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Lütfen işlem yapmak için en az bir müsabaka seçin.');
        } else {
            var islem = document.querySelector('select[name="toplu_islem"]').value;
            if (islem === 'sil' && !confirm('Seçili ' + checkedBoxes.length + ' müsabakayı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
                e.preventDefault();
            }
        }
    });
});
</script>

<?php
include '../templates/footer.php';
ob_end_flush();
?>