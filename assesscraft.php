<?php
/**
 * Plugin Name: AssessCraft - Assessment & Report Builder
 * Plugin URI:  https://assesscraft.com/
 * Description: Build scored, multi-stage assessments that generate personalized reports and qualified leads.
 * Version:     0.15.0-alpha.2
 * Author:      AssessCraft
 * Text Domain: assesscraft
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'ASSESSCRAFT_VERSION', '0.15.0-alpha.2' );
define( 'ASSESSCRAFT_FILE', __FILE__ );
define( 'ASSESSCRAFT_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASSESSCRAFT_URL', plugin_dir_url( __FILE__ ) );

if ( ! function_exists( 'assesscraft_fs' ) ) {
	function assesscraft_fs() {
		global $assesscraft_fs;

		if ( ! isset( $assesscraft_fs ) ) {
			require_once ASSESSCRAFT_DIR . 'vendor/freemius/start.php';
			$assesscraft_fs = fs_dynamic_init(
				array(
					'id'                  => '35179',
					'slug'                => 'assesscraft',
					'type'                => 'plugin',
					'public_key'          => 'pk_242a0418bb9aac1190ca55d6a453b',
					'is_premium'          => true,
					'premium_suffix'      => 'Pro',
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => true,
					'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
					'menu'                => array(
						'slug'       => 'edit.php?post_type=ac_assessment',
						'first-path' => 'edit.php?post_type=ac_assessment&page=assesscraft-getting-started',
						'support'    => false,
					),
				)
			);
		}

		return $assesscraft_fs;
	}

	assesscraft_fs();
	do_action( 'assesscraft_fs_loaded' );
}

add_filter(
	'assesscraft_current_plan',
	static function ( string $plan ): string {
		return assesscraft_fs()->can_use_premium_code() ? 'pro' : $plan;
	}
);

require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-schema.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-scoring.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-features.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-migrations.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-post-type.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-shortcode.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-lead-endpoint.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-lead-store.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-template-registry.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-block.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-elementor.php';
require_once ASSESSCRAFT_DIR . 'admin/class-assesscraft-admin.php';
require_once ASSESSCRAFT_DIR . 'admin/class-assesscraft-templates-admin.php';
require_once ASSESSCRAFT_DIR . 'admin/class-assesscraft-onboarding.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-plugin.php';

register_activation_hook( __FILE__, array( 'AssessCraft_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AssessCraft_Plugin', 'deactivate' ) );

AssessCraft_Plugin::instance()->boot();
