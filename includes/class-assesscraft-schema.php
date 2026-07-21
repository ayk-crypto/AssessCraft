<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Schema {
	public const VERSION = 1;

	public static function defaults(): array {
		return array(
			'schema_version' => self::VERSION,
			'overview' => array(
				'heading'     => '',
				'description' => '',
				'start_label' => __( 'Begin Assessment', 'assesscraft' ),
				'disclaimer'  => '',
			),
			'stages'   => array(),
			'scoring'  => array(
				'method' => 'weighted_percentage',
				'bands'  => array(),
			),
			'profiles' => array(),
			'report'   => array(
				'sections' => array( 'profile', 'scores', 'interpretations', 'strengths', 'concerns', 'recommendation', 'cta' ),
			),
			'lead_form' => array(
				'enabled'         => false,
				'store_responses' => false,
				'send_results'    => true,
			),
			'design' => array(
				'primary'    => '#1B2430',
				'accent'     => '#B08D2B',
				'background' => '#F6F4EE',
			),
		);
	}

	public static function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return self::defaults();
		}

		return self::sanitize_recursive( array_replace_recursive( self::defaults(), $value ) );
	}

	private static function sanitize_recursive( array $data ): array {
		$clean = array();
		foreach ( $data as $key => $value ) {
			$key = is_int( $key ) ? $key : sanitize_key( (string) $key );
			if ( is_array( $value ) ) {
				$clean[ $key ] = self::sanitize_recursive( $value );
			} elseif ( is_bool( $value ) || is_numeric( $value ) ) {
				$clean[ $key ] = $value;
			} else {
				$clean[ $key ] = sanitize_textarea_field( (string) $value );
			}
		}
		return $clean;
	}
}
