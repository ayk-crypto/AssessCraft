<?php

defined( 'ABSPATH' ) || exit;

final class AssessCraft_Maintenance {
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_assets' ), 30 );
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
}
