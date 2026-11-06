<?php

namespace Javaabu\FormBuilder\Assets;

use Javaabu\Manifest\Package;

/**
 * The package's frontend assets, registered with javaabu/manifest
 */
class Assets
{
    public const string PACKAGE = 'form-builder';

    /** Styles for repeating groups, headings, paragraphs and entries (logical properties only) */
    public const string STYLES = 'form-builder::styles';

    /** The Blade builder page's script: ordering by drag and drop or the keyboard, confirmations */
    public const string BUILDER = 'form-builder::builder';

    /** Adding and removing rows of repeating groups in Blade forms */
    public const string REPEATER = 'form-builder::repeater';

    public static function register(Package $package): void
    {
        $package
            ->js('js')
            ->pages()
            ->dist('../dist')
            ->style('styles', 'css/form-builder.css')
            ->script('builder', 'js/blade/builder.ts')
            ->script('repeater', 'js/blade/repeater.ts')
            // The Blade scripts and styles import no npm packages; only the
            // Inertia pages and what apps import as @javaabu/form-builder do
            ->npm([
                '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                '@javaabu/onu' => '^0.2.1',
            ], [
                'react' => '^19.0',
                'react-dom' => '^19.0',
                '@inertiajs/react' => '^2.0 || ^3.0',
            ], for: 'pages');
    }
}
