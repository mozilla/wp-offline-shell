<?php
/*
Plugin Name: Service Worker Cache
Plugin URI: https://github.com/darkwing/wp-sw-cache
Description: This WordPress plugin provides a method for caching theme assets via a service worker.
Version: 0.2
Text Domain: service-worker-cache
Author: David Walsh
Author URI:  https://davidwalsh.name
*/

require_once(plugin_dir_path(__FILE__).'wp-sw-cache-main.php');
require_once(plugin_dir_path(__FILE__).'wp-sw-cache-db.php');

SW_Cache_DB::init();
SW_Cache_Main::init();

if (is_admin()) {
  require_once(plugin_dir_path(__FILE__).'wp-sw-cache-admin.php');
  SW_Cache_Admin::init();
}

register_activation_hook(__FILE__, array('SW_Cache_DB', 'on_activate'));
register_deactivation_hook(__FILE__, array('SW_Cache_DB', 'on_deactivate'));
register_uninstall_hook(__FILE__, array('SW_Cache_DB', 'on_uninstall'));

?>
