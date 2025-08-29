<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ isset($match) ? 'Müsabakayı Düzenle' : 'Yeni Müsabaka Ekle' }}</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 bg-white border-b border-gray-200">
                    <form action="{{ isset($match) ? route('admin.matches.update', $match) : route('admin.matches.store') }}" method="POST">
                        @csrf
                        @if(isset($match)) @method('PUT') @endif
                        
                        <h3 class="text-lg font-semibold border-b pb-2 mb-6">Müsabaka Bilgileri</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div><label class="block text-sm font-medium text-gray-700">Hafta No</label><input type="number" name="week_no" value="{{ old('week_no', $match->week_no ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Maç Tarihi ve Saati</label><input type="datetime-local" name="match_date" value="{{ old('match_date', isset($match) ? \Carbon\Carbon::parse($match->match_date)->format('Y-m-d\TH:i') : '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required></div>
                            <div><label class="block text-sm font-medium text-gray-700">Lig</label><select name="league_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required><option value="">Seçiniz...</option>@foreach($leagues as $league)<option value="{{ $league->id }}" @selected(old('league_id', $match->league_id ?? '') == $league->id)>{{ $league->name }}</option>@endforeach</select></div>
                            <div><label class="block text-sm font-medium text-gray-700">Stadyum</label><select name="stadium_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required><option value="">Seçiniz...</option>@foreach($stadiums as $stadium)<option value="{{ $stadium->id }}" @selected(old('stadium_id', $match->stadium_id ?? '') == $stadium->id)>{{ $stadium->name }}</option>@endforeach</select></div>
                            <div><label class="block text-sm font-medium text-gray-700">Ev Sahibi Takım</label><select name="home_team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required><option value="">Seçiniz...</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected(old('home_team_id', $match->home_team_id ?? '') == $team->id)>{{ $team->name }}</option>@endforeach</select></div>
                            <div><label class="block text-sm font-medium text-gray-700">Misafir Takım</label><select name="away_team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required><option value="">Seçiniz...</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected(old('away_team_id', $match->away_team_id ?? '') == $team->id)>{{ $team->name }}</option>@endforeach</select></div>
                        </div>

                        <h3 class="text-lg font-semibold border-b pb-2 mt-10 mb-6">Görevli Atamaları</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div><label class="block text-sm font-medium text-gray-700">Hakem (H)</label><select name="referee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Seçiniz...</option>@foreach($referees as $referee)<option value="{{ $referee->id }}" @selected(old('referee_id', $match->referee_id ?? '') == $referee->id)>{{ $referee->name }}</option>@endforeach</select></div>
                            <div><label class="block text-sm font-medium text-gray-700">1. Yardımcı Hakem (Y1)</label><select name="assistant_referee_1_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Seçiniz...</option>@foreach($referees as $referee)<option value="{{ $referee->id }}" @selected(old('assistant_referee_1_id', $match->assistant_referee_1_id ?? '') == $referee->id)>{{ $referee->name }}</option>@endforeach</select></div>
                            <div><label class="block text-sm font-medium text-gray-700">2. Yardımcı Hakem (Y2)</label><select name="assistant_referee_2_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Seçiniz...</option>@foreach($referees as $referee)<option value="{{ $referee->id }}" @selected(old('assistant_referee_2_id', $match->assistant_referee_2_id ?? '') == $referee->id)>{{ $referee->name }}</option>@endforeach</select></div>
                            <div><label class="block text-sm font-medium text-gray-700">Dördüncü Hakem (4.)</label><select name="fourth_official_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Seçiniz...</option>@foreach($referees as $referee)<option value="{{ $referee->id }}" @selected(old('fourth_official_id', $match->fourth_official_id ?? '') == $referee->id)>{{ $referee->name }}</option>@endforeach</select></div>
                            <div><label class="block text-sm font-medium text-gray-700">Gözlemci</label><select name="observer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Seçiniz...</option>@foreach($observers as $observer)<option value="{{ $observer->id }}" @selected(old('observer_id', $match->observer_id ?? '') == $observer->id)>{{ $observer->name }}</option>@endforeach</select></div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <a href="{{ route('admin.matches.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md mr-4">İptal</a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </x-app-layout>