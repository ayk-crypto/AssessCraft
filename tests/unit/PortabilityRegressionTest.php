<?php
use PHPUnit\Framework\TestCase;

final class PortabilityRegressionTest extends TestCase {
	public function test_json_round_trip_preserves_assessment_structure(): void {
		$config = AssessCraft_Schema::defaults();
		$config['overview']['heading'] = 'Portable assessment';
		$config['stages'] = array( array( 'id' => 'stage', 'name' => 'Stage', 'weight' => 1, 'questions' => array(
			array( 'id' => 'question', 'type' => 'choice', 'prompt' => 'Question?', 'required' => true, 'reverse' => false, 'answers' => array(
				array( 'id' => 'yes', 'label' => 'Yes', 'score' => 5 ), array( 'id' => 'no', 'label' => 'No', 'score' => 1 ),
			) ),
		) ) );
		$export = array( 'assesscraft_export' => 1, 'schema_version' => AssessCraft_Schema::VERSION, 'title' => 'Portable', 'config' => $config );
		$decoded = json_decode( json_encode( $export, JSON_THROW_ON_ERROR ), true, 512, JSON_THROW_ON_ERROR );
		$imported = AssessCraft_Schema::migrate( $decoded['config'] );
		$this->assertSame( $config['overview']['heading'], $imported['overview']['heading'] );
		$this->assertSame( $config['stages'], $imported['stages'] );
		$this->assertSame( AssessCraft_Schema::VERSION, $imported['schema_version'] );
	}

	public function test_invalid_design_values_fall_back_safely(): void {
		$config = AssessCraft_Schema::defaults();
		$config['design']['primary'] = 'javascript:alert(1)';
		$config['design']['width'] = 99999;
		$clean = AssessCraft_Schema::sanitize( $config );
		$this->assertSame( '#1B2430', $clean['design']['primary'] );
		$this->assertSame( 1200, $clean['design']['width'] );
	}
}
