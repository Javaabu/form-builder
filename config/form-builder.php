<?php

use Javaabu\FormBuilder\Http\Controllers\EntriesController;
use Javaabu\FormBuilder\Http\Controllers\FieldsController;
use Javaabu\FormBuilder\Http\Controllers\FillController;
use Javaabu\FormBuilder\Http\Controllers\FormsController;
use Javaabu\FormBuilder\Http\Controllers\SectionsController;
use Javaabu\FormBuilder\InputTypes;
use Javaabu\FormBuilder\Models;
use Javaabu\FormBuilder\Policies\FormEntryPolicy;
use Javaabu\FormBuilder\Policies\FormPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | Input types
    |--------------------------------------------------------------------------
    |
    | The field types the builder offers and forms can use. Add your own
    | (classes extending Javaabu\FormBuilder\InputTypes\InputType), replace a
    | built-in one with a subclass, or remove one to hide it. You can also
    | register them from a service provider with FormBuilder::registerInputType().
    |
    */

    'input_types' => [
        InputTypes\TextType::class,
        InputTypes\TextareaType::class,
        InputTypes\EmailType::class,
        InputTypes\NumberType::class,
        InputTypes\PhoneType::class,
        InputTypes\UrlType::class,
        InputTypes\SelectType::class,
        InputTypes\RadioType::class,
        InputTypes\CheckboxesType::class,
        InputTypes\CheckboxType::class,
        InputTypes\DateType::class,
        InputTypes\TimeType::class,
        InputTypes\DateTimeType::class,
        InputTypes\FileType::class,
        InputTypes\HeadingType::class,
        InputTypes\ParagraphType::class,
        InputTypes\RepeaterType::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Builder
    |--------------------------------------------------------------------------
    |
    | Turn the builder off to define forms only in code (seeders and
    | FormBuilder::define()). Route::formBuilder() then registers the forms
    | list, previews, versions, export and entries, but nothing that changes
    | a form.
    |
    */

    'builder' => env('FORM_BUILDER_BUILDER', true),

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | The models and tables. Extend a model to add to it and set it here.
    | A null form model is Models\Translatable\Form when javaabu/translatable
    | is on (names and descriptions in every language), Models\Form otherwise.
    |
    */

    'models' => [
        'form' => null,
        'version' => Models\FormVersion::class,
        'section' => Models\FormSection::class,
        'field' => Models\FormField::class,
        'entry' => Models\FormEntry::class,
        'value' => Models\FormEntryValue::class,
    ],

    'tables' => [
        'forms' => 'forms',
        'form_versions' => 'form_versions',
        'form_sections' => 'form_sections',
        'form_fields' => 'form_fields',
        'form_entries' => 'form_entries',
        'form_entry_values' => 'form_entry_values',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entries
    |--------------------------------------------------------------------------
    |
    | store_ip keeps the submitter's IP address with each entry; columns is
    | how many answers the entries list shows.
    |
    */

    'entries' => [
        'store_ip' => false,
        'columns' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Where file answers are stored: a disk (null for the default one), a
    | directory (the form's key is added to it) and the largest file in KB.
    | Use a private disk: files are served through the entries pages.
    |
    */

    'files' => [
        'disk' => env('FORM_BUILDER_DISK'),
        'directory' => 'form-builder',
        'max_size' => 10240,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dates
    |--------------------------------------------------------------------------
    |
    | How date answers are shown, as PHP date formats (translated).
    |
    */

    'date_format' => 'j F Y',

    'datetime_format' => 'j F Y, H:i',

    /*
    |--------------------------------------------------------------------------
    | Seeders
    |--------------------------------------------------------------------------
    |
    | Whether FormBuilder::sync() publishes the version it makes. Turn it off
    | to review synced forms in the builder before they go live.
    |
    */

    'sync' => [
        'publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cascading selects
    |--------------------------------------------------------------------------
    |
    | The route that serves a cascading select's options. Route::formBuilder()
    | and Route::formBuilderFill() register one each; null uses the current
    | page's, then the fill routes', then the admin routes'.
    |
    */

    'options_route' => null,

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | The pages Route::formBuilder() and Route::formBuilderFill() render:
    | blade (javaabu/forms components and stencil's Blade driver) or inertia
    | (React pages shipped through javaabu/manifest). Requests that expect
    | JSON always get JSON. per_page is the length of lists; entries.columns
    | the answers the entries list shows.
    |
    */

    'views' => env('FORM_BUILDER_VIEWS', 'blade'),

    /*
    | The Blade layout the pages extend, and the section they fill.
    */

    'layout' => 'layouts.app',

    'layout_section' => 'content',

    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Controllers and authorization
    |--------------------------------------------------------------------------
    |
    | Replace a controller with a subclass, or pass `controllers` to
    | Route::formBuilder() (`controller` to Route::formBuilderFill()). The
    | policies are the package defaults; apps can also override them with
    | config('permissions.policies') or Gate::policy(). Turn off
    | register_permissions to register your own.
    |
    */

    'controllers' => [
        'forms' => FormsController::class,
        'sections' => SectionsController::class,
        'fields' => FieldsController::class,
        'entries' => EntriesController::class,
        'fill' => FillController::class,
    ],

    'policies' => [
        'form' => FormPolicy::class,
        'entry' => FormEntryPolicy::class,
    ],

    'register_permissions' => true,

    /*
    |--------------------------------------------------------------------------
    | AI assistant
    |--------------------------------------------------------------------------
    |
    | With laravel/ai installed, the builder has an assistant that builds a
    | form, or changes one, from a description, as a draft to review. provider
    | and model pick what laravel/ai uses (null: its defaults); attempts is how
    | many answers it gets to produce a valid form.
    |
    */

    'assistant' => [
        'enabled' => env('FORM_BUILDER_ASSISTANT', true),
        'provider' => env('FORM_BUILDER_ASSISTANT_PROVIDER'),
        'model' => env('FORM_BUILDER_ASSISTANT_MODEL'),
        'timeout' => 120,
        'attempts' => 2,
        'max_length' => 4000,
    ],

];
