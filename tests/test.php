<?php

class SW_Tests extends WP_UnitTestCase {

	function setup() {
		parent::setup();

		// Ensure the plugin is enabled
		update_option('wp_sw_cache_enabled', 1);

		// Set which files should be cached
		update_option('wp_sw_cache_files', array('style.css', 'screenshot.png'));
	}

	function test_file_must_exist() {
		// Step 1:  Get the SW content
		$sw_content_1 = SW_Cache_Main::build_sw();

		// Step 2:  Rename file so to mock that the file has been deleted
		rename(get_template_directory().'/style.css', get_template_directory().'/style.temp');

		// Step 3:  Get SW content
		$sw_content_2 = SW_Cache_Main::build_sw();

		// Success means the new SW content is no longer the same
		$this->assertTrue($sw_content_1 != $sw_content_2);

		// Cleanup:  put the file back
		rename(get_template_directory().'/style.temp', get_template_directory().'/style.css');
	}

	function test_unchanged_files_and_times_generates_same_sw() {
		// Success means the SW content is the same because nothing has changed
		$this->assertTrue(SW_Cache_Main::build_sw() === SW_Cache_Main::build_sw());
	}

	function test_file_changed_generates_new_sw() {
		// Step 1:  Get content
		$sw_content_1 = SW_Cache_Main::build_sw();

		// Step 2:  Update a file's contents to nudge the modified time
		$file_to_edit = get_template_directory().'/style.css';
		$original_content = file_get_contents($file_to_edit);
		file_put_contents($file_to_edit, 'blah blah');

		// Step 3:  Get the new content
		$sw_content_2 = SW_Cache_Main::build_sw();

		// Success means the SW content is different because a file has changed
		$this->assertTrue($sw_content_1 !== $sw_content_2);
	}

	function test_changed_file_list_generates_new_sw() {
		// Step 1:  Get content
		$sw_content_1 = SW_Cache_Main::build_sw();

		// Step 2:  Remove the last item from the list
		$files = get_option('wp_sw_cache_files');
		$last_file = array_pop($files);
		update_option('wp_sw_cache_files', $files);

		// Step 3:  Get the new content
		$sw_content_2 = SW_Cache_Main::build_sw();

		// Success means the content is different because a file has changed
		$this->assertTrue($sw_content_1 !== $sw_content_2);

		// Cleanup:  Put the removed item back
		$files[] = $last_file;
		update_option('wp_sw_cache_files', $files);
	}

	function test_update_runs_on_version_mismatch() {
		$old_version = '0.0.0';
		update_option('wp_sw_cache_version', $old_version);

		SW_Cache_DB::update();

		$this->assertTrue($old_version !== get_option('wp_sw_cache_version'));
	}
}
