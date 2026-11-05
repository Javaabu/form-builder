<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\TimePicker;

class TimeType extends DateType
{
    public static function key(): string
    {
        return 'time';
    }

    public function name(): string
    {
        return __('Time');
    }

    public function icon(): string
    {
        return 'clock';
    }

    public function storage(): string
    {
        return self::STORE_TEXT;
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return TimePicker::make($context->name($field->getKey()));
    }

    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function settings(): array
    {
        return [];
    }

    public function settingsRules(): array
    {
        return [];
    }
}
