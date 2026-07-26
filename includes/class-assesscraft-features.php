<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Features {
	public const PLAN_FREE = 'free';
	public const PLAN_PRO = 'pro';

	private const MATRIX = array(
		'published_assessments' => array( 'free' => 1, 'pro' => -1 ),
		'profiles'              => array( 'free' => 3, 'pro' => -1 ),
		'standard_scoring'       => array( 'free' => true, 'pro' => true ),
		'weighted_scoring'       => array( 'free' => false, 'pro' => true ),
		'reverse_scoring'        => array( 'free' => false, 'pro' => true ),
		'standard_report'        => array( 'free' => true, 'pro' => true ),
		'consultation_email'     => array( 'free' => false, 'pro' => true ),
		'lead_storage'           => array( 'free' => true, 'pro' => true ),
		'lead_csv_export'        => array( 'free' => false, 'pro' => true ),
		'shortcode'              => array( 'free' => true, 'pro' => true ),
		'gutenberg'              => array( 'free' => true, 'pro' => true ),
		'elementor'              => array( 'free' => false, 'pro' => true ),
		'json_portability'       => array( 'free' => false, 'pro' => true ),
		'custom_templates'       => array( 'free' => false, 'pro' => true ),
		'advanced_design'        => array( 'free' => false, 'pro' => true ),
		'priority_support'       => array( 'free' => false, 'pro' => true ),
	);

	public static function plan(): string {
		$plan = sanitize_key( (string) apply_filters( 'assesscraft_current_plan', self::PLAN_FREE ) );
		return self::PLAN_PRO === $plan ? self::PLAN_PRO : self::PLAN_FREE;
	}

	public static function value( string $feature ) {
		$plan = self::plan();
		return self::MATRIX[ $feature ][ $plan ] ?? false;
	}

	public static function available( string $feature ): bool {
		// Alpha builds expose every feature while the commercial integration is tested.
		if ( ! self::enforcement_enabled() ) {
			return true;
		}
		$value = self::value( $feature );
		return true === $value || ( is_int( $value ) && 0 !== $value );
	}

	public static function limit( string $feature ): int {
		if ( ! self::enforcement_enabled() ) {
			return -1;
		}
		$value = self::value( $feature );
		return is_int( $value ) ? $value : ( $value ? -1 : 0 );
	}

	public static function matrix(): array {
		return self::MATRIX;
	}

	public static function is_pro(): bool {
		return self::PLAN_PRO === self::plan();
	}

	public static function upgrade_url(): string {
		return (string) apply_filters( 'assesscraft_pro_url', 'https://assesscraft.com/#pro-coming-soon' );
	}

	public static function enforcement_enabled(): bool {
		if ( defined( 'ASSESSCRAFT_COMMERCIAL_ENFORCEMENT' ) ) {
			return (bool) ASSESSCRAFT_COMMERCIAL_ENFORCEMENT;
		}

		return (bool) apply_filters( 'assesscraft_commercial_enforcement', true );
	}
}
