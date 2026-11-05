<?php

namespace Javaabu\FormBuilder;

use Illuminate\Support\ServiceProvider;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/form-builder.php', 'form-builder');

        $this->app->singleton(FormBuilderManager::class);
        $this->app->alias(FormBuilderManager::class, 'form-builder');
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'form-builder');

        if ($this->app->runningInConsole()) {
            if (FormBuilderManager::$runs_migrations) {
                $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            }

            $this->registerPublishing();
        }
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
