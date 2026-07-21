<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Templates_Admin {
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_assesscraft_use_template', array( $this, 'use_template' ) );
		add_action( 'admin_post_assesscraft_import', array( $this, 'import' ) );
		add_action( 'admin_post_assesscraft_export', array( $this, 'export' ) );
		add_action( 'admin_post_assesscraft_duplicate', array( $this, 'duplicate' ) );
		add_action( 'admin_post_assesscraft_save_template', array( $this, 'save_template' ) );
		add_action( 'admin_post_assesscraft_import_template', array( $this, 'import_template' ) );
		add_action( 'admin_post_assesscraft_import_json', array( $this, 'import_json' ) );
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
			<?php $this->render_notice(); ?>
			<p class="description"><?php esc_html_e( 'Start with a professionally structured assessment, or import a portable AssessCraft JSON file.', 'assesscraft' ); ?></p>
			<div class="ac-template-grid">
				<?php foreach ( AssessCraft_Template_Registry::all() as $slug => $template ) : ?>
					<?php
					$stage_count = count( $template['config']['stages'] ?? array() );
					$question_count = array_sum( array_map( static fn( array $stage ): int => count( $stage['questions'] ?? array() ), $template['config']['stages'] ?? array() ) );
					?>
					<article class="ac-template-card">
						<span><?php echo esc_html( $template['category'] ); ?></span>
						<h2><?php echo esc_html( $template['name'] ); ?></h2>
						<p><?php echo esc_html( $template['description'] ); ?></p>
						<div class="ac-template-meta"><span><strong><?php echo absint( $stage_count ); ?></strong> <?php esc_html_e( 'stages', 'assesscraft' ); ?></span><span><strong><?php echo absint( $question_count ); ?></strong> <?php esc_html_e( 'questions', 'assesscraft' ); ?></span></div>
						<div class="ac-template-card-actions"><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_use_template&template=' . rawurlencode( $slug ) ), 'assesscraft_use_template' ) ); ?>"><?php esc_html_e( 'Use this template', 'assesscraft' ); ?></a><button class="button ac-preview-template" type="button" data-template="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Preview', 'assesscraft' ); ?></button></div>
						<footer><span><?php echo esc_html( $template['source'] ?? __( 'Bundled', 'assesscraft' ) ); ?></span><span>v<?php echo esc_html( $template['version'] ?? '1.0.0' ); ?></span></footer>
					</article>
					<dialog class="ac-template-dialog" id="ac-template-<?php echo esc_attr( $slug ); ?>">
						<div class="ac-template-dialog-header"><div><span><?php echo esc_html( $template['category'] ); ?></span><h2><?php echo esc_html( $template['name'] ); ?></h2></div><button type="button" class="ac-dialog-close" aria-label="<?php esc_attr_e( 'Close preview', 'assesscraft' ); ?>">&times;</button></div>
						<p><?php echo esc_html( $template['description'] ); ?></p>
						<div class="ac-template-preview-summary"><span><strong><?php echo absint( $stage_count ); ?></strong><?php esc_html_e( 'Stages', 'assesscraft' ); ?></span><span><strong><?php echo absint( $question_count ); ?></strong><?php esc_html_e( 'Questions', 'assesscraft' ); ?></span><span><strong><?php echo absint( count( $template['config']['profiles'] ?? array() ) ); ?></strong><?php esc_html_e( 'Profiles', 'assesscraft' ); ?></span></div>
						<div class="ac-template-stage-preview"><?php foreach ( $template['config']['stages'] ?? array() as $index => $stage ) : ?><article><span><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><div><h3><?php echo esc_html( $stage['name'] ?? '' ); ?></h3><p><?php echo esc_html( $stage['description'] ?? '' ); ?></p><small><?php printf( esc_html__( '%d questions', 'assesscraft' ), count( $stage['questions'] ?? array() ) ); ?></small></div></article><?php endforeach; ?></div>
						<div class="ac-template-dialog-actions"><button type="button" class="button ac-dialog-close"><?php esc_html_e( 'Close', 'assesscraft' ); ?></button><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_use_template&template=' . rawurlencode( $slug ) ), 'assesscraft_use_template' ) ); ?>"><?php esc_html_e( 'Use this template', 'assesscraft' ); ?></a></div>
					</dialog>
				<?php endforeach; ?>
			</div>
			<div class="ac-import-card ac-unified-import">
				<div><span class="ac-eyebrow"><?php esc_html_e( 'Optional', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Import AssessCraft JSON', 'assesscraft' ); ?></h2>
				<p><?php esc_html_e( 'Move an assessment from another website or install a reusable template pack. AssessCraft detects the file type automatically.', 'assesscraft' ); ?></p></div>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="assesscraft_import_json">
					<?php wp_nonce_field( 'assesscraft_import_json' ); ?>
					<input type="file" name="json_file" accept="application/json,.json" required>
					<button class="button button-secondary" type="submit"><?php esc_html_e( 'Import File', 'assesscraft' ); ?></button>
				</form>
				<small><?php esc_html_e( 'Accepted: AssessCraft assessment exports and template packages up to 2 MB.', 'assesscraft' ); ?></small>
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

	public function duplicate(): void {
		$this->guard( 'assesscraft_duplicate' );
		$post_id = absint( $_GET['assessment_id'] ?? 0 );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || AssessCraft_Post_Type::TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'The selected assessment cannot be duplicated.', 'assesscraft' ) );
		}
		$new_id = wp_insert_post( array( 'post_type' => AssessCraft_Post_Type::TYPE, 'post_status' => 'draft', 'post_title' => sprintf( __( '%s — Copy', 'assesscraft' ), get_the_title( $post_id ) ) ), true );
		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}
		update_post_meta( $new_id, '_assesscraft_config', AssessCraft_Schema::sanitize( (array) get_post_meta( $post_id, '_assesscraft_config', true ) ) );
		wp_safe_redirect( get_edit_post_link( $new_id, 'url' ) );
		exit;
	}

	public function save_template(): void {
		$this->guard( 'assesscraft_save_template' );
		$post_id = absint( $_POST['assessment_id'] ?? 0 );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || AssessCraft_Post_Type::TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'The selected assessment cannot be saved as a template.', 'assesscraft' ) );
		}
		$result = AssessCraft_Template_Registry::write_custom( array(
			'name' => sanitize_text_field( wp_unslash( $_POST['template_name'] ?? get_the_title( $post_id ) ) ),
			'description' => sanitize_text_field( wp_unslash( $_POST['template_description'] ?? '' ) ),
			'category' => sanitize_text_field( wp_unslash( $_POST['template_category'] ?? __( 'Custom', 'assesscraft' ) ) ),
			'version' => sanitize_text_field( wp_unslash( $_POST['template_version'] ?? '1.0.0' ) ),
			'config' => (array) get_post_meta( $post_id, '_assesscraft_config', true ),
		) );
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}
		wp_safe_redirect( add_query_arg( 'assesscraft_notice', 'template-saved', admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-templates' ) ) );
		exit;
	}

	public function import_template(): void {
		$this->guard( 'assesscraft_import_template' );
		$file = $_FILES['template_file'] ?? null;
		if ( ! is_array( $file ) || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? -1 ) || ! is_uploaded_file( $file['tmp_name'] ?? '' ) || (int) ( $file['size'] ?? 0 ) > 2 * MB_IN_BYTES ) {
			wp_die( esc_html__( 'Please upload a valid template JSON file smaller than 2 MB.', 'assesscraft' ) );
		}
		$data = json_decode( (string) file_get_contents( $file['tmp_name'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $data ) || 1 !== (int) ( $data['assesscraft_template'] ?? 0 ) || ! is_array( $data['config'] ?? null ) ) {
			wp_die( esc_html__( 'This is not a valid AssessCraft template package.', 'assesscraft' ) );
		}
		$result = AssessCraft_Template_Registry::write_custom( $data );
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}
		wp_safe_redirect( add_query_arg( 'assesscraft_notice', 'template-imported', admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-templates' ) ) );
		exit;
	}

	public function import_json(): void {
		$this->guard( 'assesscraft_import_json' );
		$file = $_FILES['json_file'] ?? null;
		if ( ! is_array( $file ) || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? -1 ) || ! is_uploaded_file( $file['tmp_name'] ?? '' ) || (int) ( $file['size'] ?? 0 ) > 2 * MB_IN_BYTES ) {
			wp_die( esc_html__( 'Please upload a valid AssessCraft JSON file smaller than 2 MB.', 'assesscraft' ) );
		}
		$data = json_decode( (string) file_get_contents( $file['tmp_name'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $data ) || ! is_array( $data['config'] ?? null ) ) {
			wp_die( esc_html__( 'This file does not contain a valid AssessCraft configuration.', 'assesscraft' ) );
		}

		if ( 1 === (int) ( $data['assesscraft_template'] ?? 0 ) ) {
			$result = AssessCraft_Template_Registry::write_custom( $data );
			if ( is_wp_error( $result ) ) {
				wp_die( esc_html( $result->get_error_message() ) );
			}
			wp_safe_redirect( add_query_arg( 'assesscraft_notice', 'template-imported', admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-templates' ) ) );
			exit;
		}

		if ( 1 === (int) ( $data['assesscraft_export'] ?? 0 ) ) {
			$post_id = wp_insert_post( array( 'post_type' => AssessCraft_Post_Type::TYPE, 'post_status' => 'draft', 'post_title' => sanitize_text_field( $data['title'] ?? __( 'Imported Assessment', 'assesscraft' ) ) ), true );
			if ( is_wp_error( $post_id ) ) {
				wp_die( esc_html( $post_id->get_error_message() ) );
			}
			update_post_meta( $post_id, '_assesscraft_config', AssessCraft_Schema::sanitize( $data['config'] ) );
			wp_safe_redirect( get_edit_post_link( $post_id, 'url' ) );
			exit;
		}

		wp_die( esc_html__( 'AssessCraft could not identify this JSON file as an assessment export or template package.', 'assesscraft' ) );
	}

	private function render_notice(): void {
		$notice = sanitize_key( wp_unslash( $_GET['assesscraft_notice'] ?? '' ) );
		$messages = array( 'template-saved' => __( 'Assessment saved to the custom template library.', 'assesscraft' ), 'template-imported' => __( 'Template package imported successfully.', 'assesscraft' ) );
		if ( isset( $messages[ $notice ] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $messages[ $notice ] ) );
		}
	}

	private function guard( string $action ): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'assesscraft' ) );
		}
		check_admin_referer( $action );
	}
}
