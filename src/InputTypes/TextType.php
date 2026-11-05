<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;

/**
 * One line of text
 */
class TextType extends InputType
{
    public static function key(): string
    {
        return 'text';
    }

    public function name(): string
    {
        return __('Short text');
    }

    public function icon(): string
    {
        return 'type';
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return $this->input(TextInput::make($context->name($field->getKey())), $field);
    }

    protected function input(TextInput $input, Field $field): TextInput
    {
        return $input
            ->thaana((bool) $field->getSetting('thaana'))
            ->minLength($this->intSetting($field, 'min_length'))
            ->maxLength($this->intSetting($field, 'max_length') ?? 255);
    }

    protected function intSetting(Field $field, string $key): ?int
    {
        $value = $field->getSetting($key);

        return is_numeric($value) ? (int) $value : null;
    }

    public function settings(): array
    {
        return [
            Checkbox::make('thaana')->label(__('Dhivehi (Thaana) keyboard')),
            TextInput::make('min_length')->label(__('Minimum length'))->integer()->minValue(0),
            TextInput::make('max_length')->label(__('Maximum length'))->integer()->minValue(1),
            TextInput::make('default')->label(__('Default value')),
        ];
    }

    public function settingsRules(): array
    {
        return [
            'thaana' => ['nullable', 'boolean'],
            'min_length' => ['nullable', 'integer', 'min:0'],
            'max_length' => ['nullable', 'integer', 'min:1'],
            'default' => ['nullable', 'string', 'max:255'],
        ];
    }
}
