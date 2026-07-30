=== AssessCraft Pro ===
Contributors: onsetmedia
Tags: assessment, quiz, scoring, reports, elementor
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Requires Plugins: assesscraft
Stable tag: 0.9.0-beta.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds licensed Pro capabilities to the AssessCraft assessment and report builder.

== Description ==

AssessCraft Pro is a separate add-on for AssessCraft Free. It uses the same assessment post type and configuration schema so activating or deactivating Pro never duplicates or migrates assessment content.

This internal beta includes:

* Dependency checks for AssessCraft Free 0.18.2 or newer.
* License-gated Pro entitlements.
* Five offline internal testing keys for controlled staging validation.
* Unlimited assessment and profile limits when licensed.
* Existing advanced scoring, Elementor, JSON portability, templates, exports, email and design gates unlocked through the shared feature matrix.
* A polished responsive WordPress administration page for activation, disconnection and status checks.
* Daily license refresh scheduling, including multisite safeguards.
* Clean versioned ZIP and SHA-256 package generation.

The licensing API contract is provider-independent and can be changed with the `assesscraft_pro_license_api_url` filter.

Important: the offline test-key mechanism is included only for this controlled beta and must be removed before the commercial 1.0.0 release.

== Installation ==

1. Install and activate AssessCraft Free 0.18.2 or newer.
2. Upload the AssessCraft Pro ZIP through Plugins > Add New > Upload Plugin.
3. Activate AssessCraft Pro.
4. Open AssessCraft > Pro License.
5. Enter one of the supplied internal beta keys.

For internal development only, define `ASSESSCRAFT_PRO_DEV_MODE` as `true` in `wp-config.php` to enable Pro entitlements without a stored license.

== Changelog ==

= 0.9.0-beta.1 =
* Promoted the add-on from foundation alpha to internal beta packaging.
* Added five offline testing licenses stored as secure hashes.
* Added local activation, refresh and disconnection behavior for testing keys.
* Rebuilt the Pro License screen with responsive cards, status badges, capability summaries and release-readiness information.
* Added dedicated admin CSS and JavaScript.
* Added dynamic versioned ZIP/checksum packaging for future releases.
* Preserved all previous dependency, multisite, permission and package-isolation safeguards.

= 0.1.0-alpha.1 =
* Added the separate Pro add-on foundation.
* Added dependency and compatibility safeguards.
* Added license-gated feature entitlements.
* Added license administration and daily status refresh.
