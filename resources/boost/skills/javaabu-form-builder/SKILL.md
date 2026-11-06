---
name: javaabu-form-builder
description: Build dynamic, database-stored forms in a Laravel app with javaabu/form-builder. Use when defining forms in seeders (FormBuilder::define()->sections([...])->sync(), Field::text/select/repeater…, Section), adding sections, wizards, repeating groups, conditions or cascading selects, rendering a form to fill (FormBuilder::fill, EntryForm, StencilForm with formBuilderComponents), storing and reading entries (FormBuilder::submit, FormEntry::whereAnswer, FormBuilder::display), setting up the builder and fill routes (Route::formBuilder, Route::formBuilderFill) and permissions, importing or exporting forms as JSON, adding a custom input type, translatable labels, or the laravel/ai form assistant.
license: MIT
metadata:
  author: Javaabu
---

# Dynamic forms with javaabu/form-builder

The docs ship with the package: `vendor/javaabu/form-builder/llms.txt` lists every page in `vendor/javaabu/form-builder/docs/`.

| Task | Read |
| --- | --- |
| Define a form, fill it, store and read entries, the routes | This file |
| Every method, field type, setting, route and config key | [references/api.md](references/api.md) |
| Mistakes to avoid | [references/common-mistakes.md](references/common-mistakes.md) |

## 1. Define a form in a seeder

```php
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Facades\FormBuilder;

FormBuilder::define('leave-request')
    ->name(['en' => 'Leave request', 'dv' => 'ޗުއްޓީއަށް އެދުން'])
    ->submitLabel(['en' => 'Request', 'dv' => 'އެދޭ'])
    ->confirmation(['en' => 'We\'ll let you know.', 'dv' => 'ނަތީޖާ އަންގާނަން.'])
    ->sections([
        Section::make('leave')->title(['en' => 'Leave', 'dv' => 'ޗުއްޓީ'])->columns(2)->fields([
            Field::select('type')->label(['en' => 'Type', 'dv' => 'ބާވަތް'])->required()->options([
                'annual' => ['en' => 'Annual', 'dv' => 'އަހަރީ'],
                'sick' => ['en' => 'Sick', 'dv' => 'ބަލި'],
            ]),
            Field::file('certificate')->label(['en' => 'Medical certificate', 'dv' => 'ސެޓްފިކެޓް'])->visibleWhen('type', 'sick')->required(),
            Field::date('from')->label(['en' => 'From', 'dv' => 'ފެށޭ ތާރީޚު'])->required()->min('today'),
            Field::date('to')->label(['en' => 'To', 'dv' => 'ނިމޭ ތާރީޚު'])->required()->min('today'),
            Field::textarea('reason')->label(['en' => 'Reason', 'dv' => 'ސަބަބު'])->thaana()->span('full'),
        ]),
    ])
    ->sync();   // a new version only when the structure changed; published by default
```

- **Keys** are snake_case and unique in the form. Answers are stored under them.
- **Text** is a string or a locale map.
- **More structure**: `->wizard()` for one section at a time; `Field::repeater('references')->fields([...])->maxRows(3)` for repeating groups; `Field::select('island')->dependsOn('atoll')->options([['value' => 'male', 'label' => 'Malé', 'parent' => 'K']])` for cascading selects.
- **Locking**: `->locked()` keeps the builder from changing it.

## 2. Routes and permissions

```php
// routes/web.php
Route::formBuilder(['guard' => 'web_admin', 'prefix' => 'admin/forms', 'name' => 'admin.forms']);   // the builder and entries
Route::formBuilderFill();                                    // /f/{form}, public; ['middleware' => ['auth']] for members
```

```bash
php artisan migrate && php artisan permissions:sync   # view_forms, edit_forms, delete_forms, publish_forms, view/export/delete_form_entries
```

`config('form-builder.views')` is `blade` or `inertia`. Blade-only apps need no React packages; Inertia apps run `php artisan manifest:check` for @javaabu/forms, @javaabu/onu and React.

## 3. Fill it in your own page

```php
public function create(Vacancy $vacancy)
{
    return Inertia::render('Applications/Create', [
        'form' => FormBuilder::fill('job-application')->action(route('vacancies.applications.store', $vacancy)),
    ]);
}

public function store(Request $request, Vacancy $vacancy)
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

In Blade: `{{ $form }}`.

## 4. Read entries

```php
$entry->answer('from');  $entry->formatted('type');   // 'Sick'
$entry->answer('references.0.name');
FormEntry::forSubject($vacancy)->whereAnswer('type', 'sick')->whereAnswer('days', '>', 3)->get();
FormBuilder::display($entry);   // read-only stencil form: {{ }} or <StencilFields schema={display} components={formBuilderComponents} />
```

## 5. A field type of your own

```php
class NationalIdType extends \Javaabu\FormBuilder\InputTypes\InputType
{
    public static function key(): string
    {
        return 'national_id';
    }

    public function name(): string
    {
        return __('National ID card');
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return TextInput::make($context->name($field->getKey()))->rules(['regex:/^A[0-9]{6}$/']);
    }
}

FormBuilder::registerInputType(NationalIdType::class);   // AppServiceProvider::boot()
```

Change built-in types with `FormBuilder::extendInputType('phone', fn ($component) => $component->rules([...]))`.
