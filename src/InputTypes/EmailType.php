<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\TextInput;

class EmailType extends InputType
{
    public static function key(): string
    {
        return 'email';
    }

    public function name(): string
    {
        return __('Email');
    }

    public function icon(): string
    {
        return 'mail';
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return TextInput::make($context->name($field->getKey()))->email()->maxLength(255);
    }
}
