# WordPress Service Worker Cache
A WordPress plugin for caching theme assets via a service worker for the sake of performance and offline functionality.

This plugin is currently experimental and should only be used with developers with [Service Worker knowledge](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API/Using_Service_Workers).

## Build

To build the plugin, ensure you have [Composer](https://getcomposer.org/),
then simply invoke `composer install`.

## Installation and Usage

Assuming the build step completed successfully, place the `wp-sw-cache` directory inside your WordPress instance's `wp-content/plugins directory`.

With the plugin in the WordPress directory structure:

  1.  Activate the plugin
  2.  Navigate to the plugin's settings page
  3.  Choose assets from the listing that are used most frequently (`style.css` is likely used on every page of the blog, for example)
  4.  Save!

A service worker will then be placed within every page of the blog and select assets will be served from the service worker!

## Contribution and Bugs

Contributions are welcome!  You can file pull requests or or issues at [this repository](https://github.com/darkwing/wp-sw-cache).

## Related WordPress Plugins

  *  [wp-offline-content](https://github.com/delapuente/wp-offline-content) - Save pages for offline reading
  *  [wp-sw-manager](https://github.com/mozilla/wp-sw-manager) - Shared service worker plugin
  *  [wp-web-push](https://github.com/mozilla/wp-web-push) - Add push notifications to your WordPress site!
