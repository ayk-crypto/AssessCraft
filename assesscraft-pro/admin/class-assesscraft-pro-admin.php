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
		if ( $screen && self::PAGE_SLUG === sanitize_key( (string) ( $_GET['page'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		$this->authorize_request( 'assesscraft_pro_activate_license' );
		$key    = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );
		$result = $this->license->activate( $key );
		$this->redirect_with_result( $result );
	}

	public function deactivate_license(): void {
		$this->authorize_request( 'assesscraft_pro_deactivate_license' );
		$this->redirect_with_result( $this->license->deactivate() );
	}

	public function refresh_license(): void {
		$this->authorize_request( 'assesscraft_pro_refresh_license' );
		$this->redirect_with_result( $this->license->refresh() );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage the AssessCraft Pro license.', 'assesscraft-pro' ) );
		}

		$status       = AssessCraft_Pro_License::status();
		$is_active    = AssessCraft_Pro_License::is_active();
		$masked_key   = AssessCraft_Pro_License::masked_key();
		$expires_at   = AssessCraft_Pro_License::expires_at();
		$last_checked = AssessCraft_Pro_License::last_checked();
		$message      = AssessCraft_Pro_License::message();
		$capabilities = $this->pro_capabilities();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AssessCraft Pro', 'assesscraft-pro' ); ?></h1>
			<p><?php esc_html_e( 'Manage the add-on license and confirm which Pro capabilities are available on this site.', 'assesscraft-pro' ); ?></p>

			<?php $this->render_action_notice(); ?>

			<div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:20px;max-width:1100px;margin-top:20px;">
				<section style="padding:24px;border:1px solid #dcdcde;border-radius:10px;background:#fff;">
					<h2 style="margin-top:0;"><?php esc_html_e( 'License status', 'assesscraft-pro' ); ?></h2>
					<table class="widefat striped" style="margin-bottom:20px;">
						<tbody>
							<tr><th scope="row"><?php esc_html_e( 'Status', 'assesscraft-pro' ); ?></th><td><strong><?php echo esc_html( $this->status_label( $status ) ); ?></strong></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'AssessCraft Free', 'assesscraft-pro' ); ?></th><td><?php echo esc_html( ASSESSCRAFT_VERSION ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'AssessCraft Pro', 'assesscraft-pro' ); ?></th><td><?php echo esc_html( ASSESSCRAFT_PRO_VERSION ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Connected key', 'assesscraft-pro' ); ?></th><td><?php echo '' !== $masked_key ? esc_html( $masked_key ) : esc_html__( 'None', 'assesscraft-pro' ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Expires', 'assesscraft-pro' ); ?></th><td><?php echo '' !== $expires_at ? esc_html( $expires_at ) : esc_html__( 'Not provided', 'assesscraft-pro' ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Last checked', 'assesscraft-pro' ); ?></th><td><?php echo $last_checked ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_checked ) ) : esc_html__( 'Not checked yet', 'assesscraft-pro' ); ?></td></tr>
						</tbody>
					</table>

					<?php if ( defined( 'ASSESSCRAFT_PRO_DEV_MODE' ) && ASSESSCRAFT_PRO_DEV_MODE ) : ?>
						<div class="notice notice-info inline"><p><?php esc_html_e( 'Development mode is enabled. Pro entitlements are active without contacting the licensing service.', 'assesscraft-pro' ); ?></p></div>
					<?php elseif ( $is_active ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
							<input type="hidden" name="action" value="assesscraft_pro_refresh_license">
							<?php wp_nonce_field( 'assesscraft_pro_refresh_license' ); ?>
							<button type="submit" class="button"><?php esc_html_e( 'Check license now', 'assesscraft-pro' ); ?></button>
						</form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;">
							<input type="hidden" name="action" value="assesscraft_pro_deactivate_license">
							<?php wp_nonce_field( 'assesscraft_pro_deactivate_license' ); ?>
							<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Disconnect license from this site', 'assesscraft-pro' ); ?></button>
						</form>
					<?php else : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="assesscraft_pro_activate_license">
							<?php wp_nonce_field( 'assesscraft_pro_activate_license' ); ?>
							<label for="assesscraft-pro-license-key"><strong><?php esc_html_e( 'License key', 'assesscraft-pro' ); ?></strong></label>
							<div style="display:flex;gap:8px;margin-top:8px;max-width:650px;">
								<input id="assesscraft-pro-license-key" class="regular-text" type="password" name="license_key" autocomplete="off" required>
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Activate license', 'assesscraft-pro' ); ?></button>
							</div>
							<p class="description"><?php esc_html_e( 'The key is verified against the AssessCraft licensing endpoint and bound to this WordPress site.', 'assesscraft-pro' ); ?></p>
						</form>
					<?php endif; ?>

					<?php if ( '' !== $message ) : ?>
						<p style="margin-bottom:0;"><strong><?php esc_html_e( 'Last response:', 'assesscraft-pro' ); ?></strong> <?php echo esc_html( $message ); ?></p>
					<?php endif; ?>
				</section>

				<aside style="padding:24px;border:1px solid #dcdcde;border-radius:10px;background:#fff;">
					<h2 style="margin-top:0;"><?php esc_html_e( 'Pro entitlement', 'assesscraft-pro' ); ?></h2>
					<p><strong><?php echo $is_active ? esc_html__( 'Active', 'assesscraft-pro' ) : esc_html__( 'Locked', 'assesscraft-pro' ); ?></strong></p>
					<p><?php echo $is_active ? esc_html__( 'The shared AssessCraft data model is operating with Pro limits and capabilities.', 'assesscraft-pro' ) : esc_html__( 'AssessCraft continues using Free limits until the license is active.', 'assesscraft-pro' ); ?></p>
					<ul style="list-style:disc;padding-left:20px;">
						<?php foreach ( $capabilities as $capability ) : ?>
							<li><?php echo esc_html( $capability ); ?></li>
						<?php endforeach; ?>
					</ul>
				</aside>
			</div>
		</div>
		<?php
	}

	private function authorize_request( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage the AssessCraft Pro license.', 'assesscraft-pro' ) );
		}
		check_admin_referer( $action );
	}

	private function redirect_with_result( array $result ): void {
		$url = add_query_arg(
			array(
				'ac_pro_result'  => ! empty( $result['success'] ) ? 'success' : 'error',
				'ac_pro_message' => rawurlencode( sanitize_text_field( (string) ( $result['message'] ?? '' ) ) ),
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

		$message = sanitize_text_field( rawurldecode( (string) ( $_GET['ac_pro_message'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$class   = 'success' === $result ? 'notice-success' : 'notice-error';
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
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
