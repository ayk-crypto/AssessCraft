# Compatibility and stability test matrix

## Automated matrix

Every pull request must run:

| Area | Matrix |
| --- | --- |
| PHP syntax | 8.0, 8.1, 8.2, 8.3, 8.4 |
| Unit tests | PHP 8.0 and 8.3 |
| JavaScript syntax | Node 20 |
| WordPress coding standards | Current WPCS |
| PHP compatibility | PHP 8.0 and later |
| Packaging | Clean ZIP plus archive integrity test |

## WordPress release matrix

Test fresh installation and upgrade from the previous public release on:

- WordPress 6.5 (minimum supported)
- Latest WordPress minor release
- WordPress trunk before each major release
- Single site and multisite subsite activation

## Manual ecosystem matrix

Record the plugin version, WordPress version, PHP version, theme/plugin version, browser, result, and evidence for every run.

### Themes

- Twenty Twenty-Four
- Twenty Twenty-Five
- Twenty Twenty-Six or current default theme
- Hello Elementor
- Astra

### Builders and integrations

- Gutenberg
- Elementor Free latest
- Elementor Pro latest
- Shortcode in the Classic Editor

### Caching and optimization

- LiteSpeed Cache
- WP Rocket
- W3 Total Cache
- Autoptimize
- Cloudflare cache with HTML caching disabled for assessment pages

Validate start, navigation, required-question validation, completion, reports, lead consent, lead storage, Pro email, restart, responsive layout, cache purge, and exclusion from delayed/deferred JavaScript when required.
