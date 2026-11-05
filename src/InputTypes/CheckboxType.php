<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Checkbox;

/**
 * One checkbox: "I agree to the terms"
 */
class CheckboxType extends InputType
{
    public static function key(): string
    {
        return 'checkbox';
    }

    public function name(): string
    {
        return __('Checkbox');
    }

    public function icon(): string
    {
        return 'square-check';
    }

    public function group(): string
    {
        return 'choice';
    }

    public function watchesAsCheckbox(): bool
    {
        return true;
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return Checkbox::make($context->name($field->getKey()));
    }

    public function normalize(mixed $value, Field $field): mixed
    {
        return (bool) $value;
    }

    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        return $value ? __('Yes') : __('No');
    }
}
