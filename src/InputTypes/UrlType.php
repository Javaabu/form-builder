<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\TextInput;

class UrlType extends InputType
{
    public static function key(): string
    {
        return 'url';
    }

    public function name(): string
    {
        return __('Link');
    }

    public function icon(): string
    {
        return 'link';
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return TextInput::make($context->name($field->getKey()))->url()->maxLength(255);
    }
}
