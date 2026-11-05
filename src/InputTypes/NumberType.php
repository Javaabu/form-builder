<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;

class NumberType extends InputType
{
    public static function key(): string
    {
        return 'number';
    }

    public function name(): string
    {
        return __('Number');
    }

    public function icon(): string
    {
        return 'hash';
    }

    public function storage(): string
    {
        return self::STORE_NUMBER;
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        $input = TextInput::make($context->name($field->getKey()));

        $field->getSetting('integer') ? $input->integer() : $input->numeric();

        $min = $field->getSetting('min');
        $max = $field->getSetting('max');

        return $input
            ->minValue(is_numeric($min) ? $min + 0 : null)
            ->maxValue(is_numeric($max) ? $max + 0 : null);
    }

    public function normalize(mixed $value, Field $field): mixed
    {
        if (! is_numeric($value)) {
            return parent::normalize($value, $field);
        }

        return $field->getSetting('integer') ? (int) $value : $value + 0;
    }

    public function export(mixed $value, Field $field, ?string $locale = null): string|int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }

    public function settings(): array
    {
        return [
            Checkbox::make('integer')->label(__('Whole numbers only')),
            TextInput::make('min')->label(__('Smallest number'))->numeric(),
            TextInput::make('max')->label(__('Largest number'))->numeric(),
        ];
    }

    public function settingsRules(): array
    {
        return [
            'integer' => ['nullable', 'boolean'],
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric'],
        ];
    }
}
