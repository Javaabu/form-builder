---
title: React
sidebar_position: 5
---

The React code is in the package's `resources/js`. Apps import it as `@javaabu/form-builder` through javaabu/manifest's Vite plugin, which also resolves `@javaabu/stencil`. Nothing is published to npm.

# Rendering form-builder forms

Forms compile to stencil schemas. Two node types are the package's own:
- `repeater`: repeating groups
- `form-builder-content`: headings and paragraphs

Pass their components to `StencilForm`, or register them once:

```tsx
import { StencilForm, StencilProvider, type StencilSchema } from "@javaabu/stencil"
import { formBuilderComponents } from "@javaabu/form-builder"

<StencilForm schema={form} components={formBuilderComponents} />

// once, at the root, next to FormsProvider
<StencilProvider components={formBuilderComponents}>{app}</StencilProvider>
```

Read-only entries are embedded schemas: `<StencilFields schema={display} components={formBuilderComponents} />`.

# Exports

| Export | What |
| --- | --- |
| `formBuilderComponents` | `{ repeater, "form-builder-content" }` for stencil |
| `FormBuilderRepeater` | Rows from the schema, Add and Remove within the fewest and most rows, the row headings, errors under the group |
| `rowFromTemplate(template, placeholder, key)` | The template row with `__ROW__` replaced in names, ids and selectors |
| `FormBuilderContent` | A heading or a paragraph |
| `FormsIndex`, `FormsEdit`, `FormsShow`, `FormsPreview`, `FormsVersions`, `FormsImport`, `SectionsEdit`, `FieldsEdit`, `EntriesIndex`, `EntriesShow`, `FillShow`, `FillThanks` | The Inertia pages |
| `SortableList` | The builder's sortable list: drag a handle, or Space, the arrow keys and Space (Escape cancels), announced in a live region. It works nested |
| `createSortable`, `moveItem`, `keyboardTarget`, `announcement`, `format` | The same for plain DOM (the Blade builder uses it) |
| `PageHeader`, `AssistantCard` | Page parts |
| Types | `FormsShowProps`, `BuilderSection`, `BuilderField`, `FormData`, `EntryData`, … |

The Blade scripts are `@javaabu/form-builder/blade/builder` and `@javaabu/form-builder/blade/repeater`. The repeater script exports `init(root)`, `initRepeater()`, `addRow()`, `removeRow()`, `rowsOf()` and `update()`, and dispatches `form-builder:row-added` and `form-builder:row-removed`. The builder script dispatches `form-builder:order` with `{ saved, name, keys }`.

# The repeater in other stencil forms

`Javaabu\FormBuilder\Stencil\Repeater` is a stencil field. Use it in any stencil form, with `formBuilderComponents` in React:

```php
use Javaabu\FormBuilder\Stencil\Repeater;

Repeater::make('dependants')
    ->schema(fn (string $row) => [
        TextInput::make('name')->required()->thaana(),
        Select::make('relationship')->options($relationships),
        TextInput::make('other')->visibleWhen("{$row}.relationship", 'other'),
    ])
    ->minRows(1)->maxRows(5)->columns(3)
    ->addLabel(__('Add a dependant'))->rowLabel(__('Dependant :number'));
```

The schema closure gets the row's state path (`dependants.0`), for conditions on fields of the same row. An array schema is copied for every row. A proposal to move it into stencil is [Javaabu/stencil#18](https://github.com/Javaabu/stencil/issues/18).
