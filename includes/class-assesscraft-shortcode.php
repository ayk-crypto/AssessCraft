<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Shortcode {
	public function register(): void {
		add_shortcode( 'assesscraft', array( $this, 'render' ) );
	}

	public function render( array $atts = array() ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'assesscraft' );
		$id   = absint( $atts['id'] );

		if ( ! $id || AssessCraft_Post_Type::TYPE !== get_post_type( $id ) ) {
			return current_user_can( 'edit_posts' )
				? '<p class="assesscraft-error">' . esc_html__( 'AssessCraft: select a valid assessment.', 'assesscraft' ) . '</p>'
				: '';
		}

		$config = get_post_meta( $id, '_assesscraft_config', true );
		$config = AssessCraft_Schema::sanitize( is_array( $config ) ? $config : array() );

		wp_enqueue_style( 'assesscraft-frontend', ASSESSCRAFT_URL . 'public/assets/frontend.css', array(), ASSESSCRAFT_VERSION );
		wp_enqueue_script( 'assesscraft-frontend', ASSESSCRAFT_URL . 'public/assets/frontend.js', array(), ASSESSCRAFT_VERSION, true );

		$payload = wp_json_encode( array( 'id' => $id, 'title' => get_the_title( $id ), 'config' => $config ) );

		return sprintf(
			'<div class="assesscraft-app" data-assessment="%s"><noscript>%s</noscript></div>',
			esc_attr( $payload ),
			esc_html__( 'JavaScript is required to complete this assessment.', 'assesscraft' )
		);
	}
}

