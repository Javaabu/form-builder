---
title: Installation & Setup
sidebar_position: 1.2
---

# Installing

Until the next-gen packages are on Packagist, add their repositories to your app's `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/Javaabu/form-builder" },
    { "type": "vcs", "url": "https://github.com/Javaabu/stencil" },
    { "type": "vcs", "url": "https://github.com/Javaabu/forms" },
    { "type": "vcs", "url": "https://github.com/Javaabu/manifest" },
    { "type": "vcs", "url": "https://github.com/Javaabu/permissions" }
]
```

```bash
composer require javaabu/form-builder:^1.0@dev
php artisan migrate                # forms, form_versions, form_sections, form_fields, form_entries, form_entry_values
php artisan permissions:sync       # view_forms, edit_forms, publish_forms, … for your guards
```

The service provider is discovered automatically. The migration runs from the package. To change it instead, publish it, and call `FormBuilderManager::ignoreMigrations()` in a service provider's `register()` so the package stops loading its own:

```bash
php artisan vendor:publish --tag=form-builder-migrations
```

Morph columns (`author`, `submitter`, `subject`) follow `Schema::morphUsingUuids()` / `morphUsingUlids()` if your app sets them before migrating.

# Routes

Register the builder where your admin routes are, with your guard and middleware, and the fill pages wherever people fill forms:

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::formBuilder([
        'guard' => 'web_admin',          // auth:web_admin, and authorization for that guard's user
        'prefix' => 'admin/forms',
        'name' => 'admin.forms',
    ]);

    Route::formBuilderFill();                                    // public: /f/{form}
    // Route::formBuilderFill(['middleware' => ['auth']]);      // signed-in people only
    // Route::formBuilderFill(['prefix' => 'apply', 'name' => 'apply']);
});
```

| Option | `Route::formBuilder()` | `Route::formBuilderFill()` |
| --- | --- | --- |
| `guard` | `null` (the default guard): `auth:{guard}` and the user to authorize | `null`; with a guard, `auth:{guard}` and the entry's submitter |
| `prefix` | `forms` | `f` |
| `name` | `forms` | `form-builder` |
| `middleware` | `['auth']` or `['auth:{guard}']` | `[]` (public), or `['auth:{guard}']` |
| `controllers` / `controller` | `config('form-builder.controllers')` | `config('form-builder.controllers.fill')` |

See [The builder](basic-usage/the-builder.md) and [Filling forms](basic-usage/filling-forms.md) for every route.

# Blade or Inertia React

The pages render with Blade by default. For Inertia React:

```php
// config/form-builder.php (or FORM_BUILDER_VIEWS=inertia)
'views' => 'inertia',
```

Blade pages extend `config('form-builder.layout')` (`layouts.app`) and fill the `content` section. Your layout needs javaabu/manifest's tags (`@manifestStyles`, `@manifestScripts`) or its automatic injection, as for any javaabu/forms page.

Inertia pages are named `form-builder::Forms/Index`, `form-builder::Forms/Show` and so on. manifest's Vite plugin and page resolver find them, so you don't copy them. The fields of repeating groups and the headings between fields use the package's stencil components. The pages register them themselves. Your own pages that render form-builder forms pass `formBuilderComponents` to `StencilForm` (see [React](advanced-usage/react.md)).

# Configuration

Publish the config to change models, tables, input types, uploads, the views, controllers, policies or the assistant:

```bash
php artisan vendor:publish --tag=form-builder-config
```

Every option is in [Configuration](advanced-usage/configuration.md). Views and translations publish with `--tag=form-builder-views` and `--tag=form-builder-translations`.

# Uploads

File answers are stored on `config('form-builder.files.disk')`, under `form-builder/{form key}`. Use a private disk: the entries pages serve files to people who can see entries.

```dotenv
FORM_BUILDER_DISK=local
```
