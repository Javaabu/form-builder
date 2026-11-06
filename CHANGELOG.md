# Changelog

All notable changes to `javaabu/form-builder` are documented in this file.

## Unreleased

Initial development of v1.0:

- Form definitions in the database: `forms`, `form_versions`, `form_sections`, `form_fields`. Each form has drafts and immutable published versions, and entries keep the version they were filled with.
- Fluent definitions (`FormDefinition`, `Section`, `Field`, `Condition`) for seeders. `FormBuilder::define()->…->sync()` is idempotent. Forms can be locked to code, and `form-builder.builder` turns the builder off.
- 17 input types (text, long text, email, number, phone, link, dropdown, multiple choice, checkboxes, checkbox, date, time, date and time, file upload, heading, paragraph, repeating group).
- An extensible registry for input types: `registerInputType()`, `extendInputType()`, `forgetInputType()`.
- Forms compile to javaabu/stencil (`EntryForm`, `DisplayForm`):
  - sections and wizards
  - declarative conditions on sections and fields
  - cascading selects with an options endpoint
  - repeating groups (the `Repeater` stencil field, with a Blade view and script and a React component)
- Entries (`form_entries`, `form_entry_values`):
  - `FormBuilder::submit()`, `EntryRecorder`, the `EntrySubmitted` and `EntryUpdated` events
  - `whereAnswer()` queries
  - read-only and printable display, CSV export, file downloads
- `Route::formBuilder()`: the forms list, the builder (add, configure, reorder by drag and drop or the keyboard, move, delete), settings, previews, publishing, discarding drafts, versions, import and export, entries.
- `Route::formBuilderFill()`: fill, submit, thank-you and options pages.
- Every page in Blade (javaabu/forms) and Inertia React (@javaabu/forms, Onu), through javaabu/manifest, with a prebuilt `dist/`. JSON responses for every route.
- Overridable controllers and policies. Seven permissions through javaabu/permissions. Any guard and user model.
- JSON export and import (format 1), validated, from the builder or `form-builder:export` and `form-builder:import`.
- Optional javaabu/translatable: form names and descriptions in every language, and per-locale inputs in the builder.
- Optional laravel/ai assistant that builds or changes a form's draft from a description. It's validated with one retry and tested with laravel/ai's fakes.
- English and Dhivehi strings, Thaana inputs, logical CSS only.
- Docs, `llms.txt`, the Laravel Boost guideline and skill, and runnable examples.
- React and Inertia npm packages are declared for pages only (`npm(..., for: 'pages')`), so Blade-only apps' `manifest:check` doesn't ask for them.
