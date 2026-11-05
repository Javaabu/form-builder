<?php

namespace Javaabu\FormBuilder\Builder;

use Illuminate\Http\Request;
use Javaabu\FormBuilder\Definitions\Condition;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\FormBuilderManager;
use Javaabu\FormBuilder\InputTypes\InputType;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\FormBuilder\Routing\FormBuilderRoutes;
use Javaabu\FormBuilder\Support\Text;

/**
 * What the builder pages show, as arrays: the same for the Blade views, the
 * Inertia props and JSON responses
 */
class BuilderData
{
    public function __construct(
        protected FormBuilderManager $builder,
    ) {}

    protected function route(Request $request, string $action, mixed $parameters = []): ?string
    {
        $name = FormBuilderRoutes::namePrefix($request) . ".{$action}";

        return app('router')->has($name) ? route($name, $parameters) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function form(Request $request, Form $form): array
    {
        $published = $form->publishedVersion;
        $draft = $form->draftVersion;

        return [
            'id' => $form->getKey(),
            'key' => $form->key,
            'name' => $this->formName($form),
            'description' => $form->description,
            'status' => $this->status($form),
            'status_label' => $this->statusLabel($form),
            'is_locked' => $form->is_locked,
            'accepts_entries' => $form->accepts_entries,
            'published_version' => $published?->number,
            'draft_version' => $draft?->number,
            'has_draft' => $draft !== null,
            'entries_count' => $form->entries_count ?? $form->entries()->count(),
            'updated_at' => $form->updated_at?->toIso8601String(),
            'urls' => array_filter([
                'show' => $this->route($request, 'show', $form->key),
                'edit' => $this->route($request, 'edit', $form->key),
                'entries' => $this->route($request, 'entries.index', $form->key),
                'preview' => $this->route($request, 'preview', $form->key),
                'destroy' => $this->route($request, 'destroy', $form->key),
                'fill' => $published && $form->accepts_entries ? $this->fillUrl($form) : null,
            ]),
        ];
    }

    public function formName(Form $form): string
    {
        $name = $form->name;

        return $name === '' ? $form->key : $name;
    }

    public function status(Form $form): string
    {
        if ($form->isPublished()) {
            return $form->hasDraft() ? 'changed' : 'published';
        }

        return 'draft';
    }

    public function statusLabel(Form $form): string
    {
        return match ($this->status($form)) {
            'changed' => __('Published, with unpublished changes'),
            'published' => __('Published'),
            default => __('Draft'),
        };
    }

    public function fillUrl(Form $form): ?string
    {
        foreach (app('router')->getRoutes() as $route) {
            if ($route->getAction(FormBuilderRoutes::FILL_ACTION) && str_ends_with((string) $route->getName(), '.show')) {
                return route($route->getName(), $form->key);
            }
        }

        return null;
    }

    /**
     * The structure the builder page lists, with the urls of each action
     *
     * @return array<int, array<string, mixed>>
     */
    public function sections(Request $request, Form $form, FormDefinition $definition, bool $editable): array
    {
        $count = count($definition->getSections());

        return array_map(function (Section $section, int $index) use ($request, $form, $definition, $editable, $count) {
            $fields = $section->getFields();

            return [
                'key' => $section->getKey(),
                'title' => Text::resolve($section->getTitle()) ?? str($section->getKey())->headline()->toString(),
                'description' => Text::resolve($section->getDescription()),
                'columns' => $section->getColumns(),
                'conditions' => $this->conditions($definition, $section->getConditions()),
                'position' => $index,
                'is_first' => $index === 0,
                'is_last' => $index === $count - 1,
                'urls' => $editable ? array_filter([
                    'edit' => $this->route($request, 'sections.edit', [$form->key, $section->getKey()]),
                    'destroy' => $this->route($request, 'sections.destroy', [$form->key, $section->getKey()]),
                    'move' => $this->route($request, 'sections.move', [$form->key, $section->getKey()]),
                    'create_field' => $this->route($request, 'fields.create', [$form->key, $section->getKey()]),
                    'order_fields' => $this->route($request, 'fields.order', [$form->key, $section->getKey()]),
                ]) : [],
                'fields' => array_map(
                    fn (Field $field, int $position) => $this->field($request, $form, $definition, $section, $field, $editable, $position, count($fields)),
                    $fields,
                    array_keys($fields),
                ),
            ];
        }, $definition->getSections(), array_keys($definition->getSections()));
    }

    /**
     * @return array<string, mixed>
     */
    protected function field(Request $request, Form $form, FormDefinition $definition, Section $section, Field $field, bool $editable, int $position, int $count, ?Field $group = null): array
    {
        $type = $this->builder->hasInputType($field->getType()) ? $this->builder->inputType($field->getType()) : null;
        $path = $group ? "{$group->getKey()}.{$field->getKey()}" : $field->getKey();
        $children = $field->getFields();

        return [
            'key' => $field->getKey(),
            'path' => $path,
            'type' => $field->getType(),
            'type_name' => $type?->name() ?? $field->getType(),
            'icon' => $type?->icon(),
            'label' => Text::resolve($field->getLabel()) ?? Text::resolve($field->getSetting('content')) ?? str($field->getKey())->headline()->toString(),
            'required' => $field->isRequired(),
            'missing_type' => $type === null,
            'has_fields' => (bool) $type?->hasFields(),
            'depends_on' => $field->getSetting('parent'),
            'conditions' => $this->conditions($definition, $field->getConditions()),
            'position' => $position,
            'is_first' => $position === 0,
            'is_last' => $position === $count - 1,
            'urls' => $editable ? array_filter([
                'edit' => $this->route($request, 'fields.edit', [$form->key, $path]),
                'destroy' => $this->route($request, 'fields.destroy', [$form->key, $path]),
                'move' => $this->route($request, 'fields.move', [$form->key, $path]),
                'create_field' => $type?->hasFields() ? $this->route($request, 'fields.create', [$form->key, $section->getKey(), 'group' => $field->getKey()]) : null,
                'order_fields' => $type?->hasFields() ? $this->route($request, 'fields.order', [$form->key, $section->getKey(), 'group' => $field->getKey()]) : null,
            ]) : [],
            'fields' => array_map(
                fn (Field $child, int $index) => $this->field($request, $form, $definition, $section, $child, $editable, $index, count($children), $field),
                $children,
                array_keys($children),
            ),
        ];
    }

    /**
     * Conditions as sentences: "Applicant type is one of Company"
     *
     * @param  array<int, Condition>  $conditions
     * @return array<int, string>
     */
    public function conditions(FormDefinition $definition, array $conditions): array
    {
        return array_map(function (Condition $condition) use ($definition) {
            $watched = $definition->getField($condition->field);
            $label = $watched ? (Text::resolve($watched->getLabel()) ?? $condition->field) : $condition->field;
            $values = $condition->values;

            if ($watched && $this->builder->hasInputType($watched->getType())) {
                $type = $this->builder->inputType($watched->getType());

                if ($type->hasOptions()) {
                    $labels = $type->optionLabels($watched);
                    $values = array_map(fn ($value) => $labels[$value] ?? $value, $values);
                }
            }

            return match ($condition->operator) {
                Condition::IN => __(':field is one of :values', ['field' => $label, 'values' => implode(', ', $values)]),
                Condition::NOT_IN => __(':field is not one of :values', ['field' => $label, 'values' => implode(', ', $values)]),
                Condition::CHECKED => __(':field is checked or filled', ['field' => $label]),
                default => __(':field is not checked or empty', ['field' => $label]),
            };
        }, $conditions);
    }

    /**
     * The input types the builder offers, grouped
     *
     * @return array<int, array{key: string, label: string, types: array<int, array<string, mixed>>}>
     */
    public function types(?bool $in_group = false): array
    {
        $groups = [
            'text' => __('Text'),
            'choice' => __('Choices'),
            'date' => __('Dates and times'),
            'file' => __('Files'),
            'layout' => __('Layout'),
        ];

        $grouped = [];

        foreach ($this->builder->inputTypes() as $type) {
            if ($in_group && $type->hasFields()) {
                continue;
            }

            $group = array_key_exists($type->group(), $groups) ? $type->group() : 'other';

            $grouped[$group][] = $type->toArray();
        }

        $result = [];

        foreach ([...array_keys($groups), 'other'] as $group) {
            if (! isset($grouped[$group])) {
                continue;
            }

            $result[] = ['key' => $group, 'label' => $groups[$group] ?? __('Other'), 'types' => $grouped[$group]];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function version(FormVersion $version): array
    {
        $author = $version->author;

        return [
            'id' => $version->getKey(),
            'number' => $version->number,
            'is_draft' => $version->isDraft(),
            'is_current' => $version->form->published_version_id === $version->getKey(),
            'published_at' => $version->published_at?->toIso8601String(),
            'author' => $author ? (string) ($author->getAttribute('name') ?? $author->getKey()) : null,
            'entries_count' => $version->entries_count ?? $version->entries()->count(),
            'fields_count' => count(array_filter($version->definition()->getFields(), fn (Field $field) => $this->builder->hasInputType($field->getType()) && $this->builder->inputType($field->getType())->takesInput())),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function type(InputType $type): array
    {
        return $type->toArray();
    }
}
