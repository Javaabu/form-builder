---
title: The builder
sidebar_position: 4
---

`Route::formBuilder()` registers the admin pages: the forms list, the builder, settings, previews, versions, import and export, and entries. Every page renders in Blade or Inertia React (`config('form-builder.views')`). Every route answers JSON when the request expects it.

```php
Route::formBuilder(['guard' => 'web_admin', 'prefix' => 'admin/forms', 'name' => 'admin.forms']);
```

# Routes

With the default `forms` prefix and name (`{form}` is the form's key, `{section}` a section's key, `{field}` a field's key or `group.key`):

| Route | Name | What |
| --- | --- | --- |
| `GET forms` | `forms.index` | Every form, with search (`?search=`) |
| `GET forms/create`, `POST forms` | `forms.create`, `forms.store` | A new form: key, name, description, layout, submit label, confirmation, accepting entries |
| `GET forms/import`, `POST forms/import` | `forms.import`, `forms.import.store` | Import an export (a file or pasted JSON) |
| `POST forms/assistant` | `forms.assistant.create` | A new form from a description (with laravel/ai) |
| `GET forms/{form}` | `forms.show` | **The builder**: sections and fields with every action |
| `GET forms/{form}/edit`, `PATCH forms/{form}` | `forms.edit`, `forms.update` | The form's settings |
| `DELETE forms/{form}` | `forms.destroy` | Delete the form (soft delete; entries stay) |
| `POST forms/{form}/publish` | `forms.publish` | Publish the draft |
| `DELETE forms/{form}/draft` | `forms.draft.destroy` | Discard the draft |
| `PUT forms/{form}/structure` | `forms.structure` | Replace the draft's whole structure (`structure`: the export format's `form`) |
| `POST forms/{form}/assistant` | `forms.assistant` | Change the draft as described (with laravel/ai) |
| `GET forms/{form}/preview`, `POST …/preview` | `forms.preview`, `forms.preview.check` | The current version as people see it; submitting checks the answers without saving |
| `GET forms/{form}/versions` | `forms.versions` | Every version, with its author and entries |
| `GET forms/{form}/export` | `forms.export` | Download the published version as JSON (`?draft=1` for the draft) |
| `GET forms/{form}/sections/create`, `POST forms/{form}/sections` | `forms.sections.create`, `forms.sections.store` | Add a section |
| `GET …/sections/{section}/edit`, `PATCH …/sections/{section}`, `DELETE …` | `forms.sections.edit`, `.update`, `.destroy` | A section's key, title, description, columns and conditions |
| `POST …/sections/{section}/move` | `forms.sections.move` | `direction=up\|down` or `position=n` |
| `PUT forms/{form}/sections/order` | `forms.sections.order` | `sections[]`: keys in order |
| `GET …/sections/{section}/fields/create?type=text&group=`, `POST …/fields` | `forms.fields.create`, `forms.fields.store` | Add a field (to a repeating group with `group`) |
| `GET forms/{form}/fields/{field}/edit`, `PATCH …`, `DELETE …` | `forms.fields.edit`, `.update`, `.destroy` | A field's key, label, help, placeholder, required, width, section, settings, options and conditions |
| `POST forms/{form}/fields/{field}/move` | `forms.fields.move` | `direction`, `position` and/or `section` |
| `PUT …/sections/{section}/fields/order?group=` | `forms.fields.order` | `fields[]`: keys in order |
| `GET forms/{form}/options/{field}` | `forms.options` | A cascading select's options for previews |
| `GET forms/{form}/entries`, `…/entries/export`, `…/entries/{entry}`, `…/print`, `…/files/{field}/{index?}`, `DELETE …` | `forms.entries.*` | See [Entries](entries.md) |

With `config('form-builder.builder')` off, only the forms list, the form page (read-only), previews, versions, export, options and entries are registered.

# Building a form

1. **New form**: a key, a name and the layout. The form starts with one section and a draft.
2. **The builder page** lists the sections and their fields. Each section has:
   - **Add field**: choose a type, then fill in its settings.
   - **Edit** and **Delete**.
   - **Move up** and **Move down** buttons, and a drag handle.

   Fields work the same way. Repeating groups list their own fields and have their own Add field.
3. **The field form** has these sections:
   - **Field**: key, label, help, placeholder, required, width, and the section it's in.
   - **Settings**: the type's own.
   - **Options**: for choice types, one row per option: value, label, parent value.
   - **Conditions**: one row per condition: field, operator, values.
4. **Preview** shows the form as people will see it. Submitting it checks the answers without saving them.
5. **Publish** freezes the draft. People fill the new version from then on, and old entries keep their version.

**Changes are checked as a whole.** Every change is validated against the whole form before it's saved. Some examples:
- keys are unique
- conditions watch fields that exist
- a cascading select depends on a field with options

Removing a field that a condition still watches is refused, with the reason.

**Ordering.** Drag a handle, or use the keyboard: focus a handle, press Space, use the arrow keys, then press Space again (Escape cancels). Moves are announced to screen readers. The order saves when you drop the item: the Blade page sends `PUT …/order` with fetch, and the React page sends it through Inertia's router.

**Drafts.** The first change to a published form copies it into a draft. A banner says people still fill the published version until you publish, and **Discard changes** throws the draft away.

**Builder URLs** address sections and fields by key, not by id. A key stays the same from one version to the next, so links keep working after a publish.

# Locked forms

Forms defined in code with `->locked()` show in the builder read-only, with a notice. See [Defining forms](defining-forms.md#locking).
