<?php
use PHPUnit\Framework\TestCase;

final class ScoringTest extends TestCase {
	private function config(): array {
		$config = AssessCraft_Schema::defaults();
		$config['stages'] = array(
			array( 'id' => 'strategy', 'name' => 'Strategy', 'weight' => 2, 'questions' => array(
				array( 'id' => 'q1', 'prompt' => 'Q1', 'required' => true, 'reverse' => false, 'answers' => array(
					array( 'id' => 'low', 'label' => 'Low', 'score' => 1 ), array( 'id' => 'high', 'label' => 'High', 'score' => 5 ),
				) ),
			) ),
			array( 'id' => 'risk', 'name' => 'Risk', 'weight' => 1, 'questions' => array(
				array( 'id' => 'q2', 'prompt' => 'Q2', 'required' => true, 'reverse' => true, 'answers' => array(
					array( 'id' => 'low', 'label' => 'Low', 'score' => 1 ), array( 'id' => 'high', 'label' => 'High', 'score' => 5 ),
				) ),
			) ),
		);
		$config['profiles'] = array(
			array( 'id' => 'ready', 'title' => 'Ready', 'priority' => 10, 'match' => 'all', 'conditions' => array( array( 'metric' => 'overall', 'operator' => 'gte', 'value' => 80 ) ) ),
			array( 'id' => 'strategy-led', 'title' => 'Strategy-led', 'priority' => 5, 'match' => 'all', 'conditions' => array( array( 'metric' => 'stage_strategy', 'operator' => 'between', 'value' => 90, 'value2' => 100 ) ) ),
		);
		return $config;
	}

	public function test_weighted_and_reverse_scoring(): void {
		$result = AssessCraft_Scoring::calculate( $this->config(), array( 'q1' => 'high', 'q2' => 'low' ) );
		$this->assertTrue( $result['valid'] );
		$this->assertSame( 100.0, $result['overall'] );
		$this->assertSame( 'Ready', $result['profile'] );
	}

	public function test_stage_weighting(): void {
		$result = AssessCraft_Scoring::calculate( $this->config(), array( 'q1' => 'high', 'q2' => 'high' ) );
		$this->assertEqualsWithDelta( 66.6667, $result['overall'], 0.001 );
		$this->assertSame( 'Strategy-led', $result['profile'] );
	}

	public function test_missing_required_answer_is_invalid(): void {
		$result = AssessCraft_Scoring::calculate( $this->config(), array( 'q1' => 'high' ) );
		$this->assertFalse( $result['valid'] );
	}

	public function test_scores_are_clamped(): void {
		$config = $this->config();
		$config['stages'][0]['questions'][0]['answers'][1]['score'] = 500;
		$result = AssessCraft_Scoring::calculate( $config, array( 'q1' => 'high', 'q2' => 'low' ) );
		$this->assertGreaterThanOrEqual( 0, $result['overall'] );
		$this->assertLessThanOrEqual( 100, $result['overall'] );
	}
}
