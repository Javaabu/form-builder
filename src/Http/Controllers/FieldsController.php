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
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Http\Controllers\Concerns\InteractsWithForms;
use Javaabu\FormBuilder\InputTypes\InputType;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Support\Strings;
use Javaabu\FormBuilder\Support\Text;
use Javaabu\Stencil\Form as StencilForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Adding, configuring, ordering, moving and removing the fields of a form's
 * draft. Fields in a repeating group are addressed as group.key.
 */
class FieldsController extends Controller
{
    use InteractsWithForms;

    public function __construct(
        protected BuilderData $data,
        protected BuilderForms $forms,
        protected DraftEditor $editor,
        protected StructureEditor $structure,
    ) {}

    /**
     * The settings form of a new field: ?type=text, and ?group=references for a field in a repeating group
     */
    public function create(Request $request, string $form, string $section): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        $this->structure->findSection($definition, $section);
        $group = $this->groupOf($request, $definition);
        $type = $this->findType((string) $request->query('type'), $group !== null);

        $stencil = $this->forms->fieldForm($definition, $type, null, $section, $group)
            ->action($this->url($request, 'fields.store', [$model->key, $section, 'type' => $type::key(), 'group' => $group]))
            ->method('POST')
            ->submit(__('Add field'));

        return $this->editPage($request, $model, $stencil, __('New :type', ['type' => mb_strtolower($type->name())]), $type);
    }

    public function store(Request $request, string $form, string $section): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        $this->structure->findSection($definition, $section);
        $group = $this->groupOf($request, $definition);
        $type = $this->findType((string) ($request->query('type') ?? $request->input('type')), $group !== null);

        $data = $this->forms->fieldForm($definition, $type, null, $section, $group)->validate($request);
        $field = $this->forms->fieldFrom($type, $data);

        $this->ensureUniqueKey($definition, $field->getKey(), $group);

        $target = $group === null ? ((string) ($data['section'] ?? $section) ?: $section) : $section;
        $path = $group === null ? $field->getKey() : "{$group}.{$field->getKey()}";

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->addField($definition, $target, $field, $group, $request->filled('position') ? $request->integer('position') : null),
            $this->user($request),
            fn (FormDefinition $definition) => DraftEditor::fieldPath($definition, $path),
        );

        return $this->done($request, __('Field added.'), $this->url($request, 'show', $model->key), ['field' => $field->toArray()], 201);
    }

    public function edit(Request $request, string $form, string $field): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        [$section, $group, $current] = $this->structure->locate($definition, $field);
        $type = $this->findType($current->getType(), $group !== null);

        $stencil = $this->forms->fieldForm($definition, $type, $current, $section->getKey(), $group?->getKey())
            ->action($this->url($request, 'fields.update', [$model->key, $field]))
            ->method('PATCH');

        $label = Text::resolve($current->getLabel()) ?? $current->getKey();

        return $this->editPage($request, $model, $stencil, __('Edit :field', ['field' => $label]), $type, $current);
    }

    public function update(Request $request, string $form, string $field): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        [$section, $group, $current] = $this->structure->locate($definition, $field);
        $type = $this->findType($current->getType(), $group !== null);

        $data = $this->forms->fieldForm($definition, $type, $current, $section->getKey(), $group?->getKey())->validate($request);
        $updated = $this->forms->fieldFrom($type, $data, $current);

        if ($updated->getKey() !== $current->getKey()) {
            $this->ensureUniqueKey($definition, $updated->getKey(), $group?->getKey());
        }

        $path = $group === null ? $updated->getKey() : "{$group->getKey()}.{$updated->getKey()}";
        $target = $group === null ? ($data['section'] ?? null) : null;

        $this->editor->change(
            $model,
            function (FormDefinition $definition) use ($field, $updated, $target, $section, $path) {
                $this->structure->updateField($definition, $field, $updated);

                if (is_string($target) && $target !== '' && $target !== $section->getKey()) {
                    $this->structure->moveField($definition, $path, $target, PHP_INT_MAX);
                }

                return $definition;
            },
            $this->user($request),
            fn (FormDefinition $definition) => DraftEditor::fieldPath($definition, $path),
        );

        return $this->done($request, __('Field saved.'), $this->url($request, 'show', $model->key), ['field' => $updated->toArray()]);
    }

    public function destroy(Request $request, string $form, string $field): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        $this->structure->locate($definition, $field);

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->removeField($definition, $field),
            $this->user($request),
        );

        return $this->done($request, __('Field removed.'), $this->url($request, 'show', $model->key));
    }

    /**
     * Move a field to `position` (from 0) or one place `up` or `down`
     * (`direction`), optionally into another `section`
     */
    public function move(Request $request, string $form, string $field): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $definition = $this->definitionOf($model);
        [$section, $group, $current] = $this->structure->locate($definition, $field);

        $container = $group ?? $section;
        $index = array_search($current, $container->getFields(), true);
        $target = $group === null && $request->filled('section') ? (string) $request->input('section') : null;

        if ($target !== null) {
            $this->structure->findSection($definition, $target);
        }

        $position = match (true) {
            $request->filled('position') => max(0, $request->integer('position')),
            $request->input('direction') === 'up' => max(0, (int) $index - 1),
            $request->input('direction') === 'down' => (int) $index + 1,
            $target !== null => PHP_INT_MAX,
            default => throw ValidationException::withMessages(['position' => __('Say where to move it.')]),
        };

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->moveField($definition, $field, $target, $position),
            $this->user($request),
        );

        return $this->done($request, __('Field moved.'), $this->url($request, 'show', $model->key));
    }

    /**
     * Put a section's fields (or a repeating group's, with ?group=) in the order of `fields` (keys)
     */
    public function order(Request $request, string $form, string $section): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $keys = $request->validate([
            'fields' => ['required', 'array'],
            'fields.*' => ['string', 'distinct'],
        ])['fields'];

        $definition = $this->definitionOf($model);
        $this->structure->findSection($definition, $section);
        $group = $this->groupOf($request, $definition);

        $this->editor->change(
            $model,
            fn (FormDefinition $definition) => $this->structure->orderFields($definition, $section, $keys, $group),
            $this->user($request),
        );

        return $this->done($request, __('Order saved.'), $this->url($request, 'show', $model->key));
    }

    protected function editPage(Request $request, Form $model, StencilForm $stencil, string $title, InputType $type, ?Field $field = null): View|Responsable|JsonResponse
    {
        if ($request->expectsJson()) {
            return new JsonResponse(['form' => $stencil->render('json'), 'type' => $type->toArray(), 'field' => $field?->toArray()]);
        }

        return $this->views()->render('fields.edit', $this->page($request, [
            'form' => $this->views()->isInertia() ? $stencil->render('json') : $stencil,
            'record' => $this->data->form($request, $model),
            'type' => $type->toArray(),
            'field' => $field?->toArray(),
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

    protected function findType(string $key, bool $in_group = false): InputType
    {
        if (! FormBuilder::hasInputType($key)) {
            throw new NotFoundHttpException;
        }

        $type = FormBuilder::inputType($key);

        if ($in_group && $type->hasFields()) {
            throw new NotFoundHttpException;
        }

        return $type;
    }

    /**
     * The repeating group in ?group=, which must be a field that holds fields
     */
    protected function groupOf(Request $request, FormDefinition $definition): ?string
    {
        $group = $request->query('group') ?? $request->input('group');

        if (! is_string($group) || $group === '') {
            return null;
        }

        $field = $this->structure->findField($definition, $group);

        if (! FormBuilder::hasInputType($field->getType()) || ! FormBuilder::inputType($field->getType())->hasFields()) {
            throw new NotFoundHttpException;
        }

        return $group;
    }

    protected function ensureUniqueKey(FormDefinition $definition, string $key, ?string $group): void
    {
        $taken = $group === null
            ? $definition->getField($key) !== null
            : $definition->getField("{$group}.{$key}") !== null;

        if ($taken) {
            throw ValidationException::withMessages(['key' => __('Another field already has the key :key.', ['key' => $key])]);
        }
    }
}
