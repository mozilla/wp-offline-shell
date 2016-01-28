<?php

require_once(plugin_dir_path(__FILE__).'wp-sw-cache.php' );
require_once(plugin_dir_path(__FILE__).'wp-sw-cache-db.php');

load_plugin_textdomain('wpswcache', false, dirname(plugin_basename(__FILE__)) . '/lang');

class SW_Cache_Main {
  private static $instance;

  public function __construct() {
    if($this->show_header()) {
      add_action('wp_head', array($this, 'register')); // TODO:  Find out why "wp_footer" isn't working
    }

    add_action('parse_request', array($this, 'on_parse_request'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public function show_header() {
    $files = get_option('wp_sw_cache_files');
    if(!$files) {
      $files = array();
    }

    // TODO:  Make sure that files are all valid and exist
    // I guess we can just leave out files that don't exist anymore

    return get_option('wp_sw_cache_enabled') && count($files) && !is_admin();
  }

  public function register() {
    $contents = file_get_contents(dirname(__FILE__).'/lib/service-worker-registration.html');
    echo $contents;
  }

  public function on_parse_request() {
    // TODO:  The relative path to the "wp-sw-cache-worker.js" file really needs to be dynamic, not hardcoded
    // This is bad

    $files = get_option('wp_sw_cache_files');
    if(!$files) {
      $files = array();
    }
    foreach($files as $index=>$file) {
      $files[$index] = get_template_directory_uri().'/'.$file;
    }

    if($_SERVER['REQUEST_URI'] === '/wp-sw-cache-worker.js') {
      header('Content-Type: application/javascript');
      $contents = file_get_contents(dirname(__FILE__).'/lib/service-worker.js');
      $contents = str_replace('$name', get_option('wp_sw_cache_name'), $contents);
      $contents = str_replace('$files', json_encode($files), $contents);
      echo $contents;
      exit();
    }
  }
}

?>
