{{--
    javaabu/form-builder guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

@verbatim
## javaabu/form-builder

- Dynamic forms (applications, surveys, requests) are form-builder forms, not hand-written forms: define code-owned ones in a seeder with `FormBuilder::define('key')->name(...)->sections([Section::make('key')->fields([Field::text('key')->label(...)->required()])])->sync()`, and let admins build others on `Route::formBuilder([...])` pages.
- Fill a form with `FormBuilder::fill('key')` (a javaabu/stencil form: `{{ $form }}` in Blade, `<StencilForm schema={form} components={formBuilderComponents} />` in React) and store submissions with `FormBuilder::submit($form, $request, submitter: $user, subject: $model)`. Read answers with `$entry->answer()`/`formatted()`, query with `FormEntry::whereAnswer()`, and show entries with `FormBuilder::display($entry)`.
- Published versions are immutable; change forms through their draft, then publish. Field keys are where answers live: never rename them on forms with entries.
- Always activate the `javaabu-form-builder` skill when defining or changing forms, rendering or submitting them, reading entries, adding input types, or setting up the builder routes and permissions.
@endverbatim
