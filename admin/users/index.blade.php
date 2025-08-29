<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kullanıcı Yönetimi</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Toplu Kullanıcı Ekle (CSV)</h3>
                    <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="flex items-center">
                            <input type="file" name="csv_file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                            <button type="submit" class="ml-4 bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">CSV ile Yükle</button>
                        </div>
                    </form>
                    <div class="text-sm text-gray-600 mt-4">
                        <a href="{{ asset('ornek-sablonlar/ornek_kullanicilar.csv') }}" class="text-blue-500 hover:underline font-semibold" download>Örnek Şablonu İndir</a>
                        <p class="mt-1"><strong>Not:</strong> CSV dosyanızın sütunları şablondaki sırada olmalıdır: <br><code>Ad Soyad,E-posta,Rol (admin, hakem veya gozlemci),Şifre</code></p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Mevcut Kullanıcılar</h3>
                        <a href="{{ route('admin.users.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Manuel Kullanıcı Ekle</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Ad Soyad</th>
                                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">E-posta</th>
                                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Rol</th>
                                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                @forelse($users as $user)
                                <tr>
                                    <td class="py-3 px-4 border-b">{{ $user->name }}</td>
                                    <td class="py-3 px-4 border-b">{{ $user->email }}</td>
                                    <td class="py-3 px-4 border-b uppercase font-bold">{{ $user->role }}</td>
                                    <td class="py-3 px-4 border-b">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-500 hover:text-blue-700 font-semibold">Düzenle</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-500">Sistemde hiç kullanıcı bulunamadı.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>