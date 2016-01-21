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
    update_option('wp_sw_cache_name', ''); // TODO:  What should we call this by defualt?
    update_option('wp_sw_cache_files', ''); // TODO:  How do we want to structure this file list?
  }

  public static function on_deactivate() {
  }

  public static function on_uninstall() {
    delete_option('wp_sw_cache_name');
    delete_option('wp_sw_cache_files');
  }

}

?>
