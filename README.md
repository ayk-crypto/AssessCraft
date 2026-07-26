# AssessCraft

Current development version: `0.18.0-alpha.1`.

**Build. Diagnose. Convert.**

AssessCraft is a WordPress assessment and report builder for consultants and professional-service businesses. It is designed to turn visitor responses into structured scores, personalized reports, and opt-in qualified leads.

> **Current status:** Free-edition alpha. Freemius has been removed, Free limits are enforced, and Pro capabilities remain provider-independent but unavailable until the direct AssessCraft licensing experience is introduced.

## Product direction

AssessCraft will provide:

- Unlimited multi-stage assessments
- Configurable questions, answers, weights, and reverse scoring
- Score bands and conditional result profiles
- Personalized reports with strengths, concerns, and recommendations
- Privacy-first lead capture
- Reusable assessment templates
- Shortcode, Gutenberg, and Elementor publishing

## Requirements

- WordPress 6.5 or later
- PHP 8.0 or later

## Installation

1. Download or clone this repository.
2. Place it at `wp-content/plugins/assesscraft`.
3. Activate **AssessCraft - Assessment & Report Builder**.
4. Open **AssessCraft** in WordPress administration.
5. Create an assessment and use its generated shortcode.

## Current capabilities

- Private `ac_assessment` post type
- Versioned `_assesscraft_config` data model
- Secure administration shell
- Visual Overview and Builder tabs
- Configurable stages, questions, answer choices, weights, and reverse scoring
- Drag-based stage and question ordering
- Responsive visitor flow with progress, validation, and navigation
- Preliminary weighted stage and overall score summaries
- Configurable classifications and score interpretations
- Conditional profiles with personalized narratives and recommendations
- Configurable result-report sections and consultation CTA
- Consent-based lead form with secure assessment-summary email delivery
- File-based JSON template registry with Sustainable Growth and Business Readiness starters
- Versioned JSON import and export
- Native dynamic Gutenberg block
- Conditional Elementor widget
- Automated clean installable-ZIP packaging
- Shortcode renderer: `[assesscraft id="123"]`
- Frontend mounting boundary
- Privacy-first storage defaults
- Activation, deactivation, and uninstall handling

Release-hardening references are maintained in `docs/`, including upgrade and rollback procedures, compatibility testing, privacy and data handling, security/accessibility review gates, support workflow, legal drafts, tutorials, and the documentation screenshot plan.

## Development roadmap

1. Visual administrator builder
2. Frontend question runner
3. Scoring engine
4. Conditional profile resolver
5. Report builder
6. Lead form and privacy controls
7. Template import/export
8. Gutenberg and Elementor integrations
9. Security, accessibility, and commercial packaging

## Contributing

Development work should use focused branches and draft pull requests. Do not commit generated ZIP packages, WordPress installations, dependencies, credentials, or local environment files.

## License

AssessCraft is licensed under the GNU General Public License v2.0 or later. See [LICENSE](LICENSE).
