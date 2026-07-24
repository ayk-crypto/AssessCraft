<?php
defined( 'ABSPATH' ) || exit;

/**
 * Keeps workflow support without exposing a standalone onboarding screen.
 */
final class AssessCraft_Onboarding {
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'reorder_menu' ), 99 );
		add_action( 'admin_head', array( $this, 'style_menu' ) );
		add_action( 'rest_api_init', array( $this, 'register_completion_route' ) );
		add_action( 'current_screen', array( $this, 'contextual_help' ) );
	}

	public function reorder_menu(): void {
		global $submenu;
		$parent = 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE;
		if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
			return;
		}

		$order = array(
			$parent                                                 => 10,
			'post-new.php?post_type=' . AssessCraft_Post_Type::TYPE => 20,
			'assesscraft-templates'                                 => 30,
			'assesscraft-leads'                                     => 40,
			'assesscraft-system-status'                             => 50,
			AssessCraft_Upgrade::PAGE_SLUG                          => 60,
		);

		usort(
			$submenu[ $parent ],
			static function ( array $left, array $right ) use ( $order ): int {
				return ( $order[ $left[2] ] ?? 45 ) <=> ( $order[ $right[2] ] ?? 45 );
			}
		);
	}

	public function style_menu(): void {
		?>
		<style>
			#adminmenu #menu-posts-ac_assessment > a.menu-top {
				font-weight: 500;
				letter-spacing: 0;
			}
			#adminmenu #menu-posts-ac_assessment > a.menu-top .wp-menu-image::before {
				opacity: .82;
			}
			#adminmenu #menu-posts-ac_assessment .wp-submenu a {
				font-weight: 400;
			}
			#adminmenu a[href*="page=assesscraft-upgrade"] {
				margin: 7px 8px 4px !important;
				padding: 7px 10px !important;
				border-radius: 4px;
				background: linear-gradient(135deg, #d6aa32, #bd8820);
				color: #17120a !important;
				font-weight: 700;
			}
			#adminmenu a[href*="page=assesscraft-upgrade"]:hover,
			#adminmenu a[href*="page=assesscraft-upgrade"].current {
				background: linear-gradient(135deg, #e2bc50, #cc982b);
				color: #111 !important;
			}
		</style>
		<?php
	}

	public function register_completion_route(): void {
		register_rest_route(
			'assesscraft/v1',
			'/completion',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_completion' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function record_completion( WP_REST_Request $request ) {
		$assessment_id = absint( $request->get_param( 'assessment_id' ) );
		if ( ! $assessment_id || AssessCraft_Post_Type::TYPE !== get_post_type( $assessment_id ) || 'publish' !== get_post_status( $assessment_id ) ) {
			return new WP_Error( 'assesscraft_invalid_completion', __( 'This assessment is not available.', 'assesscraft' ), array( 'status' => 404 ) );
		}
		if ( ! get_post_meta( $assessment_id, '_assesscraft_has_completion', true ) ) {
			update_post_meta( $assessment_id, '_assesscraft_has_completion', current_time( 'mysql', true ) );
		}
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function contextual_help( WP_Screen $screen ): void {
		if ( AssessCraft_Post_Type::TYPE !== $screen->post_type ) {
			return;
		}
		$screen->add_help_tab(
			array(
				'id'      => 'assesscraft-workflow',
				'title'   => __( 'Assessment workflow', 'assesscraft' ),
				'content' => '<p>' . esc_html__( 'Work through Overview, Builder, Scoring, Profiles, Report, Lead Form, Design, and Publish. Save changes with the WordPress Publish or Update button.', 'assesscraft' ) . '</p>',
			)
		);
	}
}
