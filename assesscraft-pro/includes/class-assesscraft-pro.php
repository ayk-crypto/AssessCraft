<?php

defined( 'ABSPATH' ) || exit;

final class AssessCraft_Pro {
	private static ?self $instance = null;

	private AssessCraft_Pro_Dependencies $dependencies;
	private AssessCraft_Pro_License $license;
	private AssessCraft_Pro_Admin $admin;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function boot(): void {
		$this->dependencies = new AssessCraft_Pro_Dependencies();
		$this->dependencies->register();

		if ( ! AssessCraft_Pro_Dependencies::satisfied() ) {
			return;
		}

		load_plugin_textdomain( 'assesscraft-pro', false, dirname( plugin_basename( ASSESSCRAFT_PRO_FILE ) ) . '/languages' );

		$this->license = new AssessCraft_Pro_License();
		$this->license->register();

		$this->admin = new AssessCraft_Pro_Admin( $this->license );
		$this->admin->register();

		add_filter( 'assesscraft_current_plan', array( $this, 'plan' ), 20 );
		add_filter( 'assesscraft_pro_url', array( $this, 'management_url' ) );
		add_filter( 'assesscraft_plan_management_url', array( $this, 'management_url' ) );

		do_action( 'assesscraft_pro_loaded', $this );
	}

	public function plan( string $plan ): string {
		return AssessCraft_Pro_License::is_active() ? AssessCraft_Features::PLAN_PRO : $plan;
	}

	public function management_url(): string {
		return AssessCraft_Pro_Admin::url();
	}

	public static function activate(): void {
		if ( ! get_option( 'assesscraft_pro_license_status', false ) ) {
			update_option( 'assesscraft_pro_license_status', 'inactive', false );
		}

		if ( ! wp_next_scheduled( AssessCraft_Pro_License::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', AssessCraft_Pro_License::CRON_HOOK );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( AssessCraft_Pro_License::CRON_HOOK );
	}
}
