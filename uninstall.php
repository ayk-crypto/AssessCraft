<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( 'delete' !== get_option( 'assesscraft_uninstall_behavior', 'keep' ) ) {
	return;
}

global $wpdb;
$assessment_ids = get_posts(
	array(
		'post_type'      => 'ac_assessment',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
foreach ( $assessment_ids as $assessment_id ) {
	wp_delete_post( $assessment_id, true );
}

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'assesscraft_leads' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
wp_clear_scheduled_hook( 'assesscraft_cleanup_leads' );

foreach ( array(
	'assesscraft_migration_version', 'assesscraft_migration_log', 'assesscraft_leads_db_version',
	'assesscraft_lead_retention_days', 'assesscraft_uninstall_behavior', 'assesscraft_onboarding_complete',
	'assesscraft_error_log', 'assesscraft_custom_templates',
) as $option ) {
	delete_option( $option );
}
