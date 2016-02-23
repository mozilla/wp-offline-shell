<?php

require_once(plugin_dir_path(__FILE__).'wp-sw-cache.php' );
require_once(plugin_dir_path(__FILE__).'wp-sw-cache-db.php');
require_once(plugin_dir_path(__FILE__).'vendor/mozilla/wp-sw-manager/class-wp-sw-manager.php');

load_plugin_textdomain('wpswcache', false, dirname(plugin_basename(__FILE__)) . '/lang');

class SW_Cache_Main {
  private static $instance;
  public static $cache_prefix = 'wp-sw-cache';

  public function __construct() {
    if (get_option('wp_sw_cache_enabled')) {
      WP_SW_Manager::get_manager()->sw()->add_content(array($this, 'write_sw'));
    }
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public function update_version($name = '') {
    if(!$name) {
      $name = time();
    }
    update_option('wp_sw_cache_name', self::$cache_prefix.'-'.$name);
  }

  public function write_sw() {

    $files = get_option('wp_sw_cache_files');
    if(!$files) {
      $files = array();
    }

    // Will contain items like 'style.css' => {filemtime() of style.css}
    $file_keys = array();

    // Ensure that every file directed to be cached still exists
    foreach($files as $index=>$file) {
      $tfile = get_template_directory().'/'.$file;

      if(file_exists($tfile)) {
        // Use file's last change time in name hash so the SW is updated if any file is updated
        $file_keys[get_template_directory_uri().'/'.$file] = filemtime($tfile);
      }
    }

    // Serialize the entire array so we match file with time
    // This will catch file updates done outside of admin (like updating via FTP)
    $name = md5(serialize($file_keys));
    self::update_version($name);

    // Template content into the JS file
    $contents = file_get_contents(dirname(__FILE__).'/lib/service-worker.js');
    $contents = str_replace('$name', $name, $contents);
    $contents = str_replace('$files', json_encode(array_keys($file_keys)), $contents);
    $contents = str_replace('$debug', get_option('wp_sw_cache_debug') ? 'true' : 'false', $contents);
    echo $contents;
  }
}

?>
