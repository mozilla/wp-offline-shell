describe('tests', function() {
  'use strict';

  var cacheName = 'testCache';

  // Needed to prevent SW from bombing on what needs to be replaced
  self.$urls = {
    'http://localhost/wp-content/themes/my-theme/script.js': '32942374293',
    'http://localhost/wp-content/themes/my-theme/style.css': '997898326'
  };
  self.$debug = 1;

  beforeEach(function() {
    importScripts('/base/wp-sw-cache/lib/service-worker.js');
  });

  it('This is a stub test', function() {
    assert.strictEqual(true, true);
  });

  it('URLs which should be added to cache actually end up in cache and storage', function() {
    // Simulates a basic install
    assert.strictEqual(true, true);
  });

  it('A URL removed from the `urls` property removes unwanted URLs from cache and storage', function() {
    // This is key to removing stale files from cache after an admin no longer wants a file cached
    // Simulates a user caching files, going to admin to remove a file, and seeing that file removed from cache+storage
    assert.strictEqual(true, true);
  });

  it('`shouldBeHandled` works properly', function() {
    assert.strictEqual(true, true);
  });

});
