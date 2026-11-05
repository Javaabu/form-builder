<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\InputTypes\Concerns\HasOptions;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Radio;
use Javaabu\Stencil\Fields\TextInput;

/**
 * One of a few options, all shown
 */
class RadioType extends InputType
{
    use HasOptions;

    public static function key(): string
    {
        return 'radio';
    }

    public function name(): string
    {
        return __('Multiple choice');
    }

    public function icon(): string
    {
        return 'circle-dot';
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        $columns = $field->getSetting('columns');

        return Radio::make($context->name($field->getKey()))
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
