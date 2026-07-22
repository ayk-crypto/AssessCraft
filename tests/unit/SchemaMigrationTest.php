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
		$this->assertSame( 2, $migrated['schema_version'] );
		$this->assertSame( 'Existing 0.15 assessment', $migrated['overview']['heading'] );
		$this->assertSame( 'Growth', $migrated['stages'][0]['name'] );
		$this->assertSame( 'Existing profile', $migrated['profiles'][0]['title'] );
		$this->assertFalse( $migrated['lead_form']['send_results'] );
		$this->assertTrue( $migrated['lead_form']['store_responses'] );
	}

	public function test_migration_is_idempotent(): void {
		$once = AssessCraft_Schema::migrate( AssessCraft_Schema::defaults() );
		$this->assertSame( $once, AssessCraft_Schema::migrate( $once ) );
	}
}
