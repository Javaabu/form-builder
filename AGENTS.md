# form-builder: notes for AI coding agents

`javaabu/form-builder` is a Laravel package for dynamic forms. It stores forms in the database:

- definitions, as versions with sections and fields
- entries, each indexed in `form_entry_values`

A version compiles to a javaabu/stencil form, which renders in Blade and React. Admins build forms on `Route::formBuilder()` pages (Blade or Inertia React), and people fill them on `Route::formBuilderFill()` pages. The package also ships TypeScript (imported by apps as `@javaabu/form-builder` through javaabu/manifest) and a prebuilt `dist/` for the Blade scripts and styles.

These are public contracts, so treat them as such: javaabu/workflows builds on them.
- the definition classes, `FormBuilderManager`/`FormBuilder`, the models
- `EntryForm`, `DisplayForm`, `Repeater`, `EntryRecorder`
- `InputType`
- the route macros and their route names
- the JSON format
- the page props and the `@javaabu/form-builder` exports

## Layout

| Path | What |
| --- | --- |
| `src/Definitions` | `FormDefinition`, `Section`, `Field`, `Condition` (fluent, arrays), `DefinitionValidator` |
| `src/Models`, `database/migrations` | `Form` (and `Translatable\Form`), `FormVersion`, `FormSection`, `FormField`, `FormEntry`, `FormEntryValue`; published versions are immutable (`GuardsPublishedVersion`) |
| `src/InputTypes` | `InputType` and the 17 built-in types |
| `src/Compiler` | `Compiler` and `CompileContext`: definitions to stencil components (fill and display modes, rows of repeating groups) |
| `src/Stencil` | `EntryForm`, `DisplayForm`, `Repeater`, `Content` |
| `src/Entries` | `EntryRecorder` (validate, normalize, store, index), `EntryColumns`, `EntriesExport` |
| `src/Builder` | `StructureEditor` (changes by key), `DraftEditor` (validated draft changes), `BuilderForms` (the builder's stencil forms), `BuilderData` (page data) |
| `src/Transfer`, `src/Commands` | `Exporter`, `Importer`, `form-builder:export`/`import` |
| `src/Ai` | `FormAssistant`, `FormAssistantAgent` (optional laravel/ai) |
| `src/Routing`, `src/Http`, `src/Policies`, `src/Views` | Route macros, controllers, policies, `ViewRenderer` |
| `src/Support` | `Text` (locale maps), `Translations` (optional translatable, feature-detected), `Strings` (page strings) |
| `resources/views` | Blade pages (javaabu/forms), partials, the stencil views for the repeater and content |
| `resources/js` | TypeScript: the stencil components, the Inertia pages, `SortableList`, `sortable.ts`, the Blade scripts in `blade/` |
| `resources/css/form-builder.css` | Styles (logical properties only) |
| `dist/` | Prebuilt Blade scripts and styles, **committed** |
| `lang/en.json`, `lang/dv.json` | Translations; English strings are the keys |
| `tests/js/fixtures` | Page props written by `InertiaPagesTest`, the repeater's Blade markup and a custom field's schema, rendered by the JS tests |
| `docs`, `llms.txt`, `resources/boost` | Docs, the index for assistants, the Laravel Boost guideline and skill |

## Rules

- Follow the Javaabu PHP guidelines (`vendor/javaabu/boost-javaabu-guidelines`): snake_case variables and properties, camelCase methods, `#[Test]` methods named `it_…`, early returns, no `else`. Pint enforces formatting.
- Builder changes are definition changes: load the draft's definition, change it with `StructureEditor`, and save it through `DraftEditor`, which validates the whole definition. Never write sections or fields of a published version.
- New stencil behaviour for form-builder fields goes in the compiler or an input type, not in the views. A field type's Blade view and React component must render the same thing.
- When the page props change, run `FORM_BUILDER_UPDATE_FIXTURES=1 vendor/bin/phpunit --filter "InertiaPagesTest|it_renders_with_blade|custom_types_render"` and commit `tests/js/fixtures`.
- After changing `resources/js/blade`, `resources/js/sortable.ts` or `resources/css`, run `pnpm build` and commit `dist/`. CI fails when it's stale.
- Nothing may assume the `web` guard or `App\Models\User`. HTTP tests use two guards: `web` → `User` and `web_admin` → `Admin`.
- Inertia, javaabu/translatable and laravel/ai are optional:
  - Feature-detect them. Translatable goes only through `Support\Translations`, and laravel/ai through `FormAssistant::available()`.
  - Keep them in `suggest`.
  - Keep test classes loadable without them. CI has a leg without all three.
- Every user-facing string goes through `__()` with an English key and gets a line in `lang/en.json` and `lang/dv.json` (`LangTest` checks). Use Thaana in tests of user-facing behaviour. List uncertain translations in `docs/advanced-usage/dhivehi-and-rtl.md`. Views use logical CSS only.
- Every change needs tests, docs (linked from `llms.txt`, checked by `DocsTest`), the Boost skill if usage changes, and a line in `CHANGELOG.md`.
- Work on `main`. Never tag or release.

## Commands

```bash
composer install && pnpm install      # pnpm 10.28 (packageManager); composer first (stencil's React code is in vendor/)
composer lint && composer test        # Pint and PHPUnit
pnpm lint && pnpm format:check && pnpm typecheck && pnpm test
pnpm build                            # dist/
pnpm examples                         # http://localhost:5173
```
