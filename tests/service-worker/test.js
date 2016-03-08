describe('tests', function() {
  'use strict';

  var storageKey = 'testCache';
  var fakeEvent;

  beforeEach(function(done) {
    // Needed to prevent SW from bombing on what needs to be replaced
    self.$urls = {
      'http://localhost:9876/socket.io/socket.io.js': '1111',
      'http://localhost:9876/karma.js': '2222'
    };
    self.$debug = 1;
    self.$raceEnabled = 0;

    importScripts('/base/wp-offline-shell/lib/service-worker.js');

    // Override our utility with a different storage object so we can manipulate
    wpOfflineShell.storage = localforage.createInstance({ name: storageKey });
    wpOfflineShell.cacheName = storageKey;

    // Clean up cache and storage before each test
    wpOfflineShell.storage.clear().then(function() {
      self.caches.delete(wpOfflineShell.cacheName).then(function(){
        done();
      });
    });
  });

  it('URLs which should be added to cache actually end up in cache and storage', function(done) {
    this.timeout(10000);

    // Simulates a basic install -- check cache and localforage
    wpOfflineShell.update().then(function() {
      return self.caches.open(wpOfflineShell.cacheName).then(function(cache) {
        return cache.keys().then(function(keys) {
          assert.isTrue(keys.length == 2);

          var cacheMatches = 0;
          var storageMatches = 0;
          return Promise.all(Object.keys(wpOfflineShell.urls).map(function(key) {
            return cache.match(key).then(function(result) {
              var isGood = (result && result.url === key);
              assert.isTrue(isGood);
              if(isGood) {
                cacheMatches++;
              }
            }).then(function() {
              return wpOfflineShell.storage.getItem(key).then(function(result) {
                var isGood = (result && result === wpOfflineShell.urls[key]);
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

    wpOfflineShell.update().then(function() {
      removedUrl = Object.keys(wpOfflineShell.urls)[0];
      delete wpOfflineShell.urls[removedUrl];

      // The second "update" call simulates teh SW being re-installed/updated
      wpOfflineShell.update().then(function() {
        return wpOfflineShell.removeOldUrls();
      }).then(function() {
        // Ensure the URL is no longer in cache
        return self.caches.open(wpOfflineShell.cacheName).then(function(cache) {
          return cache.match(removedUrl).then(function(result) {
            assert.strictEqual(result, undefined);
          }).then(function() {
            return wpOfflineShell.storage.getItem(removedUrl).then(function(hash) {
              assert.strictEqual(hash, undefined);
              done();
            });
          });
        });
      });
    });
  });

  it('A URL manually removed from cache after it\'s been cached is re-downloaded and cached', function() {
    // https://github.com/mozilla/offline-shell/issues/43

  });

  it('Debug option works properly', function() {
    // We don't want to muddle up the user's console if they option isn't on
    console.log = console.warn = sinon.spy();

    wpOfflineShell.debug = false;
    wpOfflineShell.log('Hello');
    assert.equal(console.log.callCount, 0);

    wpOfflineShell.debug = true;
    wpOfflineShell.log('Hello2');
    assert.equal(console.log.callCount, 1);
  });

  it('`shouldBeHandled` works properly', function() {
    // We only want to cache vanilla URLs -- no credentials, query string, etc.
    var firstUrl = Object.keys(wpOfflineShell.urls)[0];

    var goodRequest = new Request(firstUrl);
    var badRequest = new Request('https://nothing');
    var badRequest2 = new Request(firstUrl, {
      method: 'POST'
    });

    assert.isTrue(wpOfflineShell.shouldBeHandled(goodRequest));
    assert.isFalse(wpOfflineShell.shouldBeHandled(badRequest));
    assert.isFalse(wpOfflineShell.shouldBeHandled(badRequest2));
  });

});
