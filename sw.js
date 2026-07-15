/* ================================================================
   Fluenta — Service Worker
   ----------------------------------------------------------------
   Makes the app installable + usable OFFLINE.

   Strategy, per resource type:
     • App shell (HTML, manifest, icons) → cache-first, refreshed
       in the background (stale-while-revalidate). Instant loads.
     • Lesson data (path.json, units/*.json) → network-first with a
       cache fallback. You always get the freshest content online;
       once a unit has been opened it works offline forever.
     • Google Fonts → cache-first (they never change).

   Bump CACHE_VERSION whenever you ship new app code so old caches
   are cleared on the next launch.
   ================================================================ */

const CACHE_VERSION = 'fluenta-v38';
const SHELL_CACHE = CACHE_VERSION + '-shell';
const DATA_CACHE  = CACHE_VERSION + '-data';
const FONT_CACHE  = CACHE_VERSION + '-fonts';

/* Core files that make the app boot with zero network. */
const SHELL_ASSETS = [
  './',
  'index.html',
  'manifest.json',
  'icon-180.png',
  'icon-192.png',
  'icon-512.png',
];

/* Luma artwork used all over the app. Pre-cached best-effort (a single missing
   file must never break the install) so the mascot always renders — this is
   what fixes images going blank after a deploy: bumping CACHE_VERSION also
   drops any older cache that may have stored a bad/partial image response. */
const IMG_ASSETS = [
  'luma-welcome.png',
  'mascot/wave.png', 'mascot/happy.png', 'mascot/talk.png', 'mascot/celebrate.png',
  'mascot/encourage.png', 'mascot/love.png', 'mascot/sleep.png',
  'mascot/p-teach.png', 'mascot/p-hi.png', 'mascot/p-idea.png', 'mascot/p-party.png',
  'mascot/p-proud.png', 'mascot/p-excited.png', 'mascot/p-cheer.png', 'mascot/p-thumb.png',
];

/* ---- install: pre-cache the shell ---- */
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE)
      .then((cache) => cache.addAll(SHELL_ASSETS).then(() =>
        // Best-effort: cache each image on its own so one 404 can't abort install.
        Promise.all(IMG_ASSETS.map((u) =>
          cache.add(u).catch(() => {})
        ))
      ))
      .then(() => self.skipWaiting())
  );
});

/* ---- activate: drop caches from older versions ---- */
self.addEventListener('activate', (event) => {
  const keep = [SHELL_CACHE, DATA_CACHE, FONT_CACHE];
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((k) => !keep.includes(k)).map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

/* ---- helpers ---- */
function isLessonData(url) {
  return url.pathname.endsWith('path.json') || url.pathname.includes('/units/') || url.pathname.endsWith('audio/manifest.json');
}
function isFont(url) {
  return url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';
}

/* network-first: try the network, fall back to cache when offline. */
async function networkFirst(request, cacheName) {
  const cache = await caches.open(cacheName);
  try {
    const fresh = await fetch(request);
    if (fresh && fresh.ok) cache.put(request, fresh.clone());
    return fresh;
  } catch (e) {
    const cached = await cache.match(request);
    if (cached) return cached;
    throw e;
  }
}

/* cache-first with background refresh (stale-while-revalidate).
   IMPORTANT: never resolve to null — respondWith(null) breaks the resource
   (this silently killed all lesson audio after a cache-version bump changed
   every mp3's ?v= cache key). On a cache hit we refresh in the background; on
   a miss we await a real network fetch and return it (or a proper error). */
async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  if (cached) {
    fetch(request).then((res) => { if (res && (res.ok || res.type === 'opaque')) cache.put(request, res.clone()); }).catch(() => {});
    return cached;
  }
  try {
    const res = await fetch(request);
    if (res && (res.ok || res.type === 'opaque')) cache.put(request, res.clone());
    return res;
  } catch (e) {
    return new Response('', { status: 504, statusText: 'offline' });
  }
}

/* ---- fetch router ---- */
self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);

  // Auth / API: never cache — must always be live (login state, etc.).
  if (url.pathname.includes('/api/')) return;

  // Lesson JSON: freshest-when-online, cached fallback offline.
  if (isLessonData(url)) {
    event.respondWith(networkFirst(request, DATA_CACHE));
    return;
  }

  // Google Fonts (CSS + font files): cache-first.
  if (isFont(url)) {
    event.respondWith(staleWhileRevalidate(request, FONT_CACHE));
    return;
  }

  // Same-origin.
  if (url.origin === self.location.origin) {
    const isHTML = request.mode === 'navigate'
      || url.pathname === '/' || url.pathname.endsWith('/index.html');
    if (isHTML) {
      // HTML shell: network-first so every deploy is picked up immediately;
      // cached copy is the offline fallback. Prevents "stuck on old version".
      event.respondWith(
        networkFirst(request, SHELL_CACHE)
          .catch(() => caches.match('index.html') || caches.match('./'))
      );
      return;
    }
    // Static assets (icons, manifest): cache-first, refreshed in background.
    event.respondWith(staleWhileRevalidate(request, SHELL_CACHE));
  }
});

/* Let the page tell a waiting SW to take over immediately. */
self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') self.skipWaiting();
});

/* ---- Push notifications ----
   We send DATA-LESS pushes (VAPID only, no payload encryption) because
   encrypted payloads are fragile across push services. The message is chosen
   here from the device's local time, so it still feels timely. If a payload
   ever is present and decodes, we use it. */
function reminderByHour() {
  const h = new Date().getHours();
  if (h < 12) return { title: '¡Buenos días! 🌅', body: 'Ready for today\'s Spanish? A few minutes with Luma keeps your streak alive. 🦉' };
  if (h < 18) return { title: '¿Practicamos? 🦉', body: 'A little Spanish today goes a long way. Keep your streak alive! 🔥' };
  if (h < 22) return { title: 'Keep your streak alive 🔥', body: 'You haven\'t finished today\'s lesson yet — a quick one now?' };
  return { title: 'Last call for today ⏰', body: 'Don\'t lose your streak — finish today\'s lesson before midnight.' };
}
self.addEventListener('push', (event) => {
  const d = reminderByHour();
  let data = { title: d.title, body: d.body, url: './' };
  if (event.data) {
    try { data = Object.assign(data, event.data.json()); }
    catch (e) { const t = event.data.text(); if (t) data.body = t; }
  }
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: 'icon-192.png',
      badge: 'icon-192.png',
      data: { url: data.url || './' },
      tag: 'fluenta-reminder',
      renotify: true,
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || './';
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const c of list) { if ('focus' in c) return c.focus(); }
      if (self.clients.openWindow) return self.clients.openWindow(url);
    })
  );
});
