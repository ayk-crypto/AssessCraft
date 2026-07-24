<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Schema {
	public const VERSION = 3;

	public static function migrate( array $config ): array {
		$version = absint( $config['schema_version'] ?? 0 );
		if ( $version < 1 ) {
			$config = self::migrate_to_1( $config );
			$version = 1;
		}
		if ( $version < 2 ) {
			$config = self::migrate_to_2( $config );
			$version = 2;
		}
		if ( $version < 3 ) {
			$config = self::migrate_to_3( $config );
		}
		$config['schema_version'] = self::VERSION;
		return self::sanitize( $config );
	}

	private static function migrate_to_1( array $config ): array {
		$config['schema_version'] = 1;
		return $config;
	}

	private static function migrate_to_2( array $config ): array {
		$config['lead_form'] = is_array( $config['lead_form'] ?? null ) ? $config['lead_form'] : array();
		if ( isset( $config['lead_form']['email_enabled'] ) && ! isset( $config['lead_form']['send_results'] ) ) {
			$config['lead_form']['send_results'] = (bool) $config['lead_form']['email_enabled'];
		}
		unset( $config['lead_form']['email_enabled'] );
		$config['lead_form']['send_results'] = isset( $config['lead_form']['send_results'] ) ? (bool) $config['lead_form']['send_results'] : true;
		$config['schema_version'] = 2;
		return $config;
	}

	private static function migrate_to_3( array $config ): array {
		$config['lead_form'] = is_array( $config['lead_form'] ?? null ) ? $config['lead_form'] : array();
		$legacy_messages = array(
			'Thank you. Your request and assessment summary have been sent.',
			'Thank you. Your request has been sent.',
		);
		if ( in_array( $config['lead_form']['success_message'] ?? '', $legacy_messages, true ) ) {
			$config['lead_form']['success_message'] = __( 'Thank you. Your consultation request has been received.', 'assesscraft' );
		}
		$config['schema_version'] = 3;
		return $config;
	}

	public static function defaults(): array {
		return array(
			'schema_version' => self::VERSION,
			'overview' => array(
				'heading'     => '',
				'description' => '',
				'start_label' => __( 'Begin Assessment', 'assesscraft' ),
				'estimated_time' => '',
				'disclaimer'  => '',
			),
			'stages'   => array(),
			'scoring'  => array(
				'method' => 'weighted_percentage',
				'bands'  => array(
					array( 'id' => 'strong', 'min' => 85, 'max' => 100, 'label' => __( 'Strong', 'assesscraft' ), 'color' => '#4E6B4A', 'interpretation' => '' ),
					array( 'id' => 'established', 'min' => 70, 'max' => 84.99, 'label' => __( 'Established', 'assesscraft' ), 'color' => '#6E7F6A', 'interpretation' => '' ),
					array( 'id' => 'developing', 'min' => 55, 'max' => 69.99, 'label' => __( 'Developing', 'assesscraft' ), 'color' => '#B08D2B', 'interpretation' => '' ),
					array( 'id' => 'constrained', 'min' => 40, 'max' => 54.99, 'label' => __( 'Constrained', 'assesscraft' ), 'color' => '#A9583F', 'interpretation' => '' ),
					array( 'id' => 'critical', 'min' => 0, 'max' => 39.99, 'label' => __( 'Critical', 'assesscraft' ), 'color' => '#8C3B2E', 'interpretation' => '' ),
				),
			),
			'profiles' => array(),
			'report'   => array(
				'sections' => array( 'profile', 'overall', 'stage_scores', 'interpretations', 'recommendation', 'cta', 'restart' ),
				'heading'  => __( 'Your preliminary results', 'assesscraft' ),
				'intro'    => __( 'This summary reflects the scoring configured for the questions you completed.', 'assesscraft' ),
				'cta_heading' => __( 'Ready to take the next step?', 'assesscraft' ),
				'cta_text'    => __( 'Share your results with us to discuss a more comprehensive assessment.', 'assesscraft' ),
				'cta_label'   => __( 'Request a Comprehensive Assessment', 'assesscraft' ),
			),
			'lead_form' => array(
				'enabled'         => false,
				'store_responses' => false,
				'send_results'    => true,
				'recipient'       => '',
				'subject'         => __( 'New AssessCraft consultation request', 'assesscraft' ),
				'success_message' => __( 'Thank you. Your consultation request has been received.', 'assesscraft' ),
				'consent_label'   => __( 'I agree to share my contact details and assessment results for follow-up.', 'assesscraft' ),
			),
			'design' => array(
				'primary'    => '#1B2430',
				'accent'     => '#B08D2B',
				'background' => '#F6F4EE',
				'surface'     => '#FFFFFF',
				'text'        => '#1B2430',
				'muted'       => '#596475',
				'button_text' => '#FFFFFF',
				'font'        => 'system',
				'radius'      => 2,
				'width'       => 760,
			),
		);
	}

	public static function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return self::defaults();
		}

		$merged = array_replace_recursive( self::defaults(), $value );
		$merged['stages'] = self::sanitize_stages( is_array( $value['stages'] ?? null ) ? $value['stages'] : array() );
		$merged['scoring']['bands'] = self::sanitize_bands( is_array( $value['scoring']['bands'] ?? null ) ? $value['scoring']['bands'] : self::defaults()['scoring']['bands'] );
		$merged['profiles'] = self::sanitize_profiles( is_array( $value['profiles'] ?? null ) ? $value['profiles'] : array() );
		$merged['design'] = self::sanitize_design( is_array( $value['design'] ?? null ) ? $value['design'] : array() );
		return self::sanitize_recursive( $merged );
	}

	public static function sanitize_design( array $design ): array {
		$defaults = self::defaults()['design'];
		$colors = array( 'primary', 'accent', 'background', 'surface', 'text', 'muted', 'button_text' );
		foreach ( $colors as $key ) {
			$color = sanitize_hex_color( $design[ $key ] ?? $defaults[ $key ] );
			$defaults[ $key ] = $color ?: $defaults[ $key ];
		}
		$defaults['font'] = in_array( $design['font'] ?? '', array( 'system', 'serif' ), true ) ? $design['font'] : 'system';
		$defaults['radius'] = max( 0, min( 24, (int) ( $design['radius'] ?? $defaults['radius'] ) ) );
		$defaults['width'] = max( 520, min( 1200, (int) ( $design['width'] ?? $defaults['width'] ) ) );
		return $defaults;
	}

	public static function sanitize_bands( array $bands ): array {
		$clean = array();
		foreach ( $bands as $band ) {
			if ( ! is_array( $band ) ) {
				continue;
			}
			$color = sanitize_hex_color( $band['color'] ?? '' );
			$clean[] = array(
				'id'             => sanitize_key( $band['id'] ?? '' ),
				'min'            => max( 0, min( 100, (float) ( $band['min'] ?? 0 ) ) ),
				'max'            => max( 0, min( 100, (float) ( $band['max'] ?? 100 ) ) ),
				'label'          => sanitize_text_field( $band['label'] ?? '' ),
				'color'          => $color ?: '#6E7F6A',
				'interpretation' => sanitize_textarea_field( $band['interpretation'] ?? '' ),
			);
		}
		return $clean;
	}

	public static function sanitize_profiles( array $profiles ): array {
		$clean = array();
		foreach ( $profiles as $profile ) {
			if ( ! is_array( $profile ) ) {
				continue;
			}
			$conditions = array();
			foreach ( $profile['conditions'] ?? array() as $condition ) {
				if ( ! is_array( $condition ) ) {
					continue;
				}
				$operator = in_array( $condition['operator'] ?? '', array( 'gte', 'lte', 'gt', 'lt', 'between' ), true ) ? $condition['operator'] : 'gte';
				$conditions[] = array(
					'metric'   => sanitize_key( $condition['metric'] ?? 'overall' ),
					'operator' => $operator,
					'value'    => (float) ( $condition['value'] ?? 0 ),
					'value2'   => (float) ( $condition['value2'] ?? 100 ),
				);
			}
			$clean[] = array(
				'id'             => sanitize_key( $profile['id'] ?? '' ),
				'title'          => sanitize_text_field( $profile['title'] ?? '' ),
				'description'    => sanitize_textarea_field( $profile['description'] ?? '' ),
				'recommendation' => sanitize_textarea_field( $profile['recommendation'] ?? '' ),
				'match'          => 'any' === ( $profile['match'] ?? '' ) ? 'any' : 'all',
				'priority'       => (int) ( $profile['priority'] ?? 0 ),
				'conditions'     => $conditions,
			);
		}
		return $clean;
	}

	public static function sanitize_stages( array $stages ): array {
		$clean = array();
		foreach ( $stages as $stage ) {
			if ( ! is_array( $stage ) ) {
				continue;
			}
			$questions = array();
			foreach ( $stage['questions'] ?? array() as $question ) {
				if ( ! is_array( $question ) ) {
					continue;
				}
				$answers = array();
				foreach ( $question['answers'] ?? array() as $answer ) {
					if ( is_array( $answer ) ) {
						$answers[] = array(
							'id'    => sanitize_key( $answer['id'] ?? '' ),
							'label' => sanitize_text_field( $answer['label'] ?? '' ),
							'score' => is_numeric( $answer['score'] ?? null ) ? (float) $answer['score'] : 0,
						);
					}
				}
				$questions[] = array(
					'id'       => sanitize_key( $question['id'] ?? '' ),
					'type'     => in_array( $question['type'] ?? '', array( 'scale', 'choice', 'yes_no', 'numeric' ), true ) ? $question['type'] : 'scale',
					'prompt'   => sanitize_textarea_field( $question['prompt'] ?? '' ),
					'required' => ! empty( $question['required'] ),
					'reverse'  => ! empty( $question['reverse'] ),
					'answers'  => $answers,
				);
			}
			$clean[] = array(
				'id'          => sanitize_key( $stage['id'] ?? '' ),
				'name'        => sanitize_text_field( $stage['name'] ?? '' ),
				'description' => sanitize_textarea_field( $stage['description'] ?? '' ),
				'weight'      => max( 0, (float) ( $stage['weight'] ?? 1 ) ),
				'questions'   => $questions,
			);
		}
		return $clean;
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
