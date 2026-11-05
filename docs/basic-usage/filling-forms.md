---
title: Filling forms
sidebar_position: 5
---

# The fill pages

`Route::formBuilderFill()` registers a page for each published form:

| Route | Name | What |
| --- | --- | --- |
| `GET f/{form}` | `form-builder.show` | The form. If it isn't accepting entries, a notice instead |
| `POST f/{form}` | `form-builder.submit` | Validate and store an entry, then redirect to the thank-you page |
| `GET f/{form}/thanks` | `form-builder.thanks` | The form's confirmation message, and the entry's reference number |
| `GET f/{form}/options/{field}` | `form-builder.options` | A cascading select's options |

- **Who can fill.** The routes are public by default: guests can fill a form while it's published and accepting entries (`FormPolicy::fill`). Pass `middleware` (or `guard`) to require signing in. The signed-in user is stored as the entry's submitter.
- **Not found.** Unpublished and deleted forms answer 404.
- **JSON.** With `Accept: application/json`, `GET` returns `{ open, form }` (the stencil JSON schema) and `POST` returns `201 { message, entry }`, or 422 with errors.

```php
Route::formBuilderFill(['prefix' => 'apply', 'name' => 'apply', 'middleware' => ['auth:web']]);
```

# A form in your own page

Every published form is a stencil form. Render it wherever you like:

```php
use Javaabu\FormBuilder\Facades\FormBuilder;

public function create()
{
    $form = FormBuilder::fill('job-application')            // the published version: an EntryForm
        ->action(route('applications.store'));

    return view('applications.create', ['form' => $form]);  // Blade: {{ $form }}
    // return Inertia::render('Applications/Create', ['form' => $form]);
}

public function store(Request $request)
{
    $entry = FormBuilder::submit('job-application', $request, submitter: $request->user(), subject: $vacancy);

    return redirect()->route('applications.show', $entry);
}
```

```tsx
import { StencilForm, type StencilSchema } from "@javaabu/stencil"
import { formBuilderComponents } from "@javaabu/form-builder"

export default function Create({ form }: { form: StencilSchema }) {
  return <StencilForm schema={form} components={formBuilderComponents} />
}
```

Here's what each call does:

| Call | What |
| --- | --- |
| `FormBuilder::fill($form)` | The stencil form (`EntryForm`) of the form's published version, or of the `FormVersion` you pass. Its submit button says the form's submit label |
| `EntryForm::for($version)` | The same, for any version |
| `EntryForm::forDefinition($definition)` | A form for a definition that isn't stored (tests, previews) |
| `FormBuilder::submit($form, $input, submitter:, subject:)` | Validates (throws `ValidationException`), normalizes the answers by type, stores the entry with its version and indexes the answers. Returns the `FormEntry`. Dispatches `EntrySubmitted` |
| `$form->validate($request)` | Only validates, and returns the validated data |

`subject` ties the entry to anything: a vacancy, a workflow step, a user. Find its entries with `FormEntry::forSubject($vacancy)`.

**Editing an entry.** Bind its answers and update it. It keeps its version:

```php
$form = EntryForm::for($entry->version)->model($entry->answers())->action(route('entries.update', $entry))->method('PATCH');

app(\Javaabu\FormBuilder\Entries\EntryRecorder::class)->update($entry, $request);   // dispatches EntryUpdated
```

# What people see

The form shows in the reader's locale:
- labels, help, placeholders and options in that locale, falling back to the default
- free-text fields marked `thaana` take Dhivehi
- the layout is right to left in RTL pages

Validation messages name fields by their labels, and fields in repeating groups by their row ("Name (Reference 2)").
