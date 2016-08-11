<?php

/*
 * Allows bulk actions between the Source and Target sites
 * @package Sync
 * @author WPSiteSync
 */

class SyncBulkActionsAdmin
{
	private static $_instance = NULL;
	private $_post_types;

	private function __construct()
	{
		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
		add_action('load-edit.php', array(&$this, 'process_bulk_actions'));
		add_action('admin_notices', array(&$this, 'admin_notices'));
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

			if (in_array($screen->post_type, $this->_post_types)) {
				$translation_array = array(
					'actions' => array(array(
						'action_name' => 'bulk_push',
						'action_text' => __('Push to Target', 'wpsitesync-bulkactions'),
					),
				));

		 		if (class_exists('WPSiteSync_Pull') && WPSiteSync_Menus::get_instance()->get_license()->check_license('sync_pull', WPSiteSync_Pull::PLUGIN_KEY, WPSiteSync_Pull::PLUGIN_NAME)) {
		 			$translation_array['actions'][] = array(
		 				'action_name' => 'bulk_pull',
		 				'action_text' => __('Pull from Target', 'wpsitesync-bulkactions'),
					);
				}

				wp_localize_script('sync-bulkactions', 'syncbulkactions', $translation_array);
				wp_enqueue_script('sync-bulkactions');
			}
		}
	}

	/**
	 * Process Bulk Actions
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function process_bulk_actions()
	{
		global $typenow;

		$this->_post_types = apply_filters('spectrom_sync_allowed_post_types', array('post', 'page'));

		$license = new SyncLicensing();
		if (!$license->check_license('sync_bulkactions', WPSiteSync_Menus::PLUGIN_KEY, WPSiteSync_Menus::PLUGIN_NAME))
			return ;

		if (in_array($typenow, $this->_post_types)) {
			$wp_list_table = _get_list_table('WP_Posts_List_Table');
			$action = $wp_list_table->current_action();
SyncDebug::log(__METHOD__ . '() list table action=' . var_export($action, TRUE));

			$allowed_actions = array('bulk_push');
			if (class_exists('WPSiteSync_Pull') && WPSiteSync_Menus::get_instance()->get_license()->check_license('sync_pull', WPSiteSync_Pull::PLUGIN_KEY, WPSiteSync_Pull::PLUGIN_NAME)) {
				$allowed_actions[] = 'bulk_pull';
			}
			if (!in_array($action, $allowed_actions)) return;

			// security check
			check_admin_referer('bulk-posts');

			// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
			if (isset($_REQUEST['post'])) {
				$post_ids = array_map('intval', $_REQUEST['post']);
			}

			if (empty($post_ids)) return;

			// this is based on wp-admin/edit.php
			$sendback = remove_query_arg(array('sync_type', 'error_ids', 'error_messages', 'untrashed', 'deleted', 'ids'), wp_get_referer());
			if (!$sendback)
				$sendback = admin_url("edit.php?post_type=$typenow");

			$pagenum = $wp_list_table->get_pagenum();
			$sendback = add_query_arg('paged', $pagenum, $sendback);

			switch ($action) {
			case 'bulk_push':
				$error_ids = array();
				$error_messages = array();
				foreach ($post_ids as $post_id) {

					$api = new SyncApiRequest();
					$response = new SyncApiResponse();
					$api_response = $api->api('push', array('post_id' => $post_id));
					$response->copy($api_response);
					if ($api_response->is_success()) {
						$response->success(TRUE);
					} else {
						$response->copy($api_response);
						$response->error_code(SyncBulkActionsApiRequest::ERROR_BULK_ACTIONS);
						$error_ids[] = $post_id;
						$error_messages[] = get_the_title($post_id). ': '. $api_response->response->error_message;
					}
SyncDebug::log(__METHOD__ . '() response=' . var_export($response, TRUE));
				}

				$sendback = add_query_arg(array(
					'sync_type' => 'push',
					'error_ids' => implode(',', $error_ids),
					'error_messages' => implode('<br>', $error_messages),
					'ids' => implode(',', $post_ids)
				), $sendback);
				break;
			case 'bulk_pull':
				$error_ids = array();
				$error_messages = array();
				foreach ($post_ids as $post_id) {

					$api = new SyncApiRequest();
					$response = new SyncApiResponse();
					$model = new SyncModel();

					$sync_data = $model->get_sync_target_post($post_id, SyncOptions::get('target_site_key'));
					if (NULL === $sync_data) {
						// could not find Target post ID. Return error message
						WPSiteSync_Pull::get_instance()->load_class('pullapirequest');
						$response->error_code(SyncPullApiRequest::ERROR_TARGET_POST_NOT_FOUND);
						return TRUE;        // return, signaling that we've handled the request
					}
					$target_post_id = abs($sync_data->target_content_id);
					$api_response = $api->api('pullcontent', array('post_id' => $post_id, 'target_id' => $target_post_id));
					$response->copy($api_response);

					if ($api_response->is_success()) {
						$response->success(TRUE);
					} else {
						$response->copy($api_response);
						$response->success(FALSE);
						$response->error_code(SyncBulkActionsApiRequest::ERROR_BULK_ACTIONS);
						$error_ids[] = $post_id;
						$error_messages[] = get_the_title($post_id) . ': ' . $api_response->response->error_message;
					}
SyncDebug::log(__METHOD__ . '() response=' . var_export($response, TRUE));
				}

				$sendback = add_query_arg(array(
					'sync_type' => 'pull',
					'error_ids' => implode(',', $error_ids),
					'error_messages' => implode('<br>', $error_messages),
					'ids' => implode(',', $post_ids)
				), $sendback);
				break;
			default: return;
			}

			$sendback = remove_query_arg(array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view'), $sendback);

			wp_redirect($sendback);
			exit();
		}
	}

	/**
	 * Admin Notices
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_notices()
	{
		global $post_type, $pagenow;

		if ($pagenow == 'edit.php' && in_array($post_type, $this->_post_types) && isset($_REQUEST['sync_type'])) {

			if (!empty($_REQUEST['error_ids'])) {
				$message = __('Error processing Sync operations.', 'wpsitesync-bulkactions');
				echo '<div class="notice notice-error is-dismissible wpsitesync-bulk-errors" data-error-ids="', $_REQUEST['error_ids'], '"><p>', $message, '</p><p>', $_REQUEST['error_messages'], '</p></div>';
			} else {
				if ('pull' === $_REQUEST['sync_type']) {
					$message = __('All Content was successfully Pulled from the Target system.', 'wpsitesync-bulkactions');
				} else {
					$message = __('All Content was successfully Pushed to the Target system.', 'wpsitesync-bulkactions');
				}
				echo '<div class="notice notice-success is-dismissible"><p>', $message, '</p></div>';
			}
		}
	}
}

// EOF
