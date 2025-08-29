<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Müsabaka Detayı ve Rapor Ekranı</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Müsabaka Bilgileri</h3>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div><strong>Tarih:</strong> {{ \Carbon\Carbon::parse($match->match_date)->format('d.m.Y H:i') }}</div>
                        <div><strong>Lig:</strong> {{ $match->league->name }}</div>
                        <div class="col-span-2"><strong>Müsabaka:</strong> {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</div>
                        <div><strong>Stadyum:</strong> {{ $match->stadium->name }}</div>
                        <div><strong>Durum:</strong> <span class="font-bold">{{ $match->status }}</span></div>
                        @if($match->home_score !== null)
                        <div class="col-span-2 text-center text-2xl font-bold mt-2">SKOR: {{ $match->home_score }} - {{ $match->away_score }}</div>
                        @endif
                    </div>
                    
                    <h3 class="text-md font-semibold border-b pb-2 mt-6 mb-4">Görevli Ekibi</h3>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                        <div><strong>Hakem:</strong> {{ $match->referee->name ?? 'Atanmadı' }}</div>
                        <div><strong>1. Yrd. Hakem:</strong> {{ $match->assistantReferee1->name ?? 'Atanmadı' }}</div>
                        <div><strong>2. Yrd. Hakem:</strong> {{ $match->assistantReferee2->name ?? 'Atanmadı' }}</div>
                        <div><strong>4. Hakem:</strong> {{ $match->fourthOfficial->name ?? 'Atanmadı' }}</div>
                        <div class="col-span-2"><strong>Gözlemci:</strong> {{ $match->observer->name ?? 'Atanmadı' }}</div>
                    </div>
                </div>
            </div>

            {{-- ... (Hakem Formu ve Rapor Görüntüleme bölümleri burada kalacak) ... --}}

            {{-- GÜNCELLENMİŞ GÖZLEMCİ BÖLÜMÜ --}}
            @if(Auth::id() == $match->observer_id && \Carbon\Carbon::now()->gte(\Carbon\Carbon::parse($match->match_date)))
                @php
                    $observerReport = $match->observerReports()->where('observer_id', Auth::id())->first();
                @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="{ isEditing: {{ $observerReport ? 'false' : 'true' }} }">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center border-b pb-2 mb-4">
                            <h3 class="text-lg font-semibold">Gözlemci Raporu</h3>
                            <button x-show="!isEditing" @click="isEditing = true" class="text-sm bg-blue-500 text-white py-1 px-3 rounded hover:bg-blue-600">Raporu Değiştir</button>
                        </div>
                        
                        {{-- Mevcut Raporu Gösterme Alanı --}}
                        <div x-show="!isEditing">
                            @if($observerReport)
                                <div class="text-green-600 font-semibold">Gözlemci raporu sisteme yüklenmiş.</div>
                                <div class="mt-4 space-y-2">
                                    <p class="font-semibold">Verilen Puanlar:</p>
                                    <ul class="list-disc list-inside text-sm">
                                        @foreach($observerReport->ratings as $rating)
                                            <li>{{ $rating->referee->name }}: <strong>{{ $rating->score }}</strong></li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-2"><a href="{{ Storage::url($observerReport->report_file_path) }}" class="text-blue-500 hover:underline" target="_blank">Yüklenen Rapor Dosyasını Görüntüle</a></p>
                                </div>
                            @endif
                        </div>

                        {{-- Rapor Giriş/Düzenleme Formu --}}
                        <div x-show="isEditing">
                            <form action="{{ route('match.storeObserverReport', $match) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="space-y-4">
                                    <p class="font-semibold">Hakem Puanları (10 üzerinden):</p>
                                    @php 
                                        $ratings = $observerReport ? $observerReport->ratings->keyBy('referee_id') : collect();
                                    @endphp
                                    @if($match->referee)<div><label class="block text-sm text-gray-700">Hakem: {{ $match->referee->name }}</label><input type="number" step="0.1" name="ratings[{{ $match->referee_id }}]" value="{{ $ratings[$match->referee_id]->score ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300"></div>@endif
                                    @if($match->assistantReferee1)<div><label class="block text-sm text-gray-700">1. Yrd. Hakem: {{ $match->assistantReferee1->name }}</label><input type="number" step="0.1" name="ratings[{{ $match->assistant_referee_1_id }}]" value="{{ $ratings[$match->assistant_referee_1_id]->score ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300"></div>@endif
                                    @if($match->assistantReferee2)<div><label class="block text-sm text-gray-700">2. Yrd. Hakem: {{ $match->assistantReferee2->name }}</label><input type="number" step="0.1" name="ratings[{{ $match->assistant_referee_2_id }}]" value="{{ $ratings[$match->assistant_referee_2_id]->score ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300"></div>@endif
                                    @if($match->fourthOfficial)<div><label class="block text-sm text-gray-700">4. Hakem: {{ $match->fourthOfficial->name }}</label><input type="number" step="0.1" name="ratings[{{ $match->fourth_official_id }}]" value="{{ $ratings[$match->fourth_official_id]->score ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300"></div>@endif
                                </div>
                                <div class="mt-6">
                                    <label class="block font-medium text-sm text-gray-700">Rapor Dosyası (Excel)</label>
                                    <input type="file" name="report_file" class="mt-1 block w-full" {{ $observerReport ? '' : 'required' }}>
                                    @if($observerReport)<small class="text-gray-500">Yeni bir dosya yüklerseniz, eskisiyle değiştirilecektir.</small>@endif
                                </div>
                                <div class="flex justify-end mt-6">
                                    <button type="button" x-show="isEditing && {{ $observerReport ? 'true' : 'false' }}" @click="isEditing = false" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md mr-4">İptal</button>
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md">Raporu ve Puanları Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>