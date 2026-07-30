=== AssessCraft Pro ===
Contributors: onsetmedia
Tags: assessment, quiz, scoring, reports, elementor
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Requires Plugins: assesscraft
Stable tag: 0.1.0-alpha.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds licensed Pro capabilities to the AssessCraft assessment and report builder.

== Description ==

AssessCraft Pro is a separate add-on for AssessCraft Free. It uses the same assessment post type and configuration schema so activating or deactivating Pro never duplicates or migrates assessment content.

The initial Pro foundation includes:

* Dependency checks for AssessCraft Free 0.18.2 or newer.
* License-gated Pro entitlements.
* Unlimited assessment and profile limits when licensed.
* Existing advanced scoring, Elementor, JSON portability, templates, exports, email and design gates unlocked through the shared feature matrix.
* A WordPress administration page for activation, disconnection and status checks.
* Daily license refresh scheduling.

The licensing API contract is provider-independent and can be changed with the `assesscraft_pro_license_api_url` filter.

== Installation ==

1. Install and activate AssessCraft Free 0.18.2 or newer.
2. Upload the `assesscraft-pro` folder to `/wp-content/plugins/`.
3. Activate AssessCraft Pro.
4. Open AssessCraft > Pro License and connect a valid license.

For internal development only, define `ASSESSCRAFT_PRO_DEV_MODE` as `true` in `wp-config.php` to enable Pro entitlements without contacting the licensing service.

== Changelog ==

= 0.1.0-alpha.1 =
* Added the separate Pro add-on foundation.
* Added dependency and compatibility safeguards.
* Added license-gated feature entitlements.
* Added license administration and daily status refresh.
