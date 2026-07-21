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
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
	}

	public function add_meta_boxes(): void {
		add_meta_box( 'assesscraft-builder', __( 'Assessment Builder', 'assesscraft' ), array( $this, 'render_builder' ), AssessCraft_Post_Type::TYPE, 'normal', 'high' );
		add_meta_box( 'assesscraft-embed', __( 'Embed Assessment', 'assesscraft' ), array( $this, 'render_publish' ), AssessCraft_Post_Type::TYPE, 'side' );
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'ac_assessment_page_assesscraft-templates' === $hook ) {
			wp_enqueue_style( 'assesscraft-admin', ASSESSCRAFT_URL . 'admin/assets/admin.css', array(), ASSESSCRAFT_VERSION );
			wp_enqueue_script( 'assesscraft-templates', ASSESSCRAFT_URL . 'admin/assets/templates.js', array(), ASSESSCRAFT_VERSION, true );
			return;
		}
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
			<header class="ac-workspace-header">
				<div class="ac-workspace-brand"><span class="dashicons dashicons-chart-bar"></span><div><strong><?php esc_html_e( 'AssessCraft', 'assesscraft' ); ?></strong><small><?php esc_html_e( 'Assessment workspace', 'assesscraft' ); ?></small></div></div>
				<div class="ac-workspace-actions">
					<span class="ac-save-hint"><span class="dashicons dashicons-saved"></span><?php esc_html_e( 'Changes save with WordPress Update', 'assesscraft' ); ?></span>
					<span class="ac-status-pill <?php echo 'publish' === $post->post_status ? 'is-published' : ''; ?>"><?php echo 'publish' === $post->post_status ? esc_html__( 'Published', 'assesscraft' ) : esc_html__( 'Draft', 'assesscraft' ); ?></span>
				</div>
			</header>
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
				$icons = array( 'overview' => 'welcome-write-blog', 'builder' => 'editor-ol', 'scoring' => 'chart-line', 'profiles' => 'groups', 'report' => 'media-document', 'lead-form' => 'email-alt', 'design' => 'art', 'publish' => 'share' );
				foreach ( $tabs as $key => $label ) {
					printf( '<button type="button" class="ac-tab%s" data-tab="%s"><span class="dashicons dashicons-%s"></span><span>%s</span></button>', 'overview' === $key ? ' is-active' : '', esc_attr( $key ), esc_attr( $icons[ $key ] ), esc_html( $label ) );
				}
				?>
			</nav>
			<div class="ac-builder-status" aria-label="<?php esc_attr_e( 'Assessment build status', 'assesscraft' ); ?>">
				<span><strong data-status="stages">0</strong><?php esc_html_e( 'Stages', 'assesscraft' ); ?></span>
				<span><strong data-status="questions">0</strong><?php esc_html_e( 'Questions', 'assesscraft' ); ?></span>
				<span><strong data-status="profiles">0</strong><?php esc_html_e( 'Profiles', 'assesscraft' ); ?></span>
				<span class="ac-status-guidance"><span class="dashicons dashicons-info-outline"></span><?php esc_html_e( 'Use the tabs in order, then publish and embed the assessment.', 'assesscraft' ); ?></span>
			</div>

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

			<section class="ac-panel" data-panel="report">
				<div class="ac-panel-heading"><div><span class="ac-eyebrow"><?php esc_html_e( 'Visitor results', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Report builder', 'assesscraft' ); ?></h2></div><p><?php esc_html_e( 'Choose the content that appears after completion.', 'assesscraft' ); ?></p></div>
				<div class="ac-form-grid">
					<label class="ac-field"><span><?php esc_html_e( 'Report heading', 'assesscraft' ); ?></span><input name="assesscraft_report_heading" value="<?php echo esc_attr( $config['report']['heading'] ); ?>"></label>
					<label class="ac-field ac-field-wide"><span><?php esc_html_e( 'Report introduction', 'assesscraft' ); ?></span><textarea rows="3" name="assesscraft_report_intro"><?php echo esc_textarea( $config['report']['intro'] ); ?></textarea></label>
				</div>
				<div class="ac-section-picker">
					<h3><?php esc_html_e( 'Visible report sections', 'assesscraft' ); ?></h3>
					<?php
					$report_sections = array(
						'profile'         => __( 'Result profile', 'assesscraft' ),
						'overall'         => __( 'Overall score', 'assesscraft' ),
						'stage_scores'    => __( 'Stage scores', 'assesscraft' ),
						'interpretations' => __( 'Score interpretation', 'assesscraft' ),
						'recommendation'  => __( 'Recommended next step', 'assesscraft' ),
						'cta'             => __( 'Consultation call to action', 'assesscraft' ),
						'restart'         => __( 'Start-over button', 'assesscraft' ),
					);
					foreach ( $report_sections as $section_key => $section_label ) {
						printf( '<label><input type="checkbox" name="assesscraft_report_sections[]" value="%s"%s> %s</label>', esc_attr( $section_key ), checked( in_array( $section_key, $config['report']['sections'], true ), true, false ), esc_html( $section_label ) );
					}
					?>
				</div>
				<div class="ac-form-grid ac-cta-settings">
					<label class="ac-field"><span><?php esc_html_e( 'CTA heading', 'assesscraft' ); ?></span><input name="assesscraft_cta_heading" value="<?php echo esc_attr( $config['report']['cta_heading'] ); ?>"></label>
					<label class="ac-field"><span><?php esc_html_e( 'CTA button', 'assesscraft' ); ?></span><input name="assesscraft_cta_label" value="<?php echo esc_attr( $config['report']['cta_label'] ); ?>"></label>
					<label class="ac-field ac-field-wide"><span><?php esc_html_e( 'CTA description', 'assesscraft' ); ?></span><textarea rows="3" name="assesscraft_cta_text"><?php echo esc_textarea( $config['report']['cta_text'] ); ?></textarea></label>
				</div>
			</section>

			<section class="ac-panel" data-panel="lead-form">
				<div class="ac-panel-heading"><div><span class="ac-eyebrow"><?php esc_html_e( 'Opt-in conversion', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Consultation lead form', 'assesscraft' ); ?></h2></div></div>
				<label class="ac-enable-card"><input type="checkbox" name="assesscraft_lead_enabled" value="1" <?php checked( ! empty( $config['lead_form']['enabled'] ) ); ?>><span><strong><?php esc_html_e( 'Enable consultation requests', 'assesscraft' ); ?></strong><small><?php esc_html_e( 'Results are sent only when the visitor submits this form.', 'assesscraft' ); ?></small></span></label>
				<div class="ac-form-grid ac-lead-settings">
					<label class="ac-field"><span><?php esc_html_e( 'Recipient email', 'assesscraft' ); ?></span><input type="email" name="assesscraft_lead_recipient" value="<?php echo esc_attr( $config['lead_form']['recipient'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"></label>
					<label class="ac-field"><span><?php esc_html_e( 'Email subject', 'assesscraft' ); ?></span><input name="assesscraft_lead_subject" value="<?php echo esc_attr( $config['lead_form']['subject'] ); ?>"></label>
					<label class="ac-field ac-field-wide"><span><?php esc_html_e( 'Consent label', 'assesscraft' ); ?></span><textarea rows="2" name="assesscraft_consent_label"><?php echo esc_textarea( $config['lead_form']['consent_label'] ); ?></textarea></label>
					<label class="ac-field ac-field-wide"><span><?php esc_html_e( 'Success message', 'assesscraft' ); ?></span><textarea rows="2" name="assesscraft_success_message"><?php echo esc_textarea( $config['lead_form']['success_message'] ); ?></textarea></label>
				</div>
				<div class="ac-privacy-note"><span class="dashicons dashicons-lock"></span><p><strong><?php esc_html_e( 'Privacy-first default', 'assesscraft' ); ?></strong><br><?php esc_html_e( 'Completing an assessment never sends or stores a visitor’s result. Transmission occurs only after explicit consent and form submission.', 'assesscraft' ); ?></p></div>
			</section>

			<section class="ac-panel" data-panel="design">
				<div class="ac-panel-heading"><div><span class="ac-eyebrow"><?php esc_html_e( 'Brand styling', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Design', 'assesscraft' ); ?></h2></div><p><?php esc_html_e( 'Match the assessment to the website’s visual identity.', 'assesscraft' ); ?></p></div>
				<div class="ac-design-layout">
					<div class="ac-design-controls">
						<?php
						$colors = array( 'primary' => __( 'Primary / dark', 'assesscraft' ), 'accent' => __( 'Accent', 'assesscraft' ), 'background' => __( 'Page background', 'assesscraft' ), 'surface' => __( 'Card surface', 'assesscraft' ), 'text' => __( 'Main text', 'assesscraft' ), 'muted' => __( 'Secondary text', 'assesscraft' ), 'button_text' => __( 'Button text', 'assesscraft' ) );
						foreach ( $colors as $key => $label ) {
							printf( '<label class="ac-color-field"><span>%s</span><i class="ac-color-swatch" style="background:%s" aria-hidden="true"></i><input class="ac-design-color-code" name="assesscraft_design_%s" value="%s" data-design="%s" maxlength="7" spellcheck="false" aria-label="%s"></label>', esc_html( $label ), esc_attr( $config['design'][ $key ] ), esc_attr( $key ), esc_attr( strtoupper( $config['design'][ $key ] ) ), esc_attr( $key ), esc_attr( sprintf( __( '%s hexadecimal color', 'assesscraft' ), $label ) ) );
						}
						?>
						<label class="ac-field"><span><?php esc_html_e( 'Typography', 'assesscraft' ); ?></span><select name="assesscraft_design_font" data-design="font"><option value="system" <?php selected( $config['design']['font'], 'system' ); ?>><?php esc_html_e( 'Modern system font', 'assesscraft' ); ?></option><option value="serif" <?php selected( $config['design']['font'], 'serif' ); ?>><?php esc_html_e( 'Editorial serif', 'assesscraft' ); ?></option></select></label>
						<label class="ac-field"><span><?php esc_html_e( 'Corner radius', 'assesscraft' ); ?>: <output data-output="radius"><?php echo absint( $config['design']['radius'] ); ?>px</output></span><input type="range" min="0" max="24" name="assesscraft_design_radius" value="<?php echo absint( $config['design']['radius'] ); ?>" data-design="radius"></label>
						<label class="ac-field"><span><?php esc_html_e( 'Maximum width', 'assesscraft' ); ?>: <output data-output="width"><?php echo absint( $config['design']['width'] ); ?>px</output></span><input type="range" min="520" max="1200" step="20" name="assesscraft_design_width" value="<?php echo absint( $config['design']['width'] ); ?>" data-design="width"></label>
					</div>
					<div class="ac-design-preview" id="ac-design-preview"><span><?php esc_html_e( 'Live preview', 'assesscraft' ); ?></span><article><small><?php esc_html_e( 'ASSESSMENT COMPLETE', 'assesscraft' ); ?></small><h3><?php esc_html_e( 'Your assessment report', 'assesscraft' ); ?></h3><p><?php esc_html_e( 'Preview how headings, supporting text, cards, and actions work together.', 'assesscraft' ); ?></p><div><strong>73%</strong><em><?php esc_html_e( 'Established', 'assesscraft' ); ?></em></div><button type="button"><?php esc_html_e( 'Primary action', 'assesscraft' ); ?></button></article></div>
				</div>
			</section>

			<section class="ac-panel" data-panel="publish">
				<div class="ac-panel-heading"><div><span class="ac-eyebrow"><?php esc_html_e( 'Place your assessment', 'assesscraft' ); ?></span><h2><?php esc_html_e( 'Publish', 'assesscraft' ); ?></h2></div><p><?php esc_html_e( 'Use the same assessment anywhere in WordPress.', 'assesscraft' ); ?></p></div>
				<div class="ac-publish-grid">
					<article><span class="dashicons dashicons-shortcode"></span><h3><?php esc_html_e( 'Shortcode', 'assesscraft' ); ?></h3><p><?php esc_html_e( 'Works in shortcode widgets, classic content, and most page builders.', 'assesscraft' ); ?></p><code>[assesscraft id=&quot;<?php echo absint( $post->ID ); ?>&quot;]</code></article>
					<article><span class="dashicons dashicons-block-default"></span><h3><?php esc_html_e( 'Gutenberg', 'assesscraft' ); ?></h3><p><?php esc_html_e( 'Add the AssessCraft Assessment block and select this assessment.', 'assesscraft' ); ?></p><strong><?php esc_html_e( 'Block available', 'assesscraft' ); ?></strong></article>
					<article><span class="dashicons dashicons-layout"></span><h3><?php esc_html_e( 'Elementor', 'assesscraft' ); ?></h3><p><?php esc_html_e( 'Search for the AssessCraft Assessment widget in Elementor.', 'assesscraft' ); ?></p><strong><?php echo did_action( 'elementor/loaded' ) ? esc_html__( 'Elementor detected', 'assesscraft' ) : esc_html__( 'Available when Elementor is active', 'assesscraft' ); ?></strong></article>
				</div>
				<p class="ac-publish-note"><span class="dashicons dashicons-visibility"></span><?php esc_html_e( 'Draft assessments are visible to editors for preview but remain hidden from public visitors until published.', 'assesscraft' ); ?></p>
			</section>

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
		$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_export&assessment_id=' . absint( $post->ID ) ), 'assesscraft_export' );
		echo '<p><a class="button button-secondary" href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export JSON', 'assesscraft' ) . '</a></p>';
		$duplicate_url = wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_duplicate&assessment_id=' . absint( $post->ID ) ), 'assesscraft_duplicate' );
		echo '<p><a class="button button-secondary" href="' . esc_url( $duplicate_url ) . '">' . esc_html__( 'Duplicate Assessment', 'assesscraft' ) . '</a></p>';
		?>
		<details class="ac-save-template-box">
			<summary><?php esc_html_e( 'Save as reusable template', 'assesscraft' ); ?></summary>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="assesscraft_save_template">
				<input type="hidden" name="assessment_id" value="<?php echo absint( $post->ID ); ?>">
				<?php wp_nonce_field( 'assesscraft_save_template' ); ?>
				<label><span><?php esc_html_e( 'Template name', 'assesscraft' ); ?></span><input name="template_name" value="<?php echo esc_attr( get_the_title( $post ) ); ?>" required></label>
				<label><span><?php esc_html_e( 'Category', 'assesscraft' ); ?></span><input name="template_category" value="<?php esc_attr_e( 'Custom', 'assesscraft' ); ?>"></label>
				<label><span><?php esc_html_e( 'Version', 'assesscraft' ); ?></span><input name="template_version" value="1.0.0"></label>
				<label><span><?php esc_html_e( 'Description', 'assesscraft' ); ?></span><textarea name="template_description" rows="3"></textarea></label>
				<p class="description"><?php esc_html_e( 'Save or update the assessment first so the template includes your latest changes.', 'assesscraft' ); ?></p>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Save Template', 'assesscraft' ); ?></button>
			</form>
		</details>
		<?php
	}

	public function row_actions( array $actions, WP_Post $post ): array {
		if ( AssessCraft_Post_Type::TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}
		$actions['assesscraft_duplicate'] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_duplicate&assessment_id=' . absint( $post->ID ) ), 'assesscraft_duplicate' ) ) . '">' . esc_html__( 'Duplicate', 'assesscraft' ) . '</a>';
		return $actions;
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
		$config['report']['heading']          = $this->posted_text( 'assesscraft_report_heading' );
		$config['report']['intro']            = $this->posted_textarea( 'assesscraft_report_intro' );
		$config['report']['cta_heading']      = $this->posted_text( 'assesscraft_cta_heading' );
		$config['report']['cta_label']        = $this->posted_text( 'assesscraft_cta_label' );
		$config['report']['cta_text']         = $this->posted_textarea( 'assesscraft_cta_text' );
		$allowed_sections = array( 'profile', 'overall', 'stage_scores', 'interpretations', 'recommendation', 'cta', 'restart' );
		$posted_sections = isset( $_POST['assesscraft_report_sections'] ) && is_array( $_POST['assesscraft_report_sections'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['assesscraft_report_sections'] ) ) : array();
		$config['report']['sections'] = array_values( array_intersect( $allowed_sections, $posted_sections ) );
		$config['lead_form']['enabled']         = ! empty( $_POST['assesscraft_lead_enabled'] );
		$config['lead_form']['recipient']       = isset( $_POST['assesscraft_lead_recipient'] ) ? sanitize_email( wp_unslash( $_POST['assesscraft_lead_recipient'] ) ) : '';
		$config['lead_form']['subject']         = $this->posted_text( 'assesscraft_lead_subject' );
		$config['lead_form']['consent_label']   = $this->posted_textarea( 'assesscraft_consent_label' );
		$config['lead_form']['success_message'] = $this->posted_textarea( 'assesscraft_success_message' );
		$config['design'] = AssessCraft_Schema::sanitize_design( array(
			'primary' => $this->posted_text( 'assesscraft_design_primary' ), 'accent' => $this->posted_text( 'assesscraft_design_accent' ),
			'background' => $this->posted_text( 'assesscraft_design_background' ), 'surface' => $this->posted_text( 'assesscraft_design_surface' ),
			'text' => $this->posted_text( 'assesscraft_design_text' ), 'muted' => $this->posted_text( 'assesscraft_design_muted' ),
			'button_text' => $this->posted_text( 'assesscraft_design_button_text' ), 'font' => $this->posted_text( 'assesscraft_design_font' ),
			'radius' => isset( $_POST['assesscraft_design_radius'] ) ? absint( $_POST['assesscraft_design_radius'] ) : 2,
			'width' => isset( $_POST['assesscraft_design_width'] ) ? absint( $_POST['assesscraft_design_width'] ) : 760,
		) );

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
