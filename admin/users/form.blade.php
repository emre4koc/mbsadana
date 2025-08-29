<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ isset($user) ? 'Kullanıcıyı Düzenle' : 'Yeni Kullanıcı Ekle' }}</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 bg-white border-b border-gray-200">
                    <form action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST">
                        @csrf
                        @if(isset($user)) @method('PUT') @endif

                        <div><label class="block">Ad Soyad</label><input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="mt-1 block w-full rounded-md" required></div>
                        <div class="mt-4"><label class="block">E-posta</label><input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="mt-1 block w-full rounded-md" required></div>
                        <div class="mt-4"><label class="block">Rol</label><select name="role" class="mt-1 block w-full rounded-md" required><option value="hakem" @selected(old('role', $user->role ?? '') == 'hakem')>Hakem</option><option value="gozlemci" @selected(old('role', $user->role ?? '') == 'gozlemci')>Gözlemci</option><option value="admin" @selected(old('role', $user->role ?? '') == 'admin')>Admin</option></select></div>
                        <div class="mt-4"><label class="block">Şifre</label><input type="password" name="password" class="mt-1 block w-full rounded-md" {{ isset($user) ? '' : 'required' }}><small class="text-gray-500">{{ isset($user) ? 'Boş bırakırsanız şifre değişmez.' : '' }}</small></div>

                        <div class="mt-6 flex justify-end">
                            <a href="{{ route('admin.users.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-md mr-4">İptal</a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>