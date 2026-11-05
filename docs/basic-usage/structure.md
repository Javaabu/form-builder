---
title: Sections, groups and conditions
sidebar_position: 3
---

# Sections

Every field is in a section. A section has:
- a key and a title
- an optional description
- `columns` (1 to 4) to lay its fields out in a grid from the `md` breakpoint

Fields take `span(2)` or `span('full')` for wider cells.

```php
Section::make('address')->title(__('Address'))->columns(2)->fields([
    Field::text('line')->label(__('Address line'))->span('full'),
    Field::select('atoll')->label(__('Atoll'))->options($atolls),
    Field::text('island')->label(__('Island')),
]);
```

They compile to stencil `Section` components (`<x-forms::card>` / `Card`).

# Wizards

`layout('wizard')` (or `->wizard()`) shows one section at a time, as the steps of a stencil `Wizard`, with Back and Next:

- Next checks the step's fields in the browser.
- The submit button shows on the last step.
- The server validates every step on submit.
- When the page comes back with errors, the first step with an error opens.

In the builder it's the form's Layout setting.

# Repeating groups

A `repeater` field holds fields that are filled any number of times: references, dependants, past jobs.

```php
Field::repeater('references')
    ->label(__('References'))
    ->minRows(1)->maxRows(3)
    ->addLabel(__('Add a reference'))
    ->setting('row_label', __('Reference :number'))
    ->fields([
        Field::text('name')->label(__('Name'))->required()->thaana(),
        Field::select('relationship')->label(__('Relationship'))->options(['work' => 'Work', 'other' => 'Other']),
        Field::text('relationship_other')->label(__('Describe the relationship'))->visibleWhen('relationship', 'other'),
    ]);
```

- **Answers.** The answer is a list of rows: `[['name' => 'މަރިޔަމް', 'relationship' => 'work'], …]`. Inputs are named `references[0][name]` and errors are keyed `references.0.name` ("Name (Reference 1)" in messages). In queries, fields of a group are `references.name`.
- **Rows on the page.** Add and Remove follow `min_rows` and `max_rows`. A required group has at least one row. The form starts with the minimum, or one row.
- **Conditions inside a row.** A condition can watch a field of the same row by its key (`relationship` above) or a field outside the group.
- **No nesting.** Groups can't be inside other groups.
- **Rendering.** The `Repeater` stencil field (`Javaabu\FormBuilder\Stencil\Repeater`) renders in Blade with `form-builder::stencil.repeater` and the `form-builder::repeater` script, and in React with `FormBuilderRepeater`. You can use it in any stencil form; see [React](../advanced-usage/react.md).

# Conditions

Sections and fields can show only when other fields match. Conditions are checked in the browser as people fill the form (javaabu/forms' conditional wrapper), and on the server: hidden fields aren't validated or saved.

| Operator | Builder | Means |
| --- | --- | --- |
| `in` | one of | the field's value is one of `values` (for checkboxes and multiple selects: any chosen value is) |
| `not_in` | not one of | it's none of them |
| `checked` | checked or filled | a checkbox is ticked, or the field has a value |
| `not_checked` | not checked or empty | the opposite |

```php
Field::text('company_name')->visibleWhen('applicant_type', 'company');      // in
Field::text('reason')->hiddenWhen('status', ['approved', 'pending']);       // not_in
Section::make('guardian')->visibleWhenChecked('is_minor');                  // checked
Field::text('nickname')->condition(Condition::notIn('title', ['dr']));
```

A section or field with several conditions shows only when all of them hold. On a checkbox field, `in` with `1` means checked. The builder writes conditions in the field's Conditions panel. It lists the fields a condition can watch, and you give option values separated by commas.

Read-only entries leave out fields their conditions hid, judged by the answers.

# Cascading selects

A select can depend on another select. Its options are those whose `parent` is the other field's value, and they change when that value changes:

```php
Field::select('atoll')->label(__('Atoll'))->options(['K' => 'Kaafu', 'S' => 'Addu']),
Field::select('island')->label(__('Island'))->dependsOn('atoll')->options([
    ['value' => 'male', 'label' => 'Malé', 'parent' => 'K'],
    ['value' => 'hulhumale', 'label' => 'Hulhumalé', 'parent' => 'K'],
    ['value' => 'hithadhoo', 'label' => 'Hithadhoo', 'parent' => 'S'],
]),
```

- **In the browser.** It uses @javaabu/forms' select2 cascade in both stacks. When the parent changes, the child loads `GET {prefix}/{form}/options/{field}?filter[parent]=K`, from the fill routes or the builder's routes (whichever the page has). The response is `{"data": [{"id": "male", "name": "Malé"}]}`.
- **On the server.** The value must be one of the options under the parent's current value, so a mismatched pair fails validation.
- **Options from your own endpoint.** `->dependsOn('atoll', url: route('api.islands.index'), filter_field: 'atoll')` loads options from that URL instead: `?filter[atoll]=K&per_page=-1&fields=id,name`, answering `{"data": [{"id": …, "name": …}]}` (a javaabu/locations API, for example). The server can't check those values against a list, so validate them yourself if it matters (see [Custom input types](../advanced-usage/custom-input-types.md#changing-a-built-in-type)).
- **Inside repeating groups.** A cascade watches the parent in the same row.
