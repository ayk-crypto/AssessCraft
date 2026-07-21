<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Templates_Admin {
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_assesscraft_use_template', array( $this, 'use_template' ) );
		add_action( 'admin_post_assesscraft_import', array( $this, 'import' ) );
		add_action( 'admin_post_assesscraft_export', array( $this, 'export' ) );
	}

	public function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . AssessCraft_Post_Type::TYPE,
			__( 'Assessment Templates', 'assesscraft' ),
			__( 'Templates', 'assesscraft' ),
			'edit_posts',
			'assesscraft-templates',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access templates.', 'assesscraft' ) );
		}
		?>
		<div class="wrap ac-template-page">
			<h1><?php esc_html_e( 'AssessCraft Templates', 'assesscraft' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Start with a professionally structured assessment, or import a portable AssessCraft JSON file.', 'assesscraft' ); ?></p>
			<div class="ac-template-grid">
				<?php foreach ( AssessCraft_Template_Registry::all() as $slug => $template ) : ?>
					<article class="ac-template-card">
						<span><?php echo esc_html( $template['category'] ); ?></span>
						<h2><?php echo esc_html( $template['name'] ); ?></h2>
						<p><?php echo esc_html( $template['description'] ); ?></p>
						<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_use_template&template=' . rawurlencode( $slug ) ), 'assesscraft_use_template' ) ); ?>"><?php esc_html_e( 'Use this template', 'assesscraft' ); ?></a>
					</article>
				<?php endforeach; ?>
			</div>
			<div class="ac-import-card">
				<h2><?php esc_html_e( 'Import assessment', 'assesscraft' ); ?></h2>
				<p><?php esc_html_e( 'Choose an AssessCraft JSON export. Imported content is sanitized before it is saved.', 'assesscraft' ); ?></p>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="assesscraft_import">
					<?php wp_nonce_field( 'assesscraft_import' ); ?>
					<input type="file" name="assessment_file" accept="application/json,.json" required>
					<button class="button button-secondary" type="submit"><?php esc_html_e( 'Import JSON', 'assesscraft' ); ?></button>
				</form>
			</div>
		</div>
		<?php
	}

	public function use_template(): void {
		$this->guard( 'assesscraft_use_template' );
		$template = AssessCraft_Template_Registry::get( sanitize_key( wp_unslash( $_GET['template'] ?? '' ) ) );
		if ( ! $template ) {
			wp_die( esc_html__( 'The selected template does not exist.', 'assesscraft' ) );
		}
		$post_id = wp_insert_post(
			array(
				'post_type'   => AssessCraft_Post_Type::TYPE,
				'post_status' => 'draft',
				'post_title'  => sanitize_text_field( $template['name'] ),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_die( esc_html( $post_id->get_error_message() ) );
		}
		update_post_meta( $post_id, '_assesscraft_config', AssessCraft_Schema::sanitize( $template['config'] ) );
		wp_safe_redirect( get_edit_post_link( $post_id, 'url' ) );
		exit;
	}

	public function import(): void {
		$this->guard( 'assesscraft_import' );
		$file = $_FILES['assessment_file'] ?? null;
		if ( ! is_array( $file ) || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? -1 ) || ! is_uploaded_file( $file['tmp_name'] ?? '' ) || (int) ( $file['size'] ?? 0 ) > 2 * MB_IN_BYTES ) {
			wp_die( esc_html__( 'Please upload a valid JSON file smaller than 2 MB.', 'assesscraft' ) );
		}
		$contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( (string) $contents, true );
		if ( ! is_array( $data ) || 1 !== (int) ( $data['assesscraft_export'] ?? 0 ) || ! is_array( $data['config'] ?? null ) ) {
			wp_die( esc_html__( 'This is not a valid AssessCraft export.', 'assesscraft' ) );
		}
		$post_id = wp_insert_post(
			array(
				'post_type'   => AssessCraft_Post_Type::TYPE,
				'post_status' => 'draft',
				'post_title'  => sanitize_text_field( $data['title'] ?? __( 'Imported Assessment', 'assesscraft' ) ),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_die( esc_html( $post_id->get_error_message() ) );
		}
		update_post_meta( $post_id, '_assesscraft_config', AssessCraft_Schema::sanitize( $data['config'] ) );
		wp_safe_redirect( get_edit_post_link( $post_id, 'url' ) );
		exit;
	}

	public function export(): void {
		$this->guard( 'assesscraft_export' );
		$post_id = absint( $_GET['assessment_id'] ?? 0 );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || AssessCraft_Post_Type::TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'The selected assessment cannot be exported.', 'assesscraft' ) );
		}
		$config = AssessCraft_Schema::sanitize( (array) get_post_meta( $post_id, '_assesscraft_config', true ) );
		$export = array( 'assesscraft_export' => 1, 'schema_version' => AssessCraft_Schema::VERSION, 'title' => get_the_title( $post_id ), 'exported_at' => gmdate( 'c' ), 'config' => $config );
		$filename = sanitize_file_name( get_the_title( $post_id ) ?: 'assessment' ) . '.assesscraft.json';
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	private function guard( string $action ): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'assesscraft' ) );
		}
		check_admin_referer( $action );
	}
}
