<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		( new AssessCraft_Post_Type() )->register();
		( new AssessCraft_Admin() )->register();
		( new AssessCraft_Templates_Admin() )->register();
		( new AssessCraft_Shortcode() )->register();
		( new AssessCraft_Lead_Endpoint() )->register();

		do_action( 'assesscraft_loaded', $this );
	}

	public static function activate(): void {
		( new AssessCraft_Post_Type() )->register_post_type();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
