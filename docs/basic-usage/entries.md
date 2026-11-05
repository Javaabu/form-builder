---
title: Entries
sidebar_position: 6
---

An entry is someone's answers to a form, tied to the version they filled.

| Column | What |
| --- | --- |
| `form_id`, `form_version_id` | The form, and the version filled |
| `submitter_type`, `submitter_id` | Who filled it (any user model), when signed in |
| `subject_type`, `subject_id` | What it's about, if anything |
| `status` | `submitted`, or `draft` for entries saved before submitting |
| `data` | Every answer, keyed by field: repeating groups are lists of rows |
| `locale`, `ip_address`, `submitted_at` | The locale it was filled in; the IP address when `config('form-builder.entries.store_ip')` is on |

```php
$entry->answers();                     // ['full_name' => 'އަޙްމަދު', 'references' => [['name' => '…'], …]]
$entry->answer('references.1.name');
$entry->formatted('skills');           // 'PHP, Design': option labels, dates, Yes/No, file names
$entry->version->definition();         // the structure it was filled with
$entry->submitter; $entry->subject; $entry->form;
```

# Querying answers

Each answer is also a row in `form_entry_values`:
- lists have one row per item
- repeating groups have one row per field per row
- answers are typed: `value` (text), `number` and `date`

```php
use Javaabu\FormBuilder\Models\FormEntry;

FormEntry::query()->where('form_id', $form->id)->whereAnswer('age', '>=', 18)->get();
FormEntry::query()->whereAnswer('skills', 'php');                        // any chosen option
FormEntry::query()->whereAnswer('references.relationship', 'other');     // a field in a repeating group
FormEntry::query()->whereAnswer('available_from', '<=', now());          // dates compare as dates
FormEntry::forSubject($vacancy)->submittedBy($user)->submitted()->latest()->first();
```

Numbers compare as numbers when you pass an int or float, and dates when you pass a `DateTimeInterface`.

# The entries pages

`Route::formBuilder()` includes them:

| Route | Name | What |
| --- | --- | --- |
| `GET forms/{form}/entries?version=` | `forms.entries.index` | The entries, newest first, with the first `config('form-builder.entries.columns')` answers (4) and a version filter |
| `GET forms/{form}/entries/export?version=` | `forms.entries.export` | CSV of every entry (streamed, UTF-8 with a BOM so spreadsheets read Thaana) |
| `GET forms/{form}/entries/{entry}` | `forms.entries.show` | The entry read-only, with links to its files |
| `GET forms/{form}/entries/{entry}/print` | `forms.entries.print` | A standalone printable page with print styles |
| `GET forms/{form}/entries/{entry}/files/{field}/{index?}` | `forms.entries.file` | Download a file answer: `cv`, `cv/1` for the second of several, `references.0.letter` in a repeating group |
| `DELETE forms/{form}/entries/{entry}` | `forms.entries.destroy` | Delete it (soft delete) |

The CSV has one column per field across every version of the form (newest version's fields first). A repeating group's field is one column, its rows joined with ` | `.

**Showing an entry elsewhere.** `FormBuilder::display($entry)` is a read-only stencil form (`DisplayForm`). It has:
- each section, with each answer as a text entry
- repeating groups row by row
- fields their conditions hid left out

It's embedded (no `<form>` tag), so put it in any page:

```blade
{{ \Javaabu\FormBuilder\Facades\FormBuilder::display($entry) }}
```

```tsx
<StencilFields schema={display} components={formBuilderComponents} />
```

`DisplayForm::forAnswers($version, $answers)` does the same for answers that aren't stored.

**Print page.** It's Blade in both stacks: a standalone document prints the same in every app. Open it from the entry page's Print button.
