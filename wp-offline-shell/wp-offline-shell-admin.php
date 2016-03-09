<?php

load_plugin_textdomain('offline-shell', false, dirname(plugin_basename(__FILE__)).'/lang');

class Offline_Shell_Admin {
  private static $instance;

  public function __construct() {
    add_action('admin_menu', array($this, 'on_admin_menu'));
    add_action('admin_notices', array($this, 'on_admin_notices'));
    add_action('after_switch_theme', array($this, 'on_switch_theme'));
    add_action('wp_ajax_offline_shell_files', array($this, 'get_files_ajax'));
  }

  public static function init() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function get_files_ajax() {
    // If they've asked for files, just output the file HTML
    if(isset($_POST['data']) && $_POST['data'] === 'files') {
      echo $this->options_files();
    }
  }

  public function process_options() {
    if(!isset($_POST['offline_shell_form_submitted'])) {
      return false;
    }

    // Update "enabled" status
    update_option('offline_shell_enabled', isset($_POST['offline_shell_enabled']) ? intval($_POST['offline_shell_enabled']) : 0);

    // Update "debug" status
    update_option('offline_shell_debug', isset($_POST['offline_shell_debug']) ? intval($_POST['offline_shell_debug']) : 0);

    // Update files to cache *only* if the file listing loaded properly
    if(isset($_POST['offline_shell_files_loaded']) && intval($_POST['offline_shell_files_loaded']) === 1) {
      $files = array();
      if(isset($_POST['offline_shell_files'])) {
        foreach($_POST['offline_shell_files'] as $file) {
          $file = urldecode($file);
          // Ensure the file actually exists
          $tfile = get_template_directory().'/'.$file;
          if(file_exists($tfile)) {
            $files[] = $file;
          }
        }
      }
      update_option('offline_shell_files', $files);
    }
    return true;
  }

  public function on_admin_notices() {
    if(get_option('offline_shell_enabled') && !count(get_option('offline_shell_files'))) {
      echo '<div class="update-nag"><p>', sprintf(__('Offline Shell is enabled but no files have been selected for caching.  To take full advantage of this plugin, <a href="%s">please select files to cache</a>.', 'offline-shell'), admin_url('options-general.php?page=offline-shell-options')),'</p></div>';
    }

    if(get_option('offline_shell_enabled') && ($_SERVER['REQUEST_SCHEME'] != 'https' && strrpos(strtolower($_SERVER['HTTP_HOST']), 'localhost', -strlen($_SERVER['HTTP_HOST']) === false))) {
      echo '<div class="update-nag"><p>', __('The ServiceWorker API requires a secure origin (HTTPS or localhost).  Your Service Worker may not work.', 'offline-shell'), '</p></div>';
    }
  }

  public function on_admin_menu() {
    add_options_page(__('Offline Shell', 'offline-shell'), __('Offline Shell', 'offline-shell'), 'manage_options', 'offline-shell-options', array($this, 'options'));
  }

  public function on_switch_theme() {
    if(get_option('offline_shell_enabled')) {
      update_option('offline_shell_enabled', Offline_Shell_DB::$options['offline_shell_enabled']);
      update_option('offline_shell_files', Offline_Shell_DB::$options['offline_shell_files']);
      add_action('admin_notices', array($this, 'show_switch_theme_message'));
    }
  }

  function show_switch_theme_message() {
    echo '<div class="update-nag"><p>',  sprintf(__('You\'ve changed themes; please update your <a href="%s">WP ServiceWorker Cache options</a>.', 'offline-shell'), admin_url('options-general.php?page=offline-shell-options')), '</p></div>';
  }

  function determine_file_recommendation($file_info, $all_files) {
    if(Offline_Shell_Recommender::has_min_file($file_info['name'], $all_files)) {
      return array(
        'verdict' => false,
        'message' => __('A matching minified file was found, deferring to minified asset.', 'offline-shell')
      );
    }

    // Standard CSS file
    if($file_info['name'] === 'style.css') {
      return array(
        'verdict' => true,
        'message' => sprintf(__('%s is a standard WordPress theme file.', 'offline-shell'), 'style.css')
      );
    }

    // screenshot.{png|gif|jpe?g} and editor-style.css are standard WP files for admin only, so don't use it
    if(Offline_Shell_Recommender::matches_any_regex(
      $file_info['name'],
      array('/screenshot\.(gif|png|jpg|jpeg|bmp)$/', '/editor-style.css$/')
    )) {
      return array(
        'verdict' => false,
        'message' => sprintf(__('%s is a standard WordPress theme file only used in admin.', 'offline-shell'), $file_info['name'])
      );
    }

    // Ignore old IE css and font files
    if(Offline_Shell_Recommender::matches_any_regex(
      $file_info['name'],
      array('/ie-?\d\.css$/', '/\.eot$/', '/html5-?(shiv)?\.js$/')
    )) {
      return array(
        'verdict' => false,
        'message' => sprintf(__('%s is likely for legacy Internet Explorer browsers.', 'offline-shell'), $file_info['name'])
      );
    }

    // Recommend woff and woff2 fonts
    // http://caniuse.com/#feat=woff2
    if(Offline_Shell_Recommender::matches_any_regex($file_info['name'], array('/\.woff2?$/'))) {
      return array(
        'verdict' => true,
        'message' => sprintf(__('woff2 is high performing and woff is a globally supported fallback', 'offline-shell'), $file_info['name'])
      );
    }

    // "Main" or secondary level CSS, JS, and image files are likely important for small theme_files
    if(in_array($file_info['category'], array('css', 'js', 'image')) && substr_count($file_info['name'], '/') < 2) {
      return array(
        'verdict' => true,
        'message' => __('Main or secondary level assets are likely important in most themes', 'offline-shell')
      );
    }

    return array('verdict' => false, 'message' => '');
  }

