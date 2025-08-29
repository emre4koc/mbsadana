<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tüm Görevleriniz</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4">
                        <a href="{{ route('dashboard') }}" class="text-blue-500 hover:underline">&larr; Ana Sayfaya Geri Dön</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border">
                             <thead class="bg-gray-200"><tr><th class="py-2 px-4 border-b">Tarih - Saat</th><th class="py-2 px-4 border-b">Müsabaka</th><th class="py-2 px-4 border-b">Durum</th></tr></thead>
                            <tbody>
                                @forelse ($matches as $match)
                                    <tr class="hover:bg-gray-100 cursor-pointer" onclick="window.location='{{ route('match.show', $match->id) }}';">
                                        <td class="py-2 px-4 border-b text-center">{{ \Carbon\Carbon::parse($match->match_date)->format('d.m.Y - H:i') }}</td>
                                        <td class="py-2 px-4 border-b text-center font-bold">{{ $match->homeTeam->name }} - {{ $match->awayTeam->name }}</td>
                                        <td class="py-2 px-4 border-b text-center">{{ $match->status }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-4 px-4 border-b text-center">Sistemde size atanmış bir görev bulunmamaktadır.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>