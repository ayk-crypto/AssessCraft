<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Post_Type {
	public const TYPE = 'ac_assessment';

	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type(): void {
		$labels = array(
			'name'          => __( 'Assessments', 'assesscraft' ),
			'singular_name' => __( 'Assessment', 'assesscraft' ),
			'add_new_item'  => __( 'Create Assessment', 'assesscraft' ),
			'edit_item'     => __( 'Edit Assessment', 'assesscraft' ),
			'menu_name'     => __( 'AssessCraft', 'assesscraft' ),
		);

		register_post_type(
			self::TYPE,
			array(
				'labels'       => $labels,
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-chart-bar',
				'supports'     => array( 'title', 'revisions' ),
				'map_meta_cap' => true,
				'capability_type' => 'post',
			)
		);

		register_post_meta(
			self::TYPE,
			'_assesscraft_config',
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array( 'schema' => array( 'type' => 'object', 'additionalProperties' => true ) ),
				'sanitize_callback' => array( 'AssessCraft_Schema', 'sanitize' ),
				'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
				'default'           => AssessCraft_Schema::defaults(),
			)
		);
	}
}

