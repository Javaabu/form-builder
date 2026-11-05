<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\InputTypes\Concerns\HasOptions;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\TextInput;

/**
 * Any number of options, all shown
 */
class CheckboxesType extends InputType
{
    use HasOptions;

    public static function key(): string
    {
        return 'checkboxes';
    }

    public function name(): string
    {
        return __('Checkboxes');
    }

    public function icon(): string
    {
        return 'list-checks';
    }

    public function isMultiple(Field $field): bool
    {
        return true;
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        $columns = $field->getSetting('columns');

        return CheckboxList::make($context->name($field->getKey()))
            ->options($this->optionLabels($field, $context->locale))
            ->gridColumns(is_numeric($columns) && (int) $columns > 1 ? (int) $columns : null);
    }

    public function settings(): array
    {
        return [
            TextInput::make('columns')->label(__('Columns'))->integer()->minValue(1)->maxValue(6),
        ];
    }

    public function settingsRules(): array
    {
        return ['columns' => ['nullable', 'integer', 'min:1', 'max:6']];
    }
}
