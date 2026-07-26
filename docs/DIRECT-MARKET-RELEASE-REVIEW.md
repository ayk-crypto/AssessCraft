# Direct-market release review — 0.18.2

## Frozen product decisions

- AssessCraft Free is distributed directly from AssessCraft.com.
- Free permits one published assessment and up to three result profiles.
- AssessCraft Pro is a separate `assesscraft-pro` add-on installed alongside Free.
- Free remains the required core plugin; Pro does not replace it.
- Both editions use the same `ac_assessment` post type and `_assesscraft_config` schema.
- The published `0.18.1` artifact remains immutable and is not the direct-market Free package.
- The WordPress.org submission and separation refactor are not part of this release.

## Free edition

- One published assessment; additional assessments remain editable drafts.
- Unlimited stages, questions, and answer choices.
- Standard scoring and score bands.
- Up to three basic result profiles.
- Standard reports.
- Gutenberg block and shortcode.
- WordPress consultation storage and dashboard.
- One starter template.
- Primary and accent color controls.
- Documentation support.

## Pro add-on

- Unlimited published assessments and profiles.
- Weighted and reverse scoring.
- Advanced conditional profiles.
- Elementor integration.
- JSON import/export, custom reusable templates, and premium templates.
- CSV lead export and consultation email notifications.
- Advanced design controls and configurable retention workflows.
- Licensed automatic updates and priority support.

## Compatibility gates

- Activating Pro must not migrate, copy, or rewrite existing assessment records.
- Deactivating Pro must leave Free active and preserve all assessment data.
- Existing Pro-authored assessments must continue rendering after expiry or deactivation.
- Restricted Pro configuration must remain stored read-only and become editable again after reactivation.
- Free and Pro package tests must verify schema compatibility before commercial release.

## Release gates

- Version metadata, ZIP filename, changelog, release notes, and tag all match `0.18.2`.
- Unit, standards, PHP compatibility, JavaScript, packaging, and ZIP-integrity checks pass.
- The final ZIP is built from the merged protected branch.
- The release checksum matches the downloadable artifact.
- Free-to-Pro activation and Pro-to-Free deactivation smoke tests pass before the paid launch.
