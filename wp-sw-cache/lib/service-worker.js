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
    var request = event.request;
    var lookupRequest = normalizeAndAnonymize(request);
    if (!shouldBeHandled(lookupRequest)) {
      return;
    }

    event.respondWith(
      caches.match(lookupRequest)
        .then(function(response) {
          if (response) {
            console.log(
              '[fetch] Returning from ServiceWorker cache: ',
              event.request.url
            );
            return response;
          }
          console.error('[fetch] Cache miss! This should not happen. It implies problems caching.');
          return fetch(event.request);
        }
      )
    );
  });

  function normalizeAndAnonymize(request) {
    var url = new URL(request.url);
    if (url.origin !== location.origin) {
      return request;
    }

    url.search = '';
    url.fragment = '';
    return new Request(url, {
      method: request.method,
      headers: request.headers,
      mode: 'no-cors',
      credentials: request.credentials,
      cache: request.cache,
      redirect: request.redirect,
      referrer: request.referrer,
      integrity: request.integrity
    });
  }

  function shouldBeHandled(request) {
    return request.method === 'GET' && CACHE_FILES.indexOf(request.url) !== -1;
  }

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
