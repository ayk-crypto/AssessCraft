<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Upgrade {
	public const PAGE_SLUG = 'assesscraft-upgrade';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
		add_action( 'current_screen', array( $this, 'plan_indicator' ) );
		add_action( 'admin_head-plugins.php', array( $this, 'plugin_link_style' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ASSESSCRAFT_FILE ), array( $this, 'plugin_links' ) );
	}

	public static function url(): string {
		return admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=' . self::PAGE_SLUG );
	}

	public function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . AssessCraft_Post_Type::TYPE,
			__( 'AssessCraft Pro — Coming Soon', 'assesscraft' ),
			__( 'Upgrade to Pro', 'assesscraft' ),
			'edit_posts',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	public function plan_indicator( WP_Screen $screen ): void {
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) );
		if ( AssessCraft_Post_Type::TYPE !== $screen->post_type || self::PAGE_SLUG === $page ) {
			return;
		}
		add_action(
			'admin_notices',
			static function (): void {
				?>
				<div class="ac-plan-indicator">
					<span><strong><?php esc_html_e( 'Current plan: Free', 'assesscraft' ); ?></strong> <?php esc_html_e( 'Core assessment building and WordPress lead storage are included.', 'assesscraft' ); ?></span>
					<a href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Explore Pro — Coming Soon', 'assesscraft' ); ?></a>
				</div>
				<?php
			}
		);
	}

	public function plugin_links( array $links ): array {
		$custom = array(
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE ) ) . '">' . esc_html__( 'Assessments', 'assesscraft' ) . '</a>',
			'<a href="' . esc_url( self::url() ) . '" class="ac-plugin-upgrade-link">' . esc_html__( 'Explore Pro', 'assesscraft' ) . '</a>',
		);
		return array_merge( $custom, $links );
	}

	public function plugin_link_style(): void {
		echo '<style>.ac-plugin-upgrade-link{color:#8a670e!important;font-weight:600}</style>';
	}

	public function render(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'assesscraft' ) );
		}
		$comparison = array(
			array( __( 'Published assessments', 'assesscraft' ), __( '1', 'assesscraft' ), __( 'Unlimited', 'assesscraft' ) ),
			array( __( 'Stages and questions', 'assesscraft' ), __( 'Unlimited', 'assesscraft' ), __( 'Unlimited', 'assesscraft' ) ),
			array( __( 'Standard scoring and bands', 'assesscraft' ), __( 'Yes', 'assesscraft' ), __( 'Yes', 'assesscraft' ) ),
			array( __( 'Weighted and reverse scoring', 'assesscraft' ), '—', __( 'Yes', 'assesscraft' ) ),
			array( __( 'Standard result report', 'assesscraft' ), __( 'Yes', 'assesscraft' ), __( 'Yes', 'assesscraft' ) ),
			array( __( 'Conditional profiles', 'assesscraft' ), __( '3 profiles', 'assesscraft' ), __( 'Unlimited', 'assesscraft' ) ),
			array( __( 'Shortcode and Gutenberg', 'assesscraft' ), __( 'Yes', 'assesscraft' ), __( 'Yes', 'assesscraft' ) ),
			array( __( 'Elementor widget', 'assesscraft' ), '—', __( 'Yes', 'assesscraft' ) ),
			array( __( 'Email consultation notifications', 'assesscraft' ), '—', __( 'Yes', 'assesscraft' ) ),
			array( __( 'WordPress lead storage/dashboard', 'assesscraft' ), __( 'Yes', 'assesscraft' ), __( 'Yes', 'assesscraft' ) ),
			array( __( 'CSV lead export and retention', 'assesscraft' ), '—', __( 'Yes', 'assesscraft' ) ),
			array( __( 'Bundled starter template', 'assesscraft' ), __( '1', 'assesscraft' ), __( 'All', 'assesscraft' ) ),
			array( __( 'JSON import/export', 'assesscraft' ), '—', __( 'Yes', 'assesscraft' ) ),
			array( __( 'Custom reusable templates', 'assesscraft' ), '—', __( 'Yes', 'assesscraft' ) ),
			array( __( 'Advanced design controls', 'assesscraft' ), __( 'Limited', 'assesscraft' ), __( 'Complete', 'assesscraft' ) ),
			array( __( 'Support', 'assesscraft' ), __( 'Documentation', 'assesscraft' ), __( 'Priority support', 'assesscraft' ) ),
		);
		?>
		<div class="wrap ac-upgrade-page">
			<hr class="wp-header-end">
			<header class="ac-upgrade-hero">
				<span class="ac-plan-pill"><?php esc_html_e( 'Your current plan: Free', 'assesscraft' ); ?></span>
				<h1><?php esc_html_e( 'Unlock more with AssessCraft Pro', 'assesscraft' ); ?></h1>
				<p><?php esc_html_e( 'Free gives you everything needed to publish a focused assessment. Pro is being built for teams that need more scale, automation, and control.', 'assesscraft' ); ?></p>
				<span class="ac-pro-launch-status"><span class="dashicons dashicons-clock" aria-hidden="true"></span><?php esc_html_e( 'AssessCraft Pro is coming soon. No license or payment is required today.', 'assesscraft' ); ?></span>
			</header>
			<div class="ac-comparison-wrap">
				<table class="ac-comparison-table">
					<caption class="screen-reader-text"><?php esc_html_e( 'Compare AssessCraft Free and Pro', 'assesscraft' ); ?></caption>
					<thead><tr><th scope="col"><?php esc_html_e( 'Capability', 'assesscraft' ); ?></th><th scope="col"><?php esc_html_e( 'Free', 'assesscraft' ); ?><small><?php esc_html_e( 'Current plan', 'assesscraft' ); ?></small></th><th scope="col"><?php esc_html_e( 'Pro', 'assesscraft' ); ?><small><?php esc_html_e( 'Coming soon', 'assesscraft' ); ?></small></th></tr></thead>
					<tbody><?php foreach ( $comparison as $row ) : ?><tr><th scope="row"><?php echo esc_html( $row[0] ); ?></th><?php for ( $index = 1; $index <= 2; $index++ ) : $unavailable = '—' === $row[ $index ]; ?><td class="<?php echo $unavailable ? 'is-unavailable' : ''; ?>"><?php if ( ! $unavailable && __( 'Yes', 'assesscraft' ) === $row[ $index ] ) : ?><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php endif; ?><?php echo esc_html( $row[ $index ] ); ?></td><?php endfor; ?></tr><?php endforeach; ?></tbody>
				</table>
			</div>
			<div class="ac-upgrade-actions"><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE ) ); ?>"><?php esc_html_e( 'Continue with Free', 'assesscraft' ); ?></a><button type="button" class="button button-primary" disabled><?php esc_html_e( 'Pro available soon', 'assesscraft' ); ?></button></div>
			<p class="ac-upgrade-reassurance"><span class="dashicons dashicons-shield" aria-hidden="true"></span><?php esc_html_e( 'Your Free assessments and stored requests will remain yours. Pro will be optional when it launches.', 'assesscraft' ); ?></p>
		</div>
		<?php
	}
}
