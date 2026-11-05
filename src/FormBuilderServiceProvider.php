<?php

namespace Javaabu\FormBuilder;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\FormBuilder\Assets\Assets;
use Javaabu\FormBuilder\Compiler\Compiler;
use Javaabu\FormBuilder\Entries\EntryRecorder;
use Javaabu\FormBuilder\Policies\FormEntryPolicy;
use Javaabu\FormBuilder\Policies\FormPolicy;
use Javaabu\FormBuilder\Routing\FormBuilderRoutes;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Permissions\Permissions;
use Javaabu\Stencil\Facades\Stencil;

class FormBuilderServiceProvider extends ServiceProvider
{
    use RegistersAssets;

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

        $this->registerFrontendAssets();
        $this->registerInputTypes();
        $this->registerStencilViews();
        $this->registerRouteMacros();
        $this->registerPermissions();

        if ($this->app->runningInConsole()) {
            if (FormBuilderManager::$runs_migrations) {
                $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            }

            $this->registerPublishing();

            $this->commands([
                Commands\ExportCommand::class,
                Commands\ImportCommand::class,
            ]);
        }
    }

    /**
     * The Inertia pages (form-builder::Forms/Index, …), the components apps
     * import as @javaabu/form-builder, the styles and the Blade scripts
     */
    protected function registerFrontendAssets(): void
    {
        $this->registerAssets(Assets::PACKAGE, __DIR__ . '/../resources', function (Package $package) {
            Assets::register($package);
        });
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

    protected function registerRouteMacros(): void
    {
        Route::macro('formBuilder', function (array $options = []) {
            FormBuilderRoutes::admin($this, $options);
        });

        Route::macro('formBuilderFill', function (array $options = []) {
            FormBuilderRoutes::fill($this, $options);
        });
    }

    /**
     * The form permissions and the default policies, which the app can override
     */
    protected function registerPermissions(): void
    {
        if (config('form-builder.register_permissions', true)) {
            Permissions::register('forms', FormPolicy::permissions(), label: 'Forms');
        }

        $manager = $this->app->make(FormBuilderManager::class);

        Permissions::policy($manager->model('form'), config('form-builder.policies.form', FormPolicy::class));
        Permissions::policy($manager->model('entry'), config('form-builder.policies.entry', FormEntryPolicy::class));
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
