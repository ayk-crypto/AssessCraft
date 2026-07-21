<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Template_Registry {
	public static function all(): array {
		$templates = array(
			'sustainable-growth' => array(
				'name'        => __( 'Sustainable Growth Assessment', 'assesscraft' ),
				'description' => __( 'Evaluate growth direction, operational capacity, and the sustainability of current ways of working.', 'assesscraft' ),
				'category'    => __( 'Business Strategy', 'assesscraft' ),
				'config'      => self::sustainable_growth(),
			),
		);
		return apply_filters( 'assesscraft_templates', $templates );
	}

	public static function get( string $slug ): ?array {
		$templates = self::all();
		return $templates[ $slug ] ?? null;
	}

	private static function scale_answers( string $prefix ): array {
		$labels = array( 'Strongly Disagree', 'Disagree', 'Neutral or Unsure', 'Agree', 'Strongly Agree' );
		return array_map(
			static fn( string $label, int $index ): array => array( 'id' => $prefix . '_a' . ( $index + 1 ), 'label' => $label, 'score' => $index + 1 ),
			$labels,
			array_keys( $labels )
		);
	}

	private static function questions( string $stage, array $prompts, array $reverse = array() ): array {
		$questions = array();
		foreach ( $prompts as $index => $prompt ) {
			$id = $stage . '_q' . ( $index + 1 );
			$questions[] = array(
				'id'       => $id,
				'type'     => 'scale',
				'prompt'   => $prompt,
				'required' => true,
				'reverse'  => in_array( $index + 1, $reverse, true ),
				'answers'  => self::scale_answers( $id ),
			);
		}
		return $questions;
	}

	private static function sustainable_growth(): array {
		$config = AssessCraft_Schema::defaults();
		$config['overview'] = array(
			'heading'        => __( 'Sustainable Growth Assessment', 'assesscraft' ),
			'description'    => __( 'Explore whether your organization’s ambitions, operating capacity, and day-to-day experience are developing at a sustainable pace.', 'assesscraft' ),
			'start_label'    => __( 'Begin Assessment', 'assesscraft' ),
			'estimated_time' => __( '4 minutes', 'assesscraft' ),
			'disclaimer'     => __( 'This assessment offers an initial perspective and is not a comprehensive organizational diagnosis.', 'assesscraft' ),
		);
		$config['stages'] = array(
			array(
				'id'          => 'growth_direction',
				'name'        => __( 'Growth Direction', 'assesscraft' ),
				'description' => __( 'Clarity, opportunity readiness, and confidence in future growth.', 'assesscraft' ),
				'weight'      => 1,
				'questions'   => self::questions(
					'growth',
					array(
						__( 'Our organization can point to meaningful progress during the past year.', 'assesscraft' ),
						__( 'Leadership has a clear and practical direction for the next stage of growth.', 'assesscraft' ),
						__( 'We can recognize and evaluate worthwhile opportunities when they appear.', 'assesscraft' ),
						__( 'We are confident that demand or impact can continue to grow over the next year.', 'assesscraft' ),
					)
				),
			),
			array(
				'id'          => 'operational_capacity',
				'name'        => __( 'Operational Capacity', 'assesscraft' ),
				'description' => __( 'People, leadership time, systems, and processes available to support demand.', 'assesscraft' ),
				'weight'      => 1,
				'questions'   => self::questions(
					'capacity',
					array(
						__( 'Our team could absorb an unexpected increase in demand without major disruption.', 'assesscraft' ),
						__( 'Our systems and processes reliably support the work expected of them.', 'assesscraft' ),
						__( 'Leaders have sufficient time and information to guide the organization effectively.', 'assesscraft' ),
						__( 'We strengthen capacity before operational pressure becomes urgent.', 'assesscraft' ),
					)
				),
			),
			array(
				'id'          => 'sustainable_operations',
				'name'        => __( 'Sustainable Operations', 'assesscraft' ),
				'description' => __( 'How manageable, resilient, and repeatable the current operating model feels.', 'assesscraft' ),
				'weight'      => 1,
				'questions'   => self::questions(
					'sustainability',
					array(
						__( 'New requests frequently leave team members feeling overloaded.', 'assesscraft' ),
						__( 'Current workloads can usually be handled without persistent firefighting.', 'assesscraft' ),
						__( 'Our present operating model can support where we intend to go.', 'assesscraft' ),
						__( 'The organization’s growth feels manageable and repeatable.', 'assesscraft' ),
					),
					array( 1 )
				),
			),
		);
		$config['profiles'] = array(
			array(
				'id' => 'balanced_and_ready', 'title' => __( 'Balanced and Ready', 'assesscraft' ), 'priority' => 40, 'match' => 'all',
				'description' => __( 'Growth direction and operating capacity appear to be developing together, with limited signs of strain.', 'assesscraft' ),
				'recommendation' => __( 'Continue monitoring capacity as new opportunities are pursued.', 'assesscraft' ),
				'conditions' => array(
					array( 'metric' => 'stage_growth_direction', 'operator' => 'gte', 'value' => 70, 'value2' => 100 ),
					array( 'metric' => 'stage_operational_capacity', 'operator' => 'gte', 'value' => 70, 'value2' => 100 ),
				),
			),
			array(
				'id' => 'growth_under_pressure', 'title' => __( 'Growth Under Pressure', 'assesscraft' ), 'priority' => 30, 'match' => 'all',
				'description' => __( 'Growth ambition is stronger than the capacity currently available to support it.', 'assesscraft' ),
				'recommendation' => __( 'Identify the operational constraints carrying the greatest risk before accelerating demand.', 'assesscraft' ),
				'conditions' => array(
					array( 'metric' => 'stage_growth_direction', 'operator' => 'gte', 'value' => 70, 'value2' => 100 ),
					array( 'metric' => 'stage_operational_capacity', 'operator' => 'lt', 'value' => 55, 'value2' => 100 ),
				),
			),
			array(
				'id' => 'capacity_to_activate', 'title' => __( 'Capacity to Activate', 'assesscraft' ), 'priority' => 20, 'match' => 'all',
				'description' => __( 'The organization may have useful capacity that is not yet being translated into clear growth opportunities.', 'assesscraft' ),
				'recommendation' => __( 'Clarify which opportunities can be pursued using existing capabilities and resources.', 'assesscraft' ),
				'conditions' => array(
					array( 'metric' => 'stage_operational_capacity', 'operator' => 'gte', 'value' => 70, 'value2' => 100 ),
					array( 'metric' => 'stage_growth_direction', 'operator' => 'lt', 'value' => 55, 'value2' => 100 ),
				),
			),
			array(
				'id' => 'foundation_first', 'title' => __( 'Foundation First', 'assesscraft' ), 'priority' => 10, 'match' => 'all',
				'description' => __( 'Both direction and operating capacity would benefit from focused development.', 'assesscraft' ),
				'recommendation' => __( 'Clarify priorities and strengthen the operating foundation before pursuing significant expansion.', 'assesscraft' ),
				'conditions' => array(
					array( 'metric' => 'stage_growth_direction', 'operator' => 'lt', 'value' => 55, 'value2' => 100 ),
					array( 'metric' => 'stage_operational_capacity', 'operator' => 'lt', 'value' => 55, 'value2' => 100 ),
				),
			),
		);
		return AssessCraft_Schema::sanitize( $config );
	}
}

