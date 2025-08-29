<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Stadyum Yönetimi
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-end mb-4">
                        <a href="{{ route('admin.stadiums.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Yeni Stadyum Ekle
                        </a>
                    </div>
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="w-1/2 text-left py-3 px-4 uppercase font-semibold text-sm">Stadyum Adı</th>
                                <th class="w-1/2 text-left py-3 px-4 uppercase font-semibold text-sm">Şehir</th>
                                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            @foreach($stadiums as $stadium)
                            <tr>
                                <td class="w-1/2 text-left py-3 px-4">{{ $stadium->name }}</td>
                                <td class="w-1/2 text-left py-3 px-4">{{ $stadium->city }}</td>
                                <td class="text-left py-3 px-4">
                                    <a href="{{ route('admin.stadiums.edit', $stadium) }}" class="text-blue-500 hover:text-blue-700">Düzenle</a>
                                    <form action="{{ route('admin.stadiums.destroy', $stadium) }}" method="POST" class="inline-block ml-4" onsubmit="return confirm('Bu stadyumu silmek istediğinizden emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>