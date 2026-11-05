<?php

use Javaabu\FormBuilder\InputTypes;
use Javaabu\FormBuilder\Models;

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
    | Storage
    |--------------------------------------------------------------------------
    |
    | The models and tables. Extend a model to add to it and set it here.
    |
    */

    'models' => [
        'form' => Models\Form::class,
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
    | store_ip keeps the submitter's IP address with each entry.
    |
    */

    'entries' => [
        'store_ip' => false,
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

];
