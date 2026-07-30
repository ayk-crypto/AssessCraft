=== AssessCraft - Assessment & Report Builder ===
Contributors: asfandyr
Tags: assessment, questionnaire, lead generation, scoring, reports
Requires at least: 6.5
Tested up to: 7.0
Stable tag: 0.18.4
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build multi-stage assessments with scoring, personalized reports, Gutenberg and shortcode publishing, and privacy-conscious lead storage.

== Description ==

AssessCraft helps consultants, agencies, coaches, and professional-service teams create structured assessments inside WordPress.

Build a guided questionnaire, assign scores to answers, organize questions into stages, and show visitors a personalized report when they finish. Assessments can be published with the native AssessCraft block or a shortcode.

= Included in AssessCraft Free =

* One published assessment, with unlimited stages and questions
* Standard answer scoring and configurable score bands
* Up to three conditional result profiles
* Personalized reports with interpretations and recommendations
* Native Gutenberg block
* Shortcode publishing
* One bundled starter template
* Consultation-request storage in the WordPress database
* Searchable consultation-request dashboard
* Privacy controls, configurable retention, and WordPress personal-data tools
* Responsive frontend and keyboard-accessible assessment flow

= How it works =

1. Create an assessment from scratch or start with the included template.
2. Add stages, questions, answer choices, scores, and result profiles.
3. Configure report content and optional consultation-request collection.
4. Publish the assessment.
5. Add it to a page with the AssessCraft block or generated shortcode.

= Privacy-conscious by design =

Consultation-request storage is disabled until you enable it for an assessment. When enabled, AssessCraft stores the submitted contact fields, consent timestamp, calculated score, result profile, and compact report summary in your WordPress database. It does not store individual question answers.

No assessment or consultation data is sent to AssessCraft or Onset Media. Site owners remain responsible for providing an appropriate privacy notice, choosing a retention period, and responding to data-subject requests.

For detailed help, visit [AssessCraft documentation](https://assesscraft.com/documentation/).

== Installation ==

1. In WordPress, go to **Plugins > Add New Plugin**.
2. Upload the AssessCraft ZIP and choose **Install Now**.
3. Activate AssessCraft.
4. Open **AssessCraft** in the WordPress administration menu.
5. Create an assessment or use the included starter template.
6. Publish it with the AssessCraft block or the generated shortcode.

AssessCraft requires WordPress 6.5 or later and PHP 8.0 or later.

== Frequently Asked Questions ==

= How many assessments can I publish with the Free edition? =

AssessCraft Free supports one published assessment. You can create additional assessments and keep them as editable drafts.

= Can I add unlimited questions and stages? =

Yes. The Free edition does not limit the number of stages, questions, or answer choices in your assessment.

= How do I place an assessment on a page? =

Use the AssessCraft block in the Gutenberg editor, or paste the shortcode shown in the assessment's Publish tab into a page or post.

= Does AssessCraft send visitor data to an external service? =

No. The Free edition processes assessments on your WordPress site and, when consultation storage is enabled, stores submitted records in your WordPress database.

= Does AssessCraft store every answer a visitor selects? =

No. Stored consultation requests contain contact fields, consent information, the calculated score, result profile, and a compact result summary. Individual question answers are not stored.

= Can I delete or export personal data? =

AssessCraft integrates with WordPress personal-data export and erasure tools. Administrators can also delete individual consultation requests or purge stored requests from the AssessCraft dashboard.

= What happens when the retention period expires? =

AssessCraft schedules a daily cleanup and removes stored consultation requests older than the configured retention period. The default is 90 days.

= Is Elementor included? =

The Free edition supports Gutenberg and shortcodes. Elementor integration is reserved for AssessCraft Pro.

= Where can I get help? =

Review the [documentation](https://assesscraft.com/documentation/) and [troubleshooting guide](https://assesscraft.com/documentation/troubleshooting/). If the issue continues, use the [support page](https://assesscraft.com/support/).

== Screenshots ==

1. Assessment list with publication status and clear actions.
2. Visual assessment builder for stages, questions, answer choices, and scores.
3. Scoring workspace with configurable score bands and interpretations.
4. Result-profile editor for personalized outcomes and recommendations.
5. Template library with the included starter template and clear Free/Pro availability.
6. Consultation Requests dashboard with search, filters, privacy controls, and retention settings.
7. Visitor assessment flow with progress, accessible answer choices, and navigation.
8. Personalized completion report with scores, interpretations, and recommendations.

== Changelog ==

= 0.18.4 =

* Keeps the native WordPress Publish button responsive when the Free publication limit is detected.
* Replaces the dead-button experience with clear publication and plan feedback.
* Allows WordPress to verify the active Pro entitlement during the actual publish request.

= 0.18.3 =

* Correctly recognizes an active AssessCraft Pro license before applying Free publication and profile limits.
* Fixes imported assessments remaining as drafts when Pro is active.
* Makes a blocked Free publication explicit instead of silently returning to the editor.
* Updates the plan indicator to report the active Free or Pro plan accurately.
* Replaces the browser-default JSON chooser with a responsive, accessible file-selection control.

= 0.18.2 =

* Restores one published assessment for the direct-download Free edition.
* Keeps additional assessments available as editable drafts.
* Freezes Pro as a separate add-on that shares Free's assessment data.

== Upgrade Notice ==

= 0.18.4 =

Recommended for sites where the assessment Publish button appears unresponsive while plan limits are being evaluated.