  function options() {
    $submitted = $this->process_options();

?>

<div class="wrap">

  <?php if($submitted) { ?>
    <div class="updated">
      <p><?php _e('Your settings have been saved.'); ?></p>
    </div>
  <?php } ?>

  <h1><?php _e('Offline Shell', 'offline-shell'); ?> <code>v<?php echo get_option('offline_shell_version'); ?></code></h1>

  <p><?php _e('Offline Shell is a utility that harnesses the power of the <a href="https://serviceworke.rs" target="_blank">ServiceWorker API</a> to cache frequently used assets for the purposes of performance and offline viewing.'); ?></p>

  <form method="post" action="">
    <input type="hidden" name="offline_shell_form_submitted" value="1">

    <h2><?php _e('Offline Shell Settings', 'offline-shell'); ?></h2>
    <table class="form-table">
    <tr>
      <th scope="row"><label for="offline_shell_enabled"><?php _e('Enable Service Worker', 'offline-shell'); ?></label></th>
      <td>
        <input type="checkbox" name="offline_shell_enabled" id="offline_shell_enabled" value="1" <?php if(intval(get_option('offline_shell_enabled'))) echo 'checked'; ?> autofocus />
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="offline_shell_debug"><?php _e('Enable Debug Messages', 'offline-shell'); ?></label></th>
      <td>
        <input type="checkbox" name="offline_shell_debug" id="offline_shell_debug" value="1" <?php if(intval(get_option('offline_shell_debug'))) echo 'checked'; ?> />
      </td>
    </tr>
    </table>

    <h2><?php _e('Theme Files to Cache', 'offline-shell'); ?> (<code><?php echo get_template(); ?></code>)</h2>
    <p class="offline-shell-buttons">
      <?php _e('Select theme assets (typically JavaScript, CSS, fonts, and image files) that are used on a majority of pages.', 'offline-shell'); ?>
      <button type="button" class="button button-primary offline-shell-toggle-all" disabled><?php _e('Select All Files', 'offline-shell'); ?></button>
      <button type="button" class="button button-primary offline-shell-clear-all"  disabled><?php _e('Clear All Files', 'offline-shell'); ?></button>
      <button type="button" class="button button-primary offline-shell-suggest-file" disabled data-suggested-text="<?php echo esc_attr__('Files Suggested: ', 'offline-shell'); ?>"><?php _e('Suggest Files'); ?> <span>(<?php _e('beta'); ?>)</span></button>
    </p>

    <div class="offline-shell-file-list" id="offline-shell-file-list">
      <p><?php _e('Loading theme files...', 'offline-shell'); ?></p>
    </div>
    <input type="hidden" name="offline_shell_files_loaded" id="offline_shell_files_loaded" value="0">

    <?php submit_button(__('Save Changes'), 'primary'); ?>
  </form>

</div>

<style>
  .offline-shell-suggested {
    background: lightgreen;
  }

  .offline-shell-suggest-file,
  .offline-shell-toggle-all,
  .offline-shell-clear-all {
    float: right;
    margin-left: 10px !important;
  }

  .offline-shell-file-list {
    max-height: 300px;
    background: #fefefe;
    border: 1px solid #ccc;
    padding: 10px;
    overflow-y: auto;
  }

  .offline-shell-suggest-file span {
    font-size: smaller;
    color: #fc0;
    font-weight: bold;
  }

  .offline-shell-file-size,
  .offline-shell-file-recommended,
  .offline-shell-file-not-recommended {
    font-size: smaller;
    color: #999;
    font-size: italic;
    display: inline-block;
    margin-left: 20px;
  }

  .offline-shell-file-recommended {
    color: green;
  }

  .offline-shell-file-all {
    font-size: 14px;
    display: inline-block;
    margin-left: 20px;
  }

</style>

<script type="text/javascript">

  // Create event listeners for the file listing helpers
  jQuery(document.body)
    .on('click', '.offline-shell-suggest-file', function() {
      var $this = jQuery(this);
      var suggestedCounter = 0;

      // Suggest main level CSS and JS files
      var $recommended = jQuery('.files-list input[type="checkbox"].recommended:not(:checked)')
                          .prop('checked', 'checked')
                          .closest('tr').addClass('offline-shell-suggested');

      // Update sugget button now that the process is done
      $this
        .text($this.data('suggested-text') + ' ' + $recommended.length)
        .prop('disabled', 'disabled');
    })
    .on('click', '.offline-shell-toggle-all', function() {
      jQuery('.files-list input[type="checkbox"]').prop('checked', 'checked');
    })
    .on('click', '.offline-shell-clear-all', function() {
      jQuery('.files-list input[type="checkbox"]').prop('checked', '');
    })
    .on('click', '.offline-shell-file-all', function(e) {
      e.preventDefault();
      jQuery(this.parentNode).next('.files-list').find('input[type=checkbox]').prop('checked', 'checked');
    });

  // Load the file listing async so as to not brick the page on huge themes
  // ajaxurl is a WordPress global JS var
  jQuery.post(ajaxurl, {
    action: 'offline_shell_files',
    data: 'files'
  }).done(function(response) {
    // Place the file listing
    jQuery('#offline-shell-file-list').html(response);
    // Notify admin that the files have been loaded and placed
    jQuery('#offline_shell_files_loaded').val(1);
    // Enable the file control buttons
    jQuery('.offline-shell-buttons button').removeProp('disabled');
  });

</script>

<?php
  }

