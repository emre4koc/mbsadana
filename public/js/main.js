/* MBS Main JavaScript v10.0 - Anlık Bildirim (Push Notification) ve Görev Onaylama Özelliği */
document.addEventListener('DOMContentLoaded', function() {

    // --- YENİ EKLENDİ: ANLIK BİLDİRİM FONKSİYONLARI ---
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function subscribeUserToPush() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
            });

            // Abonelik bilgilerini sunucuya gönder
            await fetch(`${baseURL}/ajax/save-subscription.php`, {
                method: 'POST',
                body: JSON.stringify(subscription),
                headers: { 'Content-Type': 'application/json' }
            });
            console.log('Kullanıcı anlık bildirimlere abone oldu.');
        } catch (error) {
            console.error('Anlık bildirim aboneliği başarısız oldu:', error);
        }
    }

    async function setupPushNotifications() {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            try {
                await navigator.serviceWorker.register(`${baseURL}/public/sw.js`);
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    await subscribeUserToPush();
                }
            } catch (error) {
                console.error('Service Worker kaydı başarısız oldu:', error);
            }
        }
    }

    // --- MEVCUT FONKSİYONLAR ---
    function setupSidebar() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        if (!sidebarToggle || !sidebar) return;

        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden';
        document.body.appendChild(overlay);

        const toggleSidebar = () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        };
        sidebarToggle.addEventListener('click', (e) => { e.stopPropagation(); toggleSidebar(); });
        overlay.addEventListener('click', toggleSidebar);
    }

    function setupNotificationBell() {
        const notificationBell = document.getElementById('notification-bell');
        const notificationPanel = document.getElementById('notification-panel');
        if (!notificationBell || !notificationPanel) return;

        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationPanel.classList.toggle('hidden');
            const notificationBadge = document.getElementById('notification-badge');
            if (!notificationPanel.classList.contains('hidden') && notificationBadge) {
                fetch(`${baseURL}/ajax/mark_notifications_read.php`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        notificationBadge.style.display = 'none';
                    }
                }).catch(error => console.error('Bildirimler okunmuş olarak işaretlenemedi:', error));
            }
        });
    }

    function setupClearNotifications() {
        const clearBtn = document.getElementById('clear-notifications-btn');
        if (!clearBtn) return;

        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch(`${baseURL}/ajax/clear_notifications.php`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const list = document.getElementById('notification-list');
                    const footer = document.getElementById('notification-footer');
                    const badge = document.getElementById('notification-badge');
                    if(list) list.innerHTML = '<p class="py-4 px-4 text-sm text-gray-500 text-center">Yeni bildiriminiz yok.</p>';
                    if(badge) badge.style.display = 'none';
                    if(footer) footer.style.display = 'none';
                }
            })
            .catch(error => console.error('Bildirim silme hatası:', error));
        });
    }
    
    function setupDuyuruModal() {
        const duyuruItems = document.querySelectorAll('.duyuru-item');
        const duyuruModal = document.getElementById('duyuru-modal');
        if (!duyuruModal || duyuruItems.length === 0) return;

        const duyuruModalClose = document.getElementById('duyuru-modal-close');
        const duyuruModalBaslik = document.getElementById('duyuru-modal-baslik');
        const duyuruModalTarih = document.getElementById('duyuru-modal-tarih');
        const duyuruModalIcerik = document.getElementById('duyuru-modal-icerik');

        if (!duyuruModalClose || !duyuruModalBaslik || !duyuruModalTarih || !duyuruModalIcerik) return;

        duyuruItems.forEach(item => {
            item.addEventListener('click', function() {
                duyuruModalBaslik.textContent = this.dataset.baslik;
                let tarihMetni = `Yayınlanma Tarihi: ${this.dataset.tarih}`;
                if (this.dataset.ilgiliTarih) {
                    tarihMetni += ` | <span class="font-semibold text-red-600">İlgili Tarih: ${this.dataset.ilgiliTarih}</span>`;
                }
                duyuruModalTarih.innerHTML = tarihMetni;
                duyuruModalIcerik.innerHTML = this.dataset.icerik;
                duyuruModal.classList.remove('hidden');
            });
        });

        const closeModal = () => {
            duyuruModal.classList.add('hidden');
        };
        duyuruModalClose.addEventListener('click', closeModal);
        duyuruModal.addEventListener('click', function(e) {
            if (e.target === duyuruModal) closeModal();
        });
    }

    function setupGlobalClickListeners() {
        document.addEventListener('click', function(e) {
            const notificationPanel = document.getElementById('notification-panel');
            const notificationBell = document.getElementById('notification-bell');
            if (notificationPanel && notificationBell && !notificationPanel.classList.contains('hidden')) {
                if (!notificationPanel.contains(e.target) && !notificationBell.contains(e.target)) {
                    notificationPanel.classList.add('hidden');
                }
            }
        });
    }
    
    function setupGorevOnayla() {
        const onaylaBtn = document.getElementById('gorevi-onayla-btn');
        if (!onaylaBtn) return;

        onaylaBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Her ihtimale karşı
            
            const musabakaId = this.dataset.musabakaId;
            const onayKutusu = document.getElementById('onay-kutusu');

            // Butonu devre dışı bırak ve bekleme durumuna al
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Onaylanıyor...';

            fetch(`${baseURL}/ajax/gorev_onayla.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'musabaka_id=' + musabakaId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if(onayKutusu) {
                        onayKutusu.innerHTML = '<p class="font-semibold text-green-800">Göreviniz başarıyla onaylandı. Teşekkürler!</p>';
                        onayKutusu.classList.remove('bg-blue-50', 'border-blue-500');
                        onayKutusu.classList.add('bg-green-50', 'border-green-500');
                    }
                } else {
                    alert('Bir hata oluştu: ' + data.message);
                    // Hata durumunda butonu tekrar aktif et
                    onaylaBtn.disabled = false;
                    onaylaBtn.innerHTML = 'Görevi Onayla';
                }
            })
            .catch(error => {
                console.error('Onaylama hatası:', error);
                alert('Onaylama sırasında bir ağ hatası oluştu.');
                onaylaBtn.disabled = false;
                onaylaBtn.innerHTML = 'Görevi Onayla';
            });
        });
    }
    
    // Tüm kurulum fonksiyonlarını çalıştır
    setupPushNotifications(); // Yeni fonksiyonu çağır
    setupSidebar();
    setupNotificationBell();
    setupDuyuruModal();
    setupClearNotifications();
    setupGlobalClickListeners();
    setupGorevOnayla();
    
    const puanInputlari = document.querySelectorAll('.puan-input');
    puanInputlari.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
            if (parts.length > 1 && parts[1].length > 1) value = parts[0] + '.' + parts[1].substring(0, 1);
            const numericValue = parseFloat(value);
            if (numericValue > 10) value = '10.0'; 
            else if (value.length > 2 && numericValue === 10 && parts.length > 1 && parts[1] !== '0') value = '10.0';
            e.target.value = value;
        });
    });
});