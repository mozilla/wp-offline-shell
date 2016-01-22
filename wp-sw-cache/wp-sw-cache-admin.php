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
    <table class="form-table">
    <tr>
      <th scope="row"><label for="wp_sw_cache_enabled"><?php _e('Enable Service Worker?', 'wpswcache'); ?></label></th>
      <td>
        <input type="checkbox" name="wp_sw_cache_enabled" id="wp_sw_cache_enabled" value="1" autofocus />
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="wp_sw_cache_name"><?php _e('Cache Prefix?', 'wpswcache'); ?></label></th>
      <td>
        <input type="text" name="wp_sw_cache_name" id="wp_sw_cache_name" value="" />
      </td>
    </tr>
    </table>

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
