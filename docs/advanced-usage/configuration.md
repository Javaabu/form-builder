---
title: Configuration
sidebar_position: 1
---

Publish `config/form-builder.php` with `php artisan vendor:publish --tag=form-builder-config`. Every option has a default, so the common case needs none.

| Option | Default | What |
| --- | --- | --- |
| `input_types` | the 17 built-in types | The field types forms can use and the builder offers. Add, replace (a subclass with the same key) or remove types. See [Custom input types](custom-input-types.md) |
| `builder` | `true` (`FORM_BUILDER_BUILDER`) | Off: forms come only from code, and `Route::formBuilder()` registers nothing that changes a form |
| `models.form` | `null` | The form model. `null` uses `Models\Translatable\Form` when javaabu/translatable is on and `Models\Form` otherwise |
| `models.version`, `.section`, `.field`, `.entry`, `.value` | the package's models | Extend a model and set it here |
| `tables.*` | `forms`, `form_versions`, `form_sections`, `form_fields`, `form_entries`, `form_entry_values` | Table names (set them before migrating) |
| `entries.store_ip` | `false` | Keep the submitter's IP address with each entry |
| `entries.columns` | `4` | How many answers the entries list shows |
| `files.disk` | `null` (`FORM_BUILDER_DISK`) | The disk file answers are stored on; `null` is the default disk. Use a private one |
| `files.directory` | `form-builder` | The directory, with the form's key under it |
| `files.max_size` | `10240` | The default largest file, in KB |
| `date_format`, `datetime_format` | `j F Y`, `j F Y, H:i` | How date answers are shown (translated) |
| `sync.publish` | `true` | Whether `FormBuilder::sync()` publishes the version it makes |
| `options_route` | `null` | The route that serves cascading options. `null` uses the page's own, then the fill routes', then the admin routes' |
| `views` | `blade` (`FORM_BUILDER_VIEWS`) | `blade` or `inertia` |
| `layout`, `layout_section` | `layouts.app`, `content` | The Blade layout the pages extend and the section they fill |
| `per_page` | `20` | Forms and entries per page |
| `controllers.*` | the package's | `forms`, `sections`, `fields`, `entries`, `fill`, `assistant`. See [Controllers and views](controllers-and-views.md) |
| `policies.form`, `policies.entry` | `FormPolicy`, `FormEntryPolicy` | See [Permissions](../basic-usage/permissions.md) |
| `register_permissions` | `true` | Register the seven permissions with javaabu/permissions |
| `assistant.enabled` | `true` (`FORM_BUILDER_ASSISTANT`) | The AI assistant, when laravel/ai is installed |
| `assistant.provider`, `assistant.model` | `null` | What laravel/ai uses; `null` is its defaults |
| `assistant.timeout`, `.attempts`, `.max_length` | `120`, `2`, `4000` | Seconds per answer; answers it gets to make a valid form; the longest description |

# Assets

javaabu/manifest registers the package as `form-builder`, with:

| Asset | What |
| --- | --- |
| `form-builder::styles` | Styles for repeating groups, headings and paragraphs (logical properties only) |
| `form-builder::builder` | The Blade builder page's ordering and confirmations; required by that page |
| `form-builder::repeater` | Adding and removing rows in Blade forms; required by the repeater view |
| `@javaabu/form-builder` | The React components and pages, from `resources/js` |

The Blade scripts and styles are prebuilt in `dist/`, so apps that don't build the package with Vite still get them. They import no npm packages. @javaabu/forms, @javaabu/onu, React and Inertia are declared with `npm(..., for: 'pages')`, so `manifest:check` asks only Inertia apps for them.
