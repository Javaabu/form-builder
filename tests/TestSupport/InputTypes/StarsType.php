<?php

namespace Javaabu\FormBuilder\Tests\TestSupport\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Tests\TestSupport\Stencil\StarsField;
use Javaabu\Stencil\Components\Component;

/**
 * An input type with a stencil field of its own (StarsField): a Blade view and
 * a React component (tests/js/custom-input.test.tsx)
 */
class StarsType extends RatingType
{
    public static function key(): string
    {
        return 'stars';
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return StarsField::make($context->name($field->getKey()))->max((int) $field->getSetting('max', 5));
    }
}
