<?php
use PHPUnit\Framework\TestCase;

final class SchemaMigrationTest extends TestCase {
	public function test_existing_015_configuration_migrates_without_losing_content(): void {
		$legacy = array(
			'schema_version' => 1,
			'overview' => array( 'heading' => 'Existing 0.15 assessment' ),
			'stages' => array( array( 'id' => 'growth', 'name' => 'Growth', 'weight' => 1, 'questions' => array() ) ),
			'profiles' => array( array( 'id' => 'profile', 'title' => 'Existing profile', 'conditions' => array() ) ),
			'lead_form' => array( 'enabled' => true, 'email_enabled' => false, 'store_responses' => true ),
		);
		$migrated = AssessCraft_Schema::migrate( $legacy );
		$this->assertSame( 3, $migrated['schema_version'] );
		$this->assertSame( 'Existing 0.15 assessment', $migrated['overview']['heading'] );
		$this->assertSame( 'Growth', $migrated['stages'][0]['name'] );
		$this->assertSame( 'Existing profile', $migrated['profiles'][0]['title'] );
		$this->assertFalse( $migrated['lead_form']['send_results'] );
		$this->assertTrue( $migrated['lead_form']['store_responses'] );
	}

	public function test_legacy_sent_message_is_updated_without_overwriting_custom_copy(): void {
		$legacy = AssessCraft_Schema::defaults();
		$legacy['schema_version'] = 2;
		$legacy['lead_form']['success_message'] = 'Thank you. Your request and assessment summary have been sent.';
		$migrated = AssessCraft_Schema::migrate( $legacy );
		$this->assertSame( 'Thank you. Your consultation request has been received.', $migrated['lead_form']['success_message'] );

		$custom = AssessCraft_Schema::defaults();
		$custom['schema_version'] = 2;
		$custom['lead_form']['success_message'] = 'We have your details and will call tomorrow.';
		$migrated_custom = AssessCraft_Schema::migrate( $custom );
		$this->assertSame( 'We have your details and will call tomorrow.', $migrated_custom['lead_form']['success_message'] );
	}

	public function test_migration_is_idempotent(): void {
		$once = AssessCraft_Schema::migrate( AssessCraft_Schema::defaults() );
		$this->assertSame( $once, AssessCraft_Schema::migrate( $once ) );
	}
}
