---
title: The PHP API
sidebar_position: 3
---

Other packages and apps use forms without the builder's pages. javaabu/workflows stores each step's data as an entry, for example. Treat everything on this page as a public contract.

# Forms and versions

```php
use Javaabu\FormBuilder\Facades\FormBuilder;

$form = FormBuilder::form('leave-request');          // Models\Form by key; FormBuilderException when there's none
$version = $form->publishedVersion;                   // Models\FormVersion, or null
$definition = $version->definition();                 // Definitions\FormDefinition
$definition->getFields(); $definition->getField('reason');

FormBuilder::define('leave-request')->…->sync();      // define or update in code
FormBuilder::model('form');                           // the configured model classes: form, version, section, field, entry, value
```

# Filling

```php
$stencil = FormBuilder::fill($form);                  // Stencil\EntryForm (a Javaabu\Stencil\Form) of the published version
$stencil = FormBuilder::fill($version);               // or of a version you keep (a workflow pins its version)
$stencil->action($url)->method('POST');               // then {{ $stencil }}, or pass it to Inertia
$stencil = EntryForm::for($version)->model($entry->answers());   // prefilled, to edit an entry
```

`EntryForm` is a stencil form class, so live fields, `validate()`, `getField()`, `toArray()` and `toHtml()` all work. Its context holds the version (`$stencil->getVersion()`, `$stencil->getDefinition()`).

# Storing entries

```php
$entry = FormBuilder::submit(
    $version,                    // a Form (its published version), a FormVersion or a form key
    $request,                    // a Request or an array of input
    submitter: $user,            // any model, or null
    subject: $step,              // what the entry is about, or null
);                               // Models\FormEntry; throws ValidationException

use Javaabu\FormBuilder\Entries\EntryRecorder;

app(EntryRecorder::class)->submit($stencil, $version, $input, $user, $step, status: FormEntry::DRAFT);  // saved, not submitted
app(EntryRecorder::class)->update($entry, $input);    // re-validated with its own version
app(EntryRecorder::class)->answers($definition, $validated);   // validated data → normalized answers
```

Events: `Events\EntrySubmitted` (after submit) and `Events\EntryUpdated`, each with `$event->entry`.

# Reading entries

```php
$entry->answers(); $entry->answer('reason'); $entry->formatted('leave_type');
$entry->version; $entry->form; $entry->submitter; $entry->subject; $entry->status; $entry->submitted_at;

FormEntry::forSubject($step)->latest()->first();
FormEntry::whereAnswer('days', '>', 3)->submittedBy($user)->get();

FormBuilder::display($entry);                         // Stencil\DisplayForm: read-only, Blade or React
DisplayForm::forAnswers($version, $answers);          // the same for answers you hold
app(\Javaabu\FormBuilder\Entries\EntryColumns::class)->for($form);       // path => label of every answer column
app(\Javaabu\FormBuilder\Entries\EntriesExport::class)->download($form, $query);   // a CSV response
```

# Compiling definitions yourself

```php
use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Compiler\Compiler;

$components = app(Compiler::class)->compile(new CompileContext($definition));                    // stencil components
$components = app(Compiler::class)->compile(new CompileContext($definition, CompileContext::DISPLAY, answers: $answers));
```

Embed them in a stencil form of your own (`Form::make()->schema($components)`), or put the fields inside a bigger form. Field names are the field keys, so they sit next to your own fields.

# Validating definitions

```php
use Javaabu\FormBuilder\Definitions\DefinitionValidator;

$errors = app(DefinitionValidator::class)->errors($array);       // MessageBag keyed by path: sections.0.fields.2.key
$definition = app(DefinitionValidator::class)->validate($array);  // or a ValidationException
```

# Classes

| Class | What |
| --- | --- |
| `FormBuilderManager` / `Facades\FormBuilder` | `model()`, `registerInputType()`, `extendInputType()`, `forgetInputType()`, `inputType()`, `hasInputType()`, `inputTypes()`, `define()`, `sync()`, `form()`, `fill()`, `submit()`, `display()`, `ignoreMigrations()` |
| `Definitions\FormDefinition`, `Section`, `Field`, `Condition` | The structure; see [Defining forms](../basic-usage/defining-forms.md) |
| `Definitions\DefinitionValidator` | Checks a definition array |
| `Models\Form`, `FormVersion`, `FormSection`, `FormField`, `FormEntry`, `FormEntryValue` | Storage; `Models\Translatable\Form` with javaabu/translatable |
| `InputTypes\InputType` and the 17 built-in types | Field types |
| `Compiler\Compiler`, `CompileContext` | Definitions to stencil components |
| `Stencil\EntryForm`, `DisplayForm`, `Repeater`, `Content` | Stencil forms and components |
| `Entries\EntryRecorder`, `EntryColumns`, `EntriesExport` | Storing, columns, CSV |
| `Transfer\Exporter`, `Importer` | JSON |
| `Builder\StructureEditor`, `DraftEditor`, `BuilderForms`, `BuilderData` | What the builder's controllers use: changes by key, validated drafts, the builder's stencil forms, page data |
| `Ai\FormAssistant`, `FormAssistantAgent` | The assistant (laravel/ai) |
| `Routing\FormBuilderRoutes` | `Route::formBuilder()`, `Route::formBuilderFill()`, `guard()`, `namePrefix()`, `optionsRoute()` |
| `Policies\FormPolicy`, `FormEntryPolicy` | Authorization and the permission names |
| `Support\Text`, `Translations`, `Strings` | Localized text, optional translatable, page strings |
| `Exceptions\FormBuilderException` | Unknown types and forms, changing published versions, locked forms |
