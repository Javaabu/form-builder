# Common mistakes

- **Rendering without `formBuilderComponents`.** `<StencilForm schema={form} />` alone can't render repeating groups or headings. Pass `components={formBuilderComponents}`, or register them with `StencilProvider`.
- **Saving entries by hand.** `FormEntry::create([...])` skips validation, normalization and the answer index, so `whereAnswer()` won't find the entry. Use `FormBuilder::submit()` (or `EntryRecorder`).
- **Changing a published version.** `$form->publishedVersion->sections()->…` throws `FormBuilderException`. Edit `$form->draft()`, the builder, or the seeder's definition, then publish.
- **Renaming field keys** on a form with entries. Old entries keep their answers under the old key. Add a new field instead, and hide the old one with a condition, or keep it.
- **Reading locale maps directly.** `$field->getLabel()['dv']` breaks for plain strings. Use `Text::resolve($text)` or `$entry->formatted()`.
- **Hand-writing validation rules** for form-builder forms. The compiled stencil form gives them, per row in repeating groups and only for visible fields. Use `FormBuilder::fill()->validate()` or `submit()`.
- **Conditions in repeating groups** that point at `references.0.type`. Inside a group, watch the sibling by its key (`type`). Paths are worked out per row.
- **Cascading selects with options from your own URL** are trusted as sent (the server can't list them). Validate those values yourself with `extendInputType()` or in your controller.
- **Defining routes by hand** for the builder or entries. Use `Route::formBuilder()` and `Route::formBuilderFill()`, and override controllers or policies through config.
- **Forgetting `php artisan permissions:sync`** after installing: everyone gets 403.
- **A custom input type naming its component by path.** Use `$context->name($field->getKey())`, so the type works inside repeating groups.
- **Storing uploads on a public disk.** File answers are served through the entries pages to people who may see entries. Use a private disk.
