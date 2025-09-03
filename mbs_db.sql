-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 15 Ağu 2025, 23:59:26
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `mbs_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bildirimler`
--

CREATE TABLE `bildirimler` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mesaj` text NOT NULL,
  `okundu` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `bildirimler`
--

INSERT INTO `bildirimler` (`id`, `user_id`, `mesaj`, `okundu`, `link`, `olusturma_tarihi`) VALUES
(1, 2, 'Yeni bir müsabakaya atandınız: Adana Demirspor - Galatasaray', 0, '/musabaka.php?id=1', '2025-08-15 20:43:49'),
(2, 6, 'Yeni bir gözlemcilik görevine atandınız: Adana Demirspor - Galatasaray', 1, '/musabaka.php?id=1', '2025-08-15 20:43:49'),
(3, 4, 'Yeni görev: Adana Demirspor - Başakşehir FK', 0, '/mbs/musabaka_detay.php?id=5', '2025-08-15 21:42:35'),
(4, 5, 'Yeni görev: Adana Demirspor - Başakşehir FK', 0, '/mbs/musabaka_detay.php?id=5', '2025-08-15 21:42:35'),
(5, 3, 'Yeni görev: Adana Demirspor - Başakşehir FK', 0, '/mbs/musabaka_detay.php?id=5', '2025-08-15 21:42:35'),
(6, 6, 'Yeni görev: Adana Demirspor - Başakşehir FK', 1, '/mbs/musabaka_detay.php?id=5', '2025-08-15 21:42:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `duyurular`
--

CREATE TABLE `duyurular` (
  `id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `icerik` text NOT NULL,
  `tarih` date DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `arsiv` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `duyurular`
--

