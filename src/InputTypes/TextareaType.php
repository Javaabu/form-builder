<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;

/**
 * Several lines of text
 */
class TextareaType extends InputType
{
    public static function key(): string
    {
        return 'textarea';
    }

    public function name(): string
    {
        return __('Long text');
    }

    public function icon(): string
    {
        return 'align-left';
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        $rows = $field->getSetting('rows');
        $min = $field->getSetting('min_length');
        $max = $field->getSetting('max_length');

        return Textarea::make($context->name($field->getKey()))
            ->rows(is_numeric($rows) ? (int) $rows : null)
            ->thaana((bool) $field->getSetting('thaana'))
            ->minLength(is_numeric($min) ? (int) $min : null)
            ->maxLength(is_numeric($max) ? (int) $max : 65535);
    }

    protected function displaysMultiline(Field $field): bool
    {
        return true;
    }

    public function settings(): array
    {
        return [
            Checkbox::make('thaana')->label(__('Dhivehi (Thaana) keyboard')),
            TextInput::make('rows')->label(__('Rows'))->integer()->minValue(1)->maxValue(50),
            TextInput::make('min_length')->label(__('Minimum length'))->integer()->minValue(0),
            TextInput::make('max_length')->label(__('Maximum length'))->integer()->minValue(1),
        ];
    }

    public function settingsRules(): array
    {
        return [
            'thaana' => ['nullable', 'boolean'],
            'rows' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_length' => ['nullable', 'integer', 'min:0'],
            'max_length' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
