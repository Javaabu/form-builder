---
title: Defining forms
sidebar_position: 1
---

A form is a **definition**: its key, name, layout, settings and sections, each section with its fields. The same definition comes from the builder, from your code, from an import or from the AI assistant. It's stored as a **version** of the form.

# Forms, versions and drafts

| Model | Table | What |
| --- | --- | --- |
| `Form` | `forms` | The key (in URLs), name, description, `accepts_entries`, `is_locked`, and the published version |
| `FormVersion` | `form_versions` | A numbered version: layout, settings, author, `published_at` |
| `FormSection` | `form_sections` | A version's sections, in order, with their conditions |
| `FormField` | `form_fields` | A version's fields, in order. Fields of a repeating group have a `parent_id` |

- **Published versions are immutable.** Changing, adding or removing their sections and fields throws a `FormBuilderException`. That's why an entry can always be read with the version it was filled with.
- **A form has at most one draft.** It's a version that isn't published yet. `$form->draft()` returns it, making a copy of the published version first when there's none, and the builder edits only the draft.
- **Publishing** (`$form->publish($user)`) freezes the draft and makes it the version people fill. `$form->discardDraft()` throws the draft away.

```php
$form = FormBuilder::form('job-application');   // by key; throws when there's none

$form->publishedVersion;      // what people fill
$form->draftVersion;          // what the builder edits, or null
$form->currentVersion();      // the draft, else the published version
$form->isPublished(); $form->hasDraft(); $form->isOpen();   // isOpen: published and accepting entries

$version->definition();       // the version's FormDefinition
$version->number; $version->isDraft(); $version->author;
```

# Defining a form in code

Use the definition classes in `Javaabu\FormBuilder\Definitions` in a seeder (or a migration, or a command):

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Facades\FormBuilder;

class FormsSeeder extends Seeder
{
    public function run(): void
    {
        FormBuilder::define('complaint')
            ->name(['en' => 'Complaint', 'dv' => 'ޝަކުވާ'])
            ->description('Tell us what went wrong.')
            ->wizard()
            ->submitLabel(['en' => 'Send', 'dv' => 'ފޮނުވާ'])
            ->confirmation(['en' => 'We\'ve received your complaint.', 'dv' => 'ތިޔަ ޝަކުވާ ލިބިއްޖެ.'])
            ->sections([
                Section::make('you')->title(['en' => 'About you', 'dv' => 'ތިޔަބޭފުޅާގެ މަޢުލޫމާތު'])->columns(2)->fields([
                    Field::text('name')->label(['en' => 'Name', 'dv' => 'ނަން'])->required()->thaana(),
                    Field::phone('phone')->label(['en' => 'Phone', 'dv' => 'ފޯނު'])->required(),
                ]),
                Section::make('complaint')->title(['en' => 'Your complaint', 'dv' => 'ޝަކުވާ'])->fields([
                    Field::textarea('details')->label(['en' => 'Details', 'dv' => 'ތަފްޞީލު'])->required()->thaana()->rows(5),
                    Field::file('evidence')->label(['en' => 'Evidence', 'dv' => 'ހެކި'])->multiple(),
                ]),
            ])
            ->sync();
    }
}
```

**`sync()`** stores the definition:

- It creates the form if there's none.
- It gives the form a new version only when the structure changed. Running the seeder again does nothing.
- It publishes that version (`config('form-builder.sync.publish')`). Pass `sync(publish: false)` to leave a draft to review in the builder.

**Text** (names, labels, help, placeholders, option labels, messages) is a string, or a map of locale => string for every language. Plain strings are fine for single-language apps. Locale maps show in each person's language with a fallback to the default locale, with or without javaabu/translatable.

**Keys** are what answers are stored under:
- Keys are lowercase snake_case and start with a letter. Form keys may also have dashes.
- Field keys are unique across the form. A repeating group's fields are unique within the group.
- Don't rename a key after people have filled the form. Old entries keep their answers under the old key.

## Locking

`->locked()` marks a form as defined in code. The builder shows it but doesn't change it: no draft, no settings, no deleting. Imports can't overwrite it either. Change it in your seeder and run it again.

To define every form in code and have no builder at all, turn it off:

```php
// config/form-builder.php
'builder' => false,
```

`Route::formBuilder()` then registers only the forms list, previews, versions, export and the entries pages.

# The definition classes

| Class | Methods |
| --- | --- |
| `FormDefinition` | `make(key)`, `name()`, `description()`, `layout('sections'\|'wizard')`, `wizard()`, `locked()`, `submitLabel()`, `confirmation()`, `setting()`, `settings()`, `sections([...])`, `section()`, `getSection(key)`, `getFields()`, `getField('key' or 'group.key')`, `structure()`, `toArray()`, `fromArray()`, `sync(?publish)` |
| `Section` | `make(key)`, `title()`, `description()`, `columns(1–4)`, `fields([...])`, `field()`, `visibleWhen()`, `hiddenWhen()`, `visibleWhenChecked()`, `hiddenWhenChecked()`, `condition()`, `conditions()` |
| `Field` | `make(type, key)` and one constructor per built-in type, `label()`, `help()`, `placeholder()`, `required()`, `span(1\|2\|'full')`, `default()`, `setting(key, value)`, `settings([...])`, the type helpers below, `visibleWhen()` and the other conditions, `fields([...])` for repeating groups |
| `Condition` | `in(field, values)`, `notIn()`, `checked(field)`, `notChecked()`, `fromArray()` |

Every class has `toArray()`/`fromArray()` (the [JSON format](../advanced-usage/json-format.md)), and `Conditionable`, `Tappable` and `Macroable`.

**Type helpers on `Field`** (they set settings):

| Helper | Setting | Types |
| --- | --- | --- |
| `thaana()` | `thaana` | text, textarea |
| `minLength(n)`, `maxLength(n)` | `min_length`, `max_length` | text, textarea |
| `rows(n)` | `rows` | textarea |
| `min(x)`, `max(x)` | `min`, `max` | number (numbers), date and datetime (dates, or words like `today`, `-18 years`) |
| `integer()` | `integer` | number |
| `options([...])` | `options` | select, radio, checkboxes |
| `multiple()` | `multiple` | select, file |
| `searchable()` | `searchable` | select |
| `dependsOn(field, ?url, ?filter_field)` | `parent`, `options_url`, `filter_field` | select |
| `accept([...])`, `maxSize(kb)` | `accept`, `max_size` | file |
| `content(text)` | `content` | heading, paragraph |
| `minRows(n)`, `maxRows(n)`, `addLabel(text)` | `min_rows`, `max_rows`, `add_label` | repeater |

Every setting of every type is listed in [Field types](field-types.md).

# Reading definitions

```php
$definition = $form->publishedVersion->definition();

$definition->getField('full_name')->getLabel();      // 'Full name' or ['en' => …, 'dv' => …]
$definition->getField('references.name');             // a field of a repeating group
Javaabu\FormBuilder\Support\Text::resolve($field->getLabel());   // in the current locale
```
