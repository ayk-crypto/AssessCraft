# Free and Pro entitlements

AssessCraft resolves the current plan through the `assesscraft_current_plan` filter. Version 0.18 is distributed as the Free edition and has no bundled licensing SDK, account screen, checkout, or telemetry provider. Commercial enforcement is enabled by default.

The provider-neutral filter remains in place so a future direct AssessCraft Pro edition can supply a verified `pro` entitlement without changing assessment data or feature rules.

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

## Downgrades

Existing published assessments keep rendering after a license expires. Existing profile, weighting, reverse-scoring, email, and advanced-design configuration is preserved rather than deleted. Restricted controls become read-only and new Pro-only actions are blocked server-side. Reactivating Pro restores editing without a migration.

For controlled development tests only, define `ASSESSCRAFT_COMMERCIAL_ENFORCEMENT` as `false` before AssessCraft loads.
