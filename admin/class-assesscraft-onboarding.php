<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Onboarding {
	private const REDIRECT_TRANSIENT = 'assesscraft_activation_redirect';
	private const COMPLETE_OPTION = 'assesscraft_onboarding_complete';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ), 9 );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
		add_action( 'rest_api_init', array( $this, 'register_completion_route' ) );
		add_action( 'admin_post_assesscraft_complete_onboarding', array( $this, 'complete' ) );
		add_action( 'current_screen', array( $this, 'contextual_help' ) );
	}

	public static function queue_redirect(): void {
		set_transient( self::REDIRECT_TRANSIENT, 1, MINUTE_IN_SECONDS );
	}

	public function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . AssessCraft_Post_Type::TYPE,
			__( 'Getting Started', 'assesscraft' ),
			__( 'Getting Started', 'assesscraft' ),
			'edit_posts',
			'assesscraft-getting-started',
			array( $this, 'render' )
		);
	}

	public function maybe_redirect(): void {
		if ( ! get_transient( self::REDIRECT_TRANSIENT ) || get_option( self::COMPLETE_OPTION ) || ! current_user_can( 'edit_posts' ) || wp_doing_ajax() || isset( $_GET['activate-multi'] ) ) {
			return;
		}
		delete_transient( self::REDIRECT_TRANSIENT );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-getting-started' ) );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access AssessCraft onboarding.', 'assesscraft' ) );
		}
		$counts = wp_count_posts( AssessCraft_Post_Type::TYPE );
		$total = array_sum( array_map( 'intval', (array) $counts ) );
		$published = absint( $counts->publish ?? 0 );
		$embedded = $this->has_embedded_assessment();
		$completed = $this->has_completed_assessment();
		$complete_url = wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_complete_onboarding' ), 'assesscraft_complete_onboarding' );
		?>
		<div class="wrap ac-onboarding">
			<h1 class="screen-reader-text"><?php esc_html_e( 'AssessCraft Getting Started', 'assesscraft' ); ?></h1>
			<hr class="wp-header-end">
			<header class="ac-onboarding-hero"><span class="dashicons dashicons-chart-bar"></span><div><span class="ac-eyebrow"><?php esc_html_e( 'Build. Diagnose. Convert.', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Welcome to AssessCraft', 'assesscraft' ); ?></h2><p><?php esc_html_e( 'Create a structured assessment, personalize its report, and publish it anywhere in WordPress.', 'assesscraft' ); ?></p></div></header>
			<div class="ac-onboarding-layout">
				<main>
					<h2><?php esc_html_e( 'Create your first assessment', 'assesscraft' ); ?></h2>
					<div class="ac-onboarding-paths">
						<article><span class="dashicons dashicons-screenoptions"></span><h3><?php esc_html_e( 'Start with a template', 'assesscraft' ); ?></h3><p><?php esc_html_e( 'Choose a professionally structured assessment and customize it for your audience.', 'assesscraft' ); ?></p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-templates' ) ); ?>"><?php esc_html_e( 'Browse templates', 'assesscraft' ); ?></a></article>
						<article><span class="dashicons dashicons-welcome-write-blog"></span><h3><?php esc_html_e( 'Start from scratch', 'assesscraft' ); ?></h3><p><?php esc_html_e( 'Build your own stages, questions, scoring, profiles, and report.', 'assesscraft' ); ?></p><a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . AssessCraft_Post_Type::TYPE ) ); ?>"><?php esc_html_e( 'Create blank assessment', 'assesscraft' ); ?></a></article>
					</div>
					<section class="ac-onboarding-steps"><h2><?php esc_html_e( 'How AssessCraft works', 'assesscraft' ); ?></h2><ol><li><strong><?php esc_html_e( 'Build', 'assesscraft' ); ?></strong><span><?php esc_html_e( 'Add stages, questions, and answer choices.', 'assesscraft' ); ?></span></li><li><strong><?php esc_html_e( 'Diagnose', 'assesscraft' ); ?></strong><span><?php esc_html_e( 'Configure scoring, profiles, and the result report.', 'assesscraft' ); ?></span></li><li><strong><?php esc_html_e( 'Convert', 'assesscraft' ); ?></strong><span><?php esc_html_e( 'Publish with a shortcode, block, or Elementor widget.', 'assesscraft' ); ?></span></li></ol></section>
				</main>
				<aside class="ac-onboarding-checklist"><h2><?php esc_html_e( 'Launch checklist', 'assesscraft' ); ?></h2><ul><li class="<?php echo $total ? 'is-complete' : ''; ?>"><span class="dashicons dashicons-<?php echo $total ? 'yes-alt' : 'marker'; ?>"></span><?php esc_html_e( 'Create an assessment', 'assesscraft' ); ?></li><li class="<?php echo $published ? 'is-complete' : ''; ?>"><span class="dashicons dashicons-<?php echo $published ? 'yes-alt' : 'marker'; ?>"></span><?php esc_html_e( 'Publish the assessment', 'assesscraft' ); ?></li><li class="<?php echo $embedded ? 'is-complete' : ''; ?>"><span class="dashicons dashicons-<?php echo $embedded ? 'yes-alt' : 'marker'; ?>"></span><?php esc_html_e( 'Embed it on a page', 'assesscraft' ); ?></li><li class="<?php echo $completed ? 'is-complete' : ''; ?>"><span class="dashicons dashicons-<?php echo $completed ? 'yes-alt' : 'marker'; ?>"></span><?php esc_html_e( 'Complete a test response', 'assesscraft' ); ?></li></ul><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE ) ); ?>"><?php esc_html_e( 'View assessments', 'assesscraft' ); ?></a><a class="ac-onboarding-dismiss" href="<?php echo esc_url( $complete_url ); ?>"><?php esc_html_e( 'Mark onboarding complete', 'assesscraft' ); ?></a></aside>
			</div>
		</div>
		<?php
	}

	public function complete(): void {
		check_admin_referer( 'assesscraft_complete_onboarding' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to complete onboarding.', 'assesscraft' ) );
		}
		update_option( self::COMPLETE_OPTION, 1, false );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE ) );
		exit;
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
		$screen->add_help_tab( array( 'id' => 'assesscraft-workflow', 'title' => __( 'Assessment workflow', 'assesscraft' ), 'content' => '<p>' . esc_html__( 'Work through Overview, Builder, Scoring, Profiles, Report, Lead Form, Design, and Publish. Save changes with the WordPress Publish or Update button.', 'assesscraft' ) . '</p>' ) );
		$screen->set_help_sidebar( '<p><strong>' . esc_html__( 'AssessCraft help', 'assesscraft' ) . '</strong></p><p><a href="' . esc_url( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-getting-started' ) ) . '">' . esc_html__( 'Open Getting Started', 'assesscraft' ) . '</a></p>' );
	}

	private function has_embedded_assessment(): bool {
		$assessment_ids = get_posts( array( 'post_type' => AssessCraft_Post_Type::TYPE, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
		foreach ( $assessment_ids as $assessment_id ) {
			if ( get_post_meta( $assessment_id, '_assesscraft_has_embed', true ) ) {
				return true;
			}
		}
		global $wpdb;
		$content_match = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type <> '" . esc_sql( AssessCraft_Post_Type::TYPE ) . "' AND (post_content LIKE '%[assesscraft%' OR post_content LIKE '%wp:assesscraft/assessment%') LIMIT 1" );
		if ( $content_match ) {
			return true;
		}
		return (bool) $wpdb->get_var( "SELECT pm.post_id FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND pm.meta_key = '_elementor_data' AND pm.meta_value LIKE '%assesscraft-assessment%' LIMIT 1" );
	}

	private function has_completed_assessment(): bool {
		return (bool) get_posts( array( 'post_type' => AssessCraft_Post_Type::TYPE, 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_assesscraft_has_completion', 'meta_compare' => 'EXISTS', 'no_found_rows' => true ) );
	}
}
