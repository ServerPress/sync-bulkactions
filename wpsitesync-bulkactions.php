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
		const REQUIRED_VERSION = '1.5.2';

		private function __construct()
		{
			add_action('spectrom_sync_init', array($this, 'init'));
			add_action('wp_loaded', array($this, 'wp_loaded'));
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
			add_filter('spectrom_sync_active_extensions', array(&$this, 'filter_active_extensions'), 10, 2);

			if (!WPSiteSyncContent::get_instance()->get_license()->check_license('sync_bulkactions', self::PLUGIN_KEY, self::PLUGIN_NAME)) {
SyncDebug::log(__METHOD__.'():' . __LINE__ . ' no license');
				return;
			}

			// check for minimum WPSiteSync version
			if (is_admin() && version_compare(WPSiteSyncContent::PLUGIN_VERSION, self::REQUIRED_VERSION) < 0 && current_user_can('activate_plugins')) {
				add_action('admin_notices', array($this, 'notice_minimum_version'));
				return;
			}

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
		 * Called when WP is loaded so we can check if parent plugin is active.
		 */
		public function wp_loaded()
		{
			if (is_admin() && !class_exists('WPSiteSyncContent', FALSE) && current_user_can('activate_plugins')) {
				add_action('admin_notices', array($this, 'notice_requires_wpss'));
			}
		}

		/**
		 * Displays the warning message stating the WPSiteSync is not present.
		 */
		public function notice_requires_wpss()
		{
			$install = admin_url('plugin-install.php?tab=search&s=wpsitesync');
			$activate = admin_url('plugins.php');
			$msg = sprintf(__('The <em>WPSiteSync for Bulk Actions</em> plugin requires the main <em>WPSiteSync for Content</em> plugin to be installed and activated. Please %1$sclick here</a> to install or %2$sclick here</a> to activate.', 'wpsitesync-bulkactions'),
						'<a href="' . $install . '">',
						'<a href="' . $activate . '">');
			$this->_show_notice($msg, 'notice-warning');
		}

		/**
		 * Display admin notice to upgrade WPSiteSync for Content plugin
		 */
		public function notice_minimum_version()
		{
			$this->_show_notice(
				sprintf(__('WPSiteSync for Bulk Actions requires version %1$s or greater of <em>WPSiteSync for Content</em> to be installed. Please <a href="2%s">click here</a> to update.', 'wpsitesync-bulkactions'),
					self::REQUIRED_VERSION,
					admin_url('plugins.php')),
				'notice-warning');
		}

		/**
		 * Helper method to display notices
		 * @param string $msg Message to display within notice
		 * @param string $class The CSS class used on the <div> wrapping the notice
		 * @param boolean $dismissable TRUE if message is to be dismissable; otherwise FALSE.
		 */
		private function _show_notice($msg, $class = 'notice-success', $dismissable = FALSE)
		{
			echo '<div class="notice ', $class, ' ', ($dismissable ? 'is-dismissible' : ''), '">';
			echo '<p>', $msg, '</p>';
			echo '</div>';
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
//SyncDebug::log(__METHOD__.'() checking license');
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
