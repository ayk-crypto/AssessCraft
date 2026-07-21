<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Admin {
	private const NONCE_ACTION = 'assesscraft_save';

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . AssessCraft_Post_Type::TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'manage_' . AssessCraft_Post_Type::TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . AssessCraft_Post_Type::TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
	}

	public function add_meta_boxes(): void {
		add_meta_box( 'assesscraft-builder', __( 'Assessment Builder', 'assesscraft' ), array( $this, 'render_builder' ), AssessCraft_Post_Type::TYPE, 'normal', 'high' );
		add_meta_box( 'assesscraft-embed', __( 'Embed Assessment', 'assesscraft' ), array( $this, 'render_publish' ), AssessCraft_Post_Type::TYPE, 'side' );
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || AssessCraft_Post_Type::TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'assesscraft-admin', ASSESSCRAFT_URL . 'admin/assets/admin.css', array(), ASSESSCRAFT_VERSION );
		wp_enqueue_script( 'assesscraft-admin', ASSESSCRAFT_URL . 'admin/assets/admin.js', array(), ASSESSCRAFT_VERSION, true );
		wp_localize_script(
			'assesscraft-admin',
			'assessCraftAdmin',
			array(
				'questionTypes' => array(
					'scale'   => __( 'Agreement scale', 'assesscraft' ),
					'choice'  => __( 'Multiple choice', 'assesscraft' ),
					'yes_no'  => __( 'Yes / No', 'assesscraft' ),
					'numeric' => __( 'Numeric rating', 'assesscraft' ),
				),
				'i18n' => array(
					'untitledStage'    => __( 'Untitled stage', 'assesscraft' ),
					'untitledQuestion' => __( 'Untitled question', 'assesscraft' ),
					'confirmDelete'    => __( 'Delete this item? This cannot be undone after saving.', 'assesscraft' ),
				),
			)
		);
	}

	public function render_builder( WP_Post $post ): void {
		$config = get_post_meta( $post->ID, '_assesscraft_config', true );
		$config = AssessCraft_Schema::sanitize( is_array( $config ) ? $config : array() );
		wp_nonce_field( self::NONCE_ACTION, 'assesscraft_nonce' );
		?>
		<div class="ac-admin" id="assesscraft-admin">
			<nav class="ac-tabs" aria-label="<?php esc_attr_e( 'Assessment settings', 'assesscraft' ); ?>">
				<?php
				$tabs = array(
					'overview'  => __( 'Overview', 'assesscraft' ),
					'builder'   => __( 'Builder', 'assesscraft' ),
					'scoring'   => __( 'Scoring', 'assesscraft' ),
					'profiles'  => __( 'Profiles', 'assesscraft' ),
					'report'    => __( 'Report', 'assesscraft' ),
					'lead-form' => __( 'Lead Form', 'assesscraft' ),
					'design'    => __( 'Design', 'assesscraft' ),
					'publish'   => __( 'Publish', 'assesscraft' ),
				);
				foreach ( $tabs as $key => $label ) {
					printf( '<button type="button" class="ac-tab%s" data-tab="%s">%s</button>', 'overview' === $key ? ' is-active' : '', esc_attr( $key ), esc_html( $label ) );
				}
				?>
			</nav>

			<section class="ac-panel is-active" data-panel="overview">
				<div class="ac-panel-heading">
					<div><span class="ac-eyebrow"><?php esc_html_e( 'Assessment setup', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Overview', 'assesscraft' ); ?></h2></div>
					<p><?php esc_html_e( 'Configure what visitors see before they begin.', 'assesscraft' ); ?></p>
				</div>
				<div class="ac-form-grid">
					<label class="ac-field ac-field-wide"><span><?php esc_html_e( 'Frontend heading', 'assesscraft' ); ?></span><input name="assesscraft_heading" value="<?php echo esc_attr( $config['overview']['heading'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Business Readiness Assessment', 'assesscraft' ); ?>"></label>
					<label class="ac-field ac-field-wide"><span><?php esc_html_e( 'Introduction', 'assesscraft' ); ?></span><textarea rows="4" name="assesscraft_description" placeholder="<?php esc_attr_e( 'Explain what this assessment measures and why it is useful.', 'assesscraft' ); ?>"><?php echo esc_textarea( $config['overview']['description'] ); ?></textarea></label>
					<label class="ac-field"><span><?php esc_html_e( 'Start button label', 'assesscraft' ); ?></span><input name="assesscraft_start_label" value="<?php echo esc_attr( $config['overview']['start_label'] ); ?>"></label>
					<label class="ac-field"><span><?php esc_html_e( 'Estimated time', 'assesscraft' ); ?></span><input name="assesscraft_estimated_time" value="<?php echo esc_attr( $config['overview']['estimated_time'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. 4 minutes', 'assesscraft' ); ?>"></label>
					<label class="ac-field ac-field-wide"><span><?php esc_html_e( 'Disclaimer', 'assesscraft' ); ?></span><textarea rows="3" name="assesscraft_disclaimer"><?php echo esc_textarea( $config['overview']['disclaimer'] ); ?></textarea></label>
				</div>
			</section>

			<section class="ac-panel" data-panel="builder">
				<div class="ac-panel-heading ac-builder-heading">
					<div><span class="ac-eyebrow"><?php esc_html_e( 'Assessment structure', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Stages and questions', 'assesscraft' ); ?></h2></div>
					<button type="button" class="button button-primary ac-add-stage" id="ac-add-stage"><?php esc_html_e( 'Add stage', 'assesscraft' ); ?></button>
				</div>
				<p class="ac-help"><?php esc_html_e( 'Use stages to group related questions, such as Growth, Capacity, or Leadership. Drag stages and questions using their handles.', 'assesscraft' ); ?></p>
				<div id="ac-stage-list" class="ac-stage-list"></div>
				<div id="ac-empty-builder" class="ac-empty-state">
					<div class="dashicons dashicons-editor-ol"></div>
					<h3><?php esc_html_e( 'Start with your first stage', 'assesscraft' ); ?></h3>
					<p><?php esc_html_e( 'A stage contains one or more related assessment questions.', 'assesscraft' ); ?></p>
					<button type="button" class="button button-primary ac-add-stage"><?php esc_html_e( 'Add first stage', 'assesscraft' ); ?></button>
				</div>
			</section>

			<section class="ac-panel" data-panel="scoring">
				<div class="ac-panel-heading">
					<div><span class="ac-eyebrow"><?php esc_html_e( 'Result classification', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Score bands', 'assesscraft' ); ?></h2></div>
					<button type="button" class="button button-primary" id="ac-add-band"><?php esc_html_e( 'Add score band', 'assesscraft' ); ?></button>
				</div>
				<p class="ac-help"><?php esc_html_e( 'Classify overall and stage scores from 0 to 100. Bands are evaluated from top to bottom.', 'assesscraft' ); ?></p>
				<div class="ac-band-list" id="ac-band-list"></div>
			</section>

			<section class="ac-panel" data-panel="profiles">
				<div class="ac-panel-heading">
					<div><span class="ac-eyebrow"><?php esc_html_e( 'Personalized outcomes', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Result profiles', 'assesscraft' ); ?></h2></div>
					<button type="button" class="button button-primary" id="ac-add-profile"><?php esc_html_e( 'Add profile', 'assesscraft' ); ?></button>
				</div>
				<p class="ac-help"><?php esc_html_e( 'Profiles use the overall or individual stage scores. The highest-priority matching profile appears in the report.', 'assesscraft' ); ?></p>
				<div class="ac-profile-list" id="ac-profile-list"></div>
				<div class="ac-empty-state" id="ac-empty-profiles"><div class="dashicons dashicons-groups"></div><h3><?php esc_html_e( 'No profiles yet', 'assesscraft' ); ?></h3><p><?php esc_html_e( 'Create a profile to turn scores into a meaningful result narrative.', 'assesscraft' ); ?></p><button type="button" class="button button-primary ac-add-profile"><?php esc_html_e( 'Add first profile', 'assesscraft' ); ?></button></div>
			</section>

			<?php foreach ( array( 'report', 'lead-form', 'design', 'publish' ) as $future_tab ) : ?>
				<section class="ac-panel" data-panel="<?php echo esc_attr( $future_tab ); ?>">
					<div class="ac-coming-soon"><span class="dashicons dashicons-admin-tools"></span><h2><?php echo esc_html( $tabs[ $future_tab ] ); ?></h2><p><?php esc_html_e( 'This workspace is part of the next AssessCraft milestone.', 'assesscraft' ); ?></p></div>
				</section>
			<?php endforeach; ?>

			<input type="hidden" id="assesscraft-stages-json" name="assesscraft_stages_json" value="<?php echo esc_attr( wp_json_encode( $config['stages'] ) ); ?>">
			<input type="hidden" id="assesscraft-scoring-json" name="assesscraft_scoring_json" value="<?php echo esc_attr( wp_json_encode( $config['scoring'] ) ); ?>">
			<input type="hidden" id="assesscraft-profiles-json" name="assesscraft_profiles_json" value="<?php echo esc_attr( wp_json_encode( $config['profiles'] ) ); ?>">
		</div>
		<?php
	}

	public function render_publish( WP_Post $post ): void {
		echo '<p>' . esc_html__( 'Place this assessment on any page:', 'assesscraft' ) . '</p>';
		echo '<p><code>[assesscraft id=&quot;' . absint( $post->ID ) . '&quot;]</code></p>';
		echo '<p class="description">' . esc_html__( 'Elementor and Gutenberg widgets will use this same assessment ID.', 'assesscraft' ) . '</p>';
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['assesscraft_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['assesscraft_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$config = get_post_meta( $post_id, '_assesscraft_config', true );
		$config = AssessCraft_Schema::sanitize( is_array( $config ) ? $config : array() );
		$config['overview']['heading']        = $this->posted_text( 'assesscraft_heading' );
		$config['overview']['description']    = $this->posted_textarea( 'assesscraft_description' );
		$config['overview']['start_label']    = $this->posted_text( 'assesscraft_start_label' );
		$config['overview']['estimated_time'] = $this->posted_text( 'assesscraft_estimated_time' );
		$config['overview']['disclaimer']     = $this->posted_textarea( 'assesscraft_disclaimer' );

		if ( isset( $_POST['assesscraft_stages_json'] ) ) {
			$stages = json_decode( wp_unslash( $_POST['assesscraft_stages_json'] ), true );
			$config['stages'] = is_array( $stages ) ? AssessCraft_Schema::sanitize_stages( $stages ) : array();
		}
		if ( isset( $_POST['assesscraft_scoring_json'] ) ) {
			$scoring = json_decode( wp_unslash( $_POST['assesscraft_scoring_json'] ), true );
			if ( is_array( $scoring ) ) {
				$config['scoring']['method'] = 'weighted_percentage';
				$config['scoring']['bands'] = AssessCraft_Schema::sanitize_bands( is_array( $scoring['bands'] ?? null ) ? $scoring['bands'] : array() );
			}
		}
		if ( isset( $_POST['assesscraft_profiles_json'] ) ) {
			$profiles = json_decode( wp_unslash( $_POST['assesscraft_profiles_json'] ), true );
			$config['profiles'] = is_array( $profiles ) ? AssessCraft_Schema::sanitize_profiles( $profiles ) : array();
		}

		update_post_meta( $post_id, '_assesscraft_config', AssessCraft_Schema::sanitize( $config ) );
	}

	private function posted_text( string $key ): string {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	private function posted_textarea( string $key ): string {
		return isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	public function columns( array $columns ): array {
		$columns['assesscraft_shortcode'] = __( 'Shortcode', 'assesscraft' );
		return $columns;
	}

	public function column_content( string $column, int $post_id ): void {
		if ( 'assesscraft_shortcode' === $column ) {
			echo '<code>[assesscraft id=&quot;' . absint( $post_id ) . '&quot;]</code>';
		}
	}
}
