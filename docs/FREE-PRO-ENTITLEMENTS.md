# Frozen direct-market Free and Pro definition

This is the authoritative product boundary for direct distribution from AssessCraft.com. It supersedes the abandoned WordPress.org separation plan.

AssessCraft Free is the required core plugin. AssessCraft Pro is a separate `assesscraft-pro` add-on installed alongside Free. Pro supplies a verified `pro` entitlement through the existing `assesscraft_current_plan` filter; it does not replace Free, fork the assessment schema, or copy assessment records.

## Free

- One published assessment; additional assessments remain editable drafts.
- Unlimited stages and questions.
- Standard scoring, bands, and on-screen reports.
- Up to three editable result profiles.
- Shortcode and Gutenberg publishing.
- Consultation forms and WordPress lead storage/dashboard.
- One bundled starter template.
- Primary and accent design colors.

## Pro

- Unlimited published assessments and profiles.
- Weighted and reverse scoring.
- Email consultation notifications.
- Elementor widget selection.
- JSON import/export and custom reusable templates.
- Full design controls.
- CSV lead export and configurable retention.

## Compatibility contract

- Free and Pro use the same `ac_assessment` post type and versioned `_assesscraft_config` data.
- Installing or activating Pro requires no assessment migration or duplication.
- Deactivating Pro leaves Free active and all assessment data intact.
- Free continues rendering existing Pro-authored assessments after license expiry or Pro deactivation.
- Restricted Pro configuration is preserved read-only until Pro is reactivated.
- The Pro add-on may extend services through hooks, but it must not redefine or replace the core schema.

## Distribution decision

- `0.18.1` remains an immutable historical release and is not the direct-market Free package.
- `0.18.2` is the first frozen direct-download Free edition for AssessCraft.com.
- WordPress.org submission and the related Free/Pro code-separation refactor are out of scope.
- Free and Pro will be downloaded, licensed, updated, and supported through AssessCraft.com.

## Downgrades

Existing published assessments keep rendering after a license expires. Existing profile, weighting, reverse-scoring, email, and advanced-design configuration is preserved rather than deleted. Restricted controls become read-only and new Pro-only actions are blocked server-side. Reactivating Pro restores editing without a migration.

For controlled development tests only, define `ASSESSCRAFT_COMMERCIAL_ENFORCEMENT` as `false` before AssessCraft loads.