INSERT INTO `duyurular` (`id`, `baslik`, `icerik`, `tarih`, `olusturma_tarihi`, `arsiv`) VALUES
(1, 'Yeni Sezon Başlıyor!', 'Tüm hakem ve gözlemcilerimize yeni sezonda başarılar dileriz.', '2025-08-15', '2025-08-15 20:43:49', 0),
(2, 'Fiziksel Testler Hakkında', 'Sezon öncesi fiziksel yeterlilik testleri 10 Ağustos tarihinde yapılacaktır.', '2025-08-01', '2025-08-15 20:43:49', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ligler`
--

CREATE TABLE `ligler` (
  `id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `ligler`
--

INSERT INTO `ligler` (`id`, `ad`) VALUES
(1, 'Süper Lig'),
(2, 'TFF 1. Lig'),
(3, 'Bölgesel Amatör Lig');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `mazeretler`
--

CREATE TABLE `mazeretler` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `baslangic_tarihi` date NOT NULL,
  `bitis_tarihi` date NOT NULL,
  `aciklama` text NOT NULL,
  `durum` varchar(50) NOT NULL DEFAULT 'Beklemede',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `musabakalar`
--

CREATE TABLE `musabakalar` (
  `id` int(11) NOT NULL,
  `hafta_no` int(11) NOT NULL,
  `tarih` date NOT NULL,
  `saat` time NOT NULL,
  `lig_id` int(11) NOT NULL,
  `stadyum_id` int(11) NOT NULL,
  `ev_sahibi_id` int(11) NOT NULL,
  `misafir_id` int(11) NOT NULL,
  `hakem_id` int(11) DEFAULT NULL,
  `yardimci_1_id` int(11) DEFAULT NULL,
  `yardimci_2_id` int(11) DEFAULT NULL,
  `dorduncu_hakem_id` int(11) DEFAULT NULL,
  `gozlemci_id` int(11) DEFAULT NULL,
  `durum` varchar(50) NOT NULL DEFAULT 'Atandı' COMMENT 'Atandı, Oynandı, İptal, Ertelendi',
  `skor` varchar(10) DEFAULT NULL,
  `ihraclar` text DEFAULT NULL,
  `arsiv` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `musabakalar`
--

INSERT INTO `musabakalar` (`id`, `hafta_no`, `tarih`, `saat`, `lig_id`, `stadyum_id`, `ev_sahibi_id`, `misafir_id`, `hakem_id`, `yardimci_1_id`, `yardimci_2_id`, `dorduncu_hakem_id`, `gozlemci_id`, `durum`, `skor`, `ihraclar`, `arsiv`) VALUES
(1, 1, '2025-08-16', '21:00:00', 1, 1, 1, 2, 2, 3, 4, 5, 6, 'Atandı', NULL, NULL, 0),
(2, 1, '2025-08-17', '19:00:00', 1, 3, 3, 5, 4, 2, 5, 3, 6, 'Atandı', NULL, NULL, 0),
(3, 1, '2025-08-15', '01:15:00', 3, 2, 1, 6, 4, 2, 5, 3, 6, 'Oynandı', '2-1', '', 0),
(4, 1, '2025-08-16', '00:15:00', 1, 3, 3, 5, 4, 2, 5, NULL, 6, 'Oynandı', '2-1', '', 0),
(5, 1, '2025-08-16', '00:40:00', 2, 1, 1, 6, 4, 5, 3, NULL, 6, 'Atandı', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `musaitlik`
--

CREATE TABLE `musaitlik` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gun` varchar(20) NOT NULL,
  `zaman_dilimi` varchar(20) NOT NULL,
  `musait` tinyint(1) NOT NULL DEFAULT 1,
  `sezon` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `raporlar`
--

CREATE TABLE `raporlar` (
  `id` int(11) NOT NULL,
  `musabaka_id` int(11) NOT NULL,
  `gozlemci_id` int(11) NOT NULL,
  `rapor_dosya_yolu` varchar(255) DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `raporlar`
--

INSERT INTO `raporlar` (`id`, `musabaka_id`, `gozlemci_id`, `rapor_dosya_yolu`, `olusturma_tarihi`) VALUES
(1, 3, 6, 'public/uploads/raporlar/1755292332_rapor.xls', '2025-08-15 21:12:12'),
(2, 4, 6, 'public/uploads/raporlar/1755292891_rapor.xls', '2025-08-15 21:21:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `rapor_detaylari`
--

CREATE TABLE `rapor_detaylari` (
  `id` int(11) NOT NULL,
  `rapor_id` int(11) NOT NULL,
  `hakem_id` int(11) NOT NULL,
  `puan` decimal(3,1) NOT NULL,
  `not` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `rapor_detaylari`
--

INSERT INTO `rapor_detaylari` (`id`, `rapor_id`, `hakem_id`, `puan`, `not`) VALUES
(1, 1, 4, 8.4, ''),
(2, 1, 2, 8.4, ''),
(3, 1, 5, 8.4, ''),
(4, 1, 3, 8.4, ''),
(5, 2, 4, 8.4, 'testir'),
(6, 2, 2, 8.4, 'teras'),
(7, 2, 5, 8.4, 'terstt');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stadyumlar`
--

CREATE TABLE `stadyumlar` (
  `id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `stadyumlar`
--

INSERT INTO `stadyumlar` (`id`, `ad`) VALUES
(1, 'Yeni Adana Stadyumu'),
(2, 'RAMS Park'),
(3, 'Şükrü Saracoğlu Stadyumu'),
(4, 'Tüpraş Stadyumu');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `takimlar`
--

CREATE TABLE `takimlar` (
  `id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `takimlar`
--

INSERT INTO `takimlar` (`id`, `ad`) VALUES
(1, 'Adana Demirspor'),
(2, 'Galatasaray'),
(3, 'Fenerbahçe'),
(4, 'Beşiktaş'),
(5, 'Trabzonspor'),
(6, 'Başakşehir FK');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `rol` int(11) NOT NULL DEFAULT 2 COMMENT '1: Yönetici, 2: Hakem, 3: Gözlemci',
  `klasman` varchar(100) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `ad`, `soyad`, `email`, `sifre`, `rol`, `klasman`, `telefon`, `aktif`, `olusturma_tarihi`) VALUES
(1, 'Admin', 'Yönetici', 'admin@mbs.com', '$2y$10$cdNTUBelLRTkvgeCTOLxJO6twBbRE7Qshw3l3RGNr82tmj64321KC', 1, 'Yönetici', '5551112233', 1, '2025-08-15 20:43:49'),
(2, 'Ahmet', 'Yılmaz', 'ahmet.yilmaz@hakem.com', '$2y$10$ce9seSNf.7evsRr4fAOQ7eV67TX5rADAUC0kxBogAReWr2fXvPNky', 2, 'Süper Lig Hakemi', '5321234567', 1, '2025-08-15 20:43:49'),
(3, 'Mehmet', 'Kaya', 'mehmet.kaya@hakem.com', '$2y$10$AKpRbFQQ/4SEV8PUYPCbtuzbB1JQMcnHEXo9eGIHfuDZ1RJHM2XTS', 2, 'Süper Lig Yardımcı Hakemi', '5429876543', 1, '2025-08-15 20:43:49'),
(4, 'Ayşe', 'Demir', 'ayse.demir@hakem.com', '$2y$10$.iqRAQqzps9lnFujpJg7KuVazi9jqQ7ETV5cecBUBCGJnIN8UR/xa', 2, 'Bölgesel Hakem', '5055554433', 1, '2025-08-15 20:43:49'),
(5, 'Fatma', 'Çelik', 'fatma.celik@hakem.com', '$2y$10$mMzSr67iCYhN5OOaq0rGhO5NVDt7TVd9f2nNNkc2nPDcjjQoQZ956', 2, 'İl Hakemi', '5078889900', 1, '2025-08-15 20:43:49'),
(6, 'Hasan', 'Hüseyin', 'hasan.huseyin@gozlemci.com', '$2y$10$JPZBuB5/eNkZJj1RYMxPCOi8Tb6e0Ow2R9dPZXLzNh2w.XmVrvOvO', 3, 'Süper Lig Gözlemcisi', '5334567890', 1, '2025-08-15 20:43:49');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `bildirimler`
--
ALTER TABLE `bildirimler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `duyurular`
--
ALTER TABLE `duyurular`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `ligler`
--
ALTER TABLE `ligler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `mazeretler`
--
ALTER TABLE `mazeretler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `musabakalar`
--
ALTER TABLE `musabakalar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `musaitlik`
--
ALTER TABLE `musaitlik`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `raporlar`
--
ALTER TABLE `raporlar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `rapor_detaylari`
--
ALTER TABLE `rapor_detaylari`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `stadyumlar`
--
ALTER TABLE `stadyumlar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `takimlar`
--
ALTER TABLE `takimlar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `bildirimler`
--
ALTER TABLE `bildirimler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `duyurular`
--
ALTER TABLE `duyurular`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `ligler`
--
ALTER TABLE `ligler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `mazeretler`
--
ALTER TABLE `mazeretler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `musabakalar`
--
ALTER TABLE `musabakalar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `musaitlik`
--
ALTER TABLE `musaitlik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `raporlar`
--
ALTER TABLE `raporlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `rapor_detaylari`
--
ALTER TABLE `rapor_detaylari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `stadyumlar`
--
ALTER TABLE `stadyumlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `takimlar`
--
ALTER TABLE `takimlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
