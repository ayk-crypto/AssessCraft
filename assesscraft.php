<?php
/**
 * Plugin Name: AssessCraft - Assessment & Report Builder
 * Plugin URI:  https://assesscraft.com/
 * Description: Build scored, multi-stage assessments that generate personalized reports and qualified leads.
 * Version:     0.9.0
 * Author:      AssessCraft
 * Text Domain: assesscraft
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'ASSESSCRAFT_VERSION', '0.9.0' );
define( 'ASSESSCRAFT_FILE', __FILE__ );
define( 'ASSESSCRAFT_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASSESSCRAFT_URL', plugin_dir_url( __FILE__ ) );

require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-schema.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-post-type.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-shortcode.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-lead-endpoint.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-template-registry.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-block.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-elementor.php';
require_once ASSESSCRAFT_DIR . 'admin/class-assesscraft-admin.php';
require_once ASSESSCRAFT_DIR . 'admin/class-assesscraft-templates-admin.php';
require_once ASSESSCRAFT_DIR . 'includes/class-assesscraft-plugin.php';

register_activation_hook( __FILE__, array( 'AssessCraft_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AssessCraft_Plugin', 'deactivate' ) );

AssessCraft_Plugin::instance()->boot();
