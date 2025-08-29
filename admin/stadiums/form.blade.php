<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($stadium) ? 'Stadyumu Düzenle' : 'Yeni Stadyum Ekle' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ isset($stadium) ? route('admin.stadiums.update', $stadium) : route('admin.stadiums.store') }}" method="POST">
                        @csrf
                        @if(isset($stadium))
                            @method('PUT')
                        @endif
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Stadyum Adı</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $stadium->name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div class="mt-4">
                            <label for="city" class="block text-sm font-medium text-gray-700">Şehir</label>
                            <input type="text" name="city" id="city" value="{{ old('city', $stadium->city ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Kaydet
                            </button>
                            <a href="{{ route('admin.stadiums.index') }}" class="ml-4 inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300">
                                İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>