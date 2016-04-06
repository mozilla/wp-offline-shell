<?php

require_once(plugin_dir_path(__FILE__).'wp-offline-shell.php' );
require_once(plugin_dir_path(__FILE__).'wp-offline-shell-db.php');
require_once(plugin_dir_path(__FILE__).'wp-offline-shell-recommender.php');

class Offline_Shell_Main {
  private static $instance;
  public static $cache_name = '__offline-shell';

  public function __construct() {
    Mozilla\WP_SW_Manager::get_manager()->sw()->add_content(array($this, 'write_sw'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function build_sw() {
    // Will contain items like 'style.css' => {filemtime() of style.css}
    $urls = array();

    // Get files and validate they are of proper type
    $files = get_option('offline_shell_files');
    if(!$files || !is_array($files)) {
      $files = array();
    }

    // Ensure that every file requested to be cached still exists
    if(get_option('offline_shell_enabled')) {
      foreach($files as $index => $file) {
        $tfile = get_template_directory().'/'.$file;
        if(file_exists($tfile)) {
          // Use file's last change time in name hash so the SW is updated if any file is updated
          $urls[get_template_directory_uri().'/'.$file] = (string)filemtime($tfile);
        }
      }
    }

    // Template content into the JS file
    $contents = file_get_contents(dirname(__FILE__).'/lib/service-worker.js');
    $contents = str_replace('$name', self::$cache_name, $contents);
    $contents = str_replace('$urls', json_encode($urls), $contents);
    $contents = str_replace('$debug', intval(get_option('offline_shell_debug')), $contents);
    $contents = str_replace('$raceEnabled', intval(get_option('offline_shell_race_enabled')), $contents);
    return $contents;
  }

  public function write_sw() {
    echo self::build_sw();
  }
}

?>
