<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Stencil\Content;
use Javaabu\FormBuilder\Support\Text;
use Javaabu\Stencil\Components\Component;

/**
 * A heading between fields. It takes no answer.
 */
class HeadingType extends InputType
{
    public static function key(): string
    {
        return 'heading';
    }

    public function name(): string
    {
        return __('Heading');
    }

    public function icon(): string
    {
        return 'heading';
    }

    public function group(): string
    {
        return 'layout';
    }

    public function takesInput(): bool
    {
        return false;
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return Content::heading($this->text($field, $context))->key($context->path($field->getKey()));
    }

    protected function text(Field $field, CompileContext $context): ?string
    {
        return Text::resolve($field->getSetting('content'), $context->locale)
            ?? Text::resolve($field->getLabel(), $context->locale);
    }
}
