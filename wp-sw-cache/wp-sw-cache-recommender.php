<?php

class SW_Cache_Recommender {
  private static $instance;

  public function __construct() {
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public static function has_min_file($file_name, $all_files) {
    $exploded_path = explode('/', $file_name);
    $immediate_name = array_pop($exploded_path);
    $split_name = explode('.', $immediate_name);
    $ext = array_pop($split_name);
    $name_without_extension = implode('/', $split_name);
    $regex = '/'.preg_quote($name_without_extension, '/').'[-|.](min|compressed).'.$ext.'/';

    return count(preg_grep($regex, $all_files));
  }

  public static function matches_any_regex($file_name, $regexes = array()) {
    foreach($regexes as $regex) {
      if(preg_match($regex, $file_name)) {
        return true;
      }
    }

    return false;
  }
}

?>
