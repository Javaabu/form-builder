<?php

namespace Javaabu\FormBuilder\InputTypes\Concerns;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Support\Text;

/**
 * For types people choose from options: select, radio, checkboxes
 */
trait HasOptions
{
    public function hasOptions(): bool
    {
        return true;
    }

    public function group(): string
    {
        return 'choice';
    }

    /**
     * The options as value => label in the locale, optionally only those under a parent value
     *
     * @return array<string, string>
     */
    public function optionLabels(Field $field, ?string $locale = null, ?string $parent = null): array
    {
        $labels = [];

        foreach ($field->getOptions() as $option) {
            if ($parent !== null && (string) ($option['parent'] ?? '') !== $parent) {
                continue;
            }

            $value = (string) $option['value'];
            $labels[$value] = Text::resolve($option['label'] ?? null, $locale) ?? $value;
        }

        return $labels;
    }

    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $labels = $this->optionLabels($field, $locale);
        $values = is_array($value) ? $value : [$value];

        return implode(', ', array_map(fn ($item) => $labels[(string) $item] ?? (string) $item, $values));
    }

    /**
     * Options for the builder's settings and the JSON format
     *
     * @return array<string, array<int, mixed>>
     */
    public function optionRules(): array
    {
        return [
            'options' => ['required', 'array', 'min:1'],
            'options.*.value' => ['required', 'string', 'max:255', 'distinct'],
            'options.*.label' => ['required'],
            'options.*.parent' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function contextLocale(CompileContext $context): ?string
    {
        return $context->locale;
    }
}
