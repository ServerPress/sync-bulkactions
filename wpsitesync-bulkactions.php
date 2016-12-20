<?php
/*
Plugin Name: WPSiteSync for Bulk Actions
Plugin URI: http://wpsitesync.com
Description: Extension for WPSiteSync for Content that provides the ability to operate on multiple pieces of Post or Page Content in a single operation.
Author: WPSiteSync
Author URI: http://wpsitesync.com
Version: 1.1
Text Domain: wpsitesync-bulkactions

The PHP code portions are distributed under the GPL license. If not otherwise stated, all
images, manuals, cascading stylesheets and included JavaScript are NOT GPL.
*/

if (!class_exists('WPSiteSync_BulkActions')) {
	/*
	 * @package WPSiteSync_BulkActions
	 * @author WPSiteSync
	 */

	class WPSiteSync_BulkActions
	{
		private static $_instance = NULL;

		const PLUGIN_NAME = 'WPSiteSync for Bulk Actions';
		const PLUGIN_VERSION = '1.1';
		const PLUGIN_KEY = 'a52e16518dcc910b9959b04c3d9ab698';

//		private $_license = NULL;

		private function __construct()
		{
			add_action('spectrom_sync_init', array(&$this, 'init'));
		}

		/**
		 * Retrieve singleton class instance
		 *
		 * @since 1.0.0
		 * @static
		 * @return null|WPSiteSync_BulkActions
		 */
		public static function get_instance()
		{
			if (NULL === self::$_instance)
				self::$_instance = new self();
			return self::$_instance;
		}

		/**
		 * Callback for Sync initialization action
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function init()
		{
//			$this->_license = new SyncLicensing();
			$license = WPSiteSyncContent::get_instance()->get_license();
			add_filter('spectrom_sync_active_extensions', array(&$this, 'filter_active_extensions'), 10, 2);
SyncDebug::log(__METHOD__.'() checking license');
			if (!$license->check_license('sync_bulkactions', self::PLUGIN_KEY, self::PLUGIN_NAME))
				return;

			if (is_admin() && SyncOptions::is_auth() ) {
				$this->load_class('bulkactionsadmin');
				SyncBulkActionsAdmin::get_instance();
			}

			$api = $this->load_class('bulkactionsapirequest', TRUE);

			add_filter('spectrom_sync_error_code_to_text', array($api, 'filter_error_codes'), 10, 2);
			add_filter('spectrom_sync_notice_code_to_text', array($api, 'filter_notice_codes'), 10, 2);
		}

		/**
		 * Loads a specified class file name and optionally creates an instance of it
		 *
		 * @since 1.0.0
		 * @param $name Name of class to load
		 * @param bool $create TRUE to create an instance of the loaded class
		 * @return bool|object Created instance of $create is TRUE; otherwise FALSE
		 */
		public function load_class($name, $create = FALSE)
		{
			$file = dirname(__FILE__) . '/classes/' . strtolower($name) . '.php';
			if (file_exists($file))
				require_once($file);
			if ($create) {
				$instance = 'Sync' . $name;
				return new $instance();
			}
			return FALSE;
		}

		/**
		 * Return reference to asset, relative to the base plugin's /assets/ directory
		 *
		 * @since 1.0.0
		 * @param string $ref asset name to reference
		 * @static
		 * @return string href to fully qualified location of referenced asset
		 */
		public static function get_asset($ref)
		{
			$ret = plugin_dir_url(__FILE__) . 'assets/' . $ref;
			return $ret;
		}

		/**
		 * Adds the WPSiteSync Bulk Actions add-on to the list of known WPSiteSync extensions
		 *
		 * @param array $extensions The list of extensions
		 * @param boolean TRUE to force adding the extension; otherwise FALSE
		 * @return array Modified list of extensions
		 */
		public function filter_active_extensions($extensions, $set = FALSE)
		{
SyncDebug::log(__METHOD__.'() checking license');
			if ($set || WPSiteSyncContent::get_instance()->get_license()->check_license('sync_bulkactions', self::PLUGIN_KEY, self::PLUGIN_NAME))
				$extensions['sync_bulkactions'] = array(
					'name' => self::PLUGIN_NAME,
					'version' => self::PLUGIN_VERSION,
					'file' => __FILE__,
				);
			return $extensions;
		}
	}
}

// Initialize the extension
WPSiteSync_BulkActions::get_instance();

// EOF
