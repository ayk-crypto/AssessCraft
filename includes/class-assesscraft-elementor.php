<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Elementor {
	public function register(): void {
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	public function register_widget( $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		require_once ASSESSCRAFT_DIR . 'integrations/elementor/class-assesscraft-elementor-widget.php';
		$widgets_manager->register( new AssessCraft_Elementor_Widget() );
	}
}

