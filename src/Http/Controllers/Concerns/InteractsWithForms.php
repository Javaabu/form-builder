<?php

namespace Javaabu\FormBuilder\Http\Controllers\Concerns;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Routing\FormBuilderRoutes;
use Javaabu\FormBuilder\Views\ViewRenderer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Shared by the package's controllers: finding forms, authorizing for the
 * route's guard, route names, and what every page gets
 */
trait InteractsWithForms
{
    protected function views(): ViewRenderer
    {
        return app(ViewRenderer::class);
    }

    protected function findForm(string $key): Form
    {
        /** @var Form|null $form */
        $form = FormBuilder::model('form')::query()->where('key', $key)->first();

        return $form ?? throw new NotFoundHttpException;
    }

    protected function authorizeFor(Request $request, string $ability, mixed $argument = []): void
    {
        $this->gate($request)->authorize($ability, $argument);
    }

    protected function allowsFor(Request $request, string $ability, mixed $argument = []): bool
    {
        return $this->gate($request)->allows($ability, $argument);
    }

    protected function gate(Request $request): Gate
    {
        $gate = app(Gate::class);

        $user = $this->user($request);

        return $user ? $gate->forUser($user) : $gate;
    }

    protected function user(Request $request): ?Model
    {
        $user = $request->user(FormBuilderRoutes::guard($request));

        return $user instanceof Model ? $user : null;
    }

    protected function routeName(Request $request, string $action): string
    {
        return FormBuilderRoutes::namePrefix($request) . ".{$action}";
    }

    /**
     * @param  array<string, mixed>|string|int  $parameters
     */
    protected function url(Request $request, string $action, mixed $parameters = []): string
    {
        return route($this->routeName($request, $action), $parameters);
    }

    protected function hasRoute(Request $request, string $action): bool
    {
        return app('router')->has($this->routeName($request, $action));
    }

    /**
     * What every page gets: the layout, the status message
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function page(Request $request, array $data): array
    {
        return [
            'status' => $request->hasSession() ? $request->session()->get('status') : null,
            'layout' => config('form-builder.layout', 'layouts.app'),
            'layout_section' => config('form-builder.layout_section', 'content'),
            ...$data,
        ];
    }

    /**
     * Back to a page with a message, or the message as JSON
     *
     * @param  array<string, mixed>  $json
     */
    protected function done(Request $request, string $message, string $url, array $json = [], int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message, ...$json], $status);
        }

        return redirect()->to($url)->with('status', $message);
    }
}
