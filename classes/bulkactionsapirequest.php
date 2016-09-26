<?php

class SyncBulkActionsApiRequest
{
	private static $_instance = NULL;

	const ERROR_BULK_ACTIONS = 400;
	//const NOTICE_BULK_ACTIONS = 403;

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
	 */
	public function filter_error_codes($message, $code)
	{
		switch ($code) {
		case self::ERROR_BULK_ACTIONS:
			$message = __('Error processing Sync operations.', 'wpsitesync-bulkactions');
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
	 */
	public function filter_notice_codes($message, $code)
	{
		switch ($code) {
//		case self::NOTICE_BULK_ACTIONS:
//			$message = __('', 'wpsitesync-bulkactions');
//			break;
		}
		return $message;
	}
}

// EOF
