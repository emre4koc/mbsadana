<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ isset($announcement) ? 'Duyuruyu Düzenle' : 'Yeni Duyuru Ekle' }}</h2>
    </x-slot>
    <div class="py-12"><div class="max-w-2xl mx-auto sm:px-6 lg:px-8"><div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"><div class="p-8 bg-white border-b border-gray-200">
        <form action="{{ isset($announcement) ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" method="POST">
            @csrf
            @if(isset($announcement)) @method('PUT') @endif
            <div><label class="block">Başlık</label><input type="text" name="title" value="{{ old('title', $announcement->title ?? '') }}" class="mt-1 block w-full rounded-md" required></div>
            <div class="mt-4"><label class="block">İçerik</label><textarea name="content" rows="5" class="mt-1 block w-full rounded-md" required>{{ old('content', $announcement->content ?? '') }}</textarea></div>
            <div class="mt-6 flex justify-end"><a href="{{ route('admin.announcements.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-md mr-4">İptal</a><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">Kaydet</button></div>
        </form>
    </div></div></div></div>
</x-app-layout>