<?php
/**
 * Plugin Name: AssessCraft Pro
 * Plugin URI:  https://assesscraft.com/
 * Description: Adds advanced scoring, unlimited publishing, premium integrations, exports, templates, and licensed updates to AssessCraft.
 * Version:     0.1.0-alpha.1
 * Author:      Onset Media
 * Author URI:  https://onset.media/
 * Text Domain: assesscraft-pro
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: assesscraft
 * License:     GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'ASSESSCRAFT_PRO_VERSION', '0.1.0-alpha.1' );
define( 'ASSESSCRAFT_PRO_FILE', __FILE__ );
define( 'ASSESSCRAFT_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASSESSCRAFT_PRO_URL', plugin_dir_url( __FILE__ ) );

require_once ASSESSCRAFT_PRO_DIR . 'includes/class-assesscraft-pro-dependencies.php';
require_once ASSESSCRAFT_PRO_DIR . 'includes/class-assesscraft-pro-license.php';
require_once ASSESSCRAFT_PRO_DIR . 'admin/class-assesscraft-pro-admin.php';
require_once ASSESSCRAFT_PRO_DIR . 'includes/class-assesscraft-pro.php';

register_activation_hook( __FILE__, array( 'AssessCraft_Pro', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AssessCraft_Pro', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		AssessCraft_Pro::instance()->boot();
	},
	20
);
