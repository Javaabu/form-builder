<?php

namespace Javaabu\FormBuilder\Compiler;

use Javaabu\FormBuilder\Definitions\Condition;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\FormBuilderManager;
use Javaabu\FormBuilder\Support\Text;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Components\Section as StencilSection;
use Javaabu\Stencil\Components\Step;
use Javaabu\Stencil\Components\Wizard;

/**
 * Turns a form definition into stencil components: sections (or the steps of
 * a wizard) holding each field's component, with conditions. In display mode,
 * read-only entries of the answers instead.
 */
class Compiler
{
    public function __construct(
        protected FormBuilderManager $builder,
    ) {}

    /**
     * @return array<int, Component>
     */
    public function compile(CompileContext $context): array
    {
        $definition = $context->definition;
        $as_steps = $definition->isWizard() && ! $context->isDisplay();

        $sections = array_map(
            fn (Section $section) => $this->section($section, $context, $as_steps),
            $definition->getSections(),
        );

        if (! $as_steps) {
            return $sections;
        }

        return [Wizard::make($sections)->key('wizard')];
    }

    public function section(Section $section, CompileContext $context, bool $as_step = false): Component
    {
        $title = Text::resolve($section->getTitle(), $context->locale);
        $description = Text::resolve($section->getDescription(), $context->locale);

        $component = $as_step
            ? Step::make($title ?? str($section->getKey())->headline()->toString())->description($description)
            : StencilSection::make($title)->description($description);

        $component
            ->key(($as_step ? 'step-' : 'section-') . $section->getKey())
            ->schema($this->fields($section->getFields(), $context));

        if ($section->getColumns() && $section->getColumns() > 1) {
            $component->columns($section->getColumns());
        }

        return $this->withConditions($component, $section->getConditions(), $context);
    }

    /**
     * @param  array<int, Field>  $fields
     * @return array<int, Component>
     */
    public function fields(array $fields, CompileContext $context): array
    {
        return array_values(array_filter(array_map(fn (Field $field) => $this->field($field, $context), $fields)));
    }

    public function field(Field $field, CompileContext $context): ?Component
    {
        $type = $this->builder->inputType($field->getType());

        if ($context->isDisplay()) {
            if (! $context->passes($field->getConditions())) {
                return null;
            }

            $component = $type->display($field, $context->answer($context->path($field->getKey())), $context);

            return $component ? $this->withSpan($component, $field) : null;
        }

        $component = $type->toComponent($field, $context);

        $this->withSpan($component, $field);
        $this->withConditions($component, $field->getConditions(), $context);

        foreach ($this->builder->inputTypeExtensions($type::key()) as $extension) {
            $component = $extension($component, $field, $context) ?? $component;
        }

        return $component;
    }

    protected function withSpan(Component $component, Field $field): Component
    {
        $span = $field->getSpan();

        if ($span === 'full') {
            return $component->columnSpanFull();
        }

        if (is_numeric($span) && (int) $span > 1) {
            return $component->columnSpan((int) $span);
        }

        return $component;
    }

    /**
     * @param  array<int, Condition>  $conditions
     */
    protected function withConditions(Component $component, array $conditions, CompileContext $context): Component
    {
        if ($context->isDisplay()) {
            return $context->passes($conditions) ? $component : $component->hidden();
        }

        foreach ($conditions as $condition) {
            $path = $context->watchedPath($condition->field);
            $watched = $context->watchedField($condition->field);
            $checkbox = $watched && $this->builder->hasInputType($watched->getType())
                ? $this->builder->inputType($watched->getType())->watchesAsCheckbox()
                : false;

            match (true) {
                $condition->operator === Condition::CHECKED => $component->visibleWhenChecked($path),
                $condition->operator === Condition::NOT_CHECKED => $component->hiddenWhenChecked($path),
                $checkbox && $condition->operator === Condition::IN => in_array('1', $condition->values, true) ? $component->visibleWhenChecked($path) : $component->hiddenWhenChecked($path),
                $checkbox => in_array('1', $condition->values, true) ? $component->hiddenWhenChecked($path) : $component->visibleWhenChecked($path),
                $condition->operator === Condition::IN => $component->visibleWhen($path, $condition->values),
                default => $component->hiddenWhen($path, $condition->values),
            };
        }

        return $component;
    }
}
