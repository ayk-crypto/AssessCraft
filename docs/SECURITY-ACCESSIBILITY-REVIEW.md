# Security, accessibility, and translation review

## Security review gates

- Capabilities and nonces protect every administrator mutation.
- REST submissions validate assessment status, consent, rate limits, field length, and email format.
- SQL values use `$wpdb->prepare`; dynamic table/column identifiers come only from controlled values.
- CSV values beginning with `=`, `+`, `-`, or `@` are neutralized.
- Imports enforce size limits, upload validity, JSON structure, schema migration, and sanitization.
- Logs exclude contact details, responses, license keys, and secrets.
- Uninstall deletion is opt-in and exact in scope.
- Run Plugin Check and a dependency vulnerability scan before release.

## Accessibility review gates

- Complete the assessment using only a keyboard.
- Maintain visible focus and logical focus order between questions.
- Announce validation and progress changes to assistive technology.
- Use semantic buttons, labels, fieldsets, legends, headings, tables, and status regions.
- Meet WCAG 2.2 AA contrast for every configurable palette.
- Do not communicate score/classification through color alone.
- Verify 200% zoom, narrow viewport reflow, reduced motion, and screen-reader output.

## Translation readiness

- Every user-facing PHP string uses the `assesscraft` text domain.
- Dynamic JavaScript messages are supplied through localized data or translation APIs.
- No sentence fragments are concatenated in ways that prevent grammatical translation.
- Dates and numbers use WordPress locale functions.
- Generate a POT file before release and test a right-to-left locale.
