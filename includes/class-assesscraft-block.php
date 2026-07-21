<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Block {
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	public function register_block(): void {
		register_block_type(
			ASSESSCRAFT_DIR . 'blocks/assessment',
			array( 'render_callback' => array( $this, 'render' ) )
		);
	}

	public function render( array $attributes ): string {
		$assessment_id = absint( $attributes['assessmentId'] ?? 0 );
		return $assessment_id ? do_shortcode( '[assesscraft id="' . $assessment_id . '"]' ) : '';
	}
}

