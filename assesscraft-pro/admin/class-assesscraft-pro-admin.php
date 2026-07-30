<?php

defined( 'ABSPATH' ) || exit;

final class AssessCraft_Pro_Admin {
	public const PAGE_SLUG = 'assesscraft-pro-license';

	private AssessCraft_Pro_License $license;

	public function __construct( AssessCraft_Pro_License $license ) {
		$this->license = $license;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ), 30 );
		add_action( 'admin_menu', array( $this, 'adjust_free_menu' ), 99 );
		add_action( 'admin_notices', array( $this, 'license_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_assesscraft_pro_activate_license', array( $this, 'activate_license' ) );
		add_action( 'admin_post_assesscraft_pro_deactivate_license', array( $this, 'deactivate_license' ) );
		add_action( 'admin_post_assesscraft_pro_refresh_license', array( $this, 'refresh_license' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ASSESSCRAFT_PRO_FILE ), array( $this, 'plugin_links' ) );
	}

	public static function url(): string {
		return admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=' . self::PAGE_SLUG );
	}

	public function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . AssessCraft_Post_Type::TYPE,
			__( 'AssessCraft Pro', 'assesscraft-pro' ),
			__( 'Pro License', 'assesscraft-pro' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	public function adjust_free_menu(): void {
		if ( AssessCraft_Pro_License::is_active() && class_exists( 'AssessCraft_Upgrade' ) ) {
			remove_submenu_page(
				'edit.php?post_type=' . AssessCraft_Post_Type::TYPE,
				AssessCraft_Upgrade::PAGE_SLUG
			);
		}
	}

	public function enqueue_assets(): void {
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( (string) $screen->id, self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'assesscraft-pro-admin',
			ASSESSCRAFT_PRO_URL . 'assets/admin.css',
			array(),
			ASSESSCRAFT_PRO_VERSION
		);
		wp_enqueue_script(
			'assesscraft-pro-admin',
			ASSESSCRAFT_PRO_URL . 'assets/admin.js',
			array(),
			ASSESSCRAFT_PRO_VERSION,
			true
		);
	}

	public function plugin_links( array $links ): array {
		array_unshift(
			$links,
			'<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'License', 'assesscraft-pro' ) . '</a>'
		);
		return $links;
	}

	public function license_notice(): void {
		if ( AssessCraft_Pro_License::is_active() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && false !== strpos( (string) $screen->id, self::PAGE_SLUG ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'AssessCraft Pro is installed but not licensed.', 'assesscraft-pro' ); ?></strong>
				<?php esc_html_e( 'Free remains fully operational. Connect a valid Pro license to unlock paid capabilities.', 'assesscraft-pro' ); ?>
				<a href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Manage license', 'assesscraft-pro' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function activate_license(): void {
		$this->authorize_request();
		check_admin_referer( 'assesscraft_pro_activate_license' );
		$key    = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );
		$result = $this->license->activate( $key );
		$this->redirect_with_result( $result );
	}

	public function deactivate_license(): void {
		$this->authorize_request();
		check_admin_referer( 'assesscraft_pro_deactivate_license' );
		$this->redirect_with_result( $this->license->deactivate() );
	}

	public function refresh_license(): void {
		$this->authorize_request();
		check_admin_referer( 'assesscraft_pro_refresh_license' );
		$this->redirect_with_result( $this->license->refresh() );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage the AssessCraft Pro license.', 'assesscraft-pro' ) );
		}

		$status       = AssessCraft_Pro_License::status();
		$is_active    = AssessCraft_Pro_License::is_active();
		$is_test      = AssessCraft_Pro_License::is_test_license();
		$masked_key   = AssessCraft_Pro_License::masked_key();
		$expires_at   = AssessCraft_Pro_License::expires_at();
		$last_checked = AssessCraft_Pro_License::last_checked();
		$message      = AssessCraft_Pro_License::message();
		$capabilities = $this->pro_capabilities();
		?>
		<div class="wrap ac-pro-page">
			<div class="ac-pro-hero">
				<div class="ac-pro-brandmark" aria-hidden="true">AC</div>
				<div class="ac-pro-hero-copy">
					<span class="ac-pro-eyebrow"><?php esc_html_e( 'Internal beta', 'assesscraft-pro' ); ?></span>
					<h1><?php esc_html_e( 'AssessCraft Pro', 'assesscraft-pro' ); ?></h1>
					<p><?php esc_html_e( 'Manage your license, confirm product readiness, and review the Pro capabilities enabled for this WordPress site.', 'assesscraft-pro' ); ?></p>
				</div>
				<div class="ac-pro-hero-status">
					<span class="ac-pro-status-dot <?php echo esc_attr( $is_active ? 'is-active' : 'is-locked' ); ?>" aria-hidden="true"></span>
					<div>
						<strong><?php echo $is_active ? esc_html__( 'Pro enabled', 'assesscraft-pro' ) : esc_html__( 'Pro locked', 'assesscraft-pro' ); ?></strong>
						<span><?php echo esc_html( $this->status_label( $status ) ); ?></span>
					</div>
				</div>
			</div>

			<?php $this->render_action_notice(); ?>

			<div class="ac-pro-summary-grid">
				<?php $this->summary_card( __( 'Plan', 'assesscraft-pro' ), $is_active ? __( 'Pro', 'assesscraft-pro' ) : __( 'Free', 'assesscraft-pro' ), $is_active ? __( 'Advanced limits active', 'assesscraft-pro' ) : __( 'Free limits remain active', 'assesscraft-pro' ) ); ?>
				<?php $this->summary_card( __( 'AssessCraft Free', 'assesscraft-pro' ), ASSESSCRAFT_VERSION, __( 'Required core plugin', 'assesscraft-pro' ) ); ?>
				<?php $this->summary_card( __( 'AssessCraft Pro', 'assesscraft-pro' ), ASSESSCRAFT_PRO_VERSION, __( 'Internal beta package', 'assesscraft-pro' ) ); ?>
				<?php $this->summary_card( __( 'Last checked', 'assesscraft-pro' ), $last_checked ? wp_date( get_option( 'date_format' ), $last_checked ) : __( 'Not yet', 'assesscraft-pro' ), $last_checked ? wp_date( get_option( 'time_format' ), $last_checked ) : __( 'Connect a key to begin', 'assesscraft-pro' ) ); ?>
			</div>

			<div class="ac-pro-layout">
				<section class="ac-pro-card ac-pro-license-card">
					<div class="ac-pro-card-heading">
						<div>
							<span class="ac-pro-section-kicker"><?php esc_html_e( 'License', 'assesscraft-pro' ); ?></span>
							<h2><?php esc_html_e( 'Activation and status', 'assesscraft-pro' ); ?></h2>
						</div>
						<span class="ac-pro-status-badge <?php echo esc_attr( $this->status_class( $status ) ); ?>"><?php echo esc_html( $this->status_label( $status ) ); ?></span>
					</div>

					<div class="ac-pro-detail-list">
						<div><span><?php esc_html_e( 'License type', 'assesscraft-pro' ); ?></span><strong><?php echo '' === $masked_key ? esc_html__( 'Not connected', 'assesscraft-pro' ) : ( $is_test ? esc_html__( 'Internal testing key', 'assesscraft-pro' ) : esc_html__( 'Commercial / remote', 'assesscraft-pro' ) ); ?></strong></div>
						<div><span><?php esc_html_e( 'Connected key', 'assesscraft-pro' ); ?></span><strong class="ac-pro-key-value"><?php echo '' !== $masked_key ? esc_html( $masked_key ) : esc_html__( 'None', 'assesscraft-pro' ); ?></strong></div>
						<div><span><?php esc_html_e( 'Expires', 'assesscraft-pro' ); ?></span><strong><?php echo '' !== $expires_at ? esc_html( $expires_at ) : esc_html__( 'No date provided', 'assesscraft-pro' ); ?></strong></div>
					</div>

					<?php if ( defined( 'ASSESSCRAFT_PRO_DEV_MODE' ) && ASSESSCRAFT_PRO_DEV_MODE ) : ?>
						<div class="ac-pro-inline-message is-info"><span class="dashicons dashicons-admin-tools" aria-hidden="true"></span><p><?php esc_html_e( 'Development mode is enabled. Pro entitlements are active without a stored license.', 'assesscraft-pro' ); ?></p></div>
					<?php elseif ( $is_active ) : ?>
						<?php if ( $is_test ) : ?>
							<div class="ac-pro-inline-message is-warning"><span class="dashicons dashicons-warning" aria-hidden="true"></span><p><?php esc_html_e( 'This build is using an offline internal testing key. It is suitable for staging only and will be removed before the commercial 1.0.0 release.', 'assesscraft-pro' ); ?></p></div>
						<?php endif; ?>
						<div class="ac-pro-actions">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="assesscraft_pro_refresh_license">
								<?php wp_nonce_field( 'assesscraft_pro_refresh_license' ); ?>
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Check license now', 'assesscraft-pro' ); ?></button>
							</form>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="assesscraft_pro_deactivate_license">
								<?php wp_nonce_field( 'assesscraft_pro_deactivate_license' ); ?>
								<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Disconnect license', 'assesscraft-pro' ); ?></button>
							</form>
						</div>
					<?php else : ?>
						<form class="ac-pro-activation-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="assesscraft_pro_activate_license">
							<?php wp_nonce_field( 'assesscraft_pro_activate_license' ); ?>
							<label for="assesscraft-pro-license-key"><?php esc_html_e( 'License key', 'assesscraft-pro' ); ?></label>
							<div class="ac-pro-license-field">
								<input id="assesscraft-pro-license-key" type="password" name="license_key" autocomplete="off" placeholder="ACPRO-…" required>
								<button type="button" class="button ac-pro-toggle-key" data-show-label="<?php esc_attr_e( 'Show', 'assesscraft-pro' ); ?>" data-hide-label="<?php esc_attr_e( 'Hide', 'assesscraft-pro' ); ?>"><?php esc_html_e( 'Show', 'assesscraft-pro' ); ?></button>
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Activate Pro', 'assesscraft-pro' ); ?></button>
							</div>
							<p class="description"><?php esc_html_e( 'For this beta, the five supplied internal testing keys are validated locally. Future commercial keys will use the AssessCraft licensing service.', 'assesscraft-pro' ); ?></p>
						</form>
					<?php endif; ?>

					<?php if ( '' !== $message ) : ?>
						<div class="ac-pro-last-response"><span><?php esc_html_e( 'Last response', 'assesscraft-pro' ); ?></span><p><?php echo esc_html( $message ); ?></p></div>
					<?php endif; ?>
				</section>

				<aside class="ac-pro-card ac-pro-capabilities-card">
					<span class="ac-pro-section-kicker"><?php esc_html_e( 'Entitlement', 'assesscraft-pro' ); ?></span>
					<h2><?php esc_html_e( 'Pro capabilities', 'assesscraft-pro' ); ?></h2>
					<p><?php echo $is_active ? esc_html__( 'The shared AssessCraft data model is operating with Pro limits and capabilities.', 'assesscraft-pro' ) : esc_html__( 'Activate a valid key to switch the shared AssessCraft feature matrix from Free to Pro.', 'assesscraft-pro' ); ?></p>
					<ul class="ac-pro-capability-list">
						<?php foreach ( $capabilities as $capability ) : ?>
							<li><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span><?php echo esc_html( $capability ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				</aside>
			</div>

			<section class="ac-pro-card ac-pro-release-card">
				<div>
					<span class="ac-pro-section-kicker"><?php esc_html_e( 'Release package', 'assesscraft-pro' ); ?></span>
					<h2><?php esc_html_e( '0.9.0-beta.1 readiness', 'assesscraft-pro' ); ?></h2>
					<p><?php esc_html_e( 'This package is versioned and built for controlled staging validation. The commercial licensing backend and automatic update delivery remain intentionally deferred until the 1.0.0 release phase.', 'assesscraft-pro' ); ?></p>
				</div>
				<div class="ac-pro-release-badges">
					<span><span class="dashicons dashicons-shield-alt" aria-hidden="true"></span><?php esc_html_e( 'Package integrity checked', 'assesscraft-pro' ); ?></span>
					<span><span class="dashicons dashicons-wordpress-alt" aria-hidden="true"></span><?php esc_html_e( 'WordPress Plugin Check', 'assesscraft-pro' ); ?></span>
					<span><span class="dashicons dashicons-editor-code" aria-hidden="true"></span><?php esc_html_e( 'PHP 8.0–8.4', 'assesscraft-pro' ); ?></span>
				</div>
			</section>
		</div>
		<?php
	}

	private function authorize_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage the AssessCraft Pro license.', 'assesscraft-pro' ) );
		}
	}

	private function redirect_with_result( array $result ): void {
		$url = add_query_arg(
			array(
				'ac_pro_result'  => ! empty( $result['success'] ) ? 'success' : 'error',
				'ac_pro_message' => sanitize_text_field( (string) ( $result['message'] ?? '' ) ),
			),
			self::url()
		);
		wp_safe_redirect( $url );
		exit;
	}

	private function render_action_notice(): void {
		$result = sanitize_key( (string) ( $_GET['ac_pro_result'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $result, array( 'success', 'error' ), true ) ) {
			return;
		}

		$message = sanitize_text_field( wp_unslash( (string) ( $_GET['ac_pro_message'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$class   = 'success' === $result ? 'notice-success' : 'notice-error';
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	}

	private function summary_card( string $label, string $value, string $meta ): void {
		?>
		<div class="ac-pro-summary-card">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( $value ); ?></strong>
			<small><?php echo esc_html( $meta ); ?></small>
		</div>
		<?php
	}

	private function status_label( string $status ): string {
		$labels = array(
			'active'      => __( 'Active', 'assesscraft-pro' ),
			'development' => __( 'Development mode', 'assesscraft-pro' ),
			'expired'     => __( 'Expired', 'assesscraft-pro' ),
			'invalid'     => __( 'Invalid', 'assesscraft-pro' ),
			'inactive'    => __( 'Inactive', 'assesscraft-pro' ),
		);
		return $labels[ $status ] ?? $labels['inactive'];
	}

	private function status_class( string $status ): string {
		return in_array( $status, array( 'active', 'development', 'expired', 'invalid' ), true ) ? 'is-' . $status : 'is-inactive';
	}

	private function pro_capabilities(): array {
		return array(
			__( 'Unlimited published assessments and profiles', 'assesscraft-pro' ),
			__( 'Weighted and reverse scoring', 'assesscraft-pro' ),
			__( 'Elementor assessment widget', 'assesscraft-pro' ),
			__( 'JSON import and export', 'assesscraft-pro' ),
			__( 'Custom and premium templates', 'assesscraft-pro' ),
			__( 'CSV lead export and email notifications', 'assesscraft-pro' ),
			__( 'Advanced design controls and configurable retention', 'assesscraft-pro' ),
		);
	}
}
