<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\TextInput;

class PhoneType extends InputType
{
    public static function key(): string
    {
        return 'phone';
    }

    public function name(): string
    {
        return __('Phone number');
    }

    public function icon(): string
    {
        return 'phone';
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return TextInput::make($context->name($field->getKey()))->tel()->maxLength(255);
    }
}
