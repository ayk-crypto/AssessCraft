# Archived WordPress.org release review — 0.18.1

This document is retained as historical release evidence only. The WordPress.org
submission and separation refactor were abandoned on 2026-07-26. The active
direct-market release checklist is `DIRECT-MARKET-RELEASE-REVIEW.md`.

## Release metadata

- Plugin header version: `0.18.1`
- Runtime version constant: `0.18.1`
- Gutenberg editor asset version: `0.18.1`
- WordPress.org stable tag: `0.18.1`
- Planned Git tag: `v0.18.1`
- Planned ZIP: `assesscraft-free-0.18.1.zip`
- Minimum WordPress: `6.5`
- Tested up to WordPress: `7.0`
- Minimum PHP: `8.0`

## Translation readiness

- PHP user-facing strings use the `assesscraft` text domain.
- The main plugin header declares `Text Domain: assesscraft`.
- Translation functions do not use a mismatched text domain.
- Dates and stored timestamps are formatted with WordPress locale-aware
  functions in administrator views.
- Dynamic configuration and visitor-authored content remain data, not
  translatable interface strings.
- WordPress.org GlotPress can extract plugin strings after directory approval.
- The exact release contents are checked by the official WordPress Plugin Check
  action, including its internationalization checks.

## Accessibility review

- Assessment progress uses a named `progressbar` with current, minimum, maximum,
  and textual progress values.
- Questions use headings, a named radio group, native radio inputs, and labels.
- Required-answer errors use `role="alert"`.
- Consultation submission status uses a polite live region; failures switch to
  an alert.
- Completion headings and successful submission messages receive programmatic
  focus.
- Buttons are native controls and remain keyboard operable.
- Decorative icons are hidden from assistive technology where appropriate.
- Color is supplemented by labels, numeric scores, and text classifications.
- The default small-text accent now has a contrast ratio above 5:1 on the
  frontend light background; CTA button text also remains above 5:1.
- Frontend layouts include narrow-screen reflow and do not require horizontal
  navigation.
- Keyboard navigation, 200% zoom, and contrast smoke testing was completed on
  the staging release candidate.

## Packaging review

- Development files, tests, repository metadata, release notes, and
  WordPress.org artwork are excluded from the installable ZIP.
- The ZIP contains one top-level `assesscraft` directory.
- WordPress.org icons, banners, and screenshots are maintained separately for
  the SVN `assets` directory.
- The build workflow creates the exact named ZIP and its SHA-256 checksum.
- The release workflow and PR checks were required before `v0.18.1` was created.
