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
				'estimated_time' => '',
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
