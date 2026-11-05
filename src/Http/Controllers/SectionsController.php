<?php

namespace Javaabu\FormBuilder\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\Builder\BuilderData;
use Javaabu\FormBuilder\Builder\BuilderForms;
use Javaabu\FormBuilder\Builder\DraftEditor;
use Javaabu\FormBuilder\Builder\StructureEditor;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Http\Controllers\Concerns\InteractsWithForms;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Support\Strings;
use Javaabu\FormBuilder\Support\Text;

/**
 * Adding, changing, ordering and removing the sections of a form's draft
 */
class SectionsController extends Controller
{
    use InteractsWithForms;

    public function __construct(
        protected BuilderData $data,
        protected BuilderForms $forms,
        protected DraftEditor $editor,
        protected StructureEditor $structure,
    ) {}

    public function create(Request $request, string $form): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);

        $stencil = $this->forms->sectionForm($definition)
            ->action($this->url($request, 'sections.store', $model->key))
            ->method('POST')
            ->submit(__('Add section'));

        return $this->editPage($request, $model, $stencil, __('New section'));
    }

    public function store(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $data = $this->forms->sectionForm($this->definitionOf($model))->validate($request);
        $section = $this->forms->sectionFrom($data);

        $this->ensureUniqueKey($this->definitionOf($model), $section->getKey());

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->addSection($definition, $section, $request->integer('position', PHP_INT_MAX)),
            $this->user($request),
            fn (FormDefinition $definition) => DraftEditor::sectionPath($definition, $section->getKey()),
        );

        return $this->done($request, __('Section added.'), $this->url($request, 'show', $model->key), ['section' => $section->toArray()], 201);
    }

    public function edit(Request $request, string $form, string $section): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        $current = $this->structure->findSection($definition, $section);

        $stencil = $this->forms->sectionForm($definition, $current)
            ->action($this->url($request, 'sections.update', [$model->key, $section]))
            ->method('PATCH');

        return $this->editPage($request, $model, $stencil, __('Edit :section', ['section' => Text::resolve($current->getTitle()) ?? $current->getKey()]), $current);
    }

    public function update(Request $request, string $form, string $section): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        $current = $this->structure->findSection($definition, $section);

        $data = $this->forms->sectionForm($definition, $current)->validate($request);
        $updated = $this->forms->sectionFrom($data);

        if ($updated->getKey() !== $section) {
            $this->ensureUniqueKey($definition, $updated->getKey());
        }

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->updateSection($definition, $section, $updated),
            $this->user($request),
            fn (FormDefinition $definition) => DraftEditor::sectionPath($definition, $updated->getKey()),
        );

        return $this->done($request, __('Section saved.'), $this->url($request, 'show', $model->key), ['section' => $updated->toArray()]);
    }

    public function destroy(Request $request, string $form, string $section): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        $this->structure->findSection($definition, $section);

        if (count($definition->getSections()) <= 1) {
            throw ValidationException::withMessages(['section' => __('A form needs at least one section.')]);
        }

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->removeSection($definition, $section),
            $this->user($request),
        );

        return $this->done($request, __('Section removed.'), $this->url($request, 'show', $model->key));
    }

    /**
     * Move a section to `position` (from 0), or one place `up` or `down` (`direction`)
     */
    public function move(Request $request, string $form, string $section): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        $index = $this->structure->sectionIndex($definition, $section);
        $position = $this->targetPosition($request, $index);

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->moveSection($definition, $section, $position),
            $this->user($request),
        );

        return $this->done($request, __('Section moved.'), $this->url($request, 'show', $model->key));
    }

    /**
     * Put the sections in the order of `sections` (keys)
     */
    public function order(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $keys = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*' => ['string', 'distinct'],
        ])['sections'];

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->orderSections($definition, $keys),
            $this->user($request),
        );

        return $this->done($request, __('Order saved.'), $this->url($request, 'show', $model->key));
    }

    protected function editPage(Request $request, Form $model, mixed $stencil, string $title, ?Section $section = null): View|Responsable|JsonResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['form' => $stencil->render('json'), 'section' => $section?->toArray()]);
        }

        return $this->views()->render('sections.edit', $this->page($request, [
            'form' => $this->views()->isInertia() ? $stencil->render('json') : $stencil,
            'record' => $this->data->form($request, $model),
            'section' => $section?->toArray(),
            'title' => $title,
            'back_url' => $this->url($request, 'show', $model->key),
            'strings' => Strings::builder(),
        ]));
    }

    protected function definitionOf(Form $form): FormDefinition
    {
        if ($form->is_locked) {
            abort(403);
        }

        $version = $form->draftVersion ?? $form->currentVersion();

        return FormDefinition::fromArray(($version?->definition() ?? FormDefinition::make($form->key))->toArray());
    }

    protected function ensureUniqueKey(FormDefinition $definition, string $key): void
    {
        if ($definition->getSection($key)) {
            throw ValidationException::withMessages(['key' => __('Another section already has the key :key.', ['key' => $key])]);
        }
    }

    protected function targetPosition(Request $request, int $index): int
    {
        if ($request->filled('position')) {
            return max(0, $request->integer('position'));
        }

        return match ($request->input('direction')) {
            'up' => max(0, $index - 1),
            'down' => $index + 1,
            default => throw ValidationException::withMessages(['position' => __('Say where to move it.')]),
        };
    }
}
