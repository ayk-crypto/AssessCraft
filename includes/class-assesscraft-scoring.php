<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Scoring {
	public static function calculate( array $config, array $responses ): array {
		$stages = array();
		$missing_required = false;

		foreach ( $config['stages'] ?? array() as $stage ) {
			$scores = array();
			foreach ( $stage['questions'] ?? array() as $question ) {
				$question_id = sanitize_key( $question['id'] ?? '' );
				$answer_id = sanitize_key( $responses[ $question_id ] ?? '' );
				$answer = self::find_answer( $question['answers'] ?? array(), $answer_id );
				if ( ! $answer ) {
					if ( ! empty( $question['required'] ) ) {
						$missing_required = true;
					}
					continue;
				}
				$scores[] = self::normalize( $question, $answer );
			}

			$stages[] = array(
				'id'          => sanitize_key( $stage['id'] ?? '' ),
				'name'        => sanitize_text_field( $stage['name'] ?? '' ),
				'score'       => $scores ? array_sum( $scores ) / count( $scores ) : 0,
				'weight'      => max( 0, (float) ( $stage['weight'] ?? 0 ) ),
			);
		}

		$weight_total = array_sum( array_column( $stages, 'weight' ) );
		if ( $weight_total > 0 ) {
			$weighted_total = 0;
			foreach ( $stages as $stage ) {
				$weighted_total += $stage['score'] * $stage['weight'];
			}
			$overall = $weighted_total / $weight_total;
		} else {
			$overall = $stages ? array_sum( array_column( $stages, 'score' ) ) / count( $stages ) : 0;
		}

		$overall = max( 0, min( 100, $overall ) );
		$band = self::band_for( $overall, $config['scoring']['bands'] ?? array() );
		$profile = self::profile_for( $config['profiles'] ?? array(), $overall, $stages );

		return array(
			'valid'          => ! $missing_required,
			'overall'        => $overall,
			'classification' => sanitize_text_field( $band['label'] ?? '' ),
			'profile'        => sanitize_text_field( $profile['title'] ?? self::generated_profile_title( $stages ) ),
			'stages'         => array_map(
				static fn( array $stage ): array => array( 'name' => $stage['name'], 'score' => $stage['score'] ),
				$stages
			),
		);
	}

	private static function find_answer( array $answers, string $answer_id ): ?array {
		foreach ( $answers as $answer ) {
			if ( is_array( $answer ) && $answer_id && sanitize_key( $answer['id'] ?? '' ) === $answer_id ) {
				return $answer;
			}
		}
		return null;
	}

	private static function normalize( array $question, array $selected ): float {
		$scores = array_map( static fn( array $answer ): float => (float) ( $answer['score'] ?? 0 ), $question['answers'] ?? array() );
		if ( ! $scores ) {
			return 0;
		}
		$minimum = min( $scores );
		$maximum = max( $scores );
		$value = (float) ( $selected['score'] ?? 0 );
		if ( ! empty( $question['reverse'] ) ) {
			$value = $maximum + $minimum - $value;
		}
		return $maximum === $minimum ? 100 : max( 0, min( 100, ( ( $value - $minimum ) / ( $maximum - $minimum ) ) * 100 ) );
	}

	private static function band_for( float $score, array $bands ): array {
		foreach ( $bands as $band ) {
			if ( is_array( $band ) && $score >= (float) ( $band['min'] ?? 0 ) && $score <= (float) ( $band['max'] ?? 100 ) ) {
				return $band;
			}
		}
		return array();
	}

	private static function profile_for( array $profiles, float $overall, array $stages ): array {
		usort( $profiles, static fn( array $a, array $b ): int => (int) ( $b['priority'] ?? 0 ) <=> (int) ( $a['priority'] ?? 0 ) );
		foreach ( $profiles as $profile ) {
			$conditions = is_array( $profile['conditions'] ?? null ) ? $profile['conditions'] : array();
			if ( ! $conditions ) {
				continue;
			}
			$matches = array_map( static fn( array $condition ): bool => self::condition_matches( $condition, $overall, $stages ), $conditions );
			if ( ( 'any' === ( $profile['match'] ?? '' ) && in_array( true, $matches, true ) ) || ( 'any' !== ( $profile['match'] ?? '' ) && ! in_array( false, $matches, true ) ) ) {
				return $profile;
			}
		}
		return array();
	}

	private static function condition_matches( array $condition, float $overall, array $stages ): bool {
		$metric = sanitize_key( $condition['metric'] ?? 'overall' );
		$actual = $overall;
		if ( str_starts_with( $metric, 'stage_' ) ) {
			$id = substr( $metric, 6 );
			$found = array_values( array_filter( $stages, static fn( array $stage ): bool => $stage['id'] === $id ) );
			if ( ! $found ) {
				return false;
			}
			$actual = (float) $found[0]['score'];
		}
		$value = (float) ( $condition['value'] ?? 0 );
		$value2 = (float) ( $condition['value2'] ?? 100 );
		return match ( $condition['operator'] ?? 'gte' ) {
			'lte' => $actual <= $value,
			'gt' => $actual > $value,
			'lt' => $actual < $value,
			'between' => $actual >= min( $value, $value2 ) && $actual <= max( $value, $value2 ),
			default => $actual >= $value,
		};
	}

	private static function generated_profile_title( array $stages ): string {
		if ( ! $stages ) {
			return '';
		}
		usort( $stages, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );
		return $stages[0]['score'] - $stages[ count( $stages ) - 1 ]['score'] >= 15
			? $stages[0]['name'] . ' Ahead of ' . $stages[ count( $stages ) - 1 ]['name']
			: __( 'Balanced Development', 'assesscraft' );
	}
}
