<?php

defined( 'ABSPATH' ) || exit;

final class AssessCraft_Pro_Dependencies {
	public const MINIMUM_CORE_VERSION = '0.18.2';

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'network_admin_notices', array( $this, 'notice' ) );
	}

	public static function satisfied(): bool {
		return defined( 'ASSESSCRAFT_VERSION' )
			&& class_exists( 'AssessCraft_Features' )
			&& version_compare( ASSESSCRAFT_VERSION, self::MINIMUM_CORE_VERSION, '>=' );
	}

	public static function state(): string {
		if ( ! defined( 'ASSESSCRAFT_VERSION' ) || ! class_exists( 'AssessCraft_Features' ) ) {
			return 'missing';
		}

		if ( version_compare( ASSESSCRAFT_VERSION, self::MINIMUM_CORE_VERSION, '<' ) ) {
			return 'outdated';
		}

		return 'ready';
	}

	public function notice(): void {
		if ( self::satisfied() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$state = self::state();
		if ( 'outdated' === $state ) {
			$message = sprintf(
				/* translators: 1: installed version, 2: required version. */
				esc_html__( 'AssessCraft Pro is inactive because AssessCraft Free %1$s is installed. Update AssessCraft Free to version %2$s or newer.', 'assesscraft-pro' ),
				esc_html( ASSESSCRAFT_VERSION ),
				esc_html( self::MINIMUM_CORE_VERSION )
			);
		} else {
			$message = sprintf(
				/* translators: %s: required AssessCraft Free version. */
				esc_html__( 'AssessCraft Pro requires AssessCraft Free version %s or newer. Install and activate AssessCraft Free first.', 'assesscraft-pro' ),
				esc_html( self::MINIMUM_CORE_VERSION )
			);
		}
		?>
		<div class="notice notice-error">
			<p><strong><?php esc_html_e( 'AssessCraft Pro dependency required', 'assesscraft-pro' ); ?></strong></p>
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}
}
