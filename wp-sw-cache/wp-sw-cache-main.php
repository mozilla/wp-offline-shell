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

  public function get_this_plugins_base() {
    return '/wp-content/plugins/wp-sw-cache';
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
    $contents = str_replace('$path', $this->get_this_plugins_base(), $contents);
    echo $contents;
  }

  public function on_parse_request() {
    if($_SERVER['REQUEST_URI'] === $this->get_this_plugins_base().'/sw.js') {
      header('Content-Type: application/javascript');
      echo file_get_contents(dirname(__FILE__).'/lib/service-worker.js');
      exit();
    }
  }

  public function output_sw_js() {
    header('Content-Type: application/javascript');

    require_once(plugin_dir_path(__FILE__) . 'lib/js/sw.php');
    exit();
  }
}

?>
