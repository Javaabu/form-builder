<?php

namespace Javaabu\FormBuilder;

use Illuminate\Support\ServiceProvider;
use Javaabu\FormBuilder\Compiler\Compiler;
use Javaabu\FormBuilder\Entries\EntryRecorder;
use Javaabu\Stencil\Facades\Stencil;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/form-builder.php', 'form-builder');

        $this->app->singleton(FormBuilderManager::class);
        $this->app->alias(FormBuilderManager::class, 'form-builder');
        $this->app->singleton(Compiler::class);
        $this->app->singleton(EntryRecorder::class);
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'form-builder');

        $this->registerInputTypes();
        $this->registerStencilViews();

        if ($this->app->runningInConsole()) {
            if (FormBuilderManager::$runs_migrations) {
                $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            }

            $this->registerPublishing();
        }
    }

    /**
     * The input types in config('form-builder.input_types')
     */
    protected function registerInputTypes(): void
    {
        $this->app->make(FormBuilderManager::class)->registerInputType((array) config('form-builder.input_types', []));
    }

    /**
     * Blade views for the stencil components the package adds
     */
    protected function registerStencilViews(): void
    {
        Stencil::view('repeater', 'form-builder::stencil.repeater');
        Stencil::view('form-builder-content', 'form-builder::stencil.content');
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/form-builder.php' => config_path('form-builder.php'),
        ], 'form-builder-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'form-builder-migrations');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/form-builder'),
        ], 'form-builder-translations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/form-builder'),
        ], 'form-builder-views');
    }
}
