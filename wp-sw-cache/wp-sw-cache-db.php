<?php

class SW_Cache_DB {

  private static $instance;
  public static $options = array(
    // For v1 we'll prompt the user enable the plugin manually upon activation
    'wp_sw_cache_enabled' => 0,
    // The "style.css" file is a standard WordPress file, so we can safely assume this exists
    'wp_sw_cache_files' => array('styles.css'),
    // Create an initial SW version
    'wp_sw_cache_version' => '0.1.0',
    // Setting debug initially will help the user understand what the SW is doing via the console
    'wp_sw_cache_debug' => 0
  );

  public function __construct() {
    add_action('plugins_loaded', array($this, 'update'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function on_activate() {
  }

  public static function on_deactivate() {
  }

  public static function on_uninstall() {
    foreach(self::$options as $option => $default) {
      delete_option($option);
    }
  }

  public static function update() {
    $current_version = self::$options['wp_sw_cache_version'];
    if($current_version == get_option('wp_sw_cache_version')) {
      return;
    }

    // Set defaults
    foreach(self::$options as $option => $default) {
      add_option($option, $default);
    }

    update_option('wp_sw_cache_version', $current_version);
  }

}

?>
