<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Arşivlenmiş Müsabakalar</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                     <form action="{{ route('admin.matches.bulkAction') }}" method="POST" id="bulk-form">
                        @csrf
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <input type="hidden" name="action" value="unarchive">
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm" onclick="return confirm('Seçilenleri arşivden çıkarmak istediğinizden emin misiniz?')">Seçilenleri Arşivden Çıkar</button>
                            </div>
                            <a href="{{ route('admin.matches.index') }}" class="text-blue-500 hover:underline">Aktif Müsabakalara Geri Dön</a>
                        </div>
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="p-3"><input type="checkbox" onclick="document.querySelectorAll('#bulk-form input[type=checkbox][name=\'match_ids[]\']').forEach(c => c.checked = this.checked)"></th>
                                    <th class="text-left py-3 px-4">Arşivlenme Tarihi</th>
                                    <th class="text-left py-3 px-4">Müsabaka</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($matches as $match)
                                <tr>
                                    <td class="p-3 border-b"><input type="checkbox" name="match_ids[]" value="{{ $match->id }}"></td>
                                    <td class="py-3 px-4 border-b">{{ \Carbon\Carbon::parse($match->archived_at)->format('d.m.Y H:i') }}</td>
                                    <td class="py-3 px-4 border-b">{{ $match->homeTeam->name ?? 'N/A' }} vs {{ $match->awayTeam->name ?? 'N/A' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="py-4 text-center">Arşivde hiç müsabaka yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>