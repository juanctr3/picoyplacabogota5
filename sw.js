const CACHE_NAME = 'picoyplaca-v3'; // ¡IMPORTANTE! Cambiamos a v3 para forzar actualización
const ASSETS_TO_CACHE = [
  '/',
  '/index.php',
  '/styles.css?v=10.0', // Coincide con la nueva versión del CSS
  '/manifest.json',
  '/favicons/favicon-32x32.png',
  '/favicons/favicon-192x192.png',
  '/favicons/favicon-512x512.png'
];

// 1. Instalación: Guardar archivos estáticos en caché
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting(); // Forzar activación inmediata
});

// 2. Activación: Limpiar cachés antiguas (v1, v2, etc.)
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[Service Worker] Borrando caché antigua:', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim(); // Tomar control de clientes abiertos
});

// 3. Fetch: Network First (Para contenido dinámico como este)
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Si hay red, guardamos copia fresca
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
                cache.put(event.request, responseToCache);
            });
        }
        return networkResponse;
      })
      .catch(() => {
        // Si no hay red, servimos caché
        return caches.match(event.request);
      })
  );
});