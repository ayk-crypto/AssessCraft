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
				'title'      => __( 'Publishing is checked by the server', 'assesscraft' ),
				'message'    => __( 'The WordPress Publish button remains available. The server will verify the current plan and display a confirmed result.', 'assesscraft' ),
				'publishing' => __( 'Saving and publishing…', 'assesscraft' ),
				'failed'     => __( 'WordPress could not save and publish this assessment.', 'assesscraft' ),
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
			<button
				class="button button-secondary"
				type="button"
				id="assesscraft-direct-publish"
				data-action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				data-assessment="<?php echo absint( $post->ID ); ?>"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'assesscraft_publish_assessment_' . $post->ID ) ); ?>"
			><?php esc_html_e( 'Save and Publish through AssessCraft', 'assesscraft' ); ?></button>
			<p class="description"><?php esc_html_e( 'Saves the current editor fields and uses a server-confirmed publishing path.', 'assesscraft' ); ?></p>
		</div>
		<?php
	}

	public function handle_direct_publish(): void {
		$post_id = isset( $_POST['assessment_id'] ) ? absint( wp_unslash( $_POST['assessment_id'] ) ) : 0;
		check_admin_referer( 'assesscraft_publish_assessment_' . $post_id );

		if ( ! $post_id || AssessCraft_Post_Type::TYPE !== get_post_type( $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The requested assessment could not be found.', 'assesscraft' ),
				),
				404
			);
		}
		if ( ! current_user_can( 'publish_posts' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to publish this assessment.', 'assesscraft' ),
				),
				403
			);
		}

		$editor_nonce = isset( $_POST['assesscraft_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['assesscraft_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $editor_nonce, 'assesscraft_save' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'The assessment editor session expired. Refresh the page and try again.', 'assesscraft' ),
				),
				403
			);
		}

		// Save the current assessment configuration before changing its status.
		( new AssessCraft_Admin() )->save( $post_id );

		$post_update = array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		);
		if ( isset( $_POST['post_title'] ) ) {
			$post_update['post_title'] = sanitize_text_field( wp_unslash( $_POST['post_title'] ) );
		}

		$result = wp_update_post( $post_update, true );
		$status = get_post_status( $post_id );
		$redirect = get_edit_post_link( $post_id, 'url' );
		if ( ! $redirect ) {
			$redirect = admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE );
		}

		if ( ! is_wp_error( $result ) && 'publish' === $status ) {
			$this->set_publish_notice( 'published' );
			wp_send_json_success(
				array(
					'redirect' => $redirect,
					'status'   => 'publish',
				)
			);
		}

		if ( ! get_transient( 'assesscraft_entitlement_notice_' . get_current_user_id() ) ) {
			$this->set_publish_notice( 'failed' );
		}

		wp_send_json_error(
			array(
				'redirect' => $redirect,
				'status'   => is_string( $status ) ? $status : 'unknown',
				'message'  => is_wp_error( $result ) ? $result->get_error_message() : __( 'The server saved the assessment, but its final status remained Draft.', 'assesscraft' ),
			),
			409
		);
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
		<div class="notice notice-success is-dismissible"><p><strong><?php esc_html_e( 'Assessment published successfully.', 'assesscraft' ); ?></strong> <?php esc_html_e( 'The server confirmed that the current editor fields were saved and the status is Published.', 'assesscraft' ); ?></p></div>
			<?php
			return;
		}
		?>
		<div class="notice notice-error is-dismissible"><p><strong><?php esc_html_e( 'WordPress did not publish the assessment.', 'assesscraft' ); ?></strong> <?php esc_html_e( 'The current fields were saved, but the final status remained Draft. Review the plan message or WordPress error log.', 'assesscraft' ); ?></p></div>
		<?php
	}

	private function set_publish_notice( string $notice ): void {
		set_transient( self::PUBLISH_NOTICE_KEY . get_current_user_id(), sanitize_key( $notice ), MINUTE_IN_SECONDS );
	}
}
