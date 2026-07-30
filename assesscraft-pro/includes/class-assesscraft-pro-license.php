<?php

defined( 'ABSPATH' ) || exit;

final class AssessCraft_Pro_License {
	public const CRON_HOOK = 'assesscraft_pro_daily_license_check';

	private const OPTION_KEY          = 'assesscraft_pro_license_key';
	private const OPTION_STATUS       = 'assesscraft_pro_license_status';
	private const OPTION_EXPIRES      = 'assesscraft_pro_license_expires_at';
	private const OPTION_LAST_CHECKED = 'assesscraft_pro_license_last_checked';
	private const OPTION_MESSAGE      = 'assesscraft_pro_license_message';
	private const OPTION_TYPE         = 'assesscraft_pro_license_type';

	private const STATUS_ACTIVE   = 'active';
	private const STATUS_EXPIRED  = 'expired';
	private const STATUS_INACTIVE = 'inactive';
	private const STATUS_INVALID  = 'invalid';

	private const TYPE_REMOTE = 'remote';
	private const TYPE_TEST   = 'test';

	/**
	 * Internal beta keys are stored only as hashes and must be removed before 1.0.0.
	 *
	 * @var string[]
	 */
	private const TEST_KEY_HASHES = array(
		'90dc9df5a5f482e01d8f6bec33dadc58e05f9eeffd97130e4fa87f80cd7e5310',
		'129ed2250a7062309930c9aa15fc969af0710488fd59cb9dbb4bfbf9ae8b9fbb',
		'31d4b5025298395b38d9153ff4153a04c9f82b4088ef37c3d7984da45fcba8f5',
		'beddcbff518d8e27ec05d6d3f2fdcfa3f32cfd29efb800d95a9455030ac565f5',
		'c23b74dbe0053e4adc5e2c8dda8c2a95e6994396e15271fbcf7114a66a3977dd',
	);

	public function register(): void {
		add_action( self::CRON_HOOK, array( $this, 'refresh' ) );
	}

	public static function is_active(): bool {
		$forced = apply_filters( 'assesscraft_pro_license_active', null );
		if ( is_bool( $forced ) ) {
			return $forced;
		}

		if ( defined( 'ASSESSCRAFT_PRO_DEV_MODE' ) && ASSESSCRAFT_PRO_DEV_MODE ) {
			return true;
		}

		if ( self::STATUS_ACTIVE !== self::status() ) {
			return false;
		}

		$expires_at = self::expires_at();
		if ( '' === $expires_at ) {
			return true;
		}

		$expiry = strtotime( $expires_at );
		return false !== $expiry && $expiry > time();
	}

	public static function status(): string {
		if ( defined( 'ASSESSCRAFT_PRO_DEV_MODE' ) && ASSESSCRAFT_PRO_DEV_MODE ) {
			return 'development';
		}

		$status  = sanitize_key( (string) get_option( self::OPTION_STATUS, self::STATUS_INACTIVE ) );
		$allowed = array( self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_INACTIVE, self::STATUS_INVALID );
		return in_array( $status, $allowed, true ) ? $status : self::STATUS_INACTIVE;
	}

	public static function license_type(): string {
		$type    = sanitize_key( (string) get_option( self::OPTION_TYPE, '' ) );
		$allowed = array( self::TYPE_REMOTE, self::TYPE_TEST );
		return in_array( $type, $allowed, true ) ? $type : '';
	}

	public static function is_test_license(): bool {
		return self::TYPE_TEST === self::license_type();
	}

	public static function key(): string {
		return sanitize_text_field( (string) get_option( self::OPTION_KEY, '' ) );
	}

	public static function masked_key(): string {
		$key = self::key();
		if ( '' === $key ) {
			return '';
		}

		$visible = substr( $key, -4 );
		return str_repeat( '•', max( 4, strlen( $key ) - 4 ) ) . $visible;
	}

	public static function expires_at(): string {
		return sanitize_text_field( (string) get_option( self::OPTION_EXPIRES, '' ) );
	}

	public static function last_checked(): int {
		return absint( get_option( self::OPTION_LAST_CHECKED, 0 ) );
	}

	public static function message(): string {
		return sanitize_text_field( (string) get_option( self::OPTION_MESSAGE, '' ) );
	}

	public function activate( string $license_key ): array {
		$license_key = strtoupper( sanitize_text_field( $license_key ) );
		if ( '' === $license_key ) {
			return $this->store_result( self::STATUS_INVALID, '', __( 'Enter a license key before activating.', 'assesscraft-pro' ), false );
		}

		if ( self::is_test_key( $license_key ) ) {
			return $this->store_result(
				self::STATUS_ACTIVE,
				'',
				__( 'Internal testing license activated. No licensing server was contacted.', 'assesscraft-pro' ),
				true,
				$license_key,
				self::TYPE_TEST
			);
		}

		$response = $this->request(
			'activate',
			array(
				'license_key' => $license_key,
			)
		);

		if ( ! $response['success'] ) {
			return $this->store_result( self::STATUS_INVALID, '', $response['message'], false, $license_key, self::TYPE_REMOTE );
		}

		$status     = self::normalize_remote_status( $response['status'] );
		$expires_at = sanitize_text_field( $response['expires_at'] );
		$is_active  = self::STATUS_ACTIVE === $status;

		return $this->store_result( $status, $expires_at, $response['message'], $is_active, $license_key, self::TYPE_REMOTE );
	}

