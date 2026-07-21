# AssessCraft

**Build. Diagnose. Convert.**

AssessCraft is a WordPress assessment and report builder for consultants and professional-service businesses. It is designed to turn visitor responses into structured scores, personalized reports, and opt-in qualified leads.

> **Current status:** Early development. The Overview and visual structure builder are implemented; scoring, profiles, reports, and the complete frontend runner remain in development.

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
- Shortcode renderer: `[assesscraft id="123"]`
- Frontend mounting boundary
- Privacy-first storage defaults
- Activation, deactivation, and uninstall handling

See [the product specification](docs/PRODUCT-SPECIFICATION.md) for the complete architecture and delivery sequence.

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
