=== AssessCraft - Assessment & Report Builder ===
Contributors: assesscraft
Tags: assessment, lead generation, reports, scoring, forms
Requires at least: 6.5
Requires PHP: 8.0
Stable tag: 0.10.3-rc.1
License: GPLv2 or later

Build scored, multi-stage assessments that generate personalized reports and qualified leads.

== Description ==

AssessCraft is a foundation release for a visual assessment builder designed for consultants and professional-service businesses.

== Installation ==

1. Upload the assesscraft folder to /wp-content/plugins/ or install the ZIP.
2. Activate AssessCraft.
3. Open AssessCraft in WordPress administration.
4. Create an assessment and embed it with the generated shortcode.

== Changelog ==

= 0.10.3-rc.1 =
* Release candidate combining reusable JSON template management, a scalable searchable template catalog, and the validated WordPress editor-save fix.
* Prepared for final shortcode, Gutenberg, Elementor, responsive frontend, lead-delivery, and upgrade regression testing.

= 0.10.3 =
* Fixed assessment Publish and Update submissions being interrupted by invalid nested form markup.
* Fixed lead-form settings, including the enable checkbox, not persisting after an assessment update.
* Preserved the Save as reusable template workflow through an isolated submission that does not interfere with WordPress publishing.

= 0.10.2 =
* Added instant template search across names, descriptions, categories, and sources.
* Added category and source filters with clear empty and reset states.
* Added nine-per-page template pagination for larger commercial template libraries.
* Improved the responsive template catalog layout for desktop, tablet, and mobile.

= 0.10.1 =
* Replaced separate template and assessment import boxes with one auto-detecting JSON importer.
* Clarified that importing is optional and intended for site transfers and future template packs.

= 0.10.0 =
* Added detailed template previews before assessment creation.
* Added assessment duplication from editor and assessment-list actions.
* Added save-as-template with update-safe custom JSON storage in WordPress uploads.
* Added separate reusable template-package import and template source/version metadata.

= 0.9.0 =
* Added a complete Business Readiness Assessment template with five stages and fifteen questions.
* Added business-readiness outcome profiles, report interpretations, recommendations, and a distinct default theme.
* Added stage and question counts to template cards.
* Moved bundled assessment content into validated, versioned JSON template packages with reusable answer scales.

= 0.8.4 =
* Fixed truncated hexadecimal fields in score-band cards.
* Aligned classification and score-range controls on a consistent baseline.

= 0.8.3 =
* Replaced native RGB color popovers with direct hexadecimal color controls in Design and Scoring.
* Added live swatches, HEX validation, and safe invalid-value recovery.

= 0.8.2 =
* Redesigned answer choices as modern option cards with clearer scores and actions.
* Redesigned score bands and added synchronized editable hexadecimal color codes.

= 0.8.1 =
* Redesigned the assessment editor as a dedicated commercial workspace.
* Added branded workspace header, vertical guided navigation, improved field hierarchy, and responsive administration layouts.

= 0.8.0 =
* Added brand color, typography, radius, and width controls with a live preview.
* Expanded reports with guaranteed outcome narratives, detailed interpretations, attention areas, and recommendations.
* Improved the builder with sticky tabs, persistent workspace selection, and live stage/question/profile counts.

= 0.7.0 =
* Added a native dynamic Gutenberg assessment block.
* Added an Elementor assessment widget that loads only when Elementor is active.
* Added the complete Publish workspace and automated clean ZIP packaging.

= 0.6.0 =
* Added a reusable and extensible assessment template registry.
* Added secure, portable JSON assessment import and export.
* Added an original generic Sustainable Growth Assessment template.

= 0.5.0 =
* Added configurable report headings, sections, interpretations, recommendations, and consultation CTA.
* Added a privacy-first, opt-in consultation lead form.
* Added secure result-email delivery, consent enforcement, honeypot protection, and rate limiting.

= 0.4.0 =
* Added configurable score bands, classifications, colors, and interpretations.
* Added conditional result profiles with stage or overall score rules.
* Added profile narratives, recommendations, priorities, and frontend resolution.

= 0.3.0 =
* Added the complete visitor question flow with progress, Back/Next navigation, and required-answer validation.
* Added preliminary weighted stage and overall score summaries.
* Added responsive and accessible frontend assessment presentation.

= 0.2.0 =
* Added the visual Overview and assessment structure builder.
* Added configurable stages, questions, answer choices, scoring values, and ordering.

= 0.1.0 =
* Initial architecture and installable foundation.
