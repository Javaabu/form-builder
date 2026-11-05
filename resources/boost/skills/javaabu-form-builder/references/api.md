# javaabu/form-builder API

## FormBuilder facade

| Method | What |
| --- | --- |
| `define(key)` | A `FormDefinition` to build fluently |
| `sync(FormDefinition, ?publish)` | Store it: create the form, or a new version when the structure changed |
| `form(key)` | The `Form` model; throws `FormBuilderException` |
| `fill(Form\|FormVersion\|key)` | The `EntryForm` (stencil form) of the published version, or the version given |
| `submit(form, Request\|array, submitter:, subject:, stencil:)` | Validate and store a `FormEntry` |
| `display(FormEntry)` | A read-only `DisplayForm` |
| `registerInputType(class\|instance\|array)`, `extendInputType(key, closure)`, `forgetInputType(key)`, `inputType(key)`, `hasInputType(key)`, `inputTypes()` | Input types |
| `model(name)` | The model class: `form`, `version`, `section`, `field`, `entry`, `value` |

## Definitions

- `FormDefinition::make(key)`: `name`, `description`, `layout('sections'|'wizard')`, `wizard()`, `locked()`, `submitLabel`, `confirmation`, `setting`, `sections`, `section`, `getSection`, `getFields`, `getField('key'|'group.key')`, `toArray`, `fromArray`, `sync`.
- `Section::make(key)`: `title`, `description`, `columns(1-4)`, `fields`, `field`, conditions.
- `Field::make(type, key)` or `Field::text|textarea|email|number|phone|url|select|radio|checkboxes|checkbox|date|time|datetime|file|heading|paragraph|repeater(key)`: `label`, `help`, `placeholder`, `required`, `span(1|2|'full')`, `default`, `setting`, `settings`, `thaana`, `minLength`, `maxLength`, `min`, `max`, `integer`, `rows`, `multiple`, `searchable`, `options`, `dependsOn(field, ?url, ?filter_field)`, `accept`, `maxSize`, `content`, `fields`, `minRows`, `maxRows`, `addLabel`.
- Conditions on sections and fields: `visibleWhen(field, values)`, `hiddenWhen(field, values)`, `visibleWhenChecked(field)`, `hiddenWhenChecked(field)`, `condition(Condition::in|notIn|checked|notChecked(...))`. All must hold.

## Field types and settings

| Type | Settings |
| --- | --- |
| `text` | `thaana`, `min_length`, `max_length`, `default` |
| `textarea` | `thaana`, `rows`, `min_length`, `max_length` |
| `email`, `phone`, `url`, `time`, `checkbox` | none |
| `number` | `integer`, `min`, `max` |
| `select` | `options`, `multiple`, `searchable`, `parent`, `options_url`, `filter_field` |
| `radio`, `checkboxes` | `options`, `columns` |
| `date`, `datetime` | `min`, `max` (dates or `today`, `-18 years`) |
| `file` | `kind` (`document`, `image`, `pdf`), `accept`, `max_size`, `multiple` |
| `heading`, `paragraph` | `content` |
| `repeater` | `min_rows`, `max_rows`, `add_label`, `row_label`, `columns`, and `fields` |

## Models

- `Form`: `key`, `name`, `description`, `is_locked`, `accepts_entries`; `versions`, `publishedVersion`, `draftVersion`, `entries`; `draft()`, `publish()`, `discardDraft()`, `currentVersion()`, `isPublished()`, `hasDraft()`, `isOpen()`, `definition()`.
- `FormVersion`: `number`, `layout`, `settings`, `published_at`, `author`; `definition()`, `stencil()`, `isDraft()`, `replaceStructure()` (drafts only).
- `FormEntry`: `status` (`submitted`, `draft`), `data`, `locale`, `submitted_at`, `submitter`, `subject`, `version`, `form`, `values`; `answers()`, `answer(path)`, `formatted(path)`; scopes `whereAnswer(field, op, value)`, `forSubject()`, `submittedBy()`, `submitted()`.
- Events: `EntrySubmitted`, `EntryUpdated`.

## Routes

`Route::formBuilder(['guard', 'prefix' => 'forms', 'name' => 'forms', 'middleware', 'controllers'])` registers:
- `index`, `create`, `store`, `import`, `import.store`, `assistant.create`
- `show` (the builder), `edit`, `update`, `destroy`, `publish`, `draft.destroy`, `structure`, `assistant`
- `preview`, `preview.check`, `versions`, `export`, `options`
- `sections.create|store|order|edit|update|destroy|move`
- `fields.create|store|order|edit|update|destroy|move`
- `entries.index|export|show|print|file|destroy`

`Route::formBuilderFill(['guard', 'prefix' => 'f', 'name' => 'form-builder', 'middleware', 'controller'])` registers `show`, `submit`, `thanks` and `options`.

## Permissions

`view_forms`, `edit_forms`, `delete_forms`, `publish_forms`, `view_form_entries`, `export_form_entries`, `delete_form_entries` (`FormPolicy` constants).

Abilities:
- `viewAny`, `view`, `create`, `update`, `delete`, `publish`
- `viewEntries`, `exportEntries`, `deleteEntries`
- `fill` (guests allowed)

## Config (`config/form-builder.php`)

- `input_types`, `builder`
- `models.*`, `tables.*`
- `entries.store_ip`, `entries.columns`
- `files.disk`, `files.directory`, `files.max_size`
- `date_format`, `datetime_format`, `sync.publish`, `options_route`
- `views`, `layout`, `layout_section`, `per_page`
- `controllers.*`, `policies.*`, `register_permissions`
- `assistant.enabled`, `.provider`, `.model`, `.timeout`, `.attempts`, `.max_length`

## React (`@javaabu/form-builder`)

- `formBuilderComponents` (`repeater`, `form-builder-content`), `FormBuilderRepeater`, `FormBuilderContent`, `rowFromTemplate`
- The Inertia pages: `FormsIndex`, `FormsShow`, `EntriesShow`, `FillShow` and the rest
- `SortableList`, `createSortable`

## Commands

- `form-builder:export {form} {path?} {--draft}`
- `form-builder:import {path} {--key=} {--publish}`
