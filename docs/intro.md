---
title: Introduction
sidebar_position: 1.0
---

# Form Builder

[Form Builder](https://github.com/Javaabu/form-builder) is for dynamic forms in Laravel: forms people build, publish and fill without a deploy. Forms and their entries live in the database:

- **Versioned definitions.** Each form has versions. People fill the published one, and the builder edits a draft. Publishing freezes the draft, so every entry keeps pointing at the version it was filled with.
- **Built on [javaabu/stencil](https://github.com/Javaabu/stencil).** A stored version compiles to a stencil form, so rendering (Blade and Inertia React), validation and conditions come from stencil.
- **Structure.** Sections, wizards (one section at a time), repeating groups, conditional sections and fields, and cascading selects.
- **Many ways in.** Admins build forms in the builder, developers define them fluently in seeders (or both), and you can import JSON exports or have the optional AI assistant write them.

```php
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Facades\FormBuilder;

// A seeder
FormBuilder::define('job-application')
    ->name(['en' => 'Job application', 'dv' => 'ވަޒީފާއަށް އެދޭ ފޯމު'])
    ->wizard()
    ->sections([
        Section::make('personal')->title(__('Personal details'))->columns(2)->fields([
            Field::text('full_name')->label(__('Full name'))->required()->thaana(),
            Field::select('atoll')->label(__('Atoll'))->options(['K' => 'Kaafu', 'S' => 'Addu']),
            Field::select('island')->label(__('Island'))->dependsOn('atoll')->options([
                ['value' => 'male', 'label' => 'Malé', 'parent' => 'K'],
                ['value' => 'hithadhoo', 'label' => 'Hithadhoo', 'parent' => 'S'],
            ]),
        ]),
        Section::make('references')->title(__('References'))->fields([
            Field::repeater('references')->label(__('References'))->maxRows(3)->fields([
                Field::text('name')->label(__('Name'))->required(),
                Field::phone('phone')->label(__('Phone')),
            ]),
        ]),
    ])
    ->sync();

// routes/web.php
Route::formBuilder(['guard' => 'web_admin', 'prefix' => 'admin/forms', 'name' => 'admin.forms']); // the builder and entries
Route::formBuilderFill();                                                                       // /f/job-application
```

# What's included

- **Definitions and versions**: forms, versions, sections and fields in the database. Drafts and immutable published versions; `sync()` from seeders makes a version only when the structure changes. See [Defining forms](basic-usage/defining-forms.md).
- **17 field types**: text, long text, email, number, phone, link, dropdown, multiple choice, checkboxes, checkbox, date, time, date and time, file upload, heading, paragraph and repeating group. See [Field types](basic-usage/field-types.md).
- **Structure**: sections and wizards, repeating groups, conditions, and cascading selects, checked in the browser and on the server. See [Sections, groups and conditions](basic-usage/structure.md).
- **The builder**: `Route::formBuilder()` pages, in Blade (javaabu/forms) or Inertia React (@javaabu/forms and Onu). Add, configure and reorder sections and fields (drag and drop or the keyboard), preview, publish, discard drafts, versions. See [The builder](basic-usage/the-builder.md).
- **Filling**: `Route::formBuilderFill()` pages, or the stencil form in your own pages: `FormBuilder::fill('job-application')`. See [Filling forms](basic-usage/filling-forms.md).
- **Entries**: answers stored with the version they were filled with, indexed for queries (`FormEntry::whereAnswer('age', '>=', 18)`). Read-only and printable views, CSV export, file downloads. See [Entries](basic-usage/entries.md).
- **Import and export**: a versioned JSON format, validated on import, from the builder or `php artisan form-builder:export|import`. See [Import and export](basic-usage/import-and-export.md).
- **Extensible input types**: add your own type (builder settings, stencil field, React component, storage, display), or change and replace the built-in ones. See [Custom input types](advanced-usage/custom-input-types.md).
- **Permissions**: a policy and seven permissions through [javaabu/permissions](https://github.com/Javaabu/permissions), for any guard and user model. See [Permissions](basic-usage/permissions.md).
- **Optional**: [javaabu/translatable](https://github.com/Javaabu/translatable) for form names, labels and options in every language ([Translatable forms](advanced-usage/translatable.md)). [laravel/ai](https://github.com/laravel/ai) for an assistant that builds or changes a form from a description ([AI assistant](advanced-usage/ai-assistant.md)).
- **Dhivehi and RTL**: every string in English and Dhivehi, Thaana inputs, logical CSS only. See [Dhivehi and RTL](advanced-usage/dhivehi-and-rtl.md).

Other packages build on the PHP API: javaabu/workflows stores its step data as entries. See [The PHP API](advanced-usage/php-api.md).
