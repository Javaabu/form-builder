<?php

namespace Javaabu\FormBuilder\Tests\TestSupport\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\InputTypes\InputType;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\TextInput;

/**
 * An app's own input type: a rating from 1 to max
 */
class RatingType extends InputType
{
    public static function key(): string
    {
        return 'rating';
    }

    public function name(): string
    {
        return __('Rating');
    }

    public function icon(): string
    {
        return 'star';
    }

    public function storage(): string
    {
        return self::STORE_NUMBER;
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return TextInput::make($context->name($field->getKey()))
            ->integer()
            ->minValue(1)
            ->maxValue((int) $field->getSetting('max', 5))
            ->meta(['stars' => (int) $field->getSetting('max', 5)]);
    }

    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        return $value === null ? null : str_repeat('★', (int) $value);
    }

    public function settings(): array
    {
        return [TextInput::make('max')->label('Stars')->integer()->minValue(2)->maxValue(10)];
    }

    public function settingsRules(): array
    {
        return ['max' => ['nullable', 'integer', 'min:2', 'max:10']];
    }
}
