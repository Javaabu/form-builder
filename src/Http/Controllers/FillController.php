<?php

namespace Javaabu\FormBuilder\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Http\Controllers\Concerns\InteractsWithForms;
use Javaabu\FormBuilder\InputTypes\Concerns\HasOptions;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\FormBuilder\Routing\FormBuilderRoutes;
use Javaabu\FormBuilder\Stencil\EntryForm;
use Javaabu\FormBuilder\Support\Strings;
use Javaabu\FormBuilder\Support\Text;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Filling forms: the form, submitting it, the thank-you page, and the options
 * of cascading selects. Extend it and set `config('form-builder.controllers.fill')`
 * (or pass `controller` to Route::formBuilderFill()).
 */
class FillController extends Controller
{
    use InteractsWithForms;

    /** The session key that holds the last entry's id, for the thank-you page */
    public const string SESSION_ENTRY = 'form_builder_entry';

    public function show(Request $request, string $form): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        if (! $model->isPublished()) {
            throw new NotFoundHttpException;
        }

        $open = $this->allowsFor($request, 'fill', $model);
        $version = $model->publishedVersion;
        $definition = $version->definition();

        $stencil = $open ? $this->form($request, $model, $version) : null;

        if ($request->expectsJson()) {
            return new JsonResponse(['open' => $open, 'form' => $stencil?->render('json')]);
        }

        return $this->views()->render('fill.show', $this->page($request, [
            'form' => $stencil && $this->views()->isInertia() ? $stencil->render('json') : $stencil,
            'open' => $open,
            'title' => $model->name,
            'description' => $model->description,
            'wizard' => $definition->isWizard(),
            'strings' => Strings::fill(),
        ]));
    }

    public function submit(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        if (! $model->isPublished()) {
            throw new NotFoundHttpException;
        }

        $this->authorizeFor($request, 'fill', $model);

        $version = $model->publishedVersion;

        $entry = FormBuilder::submit($version, $request, submitter: $this->user($request), stencil: $this->form($request, $model, $version));

        $this->submitted($request, $entry);

        $message = $this->confirmation($version);

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message, 'entry' => $entry->getKey()], 201);
        }

        if ($request->hasSession()) {
            $request->session()->flash(self::SESSION_ENTRY, $entry->getKey());
        }

        return redirect()->to($this->url($request, 'thanks', $model->key));
    }

    public function thanks(Request $request, string $form): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        if (! $model->isPublished()) {
            throw new NotFoundHttpException;
        }

        $message = $this->confirmation($model->publishedVersion);

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message]);
        }

        return $this->views()->render('fill.thanks', $this->page($request, [
            'title' => $model->name,
            'message' => $message,
            'entry' => $request->hasSession() ? $request->session()->get(self::SESSION_ENTRY) : null,
            'form_url' => $model->accepts_entries ? $this->url($request, 'show', $model->key) : null,
            'strings' => Strings::fill(),
        ]));
    }

    /**
     * A cascading select's options for its parent's value, as @javaabu/forms'
     * select2 cascade expects: GET …/options/island?filter[parent]=K →
     * {"data": [{"id": "male", "name": "Malé"}]}
     */
    public function options(Request $request, string $form, string $field): JsonResponse
    {
        $model = $this->findForm($form);
        $fill = (bool) $request->route()?->getAction(FormBuilderRoutes::FILL_ACTION);

        $version = $this->optionsVersion($request, $model, $fill);

        $this->authorizeFor($request, $fill ? 'fill' : 'view', $model);

        $definition = $version->definition();
        $target = $definition->getField($field) ?? throw new NotFoundHttpException;
        $type = FormBuilder::hasInputType($target->getType()) ? FormBuilder::inputType($target->getType()) : null;

        if (! $type || ! in_array(HasOptions::class, class_uses_recursive($type), true)) {
            throw new NotFoundHttpException;
        }

        $filter = (array) $request->query('filter', []);
        $parent = $filter[$target->getSetting('filter_field') ?: 'parent'] ?? null;

        $labels = $type->optionLabels($target, app()->getLocale(), is_scalar($parent) && $parent !== '' ? (string) $parent : null);

        if ($parent === null || $parent === '') {
            $labels = [];
        }

        return new JsonResponse([
            'data' => array_map(fn (string $value, string $label) => ['id' => $value, 'name' => $label], array_keys($labels), $labels),
        ]);
    }

    protected function optionsVersion(Request $request, Form $form, bool $fill): FormVersion
    {
        $id = $request->query('version');

        $version = $id
            ? $form->versions()->whereKey($id)->first()
            : ($fill ? $form->publishedVersion : $form->currentVersion());

        if (! $version || ($fill && ! $version->isPublished())) {
            throw new NotFoundHttpException;
        }

        return $version;
    }

    /**
     * The stencil form people fill
     */
    protected function form(Request $request, Form $form, FormVersion $version): EntryForm
    {
        return EntryForm::for($version)
            ->action($this->url($request, 'submit', $form->key))
            ->method('POST');
    }

    /**
     * Called after an entry is stored
     */
    protected function submitted(Request $request, FormEntry $entry): void {}

    protected function confirmation(FormVersion $version): string
    {
        return Text::resolve($version->definition()->getSetting('confirmation')) ?? __('Thank you. Your answers were sent.');
    }
}
