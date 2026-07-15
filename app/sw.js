// Nordic Trend — service worker (web push).
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });

self.addEventListener('push', function (event) {
  var data = { title: 'Nordic Trend', body: '', url: '/' };
  try { if (event.data) { data = Object.assign(data, event.data.json()); } }
  catch (e) { if (event.data) { data.body = event.data.text(); } }
  event.waitUntil(self.registration.showNotification(data.title, {
    body: data.body,
    icon: 'icon-192.png',
    badge: 'icon-192.png',
    tag: data.tag || 'nordic-trend',
    data: { url: data.url || '/' }
  }));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) || '/';
  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
    for (var i = 0; i < list.length; i++) {
      if ('focus' in list[i]) { try { list[i].navigate(url); } catch (e) {} return list[i].focus(); }
    }
    if (self.clients.openWindow) return self.clients.openWindow(url);
  }));
});
