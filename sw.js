const CACHE_NAME = 'picoyplaca-v36'; // Actualizado para coincidir con la versión del index
const ASSETS_TO_CACHE = [
  '/',
  '/index.php',
  '/styles.css?v=36.0', // Sincronizado con la versión real del CSS
  '/favicons/manifest.json', // Ruta correcta del manifest
  '/favicons/android-icon-192x192.png',
  '/ads/ads.js?v=3.0'
];

// 1. Instalación
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// 2. Activación (Limpiar viejas cachés)
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[Service Worker] Limpiando caché antigua:', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// 3. Fetch (Network First, Fallback to Cache)
self.addEventListener('fetch', (event) => {
  // Solo interceptamos GET
  if (event.request.method !== 'GET') return;

  // Estrategia: Intentar red primero, si falla, usar caché
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Si la respuesta es válida, la guardamos en caché nueva
        if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
                cache.put(event.request, responseToCache);
            });
        }
        return networkResponse;
      })
      .catch(() => {
        // Si no hay internet, devolvemos lo que haya en caché
        return caches.match(event.request);
      })
  );
});