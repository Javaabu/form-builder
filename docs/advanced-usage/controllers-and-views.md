---
title: Controllers and views
sidebar_position: 4
---

# Controllers

Every route's controller can be replaced with a subclass, in config or per registration:

```php
// config/form-builder.php
'controllers' => [
    'forms' => App\Http\Controllers\FormsController::class,       // extends Javaabu\FormBuilder\Http\Controllers\FormsController
    'sections' => …, 'fields' => …, 'entries' => …, 'fill' => …, 'assistant' => …,
],
```

```php
Route::formBuilder(['controllers' => ['entries' => App\Http\Controllers\FormEntriesController::class]]);
Route::formBuilderFill(['controller' => App\Http\Controllers\ApplyController::class]);
```

The controllers have hooks for the common changes:

| Method | Controller | For |
| --- | --- | --- |
| `query(Request, Form)` | `EntriesController` | Which entries the list, export and pages show (scope them by subject or submitter) |
| `submitted(Request, FormEntry)` | `FillController` | After an entry is stored: notify someone, start a workflow |
| `form(Request, Form, FormVersion)` | `FillController` | The `EntryForm` people fill (another action, extra context) |
| `confirmation(FormVersion)` | `FillController` | The thank-you message |
| `entryData()`, `files()` | `EntriesController` | What the entry pages show |
| `previewForm()` | `FormsController` | The preview's form |

```php
class ApplyController extends \Javaabu\FormBuilder\Http\Controllers\FillController
{
    protected function submitted(Request $request, FormEntry $entry): void
    {
        Notification::route('mail', 'hr@example.com')->notify(new ApplicationReceived($entry));
    }
}
```

The controllers authorize with the route's guard user (`FormBuilderRoutes::guard($request)`) and build URLs from the route name prefix, so the same controller serves several registrations.

# Blade views

Publish the views to change them:

```bash
php artisan vendor:publish --tag=form-builder-views
```

| View | Page |
| --- | --- |
| `forms/index`, `forms/edit`, `forms/show`, `forms/preview`, `forms/versions`, `forms/import` | The forms list, settings (new and edit), the builder, preview, versions, import |
| `sections/edit`, `fields/edit` | A section's and a field's form |
| `entries/index`, `entries/show`, `entries/print` | Entries |
| `fill/show`, `fill/thanks` | Filling |
| `partials/*` | The header, status messages, pagination, builder items, move buttons, add field, entry details, the assistant |
| `stencil/repeater`, `stencil/repeater-row`, `stencil/content` | The stencil components the package adds |

Pages extend `config('form-builder.layout')` and fill `config('form-builder.layout_section')`. Each page gets:
- its data as arrays (the same as the Inertia props)
- `strings`: the page's strings in the current locale
- `status`: the flash message

They use javaabu/forms components, Tailwind utilities that work with Onu, and logical properties only.

# Inertia pages

`config('form-builder.views') = 'inertia'` renders the same pages as `form-builder::Forms/Index`, `Forms/Edit`, `Forms/Show`, `Forms/Preview`, `Forms/Versions`, `Forms/Import`, `Sections/Edit`, `Fields/Edit`, `Entries/Index`, `Entries/Show`, `Fill/Show` and `Fill/Thanks`. Their props are typed in `@javaabu/form-builder` (`FormsShowProps`, …). To replace one, resolve that page name to your own component in your Inertia page resolver before manifest's. The package's pages are exported for reuse (`FormsShow`, `EntriesShow`, …).

The print page (`entries/print`) is Blade in both stacks: a standalone document prints the same in every app.
