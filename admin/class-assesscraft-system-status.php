<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_System_Status {
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_assesscraft_clear_log', array( $this, 'clear_log' ) );
	}

	public function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . AssessCraft_Post_Type::TYPE,
			__( 'System Status', 'assesscraft' ),
			__( 'System Status', 'assesscraft' ),
			'manage_options',
			'assesscraft-system-status',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view system status.', 'assesscraft' ) );
		}
		$environment = array(
			__( 'AssessCraft version', 'assesscraft' ) => ASSESSCRAFT_VERSION,
			__( 'Assessment schema', 'assesscraft' ) => (string) AssessCraft_Schema::VERSION,
			__( 'Migration version', 'assesscraft' ) => (string) get_option( 'assesscraft_migration_version', 0 ),
			__( 'Lead database version', 'assesscraft' ) => (string) get_option( 'assesscraft_leads_db_version', 0 ),
			__( 'WordPress version', 'assesscraft' ) => get_bloginfo( 'version' ),
			__( 'PHP version', 'assesscraft' ) => PHP_VERSION,
			__( 'Site language', 'assesscraft' ) => get_locale(),
			__( 'Multisite', 'assesscraft' ) => is_multisite() ? __( 'Yes', 'assesscraft' ) : __( 'No', 'assesscraft' ),
		);
		$entries = array_reverse( AssessCraft_Logger::entries() );
		$clear_url = wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_clear_log' ), 'assesscraft_clear_log' );
		?>
		<div class="wrap"><h1><?php esc_html_e( 'AssessCraft System Status', 'assesscraft' ); ?></h1><p><?php esc_html_e( 'Use this information when troubleshooting. The log intentionally excludes respondent contact details, answers, license keys, and secrets.', 'assesscraft' ); ?></p>
		<table class="widefat striped"><tbody><?php foreach ( $environment as $label => $value ) : ?><tr><th><?php echo esc_html( $label ); ?></th><td><code><?php echo esc_html( $value ); ?></code></td></tr><?php endforeach; ?></tbody></table>
		<h2><?php esc_html_e( 'Recent AssessCraft events', 'assesscraft' ); ?></h2>
		<?php if ( ! $entries ) : ?><p><?php esc_html_e( 'No errors or warnings have been recorded.', 'assesscraft' ); ?></p><?php else : ?><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Time', 'assesscraft' ); ?></th><th><?php esc_html_e( 'Level', 'assesscraft' ); ?></th><th><?php esc_html_e( 'Code', 'assesscraft' ); ?></th><th><?php esc_html_e( 'Message', 'assesscraft' ); ?></th><th><?php esc_html_e( 'Context', 'assesscraft' ); ?></th></tr></thead><tbody><?php foreach ( $entries as $entry ) : ?><tr><td><?php echo esc_html( $entry['occurred_at'] ?? '' ); ?></td><td><?php echo esc_html( $entry['level'] ?? '' ); ?></td><td><code><?php echo esc_html( $entry['code'] ?? '' ); ?></code></td><td><?php echo esc_html( $entry['message'] ?? '' ); ?></td><td><code><?php echo esc_html( wp_json_encode( $entry['context'] ?? array() ) ); ?></code></td></tr><?php endforeach; ?></tbody></table><p><a class="button" href="<?php echo esc_url( $clear_url ); ?>"><?php esc_html_e( 'Clear event log', 'assesscraft' ); ?></a></p><?php endif; ?></div>
		<?php
	}

	public function clear_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear the event log.', 'assesscraft' ) );
		}
		check_admin_referer( 'assesscraft_clear_log' );
		delete_option( 'assesscraft_error_log' );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-system-status' ) );
		exit;
	}
}
