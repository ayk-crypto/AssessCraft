<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach (
	array(
		'assesscraft_pro_license_key',
		'assesscraft_pro_license_status',
		'assesscraft_pro_license_expires_at',
		'assesscraft_pro_license_last_checked',
		'assesscraft_pro_license_message',
	) as $option
) {
	delete_option( $option );
}

wp_clear_scheduled_hook( 'assesscraft_pro_daily_license_check' );
