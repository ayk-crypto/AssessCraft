# Upgrades, migrations, and rollback

## Version model

AssessCraft tracks three independent versions:

- `ASSESSCRAFT_VERSION`: installed plugin release.
- `assesscraft_migration_version`: completed plugin-level migrations.
- `schema_version` inside every `_assesscraft_config` record.
- `assesscraft_leads_db_version`: consultation-request table structure.

Never infer one version from another. Each migration must be incremental and idempotent.

## Upgrade process

1. Back up the WordPress database and `wp-content`.
2. Test the upgrade on staging using a copy of production data.
3. Install the new plugin package without deleting the previous plugin data.
4. AssessCraft acquires a temporary migration lock and runs migrations sequentially.
5. Before an assessment configuration changes, its previous configuration is stored in `_assesscraft_config_backup`.
6. The migration version advances only after the migration completes.
7. Review the migration log and test one low-, middle-, and high-scoring response.

## Compatibility with 0.15.x

Schema version 2 accepts 0.15.x schema-version-1 configurations. It preserves overview content, stages, questions, answers, scoring bands, profiles, report settings, lead settings, and design values. The legacy `lead_form.email_enabled` field is mapped to `lead_form.send_results`.

## Rollback

Code rollback and data rollback are different operations.

1. Put the site in maintenance mode and take a fresh database backup.
2. Restore the prior plugin package.
3. If the earlier version cannot read the new schema, restore the pre-upgrade database backup. This is the safest rollback.
4. For a single assessment, an administrator may restore `_assesscraft_config_backup` into `_assesscraft_config` after validating the backup structure.
5. Do not reduce migration-version options manually without also restoring the corresponding data.

AssessCraft never automatically rolls a schema backward. Automated down-migrations can destroy fields that older releases do not understand.
