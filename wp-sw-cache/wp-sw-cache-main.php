<?php

require_once(plugin_dir_path(__FILE__).'wp-sw-cache.php' );
require_once(plugin_dir_path(__FILE__).'wp-sw-cache-db.php');
require_once(plugin_dir_path(__FILE__).'wp-sw-cache-recommender.php');
require_once(plugin_dir_path(__FILE__).'vendor/mozilla/wp-sw-manager/class-wp-sw-manager.php');

load_plugin_textdomain('wpswcache', false, dirname(plugin_basename(__FILE__)) . '/lang');

class SW_Cache_Main {
  private static $instance;
  public static $cache_name = '__wp-sw-cache';

  public function __construct() {
    if (get_option('wp_sw_cache_enabled')) {
      WP_SW_Manager::get_manager()->sw()->add_content(array($this, 'write_sw'));
    }
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function update_version($name = '') {
    if(!$name) {
      $name = time();
    }
    update_option('wp_sw_cache_version', $name);
    return $name;
  }

  public static function build_sw() {
    // Return nothing if the plugin is disabled
    if(!get_option('wp_sw_cache_enabled')) {
      return '';
    }

    $files = get_option('wp_sw_cache_files');
    if(!$files) {
      $files = array();
    }

    // Will contain items like 'style.css' => {filemtime() of style.css}
    $urls = array();

    // Ensure that every file directed to be cached still exists
    foreach($files as $index=>$file) {
      $tfile = get_template_directory().'/'.$file;
      if(file_exists($tfile)) {
        // Use file's last change time in name hash so the SW is updated if any file is updated
        $urls[get_template_directory_uri().'/'.$file] = (string)filemtime($tfile);
      }
    }

    // Serialize the entire array so we match file with time
    // This will catch file updates done outside of admin (like updating via FTP)
    $version = self::update_version(md5(serialize($urls)));

    // Template content into the JS file
    $contents = file_get_contents(dirname(__FILE__).'/lib/service-worker.js');
    $contents = str_replace('$name', self::$cache_name, $contents);
    $contents = str_replace('$urls', json_encode($urls), $contents);
    $contents = str_replace('$debug', intval(get_option('wp_sw_cache_debug')), $contents);
    return $contents;
  }

  public function write_sw() {
    echo self::build_sw();
  }
}

?>
