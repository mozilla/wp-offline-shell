<?php

class SW_Cache_DB {

  private static $instance;

  public function __construct() {
  }

  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function on_activate() {
    // For v1 we'll prompt the user enable the plugin manually upon activation
    update_option('wp_sw_cache_enabled', 0);
    // The "style.css" file is a standard WordPress file, so we can safely assume this exists
    update_option('wp_sw_cache_files', array('style.css'));
    // Create an initial SW version
    SW_Cache_Main::update_version();
    // Setting debug initially will help the user understand what the SW is doing via the console
    update_option('wp_sw_cache_debug', 1);
  }

  public static function on_deactivate() {
  }

  public static function on_uninstall() {
    delete_option('wp_sw_cache_enabled');
    delete_option('wp_sw_cache_version');
    delete_option('wp_sw_cache_files');
    delete_option('wp_sw_cache_debug');
  }

}

?>
