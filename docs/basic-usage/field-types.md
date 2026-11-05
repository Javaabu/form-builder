---
title: Field types
sidebar_position: 2
---

Every field has a `type`. Each type is an `InputType` class that knows:

- its name and icon in the builder
- its settings
- the stencil component it fills as
- how its answers are stored, shown and exported

The built-in types are listed in `config('form-builder.input_types')`. To add your own, see [Custom input types](../advanced-usage/custom-input-types.md).

Every field takes `label`, `help`, `placeholder`, `required` and `span`, plus `default` for its starting value. The table lists each type's own settings.

| Type | Builder name | Stencil field | Settings | Answer |
| --- | --- | --- | --- | --- |
| `text` | Short text | `TextInput` | `thaana`, `min_length`, `max_length` (default 255), `default` | string |
| `textarea` | Long text | `Textarea` | `thaana`, `rows`, `min_length`, `max_length` | string |
| `email` | Email | `TextInput->email()` | | string |
| `number` | Number | `TextInput->numeric()` / `->integer()` | `integer`, `min`, `max` | int or float, indexed as a number |
| `phone` | Phone number | `TextInput->tel()` | | string |
| `url` | Link | `TextInput->url()` | | string |
| `select` | Dropdown | `Select` | `options`, `multiple`, `searchable`, `parent`, `options_url`, `filter_field` | the option value, or a list of them |
| `radio` | Multiple choice | `Radio` | `options`, `columns` | the option value |
| `checkboxes` | Checkboxes | `CheckboxList` | `options`, `columns` | a list of option values |
| `checkbox` | Checkbox | `Checkbox` | | `true` or `false` |
| `date` | Date | `DatePicker` | `min`, `max` | `Y-m-d`, indexed as a date |
| `time` | Time | `TimePicker` | | `H:i` |
| `datetime` | Date and time | `DateTimePicker` | `min`, `max` | `Y-m-d H:i`, indexed as a date |
| `file` | File upload | `FileUpload` | `kind` (`document`, `image`, `pdf`), `accept`, `max_size` (KB), `multiple` | the stored path, or a list of them |
| `heading` | Heading | `Content` (`form-builder-content`) | `content` | none |
| `paragraph` | Paragraph | `Content` | `content` | none |
| `repeater` | Repeating group | `Repeater` | `min_rows`, `max_rows`, `add_label`, `row_label` (`:number` for the row's number), `columns`; its `fields` | a list of rows, each an object of answers |

Notes:
- **Options** are a list of `{"value": "K", "label": "Kaafu", "parent": "…"}`. The label can be a locale map, and `parent` is for [cascading selects](structure.md#cascading-selects). `Field::options()` also takes `value => label`. Submitted values must be one of the options.
- **Thaana.** `thaana` makes a text field type Dhivehi with a Latin keyboard (javaabu/forms' Thaana input).
- **Checkboxes** are fields that conditions can watch with "checked" and "not checked".
- **Files** are stored on `config('form-builder.files.disk')` under `form-builder/{form key}`. The default largest file is `config('form-builder.files.max_size')`.
- **Display types** (`heading`, `paragraph`) take no answer and can't be watched by conditions. Their text is the `content` setting (or the label).
- **Dates.** `min` and `max` take a date or a relative one: `Field::date('date_of_birth')->max('-18 years')` for adults only.

# How answers show

Read-only entries, the entries list and CSV exports format each answer with its type:

- option values become their labels
- checkboxes become Yes or No
- dates are formatted with `config('form-builder.date_format')` (`j F Y`) in the reader's locale
- files show their names
- repeating groups show row by row

`$entry->formatted('skills')` gives the same text.
