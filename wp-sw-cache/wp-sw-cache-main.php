<?php

require_once(plugin_dir_path(__FILE__).'wp-sw-cache.php' );
require_once(plugin_dir_path(__FILE__).'wp-sw-cache-db.php');
require_once(plugin_dir_path(__FILE__).'vendor/mozilla/wp-sw-manager/class-wp-sw-manager.php');

load_plugin_textdomain('wpswcache', false, dirname(plugin_basename(__FILE__)) . '/lang');

class SW_Cache_Main {
  private static $instance;

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

  public function write_sw() {
    $files = get_option('wp_sw_cache_files');
    if(!$files) {
        $files = array();
    }
    foreach($files as $index=>$file) {
        $files[$index] = get_template_directory_uri().'/'.$file;
    }

    $contents = file_get_contents(dirname(__FILE__).'/lib/service-worker.js');
    $contents = str_replace('$name', json_encode(get_option('wp_sw_cache_name')), $contents);
    $contents = str_replace('$files', json_encode($files), $contents);
    echo $contents;
  }
}

?>
