<?php

load_plugin_textdomain('service-worker-cache', false, dirname(plugin_basename(__FILE__)).'/lang');

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
    return self::$instance;
  }

  public function process_options() {
    // Form submission
    if(isset($_POST['wpswcache_form_submitted'])) {

      // Update "enabled" status
      update_option('wp_sw_cache_enabled', isset($_POST['wp_sw_cache_enabled']) ? intval($_POST['wp_sw_cache_enabled']) : 0);

      // Update "debug" status
      update_option('wp_sw_cache_debug', isset($_POST['wp_sw_cache_debug']) ? intval($_POST['wp_sw_cache_debug']) : 0);

      // Update files to cache
      $files = array();
      if(isset($_POST['wp_sw_cache_files'])) {
        foreach($_POST['wp_sw_cache_files'] as $file) {
          $file = urldecode($file);
          // Ensure the file actually exists
          $tfile = get_template_directory().'/'.$file;
          if(file_exists($tfile)) {
            array_push($files, $file);
          }
        }
      }
      update_option('wp_sw_cache_files', $files);

      return true;
    }

    return false;
  }

  public function on_admin_notices() {
    if(get_option('wp_sw_cache_enabled') && !count(get_option('wp_sw_cache_files'))) {
      echo '<div class="update-nag"><p>', sprintf(__('Service Worker is enabled but no files have been selected for caching.  To take full advantage of this plugin, <a href="%s">please select files to cache</a>.', 'service-worker-cache'), admin_url('options-general.php?page=wp-sw-cache-options')),'</p></div>';
    }

    if(get_option('wp_sw_cache_enabled') && ($_SERVER['REQUEST_SCHEME'] != 'https' && strrpos(strtolower($_SERVER['HTTP_HOST']), 'localhost', -strlen($_SERVER['HTTP_HOST']) === false))) {
      echo '<div class="update-nag"><p>', __('The ServiceWorker API requires a secure origin (HTTPS or localhost).  Your Service Worker may not work.', 'service-worker-cache'), '</p></div>';
    }
  }

  public function on_admin_menu() {
    add_options_page(__('Service Worker Cache', 'service-worker-cache'), __('Service Worker Cache', 'service-worker-cache'), 'manage_options', 'wp-sw-cache-options', array($this, 'options'));
  }

  public function on_switch_theme() {
    if(get_option('wp_sw_cache_enabled')) {
      update_option('wp_sw_cache_enabled', SW_Cache_DB::$options['wp_sw_cache_enabled']);
      update_option('wp_sw_cache_files', SW_Cache_DB::$options['wp_sw_cache_files']);
      add_action('admin_notices', array($this, 'show_switch_theme_message'));
    }
  }

  function show_switch_theme_message() {
    echo '<div class="update-nag"><p>',  sprintf(__('You\'ve changed themes; please update your <a href="%s">WP ServiceWorker Cache options</a>.', 'service-worker-cache'), admin_url('options-general.php?page=wp-sw-cache-options')), '</p></div>';
  }

  function determine_file_recommendation($file_info, $all_files) {
    if(SW_Cache_Recommender::has_min_file($file_info['name'], $all_files)) {
      return array(
        'verdict' => false,
        'message' => __('A matching minified file was found, deferring to minified asset.', 'service-worker-cache')
      );
    }

    // Standard CSS file
    if($file_info['name'] === 'style.css') {
      return array(
        'verdict' => true,
        'message' => sprintf(__('%s is a standard WordPress theme file.', 'service-worker-cache'), 'style.css')
      );
    }

    // screenshot.{png|gif|jpe?g} and editor-style.css are standard WP files for admin only, so don't use it
    if(SW_Cache_Recommender::matches_any_regex(
      $file_info['name'],
      array('/screenshot\.(gif|png|jpg|jpeg|bmp)$/', '/editor-style.css$/')
    )) {
      return array(
        'verdict' => false,
        'message' => sprintf(__('%s is a standard WordPress theme file only used in admin.', 'service-worker-cache'), $file_info['name'])
      );
    }

    // Ignore old IE css and font files
    if(SW_Cache_Recommender::matches_any_regex(
      $file_info['name'],
      array('/ie-?\d\.css$/', '/\.eot$/', '/html5-?(shiv)?\.js$/')
    )) {
      return array(
        'verdict' => false,
        'message' => sprintf(__('%s is likely for legacy Internet Explorer browsers.', 'service-worker-cache'), $file_info['name'])
      );
    }

    // Recommend woff and woff2 fonts
    // http://caniuse.com/#feat=woff2
    if(SW_Cache_Recommender::matches_any_regex($file_info['name'], array('/\.woff2?$/'))) {
      return array(
        'verdict' => true,
        'message' => sprintf(__('woff2 is high performing and woff is a globally supported fallback', 'service-worker-cache'), $file_info['name'])
      );
    }

    // "Main" or secondary level CSS, JS, and image files are likely important for small theme_files
    if(in_array($file_info['category'], array('css', 'js', 'image')) && substr_count($file_info['name'], '/') < 2) {
      return array(
        'verdict' => true,
        'message' => __('Main or secondary level assets are likely important in most themes', 'service-worker-cache')
      );
    }

    return array('verdict' => false, 'message' => '');
  }

  function options() {
    $submitted = $this->process_options();

    // Get default values for file listing
    $selected_files = get_option('wp_sw_cache_files');
    if(!$selected_files) {
      $selected_files = array();
    }

?>

<div class="wrap">

  <?php if($submitted) { ?>
    <div class="updated">
      <p><?php _e('Your settings have been saved.'); ?></p>
    </div>
  <?php } ?>

  <h1><?php _e('Service Worker Cache', 'service-worker-cache'); ?></h1>

  <p><?php _e('Service Worker Cache is a utility that harnesses the power of the <a href="https://serviceworke.rs" target="_blank">ServiceWorker API</a> to cache frequently used assets for the purposes of performance and offline viewing.'); ?></p>

  <form method="post" action="">
    <input type="hidden" name="wpswcache_form_submitted" value="1">

    <h2><?php _e('ServiceWorker Cache Settings', 'service-worker-cache'); ?></h2>
    <table class="form-table">
    <tr>
      <th scope="row"><label for="wp_sw_cache_enabled"><?php _e('Enable Service Worker', 'service-worker-cache'); ?></label></th>
      <td>
        <input type="checkbox" name="wp_sw_cache_enabled" id="wp_sw_cache_enabled" value="1" <?php if(intval(get_option('wp_sw_cache_enabled'))) echo 'checked'; ?> autofocus />
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="wp_sw_cache_debug"><?php _e('Enable Debug Messages', 'service-worker-cache'); ?></label></th>
      <td>
        <input type="checkbox" name="wp_sw_cache_debug" id="wp_sw_cache_debug" value="1" <?php if(intval(get_option('wp_sw_cache_debug'))) echo 'checked'; ?> />
      </td>
    </tr>
    </table>

    <h2><?php _e('Theme Files to Cache', 'service-worker-cache'); ?> (<code><?php echo get_template(); ?></code>)</h2>
    <p>
      <?php _e('Select theme assets (typically JavaScript, CSS, fonts, and image files) that are used on a majority of pages.', 'service-worker-cache'); ?>
      <button type="button" class="button button-primary wp-sw-cache-toggle-all"><?php _e('Select All Files'); ?></button>
      <button type="button" class="button button-primary wp-sw-cache-clear-all"><?php _e('Clear All Files'); ?></button>
      <button type="button" class="button button-primary wp-sw-cache-suggest-file" data-suggested-text="<?php echo esc_attr__('Files Suggested: '); ?>"><?php _e('Suggest Files'); ?> <span>(<?php _e('beta'); ?>)</span></button>
    </p>

    <div class="wp-sw-cache-file-list">
      <?php
        $template_abs_path = get_template_directory();
        $theme_files = wp_get_theme()->get_files(null, 10); // 10 is arbitrary

        $categories = array(
          array('key' => 'css', 'title' => __('CSS Files', 'service-worker-cache'), 'extensions' => array('css'), 'files' => array()),
          array('key' => 'js', 'title' => __('JavaScript Files', 'service-worker-cache'), 'extensions' => array('js'), 'files' => array()),
          array('key' => 'font', 'title' => __('Font Files', 'service-worker-cache'), 'extensions' => array('woff', 'woff2', 'ttf', 'eot'), 'files' => array()),
          array('key' => 'image', 'title' => __('Image Files', 'service-worker-cache'), 'extensions' => array('svg', 'jpg', 'jpeg', 'gif', 'png', 'webp'), 'files' => array()),
          array('key' => 'other', 'title' => __('Other Files', 'service-worker-cache'), 'extensions' => array('*'), 'files' => array()) // Needs to be last
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
          <h3>
            <?php echo esc_html($category['title']); ?>
            (<?php echo esc_html(implode(', ', $category['extensions'])); ?>)
            <a href="" class="wp-sw-cache-file-all">Select All</a>
          </h3>

          <?php if($category['key'] === 'other') { ?>
            <p><em><?php _e('The following assets, especially <code>.php</code> files, have no value in being cached directly by service workers.'); ?></em></p>
          <?php } ?>

          <?php if(count($category['files'])) { ?>
          <table class="files-list">
            <?php foreach($category['files'] as $file) { $file_id++; ?>
            <tr>
              <td style="width: 30px;">
                <input type="checkbox" class="<?php if($file['recommended']['verdict']) { echo 'recommended'; } ?>" name="wp_sw_cache_files[]" id="wp_sw_cache_files['file_<?php echo $file_id; ?>']" value="<?php echo esc_attr(urlencode($file['name'])); ?>" <?php if(in_array($file['name'], $selected_files)) { echo 'checked'; } ?> />
              </td>
              <td>
                <label for="wp_sw_cache_files['file_<?php echo $file_id; ?>']">
                  <?php echo esc_html($file['name']); ?>
                  <span class="wp-sw-cache-file-size"><?php echo ($file['size'] > 0 ? round($file['size']/1024, 2) : 0).'kb'; ?></span>
                  <?php if($file['recommended']['message']) { ?>
                    <?php if($file['recommended']['verdict']) { ?>
                      <span class="wp-sw-cache-file-recommended">&#10004; <?php echo esc_html($file['recommended']['message']); ?></span>
                    <?php } else { ?>
                      <span class="wp-sw-cache-file-not-recommended">&times; <?php echo esc_html($file['recommended']['message']); ?></span>
                    <?php } ?>
                  <?php } ?>
                </label>
              </td>
            </tr>
            <?php } ?>
          </table>
          <?php } else { ?><p><?php _e('No matching files found.', 'service-worker-cache'); ?></p><?php } ?>
        <?php } ?>
    </div>

    <?php submit_button(__('Save Changes'), 'primary'); ?>
  </form>

  <h2>Clear Caches</h2>
  <p><?php _e('Click the button below to clear any caches created by this plugin.'); ?></p>
  <button type="button" class="button button-primary wp-sw-cache-clear-caches-button" data-cleared-text="<?php echo esc_attr('Cache Cleared!'); ?>"><?php _e('Clear Local Cache'); ?></button>

</div>

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
  .wp-sw-cache-file-recommended,
  .wp-sw-cache-file-not-recommended {
    font-size: smaller;
    color: #999;
    font-size: italic;
    display: inline-block;
    margin-left: 20px;
  }

  .wp-sw-cache-file-recommended {
    color: green;
  }

  .wp-sw-cache-file-all {
    font-size: 14px;
    display: inline-block;
    margin-left: 20px;
  }

</style>

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

          if(cacheName === '<?php echo esc_html(SW_Cache_Main::$cache_name); ?>') {
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

  jQuery('.wp-sw-cache-file-all').on('click', function(e) {
    e.preventDefault();
    jQuery(this.parentNode).next('.files-list').find('input[type=checkbox]').prop('checked', 'checked');
  });
</script>

<?php
  }
}
?>
