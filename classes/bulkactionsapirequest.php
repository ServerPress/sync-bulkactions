<?php

class SyncBulkActionsApiRequest
{
	private static $_instance = NULL;

	// @todo change to needed errors/notices
	const ERROR_TARGET_MENU_NOT_FOUND = 400;
	const ERROR_TARGET_MENU_ITEMS_NOT_FOUND = 401;
	const ERROR_MENU_ITEM_NOT_ADDED = 402;
	const NOTICE_MENU_ITEM_NOT_MODIFIED = 403;
	const NOTICE_MENU_MODIFIED = 400;

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
	 * Filters the errors list, adding Bulk Actions specific code-to-string values
	 *
	 * @param string $message The error string message to be returned
	 * @param int $code The error code being evaluated
	 * @return string The modified $message string, with Bulk Actions specific errors added to it
	 * @todo change errors and messages
	 */
	public function filter_error_codes($message, $code)
	{
		switch ($code) {
			case self::ERROR_TARGET_MENU_NOT_FOUND:
				$message = __('Menu cannot be found on Target site', 'wpsitesync-bulkactions');
				break;
			case self::ERROR_TARGET_MENU_ITEMS_NOT_FOUND:
				$message = __('Some of the Content in the menu is missing on the Target. Please push these Pages to the Target before Syncing this menu.', 'wpsitesync-bulkactions');
				break;
			case self::ERROR_MENU_ITEM_NOT_ADDED:
				$message = __('Menu item was not able to be added.', 'wpsitesync-bulkactions');
				break;
			case self::ERROR_MENU_ITEM_NOT_MODIFIED:
				$message = __('Menu item was unable to be updated.', 'wpsitesync-bulkactions');
				break;
		}
		return $message;
	}

	/**
	 * Filters the notices list, adding Bulk Actions specific code-to-string values
	 *
	 * @param string $message The notice string message to be returned
	 * @param int $code The notice code being evaluated
	 * @return string The modified $message string, with Bulk Actions specific notices added to it
	 * @todo change notices and messages
	 */
	public function filter_notice_codes($message, $code)
	{
		switch ($code) {
			case self::NOTICE_MENU_MODIFIED:
				$message = __('Menu has been modified on Target site since the last Push. Continue?', 'wpsitesync-bulkactions');
				break;
		}
		return $message;
	}
}

// EOF
