# AssessCraft JSON template format

Bundled templates live in `templates/*.json`. Additional directories can be registered with the `assesscraft_template_directories` WordPress filter.

Each file contains:

- `assesscraft_template`: format marker, currently `1`
- `schema_version`: target AssessCraft schema
- `version`: content-package version
- `slug`, `name`, `description`, and `category`: library metadata
- `scales`: optional reusable answer scales
- `config`: the assessment configuration

Questions may provide canonical `answers` directly or reference a reusable scale with `"scale": "agreement"`. The registry expands scale references, validates the file size and markers, sanitizes all metadata, and passes the hydrated configuration through `AssessCraft_Schema` before it reaches WordPress.

Assessment exports remain fully expanded portable JSON documents. Template packages are source content; exports are site-created assessment instances.
