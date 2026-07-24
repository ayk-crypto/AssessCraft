<?php
use PHPUnit\Framework\TestCase;

final class EntitlementsTest extends TestCase {
	protected function tearDown(): void {
		unset( $GLOBALS['assesscraft_test_plan'] );
	}

	public function test_free_matrix_matches_product_decision(): void {
		$GLOBALS['assesscraft_test_plan'] = 'free';
		$this->assertSame( 1, AssessCraft_Features::limit( 'published_assessments' ) );
		$this->assertSame( 3, AssessCraft_Features::limit( 'profiles' ) );
		$this->assertTrue( AssessCraft_Features::available( 'lead_storage' ) );
		$this->assertFalse( AssessCraft_Features::available( 'consultation_email' ) );
		$this->assertFalse( AssessCraft_Features::available( 'json_portability' ) );
	}

	public function test_pro_unlocks_restricted_features(): void {
		$GLOBALS['assesscraft_test_plan'] = 'pro';
		$this->assertSame( -1, AssessCraft_Features::limit( 'profiles' ) );
		$this->assertTrue( AssessCraft_Features::available( 'consultation_email' ) );
		$this->assertTrue( AssessCraft_Features::available( 'elementor' ) );
	}

	public function test_downgrade_preserves_locked_configuration(): void {
		$GLOBALS['assesscraft_test_plan'] = 'free';
		$current = AssessCraft_Schema::defaults();
		$current['stages'] = array( array( 'id' => 's1', 'weight' => 4, 'questions' => array( array( 'id' => 'q1', 'reverse' => true ) ) ) );
		$current['profiles'] = array(
			array( 'id' => 'p1' ), array( 'id' => 'p2' ), array( 'id' => 'p3' ), array( 'id' => 'p4', 'title' => 'Locked profile' ),
		);
		$current['lead_form']['recipient'] = 'licensed@example.test';
		$current['design']['width'] = 1100;
		$posted = $current;
		$posted['stages'][0]['weight'] = 1;
		$posted['stages'][0]['questions'][0]['reverse'] = false;
		$posted['profiles'] = array_slice( $posted['profiles'], 0, 3 );
		$posted['lead_form']['recipient'] = '';
		$posted['design']['width'] = 760;

		$result = AssessCraft_Entitlements::preserve_restricted_config( $current, $posted );
		$this->assertSame( 4, $result['stages'][0]['weight'] );
		$this->assertTrue( $result['stages'][0]['questions'][0]['reverse'] );
		$this->assertCount( 4, $result['profiles'] );
		$this->assertSame( 'licensed@example.test', $result['lead_form']['recipient'] );
		$this->assertSame( 1100, $result['design']['width'] );
	}
}
