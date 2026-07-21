# AssessCraft Product Specification

## Product definition

**Name:** AssessCraft  
**Descriptor:** Assessment & Report Builder  
**Tagline:** Build. Diagnose. Convert.

AssessCraft is a WordPress assessment platform for consultants and professional-service businesses. It turns visitor responses into structured scores, personalized reports, and opt-in qualified leads.

## Product principles

1. An assessment is more than a form or quiz.
2. Results remain private unless storage is enabled or the visitor submits a lead form.
3. Non-technical administrators should never need to write formulas.
4. Templates are portable, versioned JSON documents.
5. Scoring, presentation, and lead conversion remain separate subsystems.
6. The core works without Elementor; Elementor and Gutenberg are presentation adapters.

## Administrator workflow

`Overview -> Builder -> Scoring -> Profiles -> Report -> Lead Form -> Design -> Publish`

### Overview

Title, introduction, estimated time, start label, disclaimer, logo, and status.

### Builder

Unlimited draggable stages. Each stage contains draggable questions and answer choices. Initial types are agreement scale, custom choice, yes/no, and numeric rating.

### Scoring

Supports total, average, percentage, weighted percentage, reverse scoring, stage weights, score bands, and gaps between dimensions.

### Profiles

Rules compare stage scores, overall scores, and gaps using all/any condition groups. Profiles have priority, title, narrative, recommendation, and presentation color.

### Report

Administrators enable and reorder profile, scores, interpretations, strengths, concerns, recommendations, CTA, and restart sections.

### Lead Form

Optional form with configurable fields. Assessment results are transmitted only when the visitor submits it. Storage is disabled by default.

### Design

Theme-level colors, typography, content width, cards, buttons, spacing, progress bar, and responsive settings.

### Publish

Shortcode, Gutenberg block, Elementor widget, preview, template export, and duplication.

## Canonical data model

Assessments use the private `ac_assessment` post type for WordPress permissions, revisions, titles, and lifecycle. A versioned `_assesscraft_config` object contains portable builder configuration.

```json
{
  "schema_version": 1,
  "overview": {},
  "stages": [
    {
      "id": "stage_uuid",
      "name": "Growth",
      "weight": 1,
      "questions": [
        {
          "id": "question_uuid",
          "type": "scale",
          "prompt": "Question text",
          "required": true,
          "reverse": false,
          "answers": [
            {"id": "answer_uuid", "label": "Agree", "score": 4, "flag": null}
          ]
        }
      ]
    }
  ],
  "scoring": {"method": "weighted_percentage", "bands": []},
  "profiles": [],
  "report": {},
  "lead_form": {"enabled": false, "store_responses": false},
  "design": {}
}
```

Responses will use a separate custom table only when response storage is implemented. This avoids bloating post metadata and permits indexed reporting. No response table is created in the foundation release.

## Extension boundaries

- `assesscraft_loaded`: plugin services are registered.
- Frontend event `assesscraft:start`: an assessment begins.
- Future service interfaces: scoring engine, profile resolver, renderer, response repository, mailer, and template registry.
- Elementor and Gutenberg consume the same assessment renderer used by the shortcode.

## MVP delivery sequence

1. Foundation and portable schema.
2. Visual React-based administrator builder.
3. Frontend question runner and state management.
4. Scoring engine and deterministic test suite.
5. Profile rule builder and resolver.
6. Report composition and design controls.
7. Lead form, consent, mail delivery, and optional storage.
8. Template import/export and generic Sustainable Growth template.
9. Gutenberg block and Elementor widget.
10. Security, accessibility, compatibility, and commercial packaging.

## Free and Pro boundary

Do not enforce licensing until the complete core is stable. The likely Free edition will support one published assessment, standard scoring, basic reports, and shortcode/Gutenberg output. Pro will add unlimited assessments, weighted dimensions, conditional profiles, Elementor, advanced reports, lead workflows, integrations, import/export, and premium templates.

