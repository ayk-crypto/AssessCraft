<?php

defined( 'ABSPATH' ) || exit;

final class AssessCraft_Pro_Dependencies {
	public const MINIMUM_CORE_VERSION = '0.18.2';

	private const CORE_PLUGIN  = 'assesscraft/assesscraft.php';
	private const DOWNLOAD_URL = 'https://github.com/ayk-crypto/AssessCraft/releases/download/v0.18.2/assesscraft-free-0.18.2.zip';

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'network_admin_notices', array( $this, 'notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ASSESSCRAFT_PRO_FILE ), array( $this, 'plugin_links' ) );
	}

	public static function satisfied(): bool {
		return defined( 'ASSESSCRAFT_VERSION' )
			&& class_exists( 'AssessCraft_Features' )
			&& version_compare( ASSESSCRAFT_VERSION, self::MINIMUM_CORE_VERSION, '>=' );
	}

	public static function state(): string {
		if ( self::satisfied() ) {
			return 'ready';
		}

		if ( defined( 'ASSESSCRAFT_VERSION' ) ) {
			return version_compare( ASSESSCRAFT_VERSION, self::MINIMUM_CORE_VERSION, '<' ) ? 'outdated' : 'unavailable';
		}

		$plugin_file = self::core_plugin_file();
		if ( '' === $plugin_file ) {
			return 'missing';
		}

		$version = self::installed_core_version( $plugin_file );
		if ( '' !== $version && version_compare( $version, self::MINIMUM_CORE_VERSION, '<' ) ) {
			return 'outdated';
		}

		return self::core_is_active( $plugin_file ) ? 'unavailable' : 'inactive';
	}

