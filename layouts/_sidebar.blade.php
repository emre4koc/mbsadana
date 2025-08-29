<div class="flex flex-col w-64 bg-gray-800">
    <div class="flex items-center justify-center h-16 bg-gray-900"><span class="text-white font-bold uppercase">Müsabaka Bilgi Sistemi</span></div>
    <div class="flex flex-col flex-1 overflow-y-auto"><nav class="flex-1 px-2 py-4 bg-gray-800">
        <p class="px-4 text-gray-400 text-xs uppercase font-semibold tracking-wider">YÖNETİCİ PANELİ</p>
        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 mt-2 text-gray-100 hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}"><span class="text-xl">🏠</span><span class="mx-4 font-medium">Yönetim Paneli</span></a>
        <a href="{{ route('admin.matches.index') }}" class="flex items-center px-4 py-2 mt-2 text-gray-100 hover:bg-gray-700 {{ request()->routeIs('admin.matches.*') ? 'bg-gray-700' : '' }}"><span class="text-xl">⚙️</span><span class="mx-4 font-medium">Müsabaka Yönetimi</span></a>
        <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-2 mt-2 text-gray-100 hover:bg-gray-700 {{ request()->routeIs('admin.users.*') ? 'bg-gray-700' : '' }}"><span class="text-xl">👥</span><span class="mx-4 font-medium">Kullanıcı Yönetimi</span></a>
        <a href="{{ route('admin.announcements.index') }}" class="flex items-center px-4 py-2 mt-2 text-gray-100 hover:bg-gray-700 {{ request()->routeIs('admin.announcements.*') ? 'bg-gray-700' : '' }}"><span class="text-xl">📢</span><span class="mx-4 font-medium">Duyuru Yönetimi</span></a>
        <a href="{{ route('admin.teams.index') }}" class="flex items-center px-4 py-2 mt-2 text-gray-100 hover:bg-gray-700 {{ request()->routeIs('admin.teams.*') ? 'bg-gray-700' : '' }}"><span class="text-xl">🛡️</span><span class="mx-4 font-medium">Takım Yönetimi</span></a>
        <a href="{{ route('admin.stadiums.index') }}" class="flex items-center px-4 py-2 mt-2 text-gray-100 hover:bg-gray-700 {{ request()->routeIs('admin.stadiums.*') ? 'bg-gray-700' : '' }}"><span class="text-xl">🏟️</span><span class="mx-4 font-medium">Stadyum Yönetimi</span></a>
        <a href="{{ route('admin.leagues.index') }}" class="flex items-center px-4 py-2 mt-2 text-gray-100 hover:bg-gray-700 {{ request()->routeIs('admin.leagues.*') ? 'bg-gray-700' : '' }}"><span class="text-xl">🏆</span><span class="mx-4 font-medium">Lig Yönetimi</span></a>
        <p class="px-4 mt-4 text-gray-400 text-xs uppercase font-semibold tracking-wider">KULLANICI ALANI</p>
        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 mt-2 text-gray-100 hover:bg-gray-700"><span class="text-xl">👤</span><span class="mx-4 font-medium">Anasayfa (Görevlerim)</span></a>
    </nav></div>
</div>