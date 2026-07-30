<?php

defined( 'ABSPATH' ) || exit;

final class AssessCraft_Maintenance {
	private const PUBLISH_NOTICE_KEY = 'assesscraft_publish_result_';

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_assets' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_assets' ), 40 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_direct_publish_action' ) );
		add_action( 'admin_post_assesscraft_publish_assessment', array( $this, 'handle_direct_publish' ) );
		add_action( 'admin_notices', array( $this, 'render_publish_result_notice' ) );
	}

	public function enqueue_import_assets( string $hook ): void {
		if ( 'ac_assessment_page_assesscraft-templates' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'assesscraft-import-polish',
			ASSESSCRAFT_URL . 'admin/assets/import-polish.css',
			array( 'assesscraft-admin' ),
			ASSESSCRAFT_VERSION
		);

		wp_enqueue_script(
			'assesscraft-import-polish',
			ASSESSCRAFT_URL . 'admin/assets/import-polish.js',
			array( 'assesscraft-templates' ),
			ASSESSCRAFT_VERSION,
			true
		);

		wp_localize_script(
			'assesscraft-import-polish',
			'assessCraftImport',
			array(
				'chooseFile' => __( 'Choose JSON file', 'assesscraft' ),
				'noFile'     => __( 'No file selected', 'assesscraft' ),
			)
		);
	}

	public function enqueue_editor_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || AssessCraft_Post_Type::TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'assesscraft-publish-feedback',
			ASSESSCRAFT_URL . 'admin/assets/publish-feedback.js',
			array( 'assesscraft-admin' ),
			ASSESSCRAFT_VERSION,
			true
		);

		wp_localize_script(
			'assesscraft-publish-feedback',
			'assessCraftPublishFeedback',
			array(
				'title'   => __( 'Publishing is checked by the server', 'assesscraft' ),
				'message' => __( 'The WordPress Publish button remains available. The server will verify the current plan and display a confirmed result.', 'assesscraft' ),
			)
		);
	}

	public function render_direct_publish_action(): void {
		global $post;
		if ( ! $post instanceof WP_Post || AssessCraft_Post_Type::TYPE !== $post->post_type || 'publish' === $post->post_status ) {
			return;
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}
		?>
		<div class="misc-pub-section assesscraft-direct-publish">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="assesscraft_publish_assessment">
				<input type="hidden" name="assessment_id" value="<?php echo absint( $post->ID ); ?>">
				<?php wp_nonce_field( 'assesscraft_publish_assessment_' . $post->ID ); ?>
				<button class="button button-secondary" type="submit"><?php esc_html_e( 'Publish through AssessCraft', 'assesscraft' ); ?></button>
			</form>
			<p class="description"><?php esc_html_e( 'Server-confirmed fallback if the standard WordPress button is intercepted.', 'assesscraft' ); ?></p>
		</div>
		<?php
	}

	public function handle_direct_publish(): void {
		check_admin_referer( 'assesscraft_publish_assessment_' . absint( $_POST['assessment_id'] ?? 0 ) );
		$post_id = isset( $_POST['assessment_id'] ) ? absint( wp_unslash( $_POST['assessment_id'] ) ) : 0;
		if ( ! $post_id || AssessCraft_Post_Type::TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'The requested assessment could not be found.', 'assesscraft' ) );
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to publish this assessment.', 'assesscraft' ) );
		}

		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);

		$status = get_post_status( $post_id );
		if ( ! is_wp_error( $result ) && 'publish' === $status ) {
			$this->set_publish_notice( 'published' );
		} elseif ( ! get_transient( 'assesscraft_entitlement_notice_' . get_current_user_id() ) ) {
			$this->set_publish_notice( 'failed' );
		}

		wp_safe_redirect( get_edit_post_link( $post_id, 'url' ) );
		exit;
	}

	public function render_publish_result_notice(): void {
		$key    = self::PUBLISH_NOTICE_KEY . get_current_user_id();
		$notice = sanitize_key( (string) get_transient( $key ) );
		if ( ! $notice ) {
			return;
		}
		delete_transient( $key );

		if ( 'published' === $notice ) {
			?>
		<div class="notice notice-success is-dismissible"><p><strong><?php esc_html_e( 'Assessment published successfully.', 'assesscraft' ); ?></strong> <?php esc_html_e( 'The server confirmed that its status is Published.', 'assesscraft' ); ?></p></div>
			<?php
			return;
		}
		?>
		<div class="notice notice-error is-dismissible"><p><strong><?php esc_html_e( 'WordPress did not publish the assessment.', 'assesscraft' ); ?></strong> <?php esc_html_e( 'The request reached the server, but the final status remained Draft. Review the current plan and WordPress error log.', 'assesscraft' ); ?></p></div>
		<?php
	}

	private function set_publish_notice( string $notice ): void {
		set_transient( self::PUBLISH_NOTICE_KEY . get_current_user_id(), sanitize_key( $notice ), MINUTE_IN_SECONDS );
	}
}
