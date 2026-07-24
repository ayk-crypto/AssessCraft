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
		add_action( 'admin_post_assesscraft_delete_template', array( $this, 'delete_template' ) );
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
		$templates  = AssessCraft_Template_Registry::all();
		$free_template = '';
		foreach ( $templates as $candidate_slug => $candidate ) {
			if ( empty( $candidate['is_custom'] ) ) {
				$free_template = (string) $candidate_slug;
				break;
			}
		}
		$categories = array_values( array_unique( array_filter( array_map( static fn( array $template ): string => (string) ( $template['category'] ?? '' ), $templates ) ) ) );
		$sources    = array_values( array_unique( array_map( static fn( array $template ): string => (string) ( $template['source'] ?? __( 'Bundled', 'assesscraft' ) ), $templates ) ) );
		sort( $categories, SORT_NATURAL | SORT_FLAG_CASE );
		sort( $sources, SORT_NATURAL | SORT_FLAG_CASE );
		?>
		<div class="wrap ac-template-page">
			<h1><?php esc_html_e( 'AssessCraft Templates', 'assesscraft' ); ?></h1>
			<?php $this->render_notice(); ?>
			<p class="description"><?php esc_html_e( 'Start with a professionally structured assessment, or import a portable AssessCraft JSON file.', 'assesscraft' ); ?></p>
			<section class="ac-template-catalog" aria-labelledby="ac-template-catalog-heading">
				<div class="ac-template-catalog-header">
					<div>
						<h2 id="ac-template-catalog-heading"><?php esc_html_e( 'Template library', 'assesscraft' ); ?></h2>
						<p><?php printf( esc_html( _n( '%d template available', '%d templates available', count( $templates ), 'assesscraft' ) ), count( $templates ) ); ?></p>
					</div>
					<div class="ac-template-result-count" id="ac-template-result-count" aria-live="polite"></div>
				</div>
				<div class="ac-template-toolbar" role="search">
					<label class="ac-template-search">
						<span class="screen-reader-text"><?php esc_html_e( 'Search templates', 'assesscraft' ); ?></span>
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<input id="ac-template-search" type="search" placeholder="<?php esc_attr_e( 'Search templates by name, category, or purpose…', 'assesscraft' ); ?>" autocomplete="off">
					</label>
					<label>
						<span class="screen-reader-text"><?php esc_html_e( 'Filter by category', 'assesscraft' ); ?></span>
						<select id="ac-template-category"><option value=""><?php esc_html_e( 'All categories', 'assesscraft' ); ?></option><?php foreach ( $categories as $category ) : ?><option value="<?php echo esc_attr( strtolower( $category ) ); ?>"><?php echo esc_html( $category ); ?></option><?php endforeach; ?></select>
					</label>
					<label>
						<span class="screen-reader-text"><?php esc_html_e( 'Filter by source', 'assesscraft' ); ?></span>
						<select id="ac-template-source"><option value=""><?php esc_html_e( 'All sources', 'assesscraft' ); ?></option><?php foreach ( $sources as $source ) : ?><option value="<?php echo esc_attr( strtolower( $source ) ); ?>"><?php echo esc_html( ucfirst( $source ) ); ?></option><?php endforeach; ?></select>
					</label>
					<label>
						<span class="screen-reader-text"><?php esc_html_e( 'Sort templates', 'assesscraft' ); ?></span>
						<select id="ac-template-sort"><option value="name"><?php esc_html_e( 'Name A–Z', 'assesscraft' ); ?></option><option value="category"><?php esc_html_e( 'Category', 'assesscraft' ); ?></option><option value="newest"><?php esc_html_e( 'Newest first', 'assesscraft' ); ?></option><option value="source"><?php esc_html_e( 'Source', 'assesscraft' ); ?></option></select>
					</label>
					<button class="button ac-template-reset" id="ac-template-reset" type="button"><?php esc_html_e( 'Reset', 'assesscraft' ); ?></button>
				</div>
				<div class="ac-template-grid" id="ac-template-grid" data-per-page="9">
				<?php foreach ( $templates as $slug => $template ) : ?>
					<?php
					$stage_count = count( $template['config']['stages'] ?? array() );
					$question_count = array_sum( array_map( static fn( array $stage ): int => count( $stage['questions'] ?? array() ), $template['config']['stages'] ?? array() ) );
					$source = (string) ( $template['source'] ?? __( 'Bundled', 'assesscraft' ) );
					$search_text = implode( ' ', array( $template['name'] ?? '', $template['description'] ?? '', $template['category'] ?? '', $source ) );
					$can_use = AssessCraft_Features::is_pro() || ( empty( $template['is_custom'] ) && $slug === $free_template );
					?>
					<article class="ac-template-card" data-search="<?php echo esc_attr( strtolower( $search_text ) ); ?>" data-name="<?php echo esc_attr( strtolower( (string) ( $template['name'] ?? '' ) ) ); ?>" data-category="<?php echo esc_attr( strtolower( (string) ( $template['category'] ?? '' ) ) ); ?>" data-source="<?php echo esc_attr( strtolower( $source ) ); ?>" data-modified="<?php echo absint( $template['modified_at'] ?? 0 ); ?>">
						<div class="ac-template-card-heading"><span class="ac-template-icon dashicons dashicons-<?php echo esc_attr( $template['icon'] ?? 'analytics' ); ?>" aria-hidden="true"></span><span><?php echo esc_html( $template['category'] ); ?></span></div>
						<h2><?php echo esc_html( $template['name'] ); ?></h2>
						<p><?php echo esc_html( $template['description'] ); ?></p>
						<div class="ac-template-meta"><span><strong><?php echo absint( $stage_count ); ?></strong> <?php esc_html_e( 'stages', 'assesscraft' ); ?></span><span><strong><?php echo absint( $question_count ); ?></strong> <?php esc_html_e( 'questions', 'assesscraft' ); ?></span></div>
						<div class="ac-template-card-actions"><?php if ( $can_use ) : ?><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_use_template&template=' . rawurlencode( $slug ) ), 'assesscraft_use_template' ) ); ?>"><?php esc_html_e( 'Use this template', 'assesscraft' ); ?></a><?php else : ?><span class="button disabled" aria-disabled="true"><?php esc_html_e( 'Pro — Coming Soon', 'assesscraft' ); ?></span><?php endif; ?><button class="button ac-preview-template" type="button" data-template="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Preview', 'assesscraft' ); ?></button><?php if ( ! empty( $template['is_custom'] ) && AssessCraft_Features::available( 'custom_templates' ) ) : ?><a class="button-link-delete ac-delete-template" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_delete_template&template=' . rawurlencode( $slug ) ), 'assesscraft_delete_template' ) ); ?>" data-template-name="<?php echo esc_attr( $template['name'] ); ?>"><?php esc_html_e( 'Delete', 'assesscraft' ); ?></a><?php endif; ?></div>
						<footer><span><?php echo esc_html( $source ); ?><?php if ( empty( $template['is_custom'] ) ) : ?> <span class="dashicons dashicons-lock" aria-label="<?php esc_attr_e( 'Protected bundled template', 'assesscraft' ); ?>"></span><?php endif; ?></span><span>v<?php echo esc_html( $template['version'] ?? '1.0.0' ); ?></span></footer>
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
				<div class="ac-template-empty" id="ac-template-empty" hidden>
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<h3><?php esc_html_e( 'No templates found', 'assesscraft' ); ?></h3>
					<p><?php esc_html_e( 'Try another search term or clear the active filters.', 'assesscraft' ); ?></p>
					<button class="button" type="button" data-reset-templates><?php esc_html_e( 'Clear filters', 'assesscraft' ); ?></button>
				</div>
				<nav class="ac-template-pagination" id="ac-template-pagination" aria-label="<?php esc_attr_e( 'Template pages', 'assesscraft' ); ?>"></nav>
			</section>
			<div class="ac-import-card ac-unified-import<?php echo AssessCraft_Features::available( 'json_portability' ) ? '' : ' ac-pro-locked'; ?>">
				<div><span class="ac-eyebrow"><?php esc_html_e( 'Optional', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Import AssessCraft JSON', 'assesscraft' ); ?></h2>
				<p><?php esc_html_e( 'Move an assessment from another website or install a reusable template pack. AssessCraft detects the file type automatically.', 'assesscraft' ); ?></p></div>
				<?php if ( AssessCraft_Features::available( 'json_portability' ) ) : ?><form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="assesscraft_import_json">
					<?php wp_nonce_field( 'assesscraft_import_json' ); ?>
					<input type="file" name="json_file" accept="application/json,.json" required>
					<button class="button button-secondary" type="submit"><?php esc_html_e( 'Import File', 'assesscraft' ); ?></button>
				</form><?php else : ?><p><span class="button disabled" aria-disabled="true"><?php esc_html_e( 'JSON import — Pro Coming Soon', 'assesscraft' ); ?></span></p><?php endif; ?>
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
		if ( ! $this->can_use_template( sanitize_key( wp_unslash( $_GET['template'] ?? '' ) ), $template ) ) {
			$this->feature_required( 'custom_templates' );
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

	public function delete_template(): void {
		$this->guard( 'assesscraft_delete_template' );
		$this->feature_required( 'custom_templates' );
		$result = AssessCraft_Template_Registry::delete_custom( sanitize_key( wp_unslash( $_GET['template'] ?? '' ) ) );
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}
		wp_safe_redirect( add_query_arg( 'assesscraft_notice', 'template-deleted', admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-templates' ) ) );
		exit;
	}

	public function import(): void {
		$this->guard( 'assesscraft_import' );
		$this->feature_required( 'json_portability' );
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
		$this->feature_required( 'json_portability' );
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
		$this->feature_required( 'custom_templates' );
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
		$this->feature_required( 'custom_templates' );
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
		$this->feature_required( 'json_portability' );
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
		$messages = array( 'template-saved' => __( 'Assessment saved to the custom template library.', 'assesscraft' ), 'template-imported' => __( 'Template package imported successfully.', 'assesscraft' ), 'template-deleted' => __( 'Custom template deleted.', 'assesscraft' ) );
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

	private function feature_required( string $feature ): void {
		if ( AssessCraft_Features::available( $feature ) ) {
			return;
		}
		wp_die(
			esc_html__( 'This feature requires AssessCraft Pro, which is coming soon.', 'assesscraft' ),
			esc_html__( 'AssessCraft Pro required', 'assesscraft' ),
			array( 'response' => 403 )
		);
	}

	private function can_use_template( string $slug, array $template ): bool {
		if ( AssessCraft_Features::is_pro() ) {
			return true;
		}
		if ( ! empty( $template['is_custom'] ) ) {
			return false;
		}
		foreach ( AssessCraft_Template_Registry::all() as $candidate_slug => $candidate ) {
			if ( empty( $candidate['is_custom'] ) ) {
				return $slug === $candidate_slug;
			}
		}
		return false;
	}
}