	public function deactivate(): array {
		$key = self::key();
		if ( '' !== $key && ! self::is_test_license() ) {
			$response = $this->request(
				'deactivate',
				array(
					'license_key' => $key,
				)
			);

			if ( ! $response['success'] ) {
				update_option( self::OPTION_LAST_CHECKED, time(), false );
				update_option( self::OPTION_MESSAGE, $response['message'], false );
				return array(
					'success' => false,
					'message' => $response['message'],
				);
			}
		}

		$this->clear_local_license();

		return array(
			'success' => true,
			'message' => __( 'License disconnected from this site.', 'assesscraft-pro' ),
		);
	}

	public function refresh(): array {
		$key = self::key();
		if ( '' === $key ) {
			return $this->store_result( self::STATUS_INACTIVE, '', __( 'No license key is connected.', 'assesscraft-pro' ), false );
		}

		if ( self::is_test_license() ) {
			$is_valid = self::is_test_key( $key );
			return $this->store_result(
				$is_valid ? self::STATUS_ACTIVE : self::STATUS_INVALID,
				'',
				$is_valid
					? __( 'Internal testing license verified locally.', 'assesscraft-pro' )
					: __( 'This internal testing license is no longer valid.', 'assesscraft-pro' ),
				$is_valid,
				$key,
				self::TYPE_TEST
			);
		}

		$response = $this->request(
			'check',
			array(
				'license_key' => $key,
			)
		);

		if ( ! $response['success'] ) {
			update_option( self::OPTION_LAST_CHECKED, time(), false );
			update_option( self::OPTION_MESSAGE, $response['message'], false );
			return array(
				'success' => false,
				'message' => $response['message'],
			);
		}

		$status     = self::normalize_remote_status( $response['status'] );
		$expires_at = sanitize_text_field( $response['expires_at'] );
		$is_active  = self::STATUS_ACTIVE === $status;

		return $this->store_result( $status, $expires_at, $response['message'], $is_active, $key, self::TYPE_REMOTE );
	}

	private function request( string $action, array $additional_body ): array {
		$endpoint = $this->endpoint( $action );
		if ( '' === $endpoint ) {
			return array(
				'success'    => false,
				'status'     => self::STATUS_INVALID,
				'expires_at' => '',
				'message'    => __( 'The AssessCraft licensing service is not configured yet.', 'assesscraft-pro' ),
			);
		}

		$body = array_merge(
			array(
				'site_url'     => home_url( '/' ),
				'pro_version'  => ASSESSCRAFT_PRO_VERSION,
				'core_version' => defined( 'ASSESSCRAFT_VERSION' ) ? ASSESSCRAFT_VERSION : '',
				'product'      => 'assesscraft-pro',
			),
			$additional_body
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'    => false,
				'status'     => self::STATUS_INVALID,
				'expires_at' => '',
				'message'    => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$success = $code >= 200 && $code < 300 && ! empty( $data['success'] );
		$message = sanitize_text_field( (string) ( $data['message'] ?? '' ) );
		if ( '' === $message ) {
			$message = $success
				? __( 'License verified.', 'assesscraft-pro' )
				: __( 'The licensing service could not verify this key.', 'assesscraft-pro' );
		}

		return array(
			'success'    => $success,
			'status'     => sanitize_key( (string) ( $data['status'] ?? self::STATUS_INVALID ) ),
			'expires_at' => sanitize_text_field( (string) ( $data['expires_at'] ?? '' ) ),
			'message'    => $message,
		);
	}

	private function endpoint( string $action ): string {
		$base = (string) apply_filters( 'assesscraft_pro_license_api_url', 'https://assesscraft.com/wp-json/assesscraft-license/v1' );
		$base = untrailingslashit( esc_url_raw( $base ) );
		if ( '' === $base ) {
			return '';
		}

		return $base . '/' . sanitize_key( $action );
	}

	private function store_result( string $status, string $expires_at, string $message, bool $success, string $license_key = '', string $license_type = '' ): array {
		if ( '' !== $license_key ) {
			update_option( self::OPTION_KEY, $license_key, false );
		}
		if ( '' !== $license_type ) {
			update_option( self::OPTION_TYPE, $license_type, false );
		}
		update_option( self::OPTION_STATUS, $status, false );
		update_option( self::OPTION_EXPIRES, $expires_at, false );
		update_option( self::OPTION_LAST_CHECKED, time(), false );
		update_option( self::OPTION_MESSAGE, sanitize_text_field( $message ), false );

		return array(
			'success' => $success,
			'message' => $message,
		);
	}

	private function clear_local_license(): void {
		delete_option( self::OPTION_KEY );
		delete_option( self::OPTION_EXPIRES );
		delete_option( self::OPTION_TYPE );
		update_option( self::OPTION_STATUS, self::STATUS_INACTIVE, false );
		update_option( self::OPTION_LAST_CHECKED, time(), false );
		update_option( self::OPTION_MESSAGE, __( 'License disconnected from this site.', 'assesscraft-pro' ), false );
	}

	private static function is_test_key( string $license_key ): bool {
		$hash = hash( 'sha256', strtoupper( trim( $license_key ) ) );
		foreach ( self::TEST_KEY_HASHES as $allowed_hash ) {
			if ( hash_equals( $allowed_hash, $hash ) ) {
				return true;
			}
		}
		return false;
	}

	private static function normalize_remote_status( string $status ): string {
		$status = sanitize_key( $status );
		if ( in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_INACTIVE ), true ) ) {
			return $status;
		}

		return self::STATUS_INVALID;
	}
}
