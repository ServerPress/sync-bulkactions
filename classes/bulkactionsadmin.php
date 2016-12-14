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
		add_action('admin_print_scripts-edit.php', array(&$this, 'print_hidden_div'));
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
		wp_register_style('sync-bulkactions', WPSiteSync_BulkActions::get_asset('css/sync-bulkactions.css'), array('sync-admin'), WPSiteSync_BulkActions::PLUGIN_VERSION);

		if ('edit.php' === $hook_suffix) {
			$screen = get_current_screen();

			if (in_array($screen->post_type, $this->_post_types)) {
				$translation_array = array(
					'actions' => array(array(
						'action_name' => 'bulk_push',
						'action_text' => __('WPSiteSync: Push to Target', 'wpsitesync-bulkactions'),
					),
				));

		 		if (class_exists('WPSiteSync_Pull', FALSE) &&
					WPSiteSyncContent::get_instance()->get_license()
						->check_license('sync_pull', WPSiteSync_Pull::PLUGIN_KEY, WPSiteSync_Pull::PLUGIN_NAME)) {
		 			$translation_array['actions'][] = array(
		 				'action_name' => 'bulk_pull',
		 				'action_text' => __('WPSiteSync: Pull from Target', 'wpsitesync-bulkactions'),
					);
				}
SyncDebug::log(__METHOD__.'() translations=' . var_export($translation_array, TRUE));
				wp_localize_script('sync-bulkactions', 'syncbulkactions', $translation_array);
				wp_enqueue_script('sync-bulkactions');
				wp_enqueue_style('sync-bulkactions');
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
		$license = WPSiteSyncContent::get_instance()->get_license();
		if (!$license->check_license('sync_bulkactions', WPSiteSync_BulkActions::PLUGIN_KEY, WPSiteSync_BulkActions::PLUGIN_NAME))
			return;

		global $typenow;

		$this->_post_types = apply_filters('spectrom_sync_allowed_post_types', array('post', 'page'));

		if (in_array($typenow, $this->_post_types)) {
			$wp_list_table = _get_list_table('WP_Posts_List_Table');
			$action = $wp_list_table->current_action();
SyncDebug::log(__METHOD__ . '() list table action=' . var_export($action, TRUE));

			$allowed_actions = array('bulk_push');
			if (class_exists('WPSiteSync_Pull') &&
				WPSiteSyncContent::get_instance()->get_license()
					->check_license('sync_pull', WPSiteSync_Pull::PLUGIN_KEY, WPSiteSync_Pull::PLUGIN_NAME)) {
				$allowed_actions[] = 'bulk_pull';
			}
			if (!in_array($action, $allowed_actions))
				return;

			// security check
			check_admin_referer('bulk-posts');

			// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
			if (isset($_REQUEST['post'])) {
				$post_ids = array_map('abs', $_REQUEST['post']);
			}

			if (empty($post_ids))
				return;

			// this is based on wp-admin/edit.php
			$sendback = remove_query_arg(array('sync_type', 'error_ids', 'error_messages', 'untrashed', 'deleted', 'ids'), wp_get_referer());
			if (!$sendback)
				$sendback = admin_url("edit.php?post_type={$typenow}");

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
SyncDebug::log(__METHOD__.'() post id=' . $post_id);
					$api = new SyncApiRequest();
					$response = new SyncApiResponse();
					// TODO: move instantiation outside the loop
					$model = new SyncModel();

					$sync_data = $model->get_sync_target_post($post_id, SyncOptions::get('target_site_key'));
					if (NULL === $sync_data) {
SyncDebug::log(__METHOD__.'() target post not found');
						// could not find Target post ID. Return error message
						WPSiteSync_Pull::get_instance()->load_class('pullapirequest');
						$response->error_code(SyncPullApiRequest::ERROR_TARGET_POST_NOT_FOUND);
						$error_ids[] = $post_id;
						$error_messages[] = get_the_title($post_id) . ': ' . $api->error_code_to_string(SyncPullApiRequest::ERROR_TARGET_POST_NOT_FOUND); //$code)response->error_message;
//						return TRUE;        // return, signaling that we've handled the request
					} else {
						// fount Target post ID. Continue with Pull request
						$target_post_id = abs($sync_data->target_content_id);
						// set the $_REQUEST['post_id'] so that WPSiteSync_Pull->api_response() can use this
						// (This is set in normal 'pull' operations because the AJAX call sets ['post_id']. Here, we have to mimic that behavior.)
						$_REQUEST['post_id'] = $post_id;
						$api_response = $api->api('pullcontent', array('post_id' => $post_id, 'target_id' => $target_post_id));
						$response->copy($api_response);

						if ($api_response->is_success()) {
							$response->success(TRUE);
						} else {
							$response->copy($api_response);
							$response->success(FALSE);		// TODO: not needed- error_code() sets this to FALSE
							$response->error_code(SyncBulkActionsApiRequest::ERROR_BULK_ACTIONS);
							$error_ids[] = $post_id;
							$error_messages[] = get_the_title($post_id) . ': ' . $api_response->response->error_message;
						}
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

			default:
				return FALSE;				// return, signalling that the requested action was not recognized
			}

			$sendback = remove_query_arg(array('action', 'action2', 'tags_input', 'post_author',
				'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view'), $sendback);

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

		if ('edit.php' === $pagenow && in_array($post_type, $this->_post_types) && isset($_REQUEST['sync_type'])) {
			if (!empty($_REQUEST['error_ids'])) {
				$message = __('Error processing Sync operations. The following items were not successful:', 'wpsitesync-bulkactions');
				echo '<div class="notice notice-error is-dismissible wpsitesync-bulk-errors" data-error-ids="', $_REQUEST['error_ids'], '">';
				echo '<img id="sync-logo" src="', WPSiteSyncContent::get_asset('imgs/wpsitesync-logo-blue.png'), '" width="125" height="45" alt="WPSiteSync logo" title="WPSiteSync for Content" />';
				echo '<p>', $message, '</p>';
				echo '<p>', $_REQUEST['error_messages'], '</p></div>';
			} else {
				if ('pull' === $_REQUEST['sync_type']) {
					$message = __('All Content was successfully Pulled from the Target system.', 'wpsitesync-bulkactions');
				} else {
					$message = __('All Content was successfully Pushed to the Target system.', 'wpsitesync-bulkactions');
				}
				echo '<div class="notice notice-success is-dismissible">';
				echo '<img id="sync-logo" src="', WPSiteSyncContent::get_asset('imgs/wpsitesync-logo-blue.png'), '" width="125" height="45" alt="WPSiteSync logo" title="WPSiteSync for Content" />';
				echo '<p>', $message, '</p></div>';
			}
		}
	}

	/**
	 * Prints hidden bulkactions ui div
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_hidden_div()
	{
		?>
		<div id="sync-bulkactions-ui" style="display:none">
			<div id="spectrom_sync" class="sync-bulkactions-contents">
				<button class="sync-bulkactions-push button button-primary sync-button" type="button" title="<?php esc_html_e('Push Content to the Target site', 'wpsitesync-bulkactions'); ?>">
					<span class="sync-button-icon dashicons dashicons-migrate"></span>
					<?php esc_html_e('Push to Target', 'wpsitesync-bulkactions'); ?>
				</button>
				<button class="sync-bulkactions-pull button sync-button
				<?php
				if (class_exists('WPSiteSync_Pull', FALSE) && WPSiteSyncContent::get_instance()->get_license()->check_license('sync_pull', WPSiteSync_Pull::PLUGIN_KEY, WPSiteSync_Pull::PLUGIN_NAME)) {
					echo 'button-primary" onclick="wpsitesynccontent.bulkactions.pull(true); return false;"';
				} else {
					echo 'button-secondary button-disabled" onclick="wpsitesynccontent.bulkactions.pull(false); return false;"';
				}
				?> type="button" title="<?php esc_html_e('Pull Content from the Target site', 'wpsitesync-bulkactions'); ?>">
					<span class="sync-button-icon sync-button-icon-rotate dashicons dashicons-migrate"></span>
					<?php esc_html_e('Pull from Target', 'wpsitesync-bulkactions'); ?>
				</button>
				<div class="sync-bulkactions-msgs" style="display:none">
					<div id="sync-pull-msg">
						<div style="color: #0085ba;"><?php
							echo sprintf(esc_html('Please activate the %1$sPull Extension%2$s%3$sfor bi-directional content sync.', 'wpsitesync-bulkactions'),
								'<a href="https://wpsitesync.com/downloads/wpsitesync-for-pull/" target="_blank">',
								'</a>', '<br/>');
						?></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

// EOF
