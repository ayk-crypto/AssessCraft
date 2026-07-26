<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Logger {
	private const OPTION = 'assesscraft_error_log';
	private const LIMIT  = 50;

	public static function error( string $code, string $message, array $context = array() ): void {
		self::write( 'error', $code, $message, $context );
	}

	public static function warning( string $code, string $message, array $context = array() ): void {
		self::write( 'warning', $code, $message, $context );
	}

	public static function entries(): array {
		$entries = get_option( self::OPTION, array() );
		return is_array( $entries ) ? $entries : array();
	}

	private static function write( string $level, string $code, string $message, array $context ): void {
		$entry = array(
			'level'      => $level,
			'code'       => sanitize_key( $code ),
			'message'    => sanitize_text_field( $message ),
			'context'    => self::sanitize_context( $context ),
			'occurred_at'=> gmdate( 'c' ),
			'version'    => defined( 'ASSESSCRAFT_VERSION' ) ? ASSESSCRAFT_VERSION : '',
		);
		$entries = self::entries();
		$entries[] = $entry;
		update_option( self::OPTION, array_slice( $entries, -self::LIMIT ), false );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[AssessCraft][' . $entry['code'] . '] ' . $entry['message'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		do_action( 'assesscraft_logged_event', $entry );
	}

	private static function sanitize_context( array $context ): array {
		$allowed = array( 'assessment_id', 'migration', 'schema_version', 'db_version', 'exception', 'operation' );
		$clean = array();
		foreach ( $allowed as $key ) {
			if ( isset( $context[ $key ] ) ) {
				$clean[ $key ] = sanitize_text_field( (string) $context[ $key ] );
			}
		}
		return $clean;
	}
}
