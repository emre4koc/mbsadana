<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Duyuru Yönetimi</h2>
    </x-slot>
    <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"><div class="p-6 bg-white border-b border-gray-200">
        <div class="flex justify-end mb-4"><a href="{{ route('admin.announcements.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Yeni Duyuru Ekle</a></div>
        <table class="min-w-full bg-white">
            <thead class="bg-gray-200"><tr><th class="text-left py-3 px-4">Başlık</th><th class="text-left py-3 px-4">Oluşturulma Tarihi</th><th class="text-left py-3 px-4">İşlemler</th></tr></thead>
            <tbody>
                @foreach($announcements as $announcement)
                <tr><td class="py-3 px-4">{{ $announcement->title }}</td><td class="py-3 px-4">{{ $announcement->created_at->format('d.m.Y') }}</td><td class="py-3 px-4"><a href="{{ route('admin.announcements.edit', $announcement) }}" class="text-blue-500">Düzenle</a></td></tr>
                @endforeach
            </tbody>
        </table>
    </div></div></div></div>
</x-app-layout>