<?php

namespace Javaabu\FormBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\FormBuilder\Ai\FormAssistant;
use Javaabu\FormBuilder\Builder\BuilderData;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Http\Controllers\Concerns\InteractsWithForms;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The AI assistant (laravel/ai): a new form from a description, or a form's
 * draft changed as described
 */
class AssistantController extends Controller
{
    use InteractsWithForms;

    public function __construct(
        protected BuilderData $data,
    ) {}

    /**
     * A new form from `description`
     */
    public function create(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureAvailable();

        $this->authorizeFor($request, 'create', FormBuilder::model('form'));

        $result = app(FormAssistant::class)->build($this->description($request), author: $this->user($request));

        return $this->done($request, $result['summary'], $this->url($request, 'show', $result['form']->key), [
            'form' => $this->data->form($request, $result['form']),
        ], 201);
    }

    /**
     * Change the form's draft as `description` says
     */
    public function update(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $this->ensureAvailable();

        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $result = app(FormAssistant::class)->build($this->description($request), $model, $this->user($request));

        return $this->done($request, $result['summary'], $this->url($request, 'show', $model->key), [
            'form' => $this->data->form($request, $result['form']),
        ]);
    }

    protected function description(Request $request): string
    {
        return (string) $request->validate([
            'description' => ['required', 'string', 'max:' . (int) config('form-builder.assistant.max_length', 4000)],
        ], [], ['description' => __('description')])['description'];
    }

    protected function ensureAvailable(): void
    {
        if (! FormAssistant::available()) {
            throw new NotFoundHttpException;
        }
    }
}
