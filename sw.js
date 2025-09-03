// Service Worker: Tarayıcı kapalıyken bile bildirimleri dinler.

// 'push' olayı: Sunucudan bir bildirim geldiğinde tetiklenir.
self.addEventListener('push', function(event) {
    const data = event.data.json(); // Gelen veriyi JSON olarak al

    const options = {
        body: data.body, // Bildirim metni
        icon: 'https://placehold.co/192x192/000000/FFFFFF?text=MBS', // Bildirim ikonu
        badge: 'https://placehold.co/72x72/000000/FFFFFF?text=MBS', // Android'de görünen küçük ikon
        data: {
            url: data.url // Tıklanınca açılacak link
        }
    };

    // Bildirimi ekranda göster
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// 'notificationclick' olayı: Kullanıcı bildirime tıkladığında tetiklenir.
self.addEventListener('notificationclick', function(event) {
    event.notification.close(); // Bildirimi kapat
    
    // Bildirimle ilişkili URL'yi yeni bir pencerede aç
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
