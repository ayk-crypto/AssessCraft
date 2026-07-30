<?php

defined( 'ABSPATH' ) || exit;

final class AssessCraft_Maintenance {
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_assets' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_assets' ), 40 );
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
				'title'   => __( 'Publication will be checked when you click Publish', 'assesscraft' ),
				'message' => __( 'Another assessment is already published under the current Free limit. The Publish button remains available so WordPress can verify the active plan and show a clear result instead of doing nothing.', 'assesscraft' ),
			)
		);
	}
}
