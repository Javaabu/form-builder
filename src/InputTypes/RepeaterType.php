<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Compiler\Compiler;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Stencil\Repeater;
use Javaabu\FormBuilder\Support\Text;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Components\Fieldset;
use Javaabu\Stencil\Fields\TextInput;

/**
 * A repeating group: its fields, filled any number of times
 */
class RepeaterType extends InputType
{
    public static function key(): string
    {
        return 'repeater';
    }

    public function name(): string
    {
        return __('Repeating group');
    }

    public function icon(): string
    {
        return 'copy-plus';
    }

    public function group(): string
    {
        return 'layout';
    }

    public function hasFields(): bool
    {
        return true;
    }

    public function isMultiple(Field $field): bool
    {
        return true;
    }

    protected function compiler(): Compiler
    {
        return app(Compiler::class);
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        $min = $field->getSetting('min_rows');
        $max = $field->getSetting('max_rows');
        $columns = $field->getSetting('columns');
        $row_label = Text::resolve($field->getSetting('row_label'), $context->locale);

        $repeater = Repeater::make($context->name($field->getKey()))
            ->schema(fn (string $row) => $this->compiler()->fields($field->getFields(), $context->forRow($field, $row)))
            ->minRows(is_numeric($min) ? (int) $min : ($field->isRequired() ? 1 : null))
            ->maxRows(is_numeric($max) ? (int) $max : null)
            ->addLabel(Text::resolve($field->getSetting('add_label'), $context->locale))
            ->rowLabel($row_label === null ? null : (str_contains($row_label, ':number') ? $row_label : "{$row_label} :number"));

        if (is_numeric($columns) && (int) $columns > 1) {
            $repeater->columns((int) $columns);
        }

        return $repeater;
    }

    public function display(Field $field, mixed $value, CompileContext $context): ?Component
    {
        $label = Text::resolve($field->getLabel(), $context->locale) ?? str($field->getKey())->headline()->toString();
        $row_label = Text::resolve($field->getSetting('row_label'), $context->locale) ?? "{$label} :number";
        $row_label = str_contains($row_label, ':number') ? $row_label : "{$row_label} :number";
        $path = $context->path($field->getKey());

        $rows = [];

        foreach (array_values(is_array($value) ? $value : []) as $index => $row) {
            $rows[] = Fieldset::make(str_replace(':number', (string) ($index + 1), $row_label))
                ->key("{$path}-{$index}")
                ->schema($this->compiler()->fields($field->getFields(), $context->forRow($field, "{$path}.{$index}")));
        }

        if ($rows === []) {
            return parent::display($field, null, $context);
        }

        return Fieldset::make($label)->key($path)->schema($rows);
    }

    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        return trans_choice('{1} :count row|[2,*] :count rows', count($value), ['count' => count($value)]);
    }

    public function settings(): array
    {
        return [
            TextInput::make('min_rows')->label(__('Fewest rows'))->integer()->minValue(0),
            TextInput::make('max_rows')->label(__('Most rows'))->integer()->minValue(1),
            TextInput::make('add_label')->label(__('Add button label')),
            TextInput::make('row_label')->label(__('Row heading'))->help(__('Use :number for the row\'s number.')),
            TextInput::make('columns')->label(__('Columns'))->integer()->minValue(1)->maxValue(4),
        ];
    }

    public function settingsRules(): array
    {
        return [
            'min_rows' => ['nullable', 'integer', 'min:0'],
            'max_rows' => ['nullable', 'integer', 'min:1'],
            'add_label' => ['nullable'],
            'row_label' => ['nullable'],
            'columns' => ['nullable', 'integer', 'min:1', 'max:4'],
        ];
    }
}
