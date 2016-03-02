localforage = {
  createInstance: function() {
    var storage = {};
    return {
      getItem: function(key) {
        return Promise.resolve(storage[key]);
      },
      setItem: function(key, value) {
        return Promise.resolve();
      },
      clear: function() {
        Object.keys(storage).forEach(function(key) {
          delete storage[key];
        });
        return Promise.resolve();
      }
    };
  }
};
