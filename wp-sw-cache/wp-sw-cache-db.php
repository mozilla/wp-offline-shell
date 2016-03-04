<?php

class SW_Cache_DB {

  private static $instance;
  public static $options;

  public function __construct() {
    self::$options = array(
      // For v1 we'll prompt the user enable the plugin manually upon activation
      'wp_sw_cache_enabled' => 0,
      // The "style.css" file is a standard WordPress file, so we can safely assume this exists
      'wp_sw_cache_files' => array('styles.css'),
      // Create an initial SW version
      'wp_sw_cache_version' => time(),
      // Setting debug initially will help the user understand what the SW is doing via the console
      'wp_sw_cache_debug' => 0
    );
  }

  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function on_activate() {
    foreach(self::$options as $option => $default) {
      update_option($option, $default);
    }
  }

  public static function on_deactivate() {
  }

  public static function on_uninstall() {
    foreach(self::$options as $option => $default) {
      delete_option($option);
    }
  }

}

?>
