<?php

/*
 * Allows bulk actions between the Source and Target sites
 * @package Sync
 * @author WPSiteSync
 */

class SyncBulkActionsAdmin
{
	private static $_instance = NULL;

	private function __construct()
	{
		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
		//add_action('spectrom_sync_ajax_operation', array(&$this, 'check_ajax_query'), 10, 3);
	}

	/**
	 * Retrieve singleton class instance
	 *
	 * @since 1.0.0
	 * @static
	 * @return null|SyncMenusAdmin instance reference to plugin
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Registers js and css to be used.
	 *
	 * @since 1.0.0
	 * @param $hook_suffix
	 * @return void
	 */
	public function admin_enqueue_scripts($hook_suffix)
	{
		wp_register_script('sync-bulkactions', WPSiteSync_BulkActions::get_asset('js/sync-bulkactions.js'), array('sync'), WPSiteSync_BulkActions::PLUGIN_VERSION, TRUE);

		if ('edit.php' === $hook_suffix) {
			$screen = get_current_screen();
			$post_types = apply_filters('spectrom_sync_allowed_post_types', array('post', 'page'));

			if (in_array($screen->post_type, $post_types)) {
				$translation_array = array(
					'some_string' => __('Some string to translate', 'wpsitesync-bulkactions'),
					'a_value' => '10'
				);
				wp_localize_script('sync-bulkactions', 'object_name', $translation_array);
				wp_enqueue_script('sync-bulkactions');
			}
		}
	}
}

// EOF
