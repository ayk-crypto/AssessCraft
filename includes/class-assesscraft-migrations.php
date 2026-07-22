<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Migrations {
	private const VERSION = 1;
	private const OPTION = 'assesscraft_migration_version';
	private const LOG_OPTION = 'assesscraft_migration_log';

	public function register(): void {
		add_action( 'init', array( $this, 'run' ), 20 );
	}

	public function run(): void {
		$current = absint( get_option( self::OPTION, 0 ) );
		if ( $current >= self::VERSION ) {
			return;
		}

		for ( $version = $current + 1; $version <= self::VERSION; $version++ ) {
			$method = 'migrate_to_' . $version;
			if ( ! method_exists( $this, $method ) ) {
				break;
			}
			$this->{$method}();
			update_option( self::OPTION, $version, false );
			$this->log( $version );
		}
	}

	private function migrate_to_1(): void {
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
				update_post_meta( $assessment_id, '_assesscraft_config', AssessCraft_Schema::migrate( $config ) );
			}
		}
	}

	private function log( int $version ): void {
		$log = get_option( self::LOG_OPTION, array() );
		$log = is_array( $log ) ? $log : array();
		$log[] = array( 'version' => $version, 'completed_at' => current_time( 'mysql', true ) );
		update_option( self::LOG_OPTION, array_slice( $log, -20 ), false );
	}
}