  function options_files() {
    // Get default values for file listing
    $selected_files = get_option('offline_shell_files');
    if(!$selected_files) {
      $selected_files = array();
    }

    $template_abs_path = get_template_directory();
    $theme_files = wp_get_theme()->get_files(null, 10); // 10 is arbitrary

    $categories = array(
      array('key' => 'css', 'title' => __('CSS Files', 'offline-shell'), 'extensions' => array('css'), 'files' => array()),
      array('key' => 'js', 'title' => __('JavaScript Files', 'offline-shell'), 'extensions' => array('js'), 'files' => array()),
      array('key' => 'font', 'title' => __('Font Files', 'offline-shell'), 'extensions' => array('woff', 'woff2', 'ttf', 'eot'), 'files' => array()),
      array('key' => 'image', 'title' => __('Image Files', 'offline-shell'), 'extensions' => array('svg', 'jpg', 'jpeg', 'gif', 'png', 'webp'), 'files' => array()),
      array('key' => 'other', 'title' => __('Other Files', 'offline-shell'), 'extensions' => array('*'), 'files' => array()) // Needs to be last
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
        <a href="" class="offline-shell-file-all">Select All</a>
      </h3>

      <?php if($category['key'] === 'other') { ?>
        <p><em><?php _e('The following assets, especially <code>.php</code> files, have no value in being cached directly by service workers.'); ?></em></p>
      <?php } ?>

      <?php if(count($category['files'])) { ?>
      <table class="files-list">
        <?php foreach($category['files'] as $file) { $file_id++; ?>
        <tr>
          <td style="width: 30px;">
            <input type="checkbox" class="<?php if($file['recommended']['verdict']) { echo 'recommended'; } ?>" name="offline_shell_files[]" id="offline_shell_files['file_<?php echo $file_id; ?>']" value="<?php echo esc_attr(urlencode($file['name'])); ?>" <?php if(in_array($file['name'], $selected_files)) { echo 'checked'; } ?> />
          </td>
          <td>
            <label for="offline_shell_files['file_<?php echo $file_id; ?>']">
              <?php echo esc_html($file['name']); ?>
              <span class="offline-shell-file-size"><?php echo ($file['size'] > 0 ? round($file['size']/1024, 2) : 0).'kb'; ?></span>
              <?php if($file['recommended']['message']) { ?>
                <?php if($file['recommended']['verdict']) { ?>
                  <span class="offline-shell-file-recommended">&#10004; <?php echo esc_html($file['recommended']['message']); ?></span>
                <?php } else { ?>
                  <span class="offline-shell-file-not-recommended">&times; <?php echo esc_html($file['recommended']['message']); ?></span>
                <?php } ?>
              <?php } ?>
            </label>
          </td>
        </tr>
        <?php } ?>
      </table>
      <?php } else { ?><p><?php _e('No matching files found.', 'offline-shell'); ?></p><?php } ?>
    <?php } ?>

<?php
  }

}
?>
