<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Yönetim Paneli
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 bg-white border-b border-gray-200 shadow-sm sm:rounded-lg">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">

                    <a href="{{ route('admin.matches.index') }}" class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg hover:bg-blue-100 border transition">
                        <span class="text-5xl mb-2">⚙️</span>
                        <span class="font-semibold text-gray-700 text-center">Müsabaka Yönetimi</span>
                    </a>

                    <a href="#" class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg hover:bg-green-100 border transition">
                        <span class="text-5xl mb-2">👥</span>
                        <span class="font-semibold text-gray-700 text-center">Kullanıcı Yönetimi</span>
                    </a>

                    <a href="{{ route('admin.leagues.index') }}" class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg hover:bg-indigo-100 border transition">
                        <span class="text-5xl mb-2">🏆</span>
                        <span class="font-semibold text-gray-700 text-center">Lig Yönetimi</span>
                    </a>

                    <a href="#" class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg hover:bg-red-100 border transition">
                        <span class="text-5xl mb-2">⭐</span>
                        <span class="font-semibold text-gray-700 text-center">Klasman Yönetimi</span>
                    </a>

                    <a href="{{ route('admin.teams.index') }}" class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg hover:bg-purple-100 border transition">
                        <span class="text-5xl mb-2">🛡️</span>
                        <span class="font-semibold text-gray-700 text-center">Takım Yönetimi</span>
                    </a>

                    <a href="{{ route('admin.stadiums.index') }}" class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg hover:bg-yellow-100 border transition">
                        <span class="text-5xl mb-2">🏟️</span>
                        <span class="font-semibold text-gray-700 text-center">Stadyum Yönetimi</span>
                    </a>

                    <a href="#" class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg hover:bg-pink-100 border transition">
                        <span class="text-5xl mb-2">📅</span>
                        <span class="font-semibold text-gray-700 text-center">Müsaitlik Talepleri</span>
                    </a>

                    <a href="#" class="flex flex-col items-center justify-center p-6 bg-gray-50 rounded-lg hover:bg-cyan-100 border transition">
                        <span class="text-5xl mb-2">📢</span>
                        <span class="font-semibold text-gray-700 text-center">Duyurular</span>
                    </a>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>