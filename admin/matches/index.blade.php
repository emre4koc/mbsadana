<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Müsabaka Yönetimi</h2>
    </x-slot>

    <div class="py-12" x-data="{ modalOpen: false, selectedMatch: null }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('import_summary'))
                <div class="mb-6 bg-gray-100 p-4 rounded-lg border">
                    <h4 class="font-bold text-lg">Toplu Yükleme Sonucu:</h4>
                    <p class="text-green-600 font-semibold">{{ session('import_summary.success') }} müsabaka başarıyla eklendi.</p>
                    @if(!empty(session('import_summary.newly_created')))
                        <div class="mt-2"><p class="font-semibold text-blue-600">Ayrıca aşağıdaki yeni kayıtlar otomatik olarak oluşturuldu:</p><div class="text-sm text-blue-500">
                            @if(!empty(session('import_summary.newly_created.leagues')))<strong>Ligler:</strong> {{ implode(', ', array_unique(session('import_summary.newly_created.leagues'))) }}@endif
                            @if(!empty(session('import_summary.newly_created.stadiums')))<br><strong>Stadyumlar:</strong> {{ implode(', ', array_unique(session('import_summary.newly_created.stadiums'))) }}@endif
                            @if(!empty(session('import_summary.newly_created.teams')))<br><strong>Takımlar:</strong> {{ implode(', ', array_unique(session('import_summary.newly_created.teams'))) }}@endif
                        </div></div>
                    @endif
                    @if(session('import_summary.skipped') > 0)
                        <p class="mt-2 text-red-600 font-semibold">{{ session('import_summary.skipped') }} satır bazı hatalar nedeniyle atlandı:</p>
                        <ul class="list-disc list-inside text-sm text-red-500 mt-2">
                            @foreach(session('import_summary.errors') as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            @endif
            @if(session('import_error'))<div class="mb-6 bg-red-100 p-4 rounded-lg border border-red-300 text-red-700"><h4 class="font-bold">Yükleme Hatası!</h4><p>{{ session('import_error') }}</p></div>@endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Toplu Müsabaka Yükle (CSV)</h3>
                    <form action="{{ route('admin.matches.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="flex items-center">
                            <input type="file" name="csv_file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                            <button type="submit" class="ml-4 bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">CSV ile Yükle</button>
                        </div>
                    </form>
                    <div class="text-sm text-gray-600 mt-4">
                        <a href="{{ asset('ornek-sablonlar/ornek_musabakalar.csv') }}" class="text-blue-500 hover:underline font-semibold" download>Örnek Şablonu İndir</a>
                        <p class="mt-1"><strong>Not:</strong> CSV dosyanızın sütunları şablondaki sırada olmalıdır: <br><code>Maç No, Hafta No, Tarih, Saat, Lig Adı, Stadyum Adı, Ev Sahibi, Misafir, Hakem, 1. Yrd, 2. Yrd, 4. Hakem, Gözlemci</code></p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('admin.matches.bulkAction') }}" method="POST" id="bulk-form">
                        @csrf
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <select name="action" class="rounded-md border-gray-300 shadow-sm text-sm"><option value="">Toplu İşlem Seç...</option><option value="archive">Seçilenleri Arşivle</option><option value="delete">Seçilenleri Kalıcı Sil</option></select>
                                <button type="submit" class="ml-2 bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm" onclick="return confirm('Bu işlemi yapmak istediğinizden emin misiniz?')">Uygula</button>
                            </div>
                            <div>
                                <a href="{{ route('admin.matches.archived') }}" class="text-blue-500 hover:underline mr-4">Arşivi Görüntüle</a>
                                <a href="{{ route('admin.matches.create') }}" class="bg-blue-500 text-white font-bold py-2 px-4 rounded">Manuel Ekle</a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200">
                                    <tr>
                                        <th class="p-3"><input type="checkbox" onclick="document.querySelectorAll('#bulk-form input[type=checkbox][name=\'match_ids[]\']').forEach(c => c.checked = this.checked)"></th>
                                        <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Tarih</th>
                                        <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Müsabaka</th>
                                        <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Durum / Skor</th>
                                        <th class="text-left py-3 px-4 uppercase font-semibold text-sm">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    @forelse($matches as $match)
                                    <tr>
                                        <td class="p-3 border-b"><input type="checkbox" name="match_ids[]" value="{{ $match->id }}"></td>
                                        <td class="py-3 px-4 border-b">{{ \Carbon\Carbon::parse($match->match_date)->format('d.m.Y H:i') }}</td>
                                        <td class="py-3 px-4 border-b font-bold">{{ $match->homeTeam->name ?? 'N/A' }} vs {{ $match->awayTeam->name ?? 'N/A' }}</td>
                                        <td class="py-3 px-4 border-b">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full @if($match->status == 'Atandı') bg-yellow-100 text-yellow-800 @elseif($match->status == 'Oynandı') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">{{ $match->status }}</span>
                                            @if($match->home_score !== null)<span class="font-bold ml-2">({{ $match->home_score }} - {{ $match->away_score }})</span>@endif
                                        </td>
                                        <td class="py-3 px-4 border-b">
                                            <button type="button" @click="selectedMatch = {{ json_encode($match) }}; modalOpen = true" class="inline-block align-middle text-gray-600 hover:text-blue-500 font-bold text-lg px-2" title="Görevlileri Görüntüle">&#9432;</button>
                                            <a href="{{ route('admin.matches.edit', $match) }}" class="inline-block align-middle ml-1 text-blue-500 font-semibold">Düzenle</a>
                                            <form action="{{ route('admin.matches.destroy', $match) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Bu müsabakayı kalıcı olarak silmek istediğinizden emin misiniz?');">@csrf @method('DELETE')<button type="submit" class="text-red-500 font-semibold">Sil</button></form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="py-4 text-center text-gray-500">Gösterilecek aktif müsabaka yok.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="mt-4">{{ $matches->links() }}</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="modalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.away="modalOpen = false" x-cloak>
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" @click.stop>
                <h3 class="text-lg font-semibold border-b pb-2 mb-4">Müsabaka Görevlileri</h3>
                <div x-show="selectedMatch" class="space-y-1">
                    <p><strong>Hakem:</strong> <span x-text="selectedMatch.referee ? selectedMatch.referee.name : 'Atanmadı'"></span></p>
                    <p><strong>1. Yrd. Hakem:</strong> <span x-text="selectedMatch.assistant_referee1 ? selectedMatch.assistant_referee1.name : 'Atanmadı'"></span></p>
                    <p><strong>2. Yrd. Hakem:</strong> <span x-text="selectedMatch.assistant_referee2 ? selectedMatch.assistant_referee2.name : 'Atanmadı'"></span></p>
                    <p><strong>4. Hakem:</strong> <span x-text="selectedMatch.fourth_official ? selectedMatch.fourth_official.name : 'Atanmadı'"></span></p>
                    <p class="mt-2"><strong>Gözlemci:</strong> <span x-text="selectedMatch.observer ? selectedMatch.observer.name : 'Atanmadı'"></span></p>
                </div>
                <div class="text-right mt-6">
                    <button @click="modalOpen = false" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Kapat</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>