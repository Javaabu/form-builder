---
title: Translatable forms
sidebar_position: 7
---

Form text comes in two kinds:

- **A version's text.** Section titles, field labels, help, placeholders, option labels, the submit label and the confirmation are stored as a string, or as a map of locale => string. This works with or without javaabu/translatable. People see the text in their locale, falling back to the default locale, then to any text there is.
- **A form's name and description.** These are columns of the `forms` table. With [javaabu/translatable](https://github.com/Javaabu/translatable) installed and languages active, they're translated with its JSON storage. Without it, a form has one name.

# With javaabu/translatable

```bash
composer require javaabu/translatable:^3.0@dev
```

When `Translatable::enabled()` (translatable is on and has active languages):

- **The form model.** `FormBuilder::model('form')` is `Models\Translatable\Form` (unless you set `form-builder.models.form`). It translates `name` and `description` in the `translations` and `lang` columns the package's migration already has. Morphs keep pointing at the base `Form` class, and policies apply to both.
- **Locales.** The builder asks for text in every active language. A label is `label[en]` and `label[dv]`: "Label (English)", "Label (ދިވެހި)". Dhivehi inputs type Thaana, right to left. The default language's text is required.
- **The rest follows.** `sync()`, imports and the assistant save name and description translations from locale maps. Exports include them.

Without translatable (or with it turned off), the builder shows one input per text: the default locale's. It also shows inputs for any locale the form's text already has, such as a seeder's `['en' => …, 'dv' => …]`, so nothing is lost.

`Support\Translations` is where the package detects translatable. `Translations::$enabled = false` turns the support off even when the package is installed.

# Reading text

```php
use Javaabu\FormBuilder\Support\Text;

Text::resolve($field->getLabel());              // the current locale, with fallbacks
Text::resolve($field->getLabel(), 'dv');
$section->titleText(); $fieldModel->labelText();
$form->translate('name', 'dv');                  // with javaabu/translatable
```
