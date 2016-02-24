<?php

load_plugin_textdomain('wpswcache', false, dirname(plugin_basename(__FILE__)) . '/lang');

class SW_Cache_Admin {
  private static $instance;

  public function __construct() {
    add_action('admin_menu', array($this, 'on_admin_menu'));
    add_action('admin_notices', array($this, 'on_admin_notices'));
    add_action('after_switch_theme', array($this, 'on_switch_theme'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
  }

  public function process_options() {
    // Form submission
    if(isset($_POST['wpswcache_form_submitted'])) {

      // Update "enabled" status
      update_option('wp_sw_cache_enabled', isset($_POST['wp_sw_cache_enabled']));

      // Update "debug" status
      update_option('wp_sw_cache_debug', isset($_POST['wp_sw_cache_debug']));

      // Update files to cache
      $files = array();
      if(isset($_POST['wp_sw_cache_files'])) {
        foreach($_POST['wp_sw_cache_files'] as $file) {
          array_push($files, urldecode($file));
        }
      }
      update_option('wp_sw_cache_files', $files);

      return true;
    }

    return false;
  }

  public function on_admin_notices() {
    if(get_option('wp_sw_cache_enabled') && !count(get_option('wp_sw_cache_files'))) {
      echo '<div class="update-nag"><p>', sprintf(__('Service Worker is enabled but no files have been selected for caching.  To take full advantage of this plugin, <a href="%s">please select files to cache</a>.', 'swpswcache'), admin_url('options-general.php?page=wp-sw-cache-options')),'</p></div>';
    }

    if(get_option('wp_sw_cache_enabled') && ($_SERVER['REQUEST_SCHEME'] != 'https' && strrpos(strtolower($_SERVER['HTTP_HOST']), 'localhost', -strlen($_SERVER['HTTP_HOST']) === false))) {
      echo '<div class="update-nag"><p>', __('The ServiceWorker API requires a secure origin (HTTPS or localhost).  Your Service Worker may not work.', 'swpswcache'), '</p></div>';
    }
  }

  public function on_admin_menu() {
    add_options_page(__('WP SW Cache', 'wpswcache'), __('WP SW Cache', 'wpswcache'), 'manage_options', 'wp-sw-cache-options', array($this, 'options'));
  }

  public function on_switch_theme() {
    if(get_option('wp_sw_cache_enabled')) {
      update_option('wp_sw_cache_enabled', false);
      update_option('wp_sw_cache_files', array());
      add_action('admin_notices', array($this, 'show_switch_theme_message'));
    }
  }

  function show_switch_theme_message() {
    echo '<div class="update-nag"><p>',  __('You\'ve changed themes; please update your WP ServiceWorker Cache options.', 'swpswcache'), '</p></div>';
  }

  function determine_file_recommendation($file_info, $all_files) {
    /*
      TODO:
        -  Detect if a "positive" file has a "-min" version; if so, choose the -min and not the standard file

    */


    // Standard CSS file
    if($file_info['name'] === 'style.css') {
      return sprintf(__('%s is a standard WordPress theme file.'), 'style.css');
    }

    // "Main level" CSS, JS, and image files are likely important for small theme_files
    if(in_array($file_info['category'], array('css', 'js', 'image')) && strpos($file_info['name'], '/') === false) {
      return __('Main level assets are likely important in small themes');
    }

    return false;
  }

  function options() {
    $submitted = $this->process_options();

    // Get default values for file listing
    $selected_files = get_option('wp_sw_cache_files');
    if(!$selected_files) {
      $selected_files = array();
    }

?>

<style>
  .wp-sw-cache-suggested {
    background: lightgreen;
  }

  .wp-sw-cache-suggest-file,
  .wp-sw-cache-toggle-all,
  .wp-sw-cache-clear-all {
    float: right;
    margin-left: 10px !important;
  }

  .wp-sw-cache-file-list {
    max-height: 300px;
    background: #fefefe;
    border: 1px solid #ccc;
    padding: 10px;
    overflow-y: auto;
  }

  .wp-sw-cache-suggest-file span {
    font-size: smaller;
    color: #fc0;
    font-weight: bold;
  }

  .wp-sw-cache-file-size,
  .wp-sw-cache-file-recommended {
    font-size: smaller;
    color: #999;
    font-size: italic;
    display: inline-block;
    margin-left: 20px;
  }

  .wp-sw-cache-file-recommended {
    color: green;
  }

</style>
<div class="wrap">

  <?php if($submitted) { ?>
    <div class="updated">
      <p><?php _e('Your settings have been saved.'); ?></p>
    </div>
  <?php } ?>

  <h1><?php _e('WordPress Service Worker Cache', 'wpswcache'); ?> (<?php echo SW_Cache_Main::$cache_prefix; ?>)</h1>

  <p><?php _e('WordPress Service Worker Cache is a ultility that harnesses the power of the <a href="https://serviceworke.rs" target="_blank">ServiceWorker API</a> to cache frequently used assets for the purposes of performance and offline viewing.'); ?></p>

  <form method="post" action="">
    <input type="hidden" name="wpswcache_form_submitted" value="1">

    <h2><?php _e('ServiceWorker Cache Settings', 'wpswcache'); ?></h2>
    <table class="form-table">
    <tr>
      <th scope="row"><label for="wp_sw_cache_enabled"><?php _e('Enable Service Worker', 'wpswcache'); ?></label></th>
      <td>
        <input type="checkbox" name="wp_sw_cache_enabled" id="wp_sw_cache_enabled" value="1" <?php if(get_option('wp_sw_cache_enabled')) echo 'checked'; ?> autofocus />
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="wp_sw_cache_debug"><?php _e('Enable Debug Messages', 'wpswcache'); ?></label></th>
      <td>
        <input type="checkbox" name="wp_sw_cache_debug" id="wp_sw_cache_debug" value="1" <?php if(get_option('wp_sw_cache_debug')) echo 'checked'; ?> />
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="wp_sw_cache_name"><?php _e('Current Cache Name', 'wpswcache'); ?></label></th>
      <td>
        <em><?php echo get_option('wp_sw_cache_name'); ?></em>
      </td>
    </tr>
    </table>

    <h2><?php _e('Theme Files to Cache', 'wpswcache'); ?> (<code><?php echo get_template(); ?></code>)</h2>
    <p>
      <?php _e('Select theme assets (typically JavaScript, CSS, fonts, and image files) that are used on a majority of pages.', 'wpswcache'); ?>
      <button type="button" class="button button-primary wp-sw-cache-toggle-all"><?php _e('Select All Files'); ?></button>
      <button type="button" class="button button-primary wp-sw-cache-clear-all"><?php _e('Clear All Files'); ?></button>
      <button type="button" class="button button-primary wp-sw-cache-suggest-file" data-suggested-text="<?php echo esc_attr__('Files Suggested: '); ?>"><?php _e('Suggest Files'); ?> <span>(<?php _e('beta'); ?>)</span></button>
    </p>

    <?php /* <pre><?php print_r($selected_files); ?></pre> */ ?>
    <div class="wp-sw-cache-file-list">
      <?php
        $template_abs_path = get_template_directory();
        $theme_files = wp_get_theme()->get_files(null, 10); // 10 is arbitrary

        $categories = array(
          array('key' => 'css', 'title' => __('CSS Files', 'wpswcache'), 'extensions' => array('css'), 'files' => array()),
          array('key' => 'js', 'title' => __('JavaScript Files', 'wpswcache'), 'extensions' => array('js'), 'files' => array()),
          array('key' => 'font', 'title' => __('Font Files', 'wpswcache'), 'extensions' => array('woff', 'woff2', 'ttf', 'eot'), 'files' => array()),
          array('key' => 'image', 'title' => __('Image Files', 'wpswcache'), 'extensions' => array('svg', 'jpg', 'jpeg', 'gif', 'png', 'webp'), 'files' => array()),
          array('key' => 'other', 'title' => __('Other Files', 'wpswcache'), 'extensions' => array('*'), 'files' => array()) // Needs to be last
        );

        // Sort the files and place them in their baskets
        foreach($theme_files as $file => $abs_path) {
          $file_relative = str_replace(get_theme_root().'/'.get_template().'/', '', $file);
          $path_info = pathinfo($file_relative);
          $file_category_found = false;

          // Build array to represent file
          $file_info = array(
            'name' => $file_relative,
            'size' => filesize($abs_path),
            'absolute' => $abs_path,
            'path' => $path_info
          );

          // Categorize the file, use "other" as the fallback
          foreach($categories as $index=>$category) {
            if(in_array(strtolower($path_info['extension']), $category['extensions']) || ($file_category_found === false && $category['key'] === 'other')) {
              // Store the category to help determine recommendation
              $file_info['category'] = $category['key'];
              // Determine if we should recommend this file be cached
              $file_info['recommended'] = self::determine_file_recommendation($file_info, $theme_files);
              // Add to category array
              $categories[$index]['files'][] = $file_info;
              $file_category_found = true;
            }
          }
        }

        $file_id = 0;
        foreach($categories as $category) { ?>
          <h3><?php echo $category['title']; ?> (<?php echo implode(', ', $category['extensions']); ?>)</h3>

          <?php if($category['key'] === 'other') { ?>
            <p><em><?php _e('The following assets, especially <code>.php</code> files, have no value in being cached directly by service workers.'); ?></em></p>
          <?php } ?>

          <?php if(count($category['files'])) { ?>
          <table class="files-list">
            <?php foreach($category['files'] as $file) { $file_id++; ?>
            <tr>
              <td style="width: 30px;">
                <input type="checkbox" class="<?php if($file['recommended']) { echo 'recommended'; } ?>" name="wp_sw_cache_files[]" id="wp_sw_cache_files['file_<?php echo $file_id; ?>']" value="<?php echo urlencode($file['name']); ?>" <?php if(in_array($file['name'], $selected_files)) { echo 'checked'; } ?> />
              </td>
              <td>
                <label for="wp_sw_cache_files['file_<?php echo $file_id; ?>']">
                  <?php echo $file['name']; ?>
                  <span class="wp-sw-cache-file-size"><?php echo ($file['size'] > 0 ? round($file['size']/1024, 2) : 0).'kb'; ?></span>
                  <?php if($file['recommended']) { ?>
                    <span class="wp-sw-cache-file-recommended">&#10004; <?php echo $file['recommended']; ?></span>
                  <?php } ?>
                </label>
              </td>
            </tr>
            <?php } ?>
          </table>
          <?php } else { ?><p><?php _e('No matching files found.', 'wpswcache'); ?></p><?php } ?>
        <?php } ?>
    </div>

    <?php submit_button(__('Save Changes'), 'primary'); ?>
  </form>

  <h2>Clear Caches</h2>
  <p><?php _e('Click the button below to clear any caches created by this plugin.'); ?></p>
  <button type="button" class="button button-primary wp-sw-cache-clear-caches-button" data-cleared-text="<?php echo esc_attr('Caches cleared: '); ?>"><?php _e('Clear Caches'); ?></button>

</div>

<script type="text/javascript">
  jQuery('.wp-sw-cache-suggest-file').on('click', function() {
    // TODO:  More advanced logic

    var $this = jQuery(this);
    var suggestedCounter = 0;

    // Suggest main level CSS and JS files
    var $recommended = jQuery('.files-list input[type="checkbox"].recommended:not(:checked)')
                        .prop('checked', 'checked')
                        .closest('tr').addClass('wp-sw-cache-suggested');

    // Update sugget button now that the process is done
    $this
      .text($this.data('suggested-text') + ' ' + $recommended.length)
      .prop('disabled', 'disabled');
  });

  jQuery('.wp-sw-cache-toggle-all').on('click', function() {
    jQuery('.files-list input[type="checkbox"]').prop('checked', 'checked');
  });

  jQuery('.wp-sw-cache-clear-all').on('click', function() {
    jQuery('.files-list input[type="checkbox"]').prop('checked', '');
  });

  jQuery('.wp-sw-cache-clear-caches-button').on('click', function() {
    var clearedCounter = 0;
    var $button = jQuery(this);

    // Clean up old cache in the background
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {

          if(cacheName.indexOf('<?php echo SW_Cache_Main::$cache_prefix; ?>') != -1) {
            console.log('Clearing cache: ', cacheName);
            clearedCounter++;
            return caches.delete(cacheName);
          }
          else {
            console.log('Leaving cache: ' + cacheName);
            return Promise.resolve();
          }
        })
      );
    }).then(function() {
      $button.text($button.data('cleared-text') + ' ' + clearedCounter);
      $button[0].disabled = true;
    });
  });
</script>

<?php
  }
}
?>
