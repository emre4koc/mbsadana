<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Anasayfa</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <a href="{{ route('assignments.weekly') }}" class="block bg-blue-100 border rounded-lg p-4 text-center hover:bg-blue-200 transition">
                            <h3 class="text-lg font-semibold text-gray-700">Bu Haftaki Görev Sayınız</h3>
                            <p class="text-5xl font-bold text-blue-600">{{ $weekly_assignments_count }}</p>
                        </a>
                        <a href="{{ route('assignments.total') }}" class="block bg-green-100 border rounded-lg p-4 text-center hover:bg-green-200 transition">
                            <h3 class="text-lg font-semibold text-gray-700">Toplam Görevleriniz</h3>
                            <p class="text-5xl font-bold text-green-600">{{ $total_assignments_count }}</p>
                        </a>
                        <div class="bg-yellow-100 border rounded-lg p-4">
                            {{-- ... Duyuru Panosu ... --}}
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Haftanın Müsabaka Bülteni</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="py-2 px-4 border-b">Tarih - Saat</th><th class="py-2 px-4 border-b">Müsabaka</th><th class="py-2 px-4 border-b">Görevliler</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($matches as $match)
                                    @php $isUserMatch = in_array($match->id, $userMatchIds); @endphp
                                    <tr class="@if($isUserMatch) hover:bg-blue-50 cursor-pointer font-bold @endif" @if($isUserMatch) onclick="window.location='{{ route('match.show', $match->id) }}';" @endif>
                                        <td class="py-2 px-4 border-b text-center">{{ \Carbon\Carbon::parse($match->match_date)->format('d.m.Y - H:i') }}</td>
                                        <td class="py-2 px-4 border-b text-center">{{ $match->homeTeam->name }} - {{ $match->awayTeam->name }}</td>
                                        <td class="py-2 px-4 border-b text-sm">H: {{ $match->referee->name ?? 'N/A' }}, Göz: {{ $match->observer->name ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-4 px-4 border-b text-center">Bu hafta için bültende gösterilecek müsabaka bulunamadı.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>