	public function enqueue_assets(): void {
		if ( self::satisfied() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		wp_enqueue_style(
			'assesscraft-pro-dependencies',
			ASSESSCRAFT_PRO_URL . 'assets/dependencies.css',
			array(),
			ASSESSCRAFT_PRO_VERSION
		);
	}

	public function plugin_links( array $links ): array {
		if ( self::satisfied() ) {
			return $links;
		}

		$state       = self::state();
		$plugin_file = self::core_plugin_file();
		$url         = 'inactive' === $state && '' !== $plugin_file
			? self::activation_url( $plugin_file )
			: self::setup_url( $state );

		array_unshift(
			$links,
			'<a class="ac-pro-setup-link" href="' . esc_url( $url ) . '">' . esc_html__( 'Complete setup', 'assesscraft-pro' ) . '</a>'
		);

		return $links;
	}

	public function notice(): void {
		if ( self::satisfied() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$state             = self::state();
		$plugin_file       = self::core_plugin_file();
		$installed_version = '' !== $plugin_file ? self::installed_core_version( $plugin_file ) : '';
		$content           = $this->notice_content( $state, $installed_version );
		?>
		<div class="notice notice-error ac-pro-dependency-notice">
			<div class="ac-pro-dependency-brand" aria-hidden="true">AC</div>
			<div class="ac-pro-dependency-content">
				<div class="ac-pro-dependency-heading">
					<div>
						<span class="ac-pro-dependency-kicker"><?php esc_html_e( 'AssessCraft Pro', 'assesscraft-pro' ); ?></span>
						<h2><?php esc_html_e( 'Complete setup to unlock Pro', 'assesscraft-pro' ); ?></h2>
					</div>
					<span class="ac-pro-dependency-status"><?php esc_html_e( 'Setup required', 'assesscraft-pro' ); ?></span>
				</div>
				<p><?php echo wp_kses_post( $content['message'] ); ?></p>
				<div class="ac-pro-dependency-actions">
					<?php if ( 'inactive' === $state && '' !== $plugin_file ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( self::activation_url( $plugin_file ) ); ?>"><?php esc_html_e( 'Activate AssessCraft Free', 'assesscraft-pro' ); ?></a>
					<?php else : ?>
						<a class="button button-primary" href="<?php echo esc_url( self::DOWNLOAD_URL ); ?>"><?php echo esc_html( $content['primary_label'] ); ?></a>
						<a class="button" href="<?php echo esc_url( self::upload_url() ); ?>"><?php esc_html_e( 'Upload plugin', 'assesscraft-pro' ); ?></a>
					<?php endif; ?>
					<a class="ac-pro-dependency-text-link" href="<?php echo esc_url( self::plugins_url() ); ?>"><?php esc_html_e( 'View installed plugins', 'assesscraft-pro' ); ?></a>
				</div>
				<p class="ac-pro-dependency-footnote"><?php esc_html_e( 'AssessCraft Pro is safely paused until the Free plugin is available. No assessment data is changed or removed.', 'assesscraft-pro' ); ?></p>
			</div>
		</div>
		<?php
	}

	private function notice_content( string $state, string $installed_version ): array {
		if ( 'inactive' === $state ) {
			return array(
				'message'       => esc_html__( 'AssessCraft Free is already installed, but it is not active. Activate it and Pro will start automatically—there is no need to reinstall Pro.', 'assesscraft-pro' ),
				'primary_label' => esc_html__( 'Activate AssessCraft Free', 'assesscraft-pro' ),
			);
		}

		if ( 'outdated' === $state ) {
			$message = '' !== $installed_version
				? sprintf(
					/* translators: 1: installed version, 2: required version. */
					esc_html__( 'AssessCraft Free %1$s is installed, but Pro requires version %2$s or newer. Download the current Free package, upload it as an update, and then return here.', 'assesscraft-pro' ),
					esc_html( $installed_version ),
					esc_html( self::MINIMUM_CORE_VERSION )
				)
				: sprintf(
					/* translators: %s: required version. */
					esc_html__( 'The installed AssessCraft Free version is not compatible. Pro requires version %s or newer.', 'assesscraft-pro' ),
					esc_html( self::MINIMUM_CORE_VERSION )
				);

			return array(
				'message'       => $message,
				'primary_label' => esc_html__( 'Download current Free version', 'assesscraft-pro' ),
			);
		}

		if ( 'unavailable' === $state ) {
			return array(
				'message'       => esc_html__( 'AssessCraft Free appears to be installed or active, but its required components did not load. Reinstall the current Free package or review the WordPress error log before retrying.', 'assesscraft-pro' ),
				'primary_label' => esc_html__( 'Download AssessCraft Free', 'assesscraft-pro' ),
			);
		}

		return array(
			'message'       => sprintf(
				/* translators: %s: required version. */
				esc_html__( 'AssessCraft Pro extends the Free plugin and cannot run by itself. Install and activate AssessCraft Free %s or newer, then Pro will start automatically.', 'assesscraft-pro' ),
				esc_html( self::MINIMUM_CORE_VERSION )
			),
			'primary_label' => esc_html__( 'Download AssessCraft Free', 'assesscraft-pro' ),
		);
	}

	private static function core_plugin_file(): string {
		self::load_plugin_functions();
		$plugins = get_plugins();

		if ( isset( $plugins[ self::CORE_PLUGIN ] ) ) {
			return self::CORE_PLUGIN;
		}

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$text_domain = sanitize_key( (string) ( $plugin_data['TextDomain'] ?? '' ) );
			$name        = (string) ( $plugin_data['Name'] ?? '' );
			if ( 'assesscraft' === $text_domain || 0 === strpos( $name, 'AssessCraft - Assessment' ) ) {
				return (string) $plugin_file;
			}
		}

		return '';
	}

	private static function installed_core_version( string $plugin_file ): string {
		self::load_plugin_functions();
		$plugins = get_plugins();
		return sanitize_text_field( (string) ( $plugins[ $plugin_file ]['Version'] ?? '' ) );
	}

	private static function core_is_active( string $plugin_file ): bool {
		self::load_plugin_functions();
		return is_plugin_active( $plugin_file ) || ( is_multisite() && is_plugin_active_for_network( $plugin_file ) );
	}

	private static function activation_url( string $plugin_file ): string {
		$network = is_network_admin();
		$url     = add_query_arg(
			array(
				'action'      => 'activate',
				'plugin'      => $plugin_file,
				'networkwide' => $network ? '1' : false,
			),
			$network ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' )
		);

		return wp_nonce_url( $url, 'activate-plugin_' . $plugin_file );
	}

	private static function setup_url( string $state ): string {
		return 'missing' === $state ? self::upload_url() : self::plugins_url();
	}

	private static function upload_url(): string {
		return is_network_admin() ? network_admin_url( 'plugin-install.php?tab=upload' ) : admin_url( 'plugin-install.php?tab=upload' );
	}

	private static function plugins_url(): string {
		return is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
	}

	private static function load_plugin_functions(): void {
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
