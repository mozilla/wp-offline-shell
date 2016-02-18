(function(self){
  var CACHE_PREFIX = '__wp-sw-cache::';

  var CACHE_NAME = CACHE_PREFIX + $name;

  var CACHE_FILES = $files;

  self.addEventListener('install', function(event) {
    // Perform install step:  loading each required file into cache
    event.waitUntil(
      caches.open(CACHE_NAME)
        .then(function(cache) {
          // Add all offline dependencies to the cache
          console.log('[install] Caches opened, adding all core components' +
            'to cache');
          return cache.addAll(CACHE_FILES);
        })
        .then(function() {
          console.log('[install] All required resources have been cached, ' +
            'we\'re good!');
          return self.skipWaiting();
        })
    );
  });

  self.addEventListener('fetch', function(event) {
    event.respondWith(
      caches.match(event.request)
        .then(function(response) {
          // Cache hit - return the response from the cached version
          if (response) {
            console.log(
              '[fetch] Returning from ServiceWorker cache: ',
              event.request.url
            );
            return response;
          }
          // Not in cache - return the result from the live server
          // `fetch` is essentially a "fallback"
          return fetch(event.request);
        }
      )
    );
  });

  self.addEventListener('activate', function(event) {
    console.log('[activate] Activating ServiceWorker!');

    // Clean up old cache in the background
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          if(cacheName.startsWith(CACHE_PREFIX) && cacheName != CACHE_NAME) {
            console.log('[activate] Deleting out of date cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    });

    // Calling claim() to force a "controllerchange" event on navigator.serviceWorker
    console.log('[activate] Claiming this ServiceWorker!');
    event.waitUntil(self.clients.claim());
  });
})(self);
