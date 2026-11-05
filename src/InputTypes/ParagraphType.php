<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Stencil\Content;
use Javaabu\Stencil\Components\Component;

/**
 * A paragraph of text between fields: instructions, a notice. It takes no answer.
 */
class ParagraphType extends HeadingType
{
    public static function key(): string
    {
        return 'paragraph';
    }

    public function name(): string
    {
        return __('Paragraph');
    }

    public function icon(): string
    {
        return 'pilcrow';
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return Content::paragraph($this->text($field, $context))->key($context->path($field->getKey()));
    }
}
