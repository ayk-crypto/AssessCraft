# AssessCraft Pro add-on architecture

## Product boundary

AssessCraft Free remains the required core plugin. It owns:

- the `ac_assessment` post type;
- the `_assesscraft_config` assessment schema;
- assessment rendering and reports;
- lead storage and privacy integration;
- the shared feature matrix and entitlement enforcement.

AssessCraft Pro is installed alongside Free and never creates a second assessment post type or a duplicate configuration schema. Pro changes the current entitlement plan only when its dependency and license checks pass.

This design keeps Free-to-Pro and Pro-to-Free transitions reversible. When Pro is deactivated or a license expires, assessment content remains intact while restricted controls return to Free limits.

## Initial add-on foundation

The `assesscraft-pro` plugin currently provides:

1. WordPress plugin dependency metadata for the `assesscraft` core slug.
2. A runtime requirement for AssessCraft Free 0.18.2 or newer.
3. A provider-independent license API client.
4. License activation, refresh and disconnection controls.
5. Daily license status checks.
6. A filter that changes `assesscraft_current_plan` to `pro` only when licensed.
7. A Pro-aware plan-management URL and WordPress administration screen.
8. Separate Free and Pro package pipelines.
9. Package regression checks proving that Pro files are excluded from Free ZIP files.

## License API contract

The default API base is:

`https://assesscraft.com/wp-json/assesscraft-license/v1`

The base can be replaced with the `assesscraft_pro_license_api_url` filter.

Supported actions:

- `POST /activate`
- `POST /check`
- `POST /deactivate`

Request fields:

- `license_key`
- `site_url`
- `product` (`assesscraft-pro`)
- `pro_version`
- `core_version`

Expected JSON response:

```json
{
  "success": true,
  "status": "active",
  "expires_at": "2027-07-30T00:00:00Z",
  "message": "License verified."
}
```

For internal staging only, `ASSESSCRAFT_PRO_DEV_MODE` may be defined as `true` in `wp-config.php`. This bypass is not enabled by default and must not be used in production packages supplied to customers.

## Capability activation

The current Free codebase already contains provider-independent gates for:

- unlimited published assessments;
- unlimited profiles;
- weighted scoring;
- reverse scoring;
- consultation email notifications;
- CSV lead export;
- Elementor integration;
- JSON import/export;
- custom templates;
- advanced design controls;
- priority support indicators.

The first Pro alpha activates these existing gates through the shared plan filter. Each capability still requires a focused regression test before the commercial release.

## Delivery sequence

### Phase 1 — add-on foundation

- dependency checks;
- license state and administration;
- entitlement filter;
- separate package builds;
- Free/Pro isolation checks.

### Phase 2 — capability verification

- unlimited publishing and profiles;
- weighted and reverse scoring;
- Elementor widget;
- JSON portability;
- custom/premium templates;
- lead CSV export;
- consultation emails;
- advanced design and retention controls.

### Phase 3 — commercial licensing service

- server-side license generation;
- activation limits per purchase;
- renewals, expiry and grace period;
- customer account and download permissions;
- signed update metadata;
- automatic Pro updates;
- cancellation and refund state handling.

### Phase 4 — release hardening

- Free-to-Pro and Pro-to-Free smoke tests;
- expired-license behavior tests;
- clean install on supported WordPress/PHP versions;
- staging checkout-to-activation test;
- final Pro ZIP, checksum, tag and release evidence.
