---
title: Requirements
sidebar_position: 1.1
---

This package requires:

- PHP 8.4 or higher
- Laravel 12 or 13
- [javaabu/stencil](https://github.com/Javaabu/stencil): forms compile to stencil schemas (installed with the package)
- [javaabu/forms](https://github.com/Javaabu/forms) v2: the Blade pages (installed with the package)
- [javaabu/permissions](https://github.com/Javaabu/permissions) v3: permissions and the policies (installed with the package)
- [javaabu/manifest](https://github.com/Javaabu/manifest): ships the scripts, styles and React code to your app (installed with the package)

Optional:

- [inertiajs/inertia-laravel](https://inertiajs.com) v2, for the Inertia React pages (`form-builder.views` = `inertia`)
- [javaabu/translatable](https://github.com/Javaabu/translatable) v3, for form names, labels and options in every language. See [Translatable forms](advanced-usage/translatable.md).
- [laravel/ai](https://github.com/laravel/ai) 1.3+, for the assistant that builds forms from a description. See [AI assistant](advanced-usage/ai-assistant.md).

For React (the Inertia pages and the repeater component), your app needs:

- React 19
- [@javaabu/forms](https://github.com/Javaabu/react-forms)
- [@javaabu/onu](https://www.npmjs.com/package/@javaabu/onu)
- manifest's Vite plugin, which resolves `@javaabu/form-builder` and `@javaabu/stencil`

Blade-only apps don't need any of these: the Blade scripts and styles are prebuilt in `dist/` (or built with Vite), and the React packages are declared for pages only. Run `php artisan manifest:check` to see anything that's missing. It checks the React packages only when your `package.json` has an Inertia adapter, or with `--scope=pages`.
