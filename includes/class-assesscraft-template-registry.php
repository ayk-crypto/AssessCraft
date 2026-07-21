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
			'business-readiness' => array(
				'name'        => __( 'Business Readiness Assessment', 'assesscraft' ),
				'description' => __( 'Evaluate whether strategy, market understanding, operations, leadership, and financial foundations are ready to support execution.', 'assesscraft' ),
				'category'    => __( 'Business Performance', 'assesscraft' ),
				'config'      => self::business_readiness(),
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

	private static function business_readiness(): array {
		$config = AssessCraft_Schema::defaults();
		$config['overview'] = array(
			'heading'        => __( 'Business Readiness Assessment', 'assesscraft' ),
			'description'    => __( 'Assess how prepared your organization is to turn priorities into consistent execution across five essential business dimensions.', 'assesscraft' ),
			'start_label'    => __( 'Assess Our Readiness', 'assesscraft' ),
			'estimated_time' => __( '5 minutes', 'assesscraft' ),
			'disclaimer'     => __( 'This assessment provides a directional readiness view and should be considered alongside business context and professional judgment.', 'assesscraft' ),
		);
		$config['stages'] = array(
			array(
				'id' => 'strategic_clarity', 'name' => __( 'Strategic Clarity', 'assesscraft' ), 'weight' => 1,
				'description' => __( 'How clearly priorities, choices, and measures of success guide the organization.', 'assesscraft' ),
				'questions' => self::questions( 'strategy', array(
					__( 'Our organization has a small number of clearly defined priorities for the next 12 months.', 'assesscraft' ),
					__( 'Teams understand how their work contributes to the organization’s strategic priorities.', 'assesscraft' ),
					__( 'Leadership uses agreed measures to review progress and adjust direction.', 'assesscraft' ),
				) ),
			),
			array(
				'id' => 'market_readiness', 'name' => __( 'Market & Customer Readiness', 'assesscraft' ), 'weight' => 1,
				'description' => __( 'Understanding of target customers, market demand, differentiation, and opportunity.', 'assesscraft' ),
				'questions' => self::questions( 'market', array(
					__( 'We have a clear understanding of the customers or stakeholders we are best positioned to serve.', 'assesscraft' ),
					__( 'Customer feedback and market evidence regularly influence our decisions.', 'assesscraft' ),
					__( 'Our value proposition is meaningfully differentiated from available alternatives.', 'assesscraft' ),
				) ),
			),
			array(
				'id' => 'operational_readiness', 'name' => __( 'Operational Readiness', 'assesscraft' ), 'weight' => 1,
				'description' => __( 'The reliability, scalability, and visibility of processes and operating systems.', 'assesscraft' ),
				'questions' => self::questions( 'operations', array(
					__( 'Critical business processes are documented, understood, and followed consistently.', 'assesscraft' ),
					__( 'Our systems and tools provide reliable information for day-to-day decisions.', 'assesscraft' ),
					__( 'The organization can handle a meaningful increase in demand without major disruption.', 'assesscraft' ),
				) ),
			),
			array(
				'id' => 'leadership_people', 'name' => __( 'Leadership & People', 'assesscraft' ), 'weight' => 1,
				'description' => __( 'Leadership alignment, accountability, capability, and the capacity of the team.', 'assesscraft' ),
				'questions' => self::questions( 'people', array(
					__( 'Leadership communicates decisions and expectations clearly and consistently.', 'assesscraft' ),
					__( 'Responsibilities and decision ownership are clear across the organization.', 'assesscraft' ),
					__( 'We have the capabilities and leadership capacity needed for our current priorities.', 'assesscraft' ),
				) ),
			),
			array(
				'id' => 'financial_risk', 'name' => __( 'Financial & Risk Readiness', 'assesscraft' ), 'weight' => 1,
				'description' => __( 'Financial visibility, resource discipline, and preparedness for material business risks.', 'assesscraft' ),
				'questions' => self::questions( 'finance', array(
					__( 'Leadership has timely visibility of cash flow, costs, and financial performance.', 'assesscraft' ),
					__( 'Resources are allocated according to agreed priorities rather than immediate pressure alone.', 'assesscraft' ),
					__( 'Material business risks have clear owners and practical response plans.', 'assesscraft' ),
				) ),
			),
		);

		$interpretations = array(
			'Strong' => __( 'This area appears well established and capable of supporting confident execution.', 'assesscraft' ),
			'Established' => __( 'A sound foundation is present, with selected opportunities to improve consistency or scale.', 'assesscraft' ),
			'Developing' => __( 'Important elements are in place, but execution may still depend on individuals or informal practices.', 'assesscraft' ),
			'Constrained' => __( 'Gaps in this area may limit execution and should be addressed through focused improvement.', 'assesscraft' ),
			'Critical' => __( 'This area requires immediate attention because it may materially affect business performance or resilience.', 'assesscraft' ),
		);
		foreach ( $config['scoring']['bands'] as &$band ) {
			$band['interpretation'] = $interpretations[ $band['label'] ] ?? '';
		}
		unset( $band );

		$config['profiles'] = array(
			array(
				'id' => 'ready_to_execute', 'title' => __( 'Ready to Execute', 'assesscraft' ), 'priority' => 60, 'match' => 'all',
				'description' => __( 'The organization demonstrates broad readiness across the essential foundations required to execute priorities with confidence.', 'assesscraft' ),
				'recommendation' => __( 'Translate readiness into a focused execution roadmap with owners, milestones, and regular performance reviews.', 'assesscraft' ),
				'conditions' => array( array( 'metric' => 'overall', 'operator' => 'gte', 'value' => 75, 'value2' => 100 ) ),
			),
			array(
				'id' => 'strategy_ahead_of_execution', 'title' => __( 'Strategy Ahead of Execution', 'assesscraft' ), 'priority' => 90, 'match' => 'all',
				'description' => __( 'Strategic direction is comparatively strong, but operating foundations may not yet support reliable delivery.', 'assesscraft' ),
				'recommendation' => __( 'Convert strategic priorities into repeatable processes, decision rights, capacity plans, and measurable operating commitments.', 'assesscraft' ),
				'conditions' => array(
					array( 'metric' => 'stage_strategic_clarity', 'operator' => 'gte', 'value' => 70, 'value2' => 100 ),
					array( 'metric' => 'stage_operational_readiness', 'operator' => 'lt', 'value' => 60, 'value2' => 100 ),
				),
			),
			array(
				'id' => 'opportunity_with_internal_gaps', 'title' => __( 'Opportunity with Internal Gaps', 'assesscraft' ), 'priority' => 80, 'match' => 'all',
				'description' => __( 'Market understanding appears promising, while internal capability may need strengthening before opportunity can be pursued consistently.', 'assesscraft' ),
				'recommendation' => __( 'Prioritize the people, process, and financial constraints that could prevent the organization from converting demand into sustainable results.', 'assesscraft' ),
				'conditions' => array(
					array( 'metric' => 'stage_market_readiness', 'operator' => 'gte', 'value' => 70, 'value2' => 100 ),
					array( 'metric' => 'stage_leadership_people', 'operator' => 'lt', 'value' => 60, 'value2' => 100 ),
				),
			),
			array(
				'id' => 'financially_exposed', 'title' => __( 'Operationally Capable, Financially Exposed', 'assesscraft' ), 'priority' => 85, 'match' => 'all',
				'description' => __( 'Execution capability is developing, but financial visibility or risk preparedness may create avoidable exposure.', 'assesscraft' ),
				'recommendation' => __( 'Strengthen cash-flow visibility, resource allocation discipline, and ownership of priority risks before expanding commitments.', 'assesscraft' ),
				'conditions' => array(
					array( 'metric' => 'stage_operational_readiness', 'operator' => 'gte', 'value' => 65, 'value2' => 100 ),
					array( 'metric' => 'stage_financial_risk', 'operator' => 'lt', 'value' => 55, 'value2' => 100 ),
				),
			),
			array(
				'id' => 'developing_readiness', 'title' => __( 'Developing Readiness', 'assesscraft' ), 'priority' => 30, 'match' => 'all',
				'description' => __( 'The business has several useful foundations, but readiness is uneven and may reduce consistency of execution.', 'assesscraft' ),
				'recommendation' => __( 'Select the two lowest-scoring dimensions and define a 90-day improvement plan with clear owners and evidence of progress.', 'assesscraft' ),
				'conditions' => array( array( 'metric' => 'overall', 'operator' => 'between', 'value' => 55, 'value2' => 74.99 ) ),
			),
			array(
				'id' => 'foundation_building', 'title' => __( 'Foundation Building', 'assesscraft' ), 'priority' => 20, 'match' => 'all',
				'description' => __( 'Core business foundations require coordinated attention before the organization can execute major priorities reliably.', 'assesscraft' ),
				'recommendation' => __( 'Stabilize strategic focus, operating discipline, leadership accountability, and financial visibility before adding complexity.', 'assesscraft' ),
				'conditions' => array( array( 'metric' => 'overall', 'operator' => 'lt', 'value' => 55, 'value2' => 100 ) ),
			),
		);
		$config['report']['heading'] = __( 'Your Business Readiness Report', 'assesscraft' );
		$config['report']['intro'] = __( 'This report summarizes overall readiness, highlights the dimensions supporting execution, and identifies where focused improvement may have the greatest value.', 'assesscraft' );
		$config['report']['cta_heading'] = __( 'Ready to strengthen your business foundations?', 'assesscraft' );
		$config['report']['cta_text'] = __( 'Share your results to discuss the priorities, constraints, and next steps identified by your assessment.', 'assesscraft' );
		$config['report']['cta_label'] = __( 'Request a Readiness Consultation', 'assesscraft' );
		$config['design'] = array_replace( $config['design'], array( 'primary' => '#183153', 'accent' => '#2F7D6D', 'background' => '#F4F7F8', 'muted' => '#5B6876' ) );
		return AssessCraft_Schema::sanitize( $config );
	}
}
