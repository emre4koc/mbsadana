<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-800 text-white transform -translate-x-full transition-transform duration-300 ease-in-out md:relative md:translate-x-0">
    <div class="flex items-center justify-center h-16 bg-gray-900">
        <i class="fas fa-whistle text-2xl mr-2"></i>
        <span class="text-xl font-semibold">Müsabaka Bilgi Sistemi</span>
    </div>
    <nav class="mt-4">
        <a href="<?php echo BASE_URL; ?>/anasayfa.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-home w-6"></i>
            <span class="ml-3">Anasayfa</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/gorevlerim.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-calendar-check w-6"></i>
            <span class="ml-3">Görevlerim</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/musaitlik.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-user-clock w-6"></i>
            <span class="ml-3">Müsaitlik Bildir</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/mazeret_bildir.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-file-medical w-6"></i>
            <span class="ml-3">Mazeret Bildir</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/takvim.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-calendar-alt w-6"></i>
            <span class="ml-3">Takvim</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/rehber.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-address-book w-6"></i>
            <span class="ml-3">Rehber</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/profil.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-user-cog w-6"></i>
            <span class="ml-3">Profilim</span>
        </a>
        
        <?php if ($_SESSION['user_rol'] == 1): // Sadece Yönetici ?>
        <div class="px-6 py-3 mt-4">
            <span class="text-xs font-semibold text-gray-500 uppercase">Yönetici Paneli</span>
        </div>
        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-tachometer-alt w-6"></i>
            <span class="ml-3">Yönetim Paneli</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/musabaka_yonetimi.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-futbol w-6"></i>
            <span class="ml-3">Müsabaka Yönetimi</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/duyurular.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-700 hover:text-white">
            <i class="fas fa-bullhorn w-6"></i>
            <span class="ml-3">Duyurular</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>