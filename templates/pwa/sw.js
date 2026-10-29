// svaerma service worker – deliberately minimal (see README, "PWA").
// Caches only the static app shell. Pages with personal data, the API and place
// lookups always go to the network and are never stored on the device.
'use strict';

const CACHE = '__CACHE__';
const PRECACHE = __PRECACHE__;

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  // Drop caches of previous app versions.
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);
  // Only same-origin GETs; everything else (POST, CDN, map tiles) is left to the browser.
  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  // Pages: always from the network; without network show the offline page.
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
    return;
  }

  // Versioned static assets: cache first.
  if (url.pathname.startsWith('/assets/') || url.pathname === '/manifest.webmanifest') {
    event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
  }
  // Anything else (API, /places, /map/data, exports) is not touched.
});
