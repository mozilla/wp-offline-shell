<?php

class SW_Tests extends WP_UnitTestCase {

	function setup() {
		parent::setup();

		// Ensure the plugin is enabled
		update_option('wp_sw_cache_enabled', true);

		// Set which files should be cached
		update_option('wp_sw_cache_files', array('style.css', 'screenshot.png'));
	}

	function test_file_must_exist() {
		// Step 1:  Set the hash
		$first_hash = get_option('wp_sw_cache_version');

		// Step 2:  Rename file so to mock that the file has been deleted
		rename(get_template_directory().'/style.css', get_template_directory().'/style.temp');

		// Step 3:  Get hash
		SW_Cache_Main::build_sw();
		$second_hash = get_option('wp_sw_cache_version');

		// Success means the hash was updated because the file was deleted
		$this->assertTrue($first_hash != $second_hash);

		// Cleanup:  put the file back
		rename(get_template_directory().'/style.temp', get_template_directory().'/style.css');
	}

	function test_unchanged_files_and_times_generates_same_hash() {
		// Step 1:  Get hash
		SW_Cache_Main::build_sw();
		$first_hash = get_option('wp_sw_cache_version');

		// Step 2:  Do nothing, get the hash again
		SW_Cache_Main::build_sw();
		$second_hash = get_option('wp_sw_cache_version');

		// Success means the hashes are the same because nothing has changed
		$this->assertTrue($first_hash === $second_hash);
	}

	function test_file_changed_generates_new_hash() {
		// Step 1:  Get hash
		SW_Cache_Main::build_sw();
		$first_hash = get_option('wp_sw_cache_version');

		// Step 2:  Update a file's contents to nudge the modified time
		$file_to_edit = get_template_directory().'/style.css';
		$original_content = file_get_contents($file_to_edit);
		file_put_contents($file_to_edit, 'blah blah');

		// Step 3:  Get the new hash
		SW_Cache_Main::build_sw();
		$second_hash = get_option('wp_sw_cache_version');

		// Success means the hashes are different because a file has changed
		$this->assertTrue($first_hash !== $second_hash);
	}

	function test_changed_file_list_generates_new_hash() {
		// Step 1:  Get hash
		SW_Cache_Main::build_sw();
		$first_hash = get_option('wp_sw_cache_version');

		// Step 2:  Remove the last item from the list
		$files = get_option('wp_sw_cache_files');
		$last_file = array_pop($files);
		update_option('wp_sw_cache_files', $files);

		// Step 3:  Get the new hash
		SW_Cache_Main::build_sw();
		$second_hash = get_option('wp_sw_cache_version');

		// Success means the hashes are different because a file has changed
		$this->assertTrue($first_hash !== $second_hash);

		// Cleanup:  Put the removed item back
		array_push($files, $last_file);
		update_option('wp_sw_cache_files', $files);
	}

	function test_disabled_plugin_returns_nothing() {
		// Step 1:  Disable the plugin
		update_option('wp_sw_cache_enabled', false);

		// Step 2:  Get the SW output, which should be nothing
		$sw_output = SW_Cache_Main::build_sw();

		// Success is no output because the plugin is disabled
		$this->assertTrue($sw_output === '');

		// Cleanup:  re-enable the plugin
		update_option('wp_sw_cache_enabled', true);
	}
}
