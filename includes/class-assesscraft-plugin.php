<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		load_plugin_textdomain( 'assesscraft', false, dirname( plugin_basename( ASSESSCRAFT_FILE ) ) . '/languages' );
		( new AssessCraft_Migrations() )->register();
		( new AssessCraft_Post_Type() )->register();
		( new AssessCraft_Entitlements() )->register();
		( new AssessCraft_Admin() )->register();
		( new AssessCraft_Templates_Admin() )->register();
		( new AssessCraft_Onboarding() )->register();
		( new AssessCraft_System_Status() )->register();
		( new AssessCraft_Shortcode() )->register();
		( new AssessCraft_Lead_Endpoint() )->register();
		( new AssessCraft_Lead_Store() )->register();
		( new AssessCraft_Privacy() )->register();
		( new AssessCraft_Block() )->register();
		( new AssessCraft_Elementor() )->register();

		do_action( 'assesscraft_loaded', $this );
	}

	public static function activate(): void {
		( new AssessCraft_Post_Type() )->register_post_type();
		( new AssessCraft_Lead_Store() )->maybe_install();
		AssessCraft_Onboarding::queue_redirect();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'assesscraft_cleanup_leads' );
		flush_rewrite_rules();
	}
}
