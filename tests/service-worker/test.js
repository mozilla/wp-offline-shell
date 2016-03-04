describe('tests', function() {
  'use strict';

  var storageKey = 'testCache';
  var fakeEvent;

  beforeEach(function(done) {
    // Needed to prevent SW from bombing on what needs to be replaced
    self.$urls = {
      'http://localhost:9876/socket.io/socket.io.js': '328947234',
      'http://localhost:9876/karma.js': '32897324923'
    };
    self.$debug = 1;
    self.$raceEnabled = 0;

    importScripts('/base/wp-sw-cache/lib/service-worker.js');

    // Override our utility with a different storage object so we can manipulate
    wpSwCache.storage = localforage.createInstance({ name: storageKey });
    wpSwCache.cacheName = storageKey;

    // Clean up cache and storage before each test
    wpSwCache.storage.clear().then(function() {
      self.caches.delete(wpSwCache.cacheName).then(function(){
        done();
      });
    });
  });

  it('URLs which should be added to cache actually end up in cache and storage', function(done) {
    this.timeout(10000);

    // Simulates a basic install -- check cache and localforage
    wpSwCache.update().then(function() {
      return self.caches.open(wpSwCache.cacheName).then(function(cache) {
        return cache.keys().then(function(keys) {
          assert.isTrue(keys.length == 2);

          var cacheMatches = 0;
          var storageMatches = 0;
          return Promise.all(Object.keys(wpSwCache.urls).map(function(key) {
            return cache.match(key).then(function(result) {
              var isGood = (result && result.url === key);
              assert.isTrue(isGood);
              if(isGood) {
                cacheMatches++;
              }
            }).then(function() {
              return wpSwCache.storage.getItem(key).then(function(result) {
                var isGood = (result && result === wpSwCache.urls[key]);
                assert.isTrue(isGood);
                if(isGood) {
                  storageMatches++;
                }
              });
            });
          })).then(function() {
            assert.strictEqual(cacheMatches, keys.length);
            done();
          });
        });
      });
    });
  });

  it('A URL removed from the `urls` property removes unwanted URLs from cache and storage', function(done) {
    // This is key to removing stale files from cache after an admin no longer wants a file cached
    // Simulates a user caching files, going to admin to remove a file, and seeing that file removed from cache+storage
    var removedUrl;

    wpSwCache.update().then(function() {
      removedUrl = Object.keys(wpSwCache.urls)[0];
      delete wpSwCache.urls[removedUrl];

      // The second "update" call simulates teh SW being re-installed/updated
      wpSwCache.update().then(function() {
        return wpSwCache.removeOldUrls();
      }).then(function() {
        // Ensure the URL is no longer in cache
        return self.caches.open(wpSwCache.cacheName).then(function(cache) {
          return cache.match(removedUrl).then(function(result) {
            assert.strictEqual(result, undefined);
          }).then(function() {
            return wpSwCache.storage.getItem(removedUrl).then(function(hash) {
              assert.strictEqual(hash, undefined);
              done();
            });
          });
        });
      });
    });
  });

  it('A URL manually removed from cache after it\'s been cached is re-downloaded and cached', function() {
    // https://github.com/darkwing/wp-sw-cache/issues/43

  });

  it('Debug option works properly', function() {
    // We don't want to muddle up the user's console if they option isn't on
    console.log = console.warn = sinon.spy();
    
    wpSwCache.debug = false;
    wpSwCache.log('Hello');
    assert.equal(console.log.callCount, 0);

    wpSwCache.debug = true;
    wpSwCache.log('Hello2');
    assert.equal(console.log.callCount, 1);
  });

  it('`shouldBeHandled` works properly', function() {
    // We only want to cache vanilla URLs -- no credentials, query string, etc.
    var firstUrl = Object.keys(wpSwCache.urls)[0];

    var goodRequest = new Request(firstUrl);
    var badRequest = new Request('https://nothing');
    var badRequest2 = new Request(firstUrl, {
      method: 'POST'
    });

    assert.isTrue(wpSwCache.shouldBeHandled(goodRequest));
    assert.isFalse(wpSwCache.shouldBeHandled(badRequest));
    assert.isFalse(wpSwCache.shouldBeHandled(badRequest2));
  });

});
