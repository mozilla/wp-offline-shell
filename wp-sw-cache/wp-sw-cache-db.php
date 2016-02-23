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
    // Set default options.
    update_option('wp_sw_cache_enabled', false);
    update_option('wp_sw_cache_files', array());
    SW_Cache_Main::update_version();
    update_option('wp_sw_cache_debug', true);
  }

  public static function on_deactivate() {
  }

  public static function on_uninstall() {
    delete_option('wp_sw_cache_enabled');
    delete_option('wp_sw_cache_name');
    delete_option('wp_sw_cache_files');
    delete_option('wp_sw_cache_debug');
  }

}

?>
