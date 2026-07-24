<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Migrations {
	private const VERSION = 2;
	private const OPTION = 'assesscraft_migration_version';
	private const LOG_OPTION = 'assesscraft_migration_log';
	private const LOCK = 'assesscraft_migration_lock';

	public function register(): void {
		add_action( 'init', array( $this, 'run' ), 20 );
	}

	public function run(): void {
		if ( get_transient( self::LOCK ) ) {
			return;
		}
		$current = absint( get_option( self::OPTION, 0 ) );
		if ( $current >= self::VERSION ) {
			return;
		}

		set_transient( self::LOCK, 1, 10 * MINUTE_IN_SECONDS );
		try {
			for ( $version = $current + 1; $version <= self::VERSION; $version++ ) {
				$method = 'migrate_to_' . $version;
				if ( ! method_exists( $this, $method ) ) {
					throw new RuntimeException( 'Missing migration handler for version ' . $version );
				}
				$this->{$method}();
				update_option( self::OPTION, $version, false );
				$this->log( $version, 'completed' );
			}
		} catch ( Throwable $error ) {
			$this->log( $current + 1, 'failed', $error->getMessage() );
			AssessCraft_Logger::error( 'migration_failed', 'AssessCraft stopped an incomplete migration.', array( 'migration' => $current + 1, 'exception' => $error->getMessage() ) );
		} finally {
			delete_transient( self::LOCK );
		}
	}

	private function migrate_to_1(): void {
		$this->migrate_assessments();
	}

	private function migrate_to_2(): void {
		$this->migrate_assessments();
		if ( false === get_option( 'assesscraft_uninstall_behavior', false ) ) {
			add_option( 'assesscraft_uninstall_behavior', 'keep', '', false );
		}
	}

	private function migrate_assessments(): void {
		$assessment_ids = get_posts(
			array(
				'post_type'      => AssessCraft_Post_Type::TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $assessment_ids as $assessment_id ) {
			$config = get_post_meta( $assessment_id, '_assesscraft_config', true );
			if ( is_array( $config ) ) {
				$migrated = AssessCraft_Schema::migrate( $config );
				if ( $migrated !== $config ) {
					update_post_meta( $assessment_id, '_assesscraft_config_backup', array( 'plugin_version' => ASSESSCRAFT_VERSION, 'schema_version' => absint( $config['schema_version'] ?? 0 ), 'backed_up_at' => gmdate( 'c' ), 'config' => $config ) );
					if ( false === update_post_meta( $assessment_id, '_assesscraft_config', $migrated ) ) {
						// This value is used only in an internal exception message, not rendered output.
						// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
						throw new RuntimeException( 'Could not update assessment ' . $assessment_id );
					}
				}
			}
		}
	}

	private function log( int $version, string $status, string $message = '' ): void {
		$log = get_option( self::LOG_OPTION, array() );
		$log = is_array( $log ) ? $log : array();
		$log[] = array( 'version' => $version, 'status' => sanitize_key( $status ), 'message' => sanitize_text_field( $message ), 'completed_at' => current_time( 'mysql', true ) );
		update_option( self::LOG_OPTION, array_slice( $log, -20 ), false );
	}
}
