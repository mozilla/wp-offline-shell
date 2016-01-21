<?php

load_plugin_textdomain('wpswcache', false, dirname(plugin_basename(__FILE__)) . '/lang');

class SW_Cache_Admin {
  private static $instance;

  public function __construct() {
    add_action('admin_menu', array($this, 'on_admin_menu'));
    add_action('admin_notices', array($this, 'on_admin_notices'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  function on_admin_notices() {
    // TODO:  Add notice that if the plugin is activated but no files are selected, nothing is happening
  }

  public function on_admin_menu() {
    add_options_page(__('WP SW Cache', 'wpswcache'), __('WP SW Cache', 'wpswcache'), 'manage_options', 'wp-sw-cache-options', array($this, 'options'));
  }

  function options() {

?>

<div class="wrap">

  <h1><?php _e('WordPress ServiceWorker Cache', 'wpswcache'); ?></h1>

  <form method="post" action="">
    <h2><?php _e('Enable ServiceWorker Cache', 'wpswcache'); ?></h2>
    <li>[checkbox] Option to enable or disable the service worker</li>
    <li>[textbox] Cache Name:</li>

    <h2><?php _e('Files to Cache', 'wpswcache'); ?></h2>
    <p><?php _e('Select files that are used on a majority of pages.', 'wpswcache'); ?></p>
    <li>[ ] Series of checkboxes of scanned files from theme</li>

    <?php submit_button(__('Save Changes'), 'primary'); ?>
  </form>

</div>

<?php
  }
}
?>
