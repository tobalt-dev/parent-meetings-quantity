<?php
/**
 * Logger
 *
 * Provides centralized logging functionality
 *
 * @package ParentMeetings
 * @since 2.4.1
 * Author: Tobalt — https://tobalt.lt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PM_Logger {

	/**
	 * Log levels
	 */
	const LEVEL_DEBUG   = 'debug';
	const LEVEL_INFO    = 'info';
	const LEVEL_WARNING = 'warning';
	const LEVEL_ERROR   = 'error';

	/**
	 * Log a message
	 *
	 * @param string $message Log message
	 * @param string $level Log level (debug, info, warning, error)
	 * @param array  $context Additional context data
	 */
	public static function log( $message, $level = self::LEVEL_INFO, $context = array() ) {
		// Only log if WP_DEBUG is enabled for debug level
		if ( self::LEVEL_DEBUG === $level && ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ) {
			return;
		}

		$log_entry = self::format_message( $message, $level, $context );

		// Log to WordPress debug.log
		error_log( $log_entry );

		// Store in database for admin viewing (errors and warnings only)
		if ( in_array( $level, array( self::LEVEL_ERROR, self::LEVEL_WARNING ), true ) ) {
			self::store_log( $message, $level, $context );
		}
	}

	/**
	 * Log debug message
	 *
	 * @param string $message Message
	 * @param array  $context Context
	 */
	public static function debug( $message, $context = array() ) {
		self::log( $message, self::LEVEL_DEBUG, $context );
	}

	/**
	 * Log info message
	 *
	 * @param string $message Message
	 * @param array  $context Context
	 */
	public static function info( $message, $context = array() ) {
		self::log( $message, self::LEVEL_INFO, $context );
	}

	/**
	 * Log warning message
	 *
	 * @param string $message Message
	 * @param array  $context Context
	 */
	public static function warning( $message, $context = array() ) {
		self::log( $message, self::LEVEL_WARNING, $context );
	}

	/**
	 * Log error message
	 *
	 * @param string $message Message
	 * @param array  $context Context
	 */
	public static function error( $message, $context = array() ) {
		self::log( $message, self::LEVEL_ERROR, $context );
	}

	/**
	 * Log exception
	 *
	 * @param Exception $exception Exception object
	 * @param array     $context Additional context
	 */
	public static function exception( $exception, $context = array() ) {
		$context['exception_class'] = get_class( $exception );
		$context['file']            = $exception->getFile();
		$context['line']            = $exception->getLine();
		$context['trace']           = $exception->getTraceAsString();

		self::error( $exception->getMessage(), $context );
	}

	/**
	 * Format log message
	 *
	 * @param string $message Message
	 * @param string $level Level
	 * @param array  $context Context
	 * @return string Formatted message
	 */
	private static function format_message( $message, $level, $context ) {
		$timestamp = current_time( 'Y-m-d H:i:s' );
		$level_upper = strtoupper( $level );

		$formatted = sprintf(
			'[Parent Meetings] [%s] [%s] %s',
			$timestamp,
			$level_upper,
			$message
		);

		if ( ! empty( $context ) ) {
			$formatted .= ' | Context: ' . wp_json_encode( $context );
		}

		return $formatted;
	}

	/**
	 * Store log in database
	 *
	 * @param string $message Message
	 * @param string $level Level
	 * @param array  $context Context
	 */
	private static function store_log( $message, $level, $context ) {
		$logs = get_option( 'pm_error_logs', array() );

		// Keep only last 100 entries
		if ( count( $logs ) >= 100 ) {
			array_shift( $logs );
		}

		$logs[] = array(
			'timestamp' => current_time( 'mysql' ),
			'level'     => $level,
			'message'   => $message,
			'context'   => $context,
		);

		update_option( 'pm_error_logs', $logs, false );
	}

	/**
	 * Get stored logs
	 *
	 * @param int    $limit Number of logs to return
	 * @param string $level Filter by level (optional)
	 * @return array Logs
	 */
	public static function get_logs( $limit = 50, $level = null ) {
		$logs = get_option( 'pm_error_logs', array() );

		if ( $level ) {
			$logs = array_filter( $logs, function( $log ) use ( $level ) {
				return $log['level'] === $level;
			} );
		}

		// Return most recent first
		$logs = array_reverse( $logs );

		return array_slice( $logs, 0, $limit );
	}

	/**
	 * Clear stored logs
	 */
	public static function clear_logs() {
		delete_option( 'pm_error_logs' );
	}
}
