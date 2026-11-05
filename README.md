# Form Builder

[![Tests](https://github.com/Javaabu/form-builder/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Javaabu/form-builder/actions/workflows/tests.yml?query=branch%3Amain)
[![codecov](https://codecov.io/gh/Javaabu/form-builder/branch/main/graph/badge.svg)](https://codecov.io/gh/Javaabu/form-builder)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/form-builder.svg)](https://packagist.org/packages/javaabu/form-builder)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

Dynamic forms for Laravel. Form definitions and entries live in the database, versioned so every entry keeps pointing at the version it was filled with. Forms compile to [javaabu/stencil](https://github.com/Javaabu/stencil) schemas, so they render in Blade and Inertia React with validation and conditions built in.

- A builder in Blade or Inertia React (`Route::formBuilder()`): sections and fields added, configured and reordered by drag and drop or the keyboard, previews, drafts and publishing.
- Sections, wizards, repeating groups, conditional sections and fields, and cascading selects.
- 17 field types, and your own: builder settings, a stencil field, a React component, storage and display.
- Forms defined fluently in seeders, the builder optional. JSON export and import, and an optional [laravel/ai](https://github.com/laravel/ai) assistant that builds forms from a description.
- Entries you can query (`whereAnswer()`), shown read-only and printable, exported to CSV.
- Permissions through [javaabu/permissions](https://github.com/Javaabu/permissions) for any guard and user model. Optional [javaabu/translatable](https://github.com/Javaabu/translatable). Dhivehi and RTL.

```php
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Facades\FormBuilder;

FormBuilder::define('feedback')
    ->name(['en' => 'Feedback', 'dv' => 'ފީޑްބެކް'])
    ->sections([
        Section::make('main')->fields([
            Field::radio('rating')->label(__('Rating'))->required()->options(['1' => __('Bad'), '5' => __('Good')]),
            Field::textarea('comment')->label(__('Comment'))->thaana()->visibleWhen('rating', '1'),
        ]),
    ])
    ->sync();

// routes/web.php
Route::formBuilder(['guard' => 'web_admin', 'prefix' => 'admin/forms', 'name' => 'admin.forms']);
Route::formBuilderFill();   // /f/feedback

// anywhere
$entry = FormBuilder::submit('feedback', $request, submitter: $request->user());
FormEntry::whereAnswer('rating', '1')->count();
```

## Documentation

The docs are in [`docs/`](docs/intro.md), indexed for assistants in [`llms.txt`](llms.txt).

## Testing

```bash
composer test
pnpm test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## Security

If you discover a security issue, email info@javaabu.com instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.
