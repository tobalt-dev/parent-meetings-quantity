<?php
/**
 * Rate Limiter
 *
 * Provides rate limiting functionality for AJAX endpoints
 *
 * @package ParentMeetings
 * @since 2.4.1
 * Author: Tobalt — https://tobalt.lt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PM_Rate_Limiter {

	/**
	 * Check if rate limit is exceeded
	 *
	 * @param string $action Action identifier (e.g., 'book_meeting', 'get_slots')
	 * @param int    $limit Maximum requests allowed in window
	 * @param int    $window Time window in seconds
	 * @param string $identifier Optional custom identifier (defaults to IP)
	 * @return bool True if rate limit exceeded, false otherwise
	 */
	public static function is_rate_limited( $action, $limit = 10, $window = 60, $identifier = null ) {
		if ( null === $identifier ) {
			$identifier = self::get_client_identifier();
		}

		$transient_key = 'pm_rl_' . md5( $action . '_' . $identifier );
		$current_count = get_transient( $transient_key );

		if ( false === $current_count ) {
			// First request in window
			set_transient( $transient_key, 1, $window );
			return false;
		}

		if ( $current_count >= $limit ) {
			// Rate limit exceeded
			self::log_rate_limit_exceeded( $action, $identifier, $current_count );
			return true;
		}

		// Increment counter
		set_transient( $transient_key, $current_count + 1, $window );
		return false;
	}

	/**
	 * Check rate limit and send error if exceeded
	 *
	 * @param string $action Action identifier
	 * @param int    $limit Maximum requests
	 * @param int    $window Time window in seconds
	 * @return void Dies with JSON error if rate limited
	 */
	public static function check_rate_limit( $action, $limit = 10, $window = 60 ) {
		if ( self::is_rate_limited( $action, $limit, $window ) ) {
			wp_send_json_error(
				__( 'Per daug užklausų. Palaukite ir bandykite vėliau.', 'parent-meetings' ),
				429 // Too Many Requests
			);
		}
	}

	/**
	 * Get client identifier (IP address)
	 *
	 * @return string Client IP or fallback identifier
	 */
	private static function get_client_identifier() {
		// Only use REMOTE_ADDR for security - other headers can be spoofed
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( empty( $ip ) ) {
			// Fallback to session-based identifier if IP not available
			if ( ! session_id() ) {
				return 'anonymous_' . wp_generate_password( 8, false );
			}
			return 'session_' . session_id();
		}

		return $ip;
	}

	/**
	 * Log rate limit exceeded events
	 *
	 * @param string $action Action that was rate limited
	 * @param string $identifier Client identifier
	 * @param int    $count Request count
	 */
	private static function log_rate_limit_exceeded( $action, $identifier, $count ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Parent Meetings] Rate limit exceeded: action=%s, identifier=%s, count=%d',
					$action,
					$identifier,
					$count
				)
			);
		}
	}

	/**
	 * Clear rate limit for an action/identifier
	 *
	 * @param string $action Action identifier
	 * @param string $identifier Optional client identifier
	 */
	public static function clear_rate_limit( $action, $identifier = null ) {
		if ( null === $identifier ) {
			$identifier = self::get_client_identifier();
		}

		$transient_key = 'pm_rl_' . md5( $action . '_' . $identifier );
		delete_transient( $transient_key );
	}
}
