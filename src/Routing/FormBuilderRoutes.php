<?php

namespace Javaabu\FormBuilder\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Javaabu\FormBuilder\Http\Controllers\EntriesController;
use Javaabu\FormBuilder\Http\Controllers\FieldsController;
use Javaabu\FormBuilder\Http\Controllers\FillController;
use Javaabu\FormBuilder\Http\Controllers\FormsController;
use Javaabu\FormBuilder\Http\Controllers\SectionsController;

/**
 * Registers the routes of `Route::formBuilder()` (the admin side: forms, the
 * builder, entries) and `Route::formBuilderFill()` (filling forms)
 *
 * ```php
 * Route::formBuilder(['guard' => 'web_admin', 'prefix' => 'admin/forms', 'name' => 'admin.forms']);
 * Route::formBuilderFill();                          // /f/{form}, public
 * Route::formBuilderFill(['middleware' => ['auth']]); // signed in only
 * ```
 */
class FormBuilderRoutes
{
    /** The route action key that holds the guard the routes were registered for */
    public const string GUARD_ACTION = 'form_builder_guard';

    /** The route action key that marks the fill routes */
    public const string FILL_ACTION = 'form_builder_fill';

    /** Route names of the options endpoint, in the order they were registered */
    protected static array $options_routes = ['admin' => null, 'fill' => null];

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controllers?: array<string, class-string>
     * }  $options
     */
    public static function admin(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'forms', '/');
        $name = rtrim($options['name'] ?? 'forms', '.');
        $middleware = $options['middleware'] ?? [$guard ? "auth:{$guard}" : 'auth'];
        $controllers = [...(array) config('form-builder.controllers', []), ...($options['controllers'] ?? [])];

        $forms = $controllers['forms'] ?? FormsController::class;
        $sections = $controllers['sections'] ?? SectionsController::class;
        $fields = $controllers['fields'] ?? FieldsController::class;
        $entries = $controllers['entries'] ?? EntriesController::class;
        $fill = $controllers['fill'] ?? FillController::class;

        $builder = (bool) config('form-builder.builder', true);

        $router->group([
            'prefix' => $prefix,
            'as' => "{$name}.",
            'middleware' => $middleware,
            self::GUARD_ACTION => $guard,
        ], function (Router $router) use ($forms, $sections, $fields, $entries, $fill, $builder) {
            $form = '[A-Za-z0-9_-]+';
            $key = '[a-z][a-z0-9_]*';
            $path = '[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)?';

            $router->get('/', [$forms, 'index'])->name('index');

            if ($builder) {
                $router->get('/create', [$forms, 'create'])->name('create');
                $router->post('/', [$forms, 'store'])->name('store');
                $router->get('/import', [$forms, 'importForm'])->name('import');
                $router->post('/import', [$forms, 'import'])->name('import.store');
            }

            $router->get('/{form}', [$forms, 'show'])->name('show')->where('form', $form);
            $router->get('/{form}/preview', [$forms, 'preview'])->name('preview')->where('form', $form);
            $router->post('/{form}/preview', [$forms, 'checkPreview'])->name('preview.check')->where('form', $form);
            $router->get('/{form}/versions', [$forms, 'versions'])->name('versions')->where('form', $form);
            $router->get('/{form}/export', [$forms, 'export'])->name('export')->where('form', $form);
            $router->get('/{form}/options/{field}', [$fill, 'options'])->name('options')->where(['form' => $form, 'field' => $path]);

            if ($builder) {
                $router->get('/{form}/edit', [$forms, 'edit'])->name('edit')->where('form', $form);
                $router->patch('/{form}', [$forms, 'update'])->name('update')->where('form', $form);
                $router->delete('/{form}', [$forms, 'destroy'])->name('destroy')->where('form', $form);
                $router->post('/{form}/publish', [$forms, 'publish'])->name('publish')->where('form', $form);
                $router->delete('/{form}/draft', [$forms, 'discardDraft'])->name('draft.destroy')->where('form', $form);
                $router->put('/{form}/structure', [$forms, 'structure'])->name('structure')->where('form', $form);

                $router->get('/{form}/sections/create', [$sections, 'create'])->name('sections.create')->where('form', $form);
                $router->post('/{form}/sections', [$sections, 'store'])->name('sections.store')->where('form', $form);
                $router->put('/{form}/sections/order', [$sections, 'order'])->name('sections.order')->where('form', $form);
                $router->get('/{form}/sections/{section}/edit', [$sections, 'edit'])->name('sections.edit')->where(['form' => $form, 'section' => $key]);
                $router->patch('/{form}/sections/{section}', [$sections, 'update'])->name('sections.update')->where(['form' => $form, 'section' => $key]);
                $router->delete('/{form}/sections/{section}', [$sections, 'destroy'])->name('sections.destroy')->where(['form' => $form, 'section' => $key]);
                $router->post('/{form}/sections/{section}/move', [$sections, 'move'])->name('sections.move')->where(['form' => $form, 'section' => $key]);

                $router->get('/{form}/sections/{section}/fields/create', [$fields, 'create'])->name('fields.create')->where(['form' => $form, 'section' => $key]);
                $router->post('/{form}/sections/{section}/fields', [$fields, 'store'])->name('fields.store')->where(['form' => $form, 'section' => $key]);
                $router->put('/{form}/sections/{section}/fields/order', [$fields, 'order'])->name('fields.order')->where(['form' => $form, 'section' => $key]);
                $router->get('/{form}/fields/{field}/edit', [$fields, 'edit'])->name('fields.edit')->where(['form' => $form, 'field' => $path]);
                $router->patch('/{form}/fields/{field}', [$fields, 'update'])->name('fields.update')->where(['form' => $form, 'field' => $path]);
                $router->delete('/{form}/fields/{field}', [$fields, 'destroy'])->name('fields.destroy')->where(['form' => $form, 'field' => $path]);
                $router->post('/{form}/fields/{field}/move', [$fields, 'move'])->name('fields.move')->where(['form' => $form, 'field' => $path]);
            }

            $router->get('/{form}/entries', [$entries, 'index'])->name('entries.index')->where('form', $form);
            $router->get('/{form}/entries/export', [$entries, 'export'])->name('entries.export')->where('form', $form);
            $router->get('/{form}/entries/{entry}', [$entries, 'show'])->name('entries.show')->where(['form' => $form, 'entry' => '[0-9]+']);
            $router->get('/{form}/entries/{entry}/print', [$entries, 'print'])->name('entries.print')->where(['form' => $form, 'entry' => '[0-9]+']);
            $router->get('/{form}/entries/{entry}/files/{field}/{index?}', [$entries, 'file'])->name('entries.file')->where(['form' => $form, 'entry' => '[0-9]+', 'field' => '[a-z0-9_.]+', 'index' => '[0-9]+']);
            $router->delete('/{form}/entries/{entry}', [$entries, 'destroy'])->name('entries.destroy')->where(['form' => $form, 'entry' => '[0-9]+']);
        });

        static::$options_routes['admin'] ??= "{$name}.options";
    }

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controller?: class-string
     * }  $options
     */
    public static function fill(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'f', '/');
        $name = rtrim($options['name'] ?? 'form-builder', '.');
        $middleware = $options['middleware'] ?? ($guard ? ["auth:{$guard}"] : []);
        $controller = $options['controller'] ?? config('form-builder.controllers.fill', FillController::class);

        $router->group([
            'prefix' => $prefix,
            'as' => "{$name}.",
            'middleware' => $middleware,
            self::GUARD_ACTION => $guard,
            self::FILL_ACTION => true,
        ], function (Router $router) use ($controller) {
            $form = '[A-Za-z0-9_-]+';

            $router->get('/{form}', [$controller, 'show'])->name('show')->where('form', $form);
            $router->post('/{form}', [$controller, 'submit'])->name('submit')->where('form', $form);
            $router->get('/{form}/thanks', [$controller, 'thanks'])->name('thanks')->where('form', $form);
            $router->get('/{form}/options/{field}', [$controller, 'options'])->name('options')->where(['form' => $form, 'field' => '[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)?']);
        });

        static::$options_routes['fill'] ??= "{$name}.options";
    }

    /**
     * The guard the current route was registered for, or null for the default guard
     */
    public static function guard(Request $request): ?string
    {
        return $request->route()?->getAction(self::GUARD_ACTION);
    }

    /**
     * The route name prefix of the current route, e.g. `admin.forms`
     */
    public static function namePrefix(Request $request, string $fallback = 'forms'): string
    {
        $name = (string) $request->route()?->getName();

        foreach (['.sections.', '.fields.', '.entries.', '.draft.', '.preview.', '.import.'] as $part) {
            if (str_contains($name, $part)) {
                return substr($name, 0, strpos($name, $part));
            }
        }

        return str_contains($name, '.')
            ? substr($name, 0, strrpos($name, '.'))
            : $fallback;
    }

    /**
     * The route that serves cascading select options: the request's own when it
     * has one, else the fill routes', else the admin routes'
     */
    public static function optionsRoute(?Request $request = null): ?string
    {
        $configured = config('form-builder.options_route');

        if (is_string($configured) && $configured !== '' && app('router')->has($configured)) {
            return $configured;
        }

        if ($request?->route()) {
            $own = static::namePrefix($request, '') . '.options';

            if (app('router')->has($own)) {
                return $own;
            }
        }

        foreach (['fill', 'admin'] as $kind) {
            $route = static::$options_routes[$kind];

            if ($route && app('router')->has($route)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * @internal for tests
     */
    public static function flush(): void
    {
        static::$options_routes = ['admin' => null, 'fill' => null];
    }
}
