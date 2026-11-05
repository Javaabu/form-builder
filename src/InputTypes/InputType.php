<?php

namespace Javaabu\FormBuilder\InputTypes;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Support\Text;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Components\TextEntry;
use Javaabu\Stencil\Fields\Field as StencilField;

/**
 * What a field type is: its name and icon in the builder, its settings, the
 * stencil component it fills as, how its answers are stored, shown and exported.
 *
 * Add your own by extending this class and registering it with
 * FormBuilder::inputType(MyType::class), or in config('form-builder.input_types').
 * Change a built-in one with FormBuilder::extendInputType('text', fn ($component, $field) => …),
 * or register a subclass under the same key.
 */
abstract class InputType
{
    /** Where answers go in form_entry_values: value (text), number or date */
    public const string STORE_TEXT = 'value';

    public const string STORE_NUMBER = 'number';

    public const string STORE_DATE = 'date';

    /**
     * The type's key, stored with every field of this type: 'text', 'rating'
     */
    abstract public static function key(): string;

    /**
     * The type's name in the builder, translated
     */
    abstract public function name(): string;

    /**
     * The stencil field people fill. Label, help, placeholder, required, span,
     * default and conditions are applied after this, by the compiler.
     */
    abstract protected function make(Field $field, CompileContext $context): Component;

    /**
     * An icon name for the builder (javaabu/forms' icon names)
     */
    public function icon(): string
    {
        return 'text';
    }

    /**
     * Where the type is listed in the builder: text, choice, date, file, layout
     */
    public function group(): string
    {
        return 'text';
    }

    /**
     * Whether it takes an answer. Headings and paragraphs don't.
     */
    public function takesInput(): bool
    {
        return true;
    }

    /**
     * Whether the builder asks for options (select, radio, checkboxes)
     */
    public function hasOptions(): bool
    {
        return false;
    }

    /**
     * Whether it holds other fields (repeating groups)
     */
    public function hasFields(): bool
    {
        return false;
    }

    /**
     * Whether answers are a list of values (checkboxes, multiple selects, multiple files)
     */
    public function isMultiple(Field $field): bool
    {
        return false;
    }

    /**
     * Whether other fields can show or hide on its value
     */
    public function canBeWatched(): bool
    {
        return $this->takesInput() && ! $this->hasFields();
    }

    /**
     * Whether conditions on it watch a checkbox (checked / not checked) rather than values
     */
    public function watchesAsCheckbox(): bool
    {
        return false;
    }

    /**
     * The builder's settings for the type: stencil components named by setting
     * key (max_length, rows), saved in the field's settings
     *
     * @return array<int, Component>
     */
    public function settings(): array
    {
        return [];
    }

    /**
     * Settings that are text in every language, edited with one input per
     * locale: setting key => 'text' or 'textarea'
     *
     * @return array<string, string>
     */
    public function localizedSettings(): array
    {
        return [];
    }

    /**
     * Rules for the settings the builder saves, keyed by setting
     *
     * @return array<string, array<int, mixed>>
     */
    public function settingsRules(): array
    {
        return [];
    }

    /**
     * Settings a new field of this type starts with
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [];
    }

    /**
     * The column of form_entry_values an answer is also stored in, for queries
     */
    public function storage(): string
    {
        return self::STORE_TEXT;
    }

    /**
     * The stencil component for the field, with the common props applied
     *
     * @internal called by the compiler
     */
    public function toComponent(Field $field, CompileContext $context): Component
    {
        $component = $this->make($field, $context);

        if ($component instanceof StencilField) {
            $this->applyCommon($component, $field, $context);
        }

        return $component;
    }

    protected function applyCommon(StencilField $component, Field $field, CompileContext $context): void
    {
        $locale = $context->locale;

        $component
            ->label(Text::resolve($field->getLabel(), $locale) ?? str($field->getKey())->headline()->toString())
            ->help(Text::resolve($field->getHelp(), $locale))
            ->placeholder(Text::resolve($field->getPlaceholder(), $locale))
            ->required($field->isRequired());

        $default = $field->getSetting('default');

        if ($default !== null && $default !== '') {
            $component->default($default);
        }
    }

    /**
     * The answer as text, for read-only entries, lists and exports
     */
    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_array($value)) {
            $parts = array_filter(array_map(fn ($item) => $this->format($item, $field, $locale), $value), fn ($item) => $item !== null);

            return $parts === [] ? null : implode(', ', $parts);
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        return (string) $value;
    }

    /**
     * The read-only component for an answer
     */
    public function display(Field $field, mixed $value, CompileContext $context): ?Component
    {
        if (! $this->takesInput()) {
            return $this->make($field, $context);
        }

        return TextEntry::make($context->path($field->getKey()))
            ->label(Text::resolve($field->getLabel(), $context->locale) ?? str($field->getKey())->headline()->toString())
            ->value($this->format($value, $field, $context->locale) ?? '—')
            ->multiline($this->displaysMultiline($field));
    }

    protected function displaysMultiline(Field $field): bool
    {
        return false;
    }

    /**
     * The answer in an export: text, a number or a date
     */
    public function export(mixed $value, Field $field, ?string $locale = null): string|int|float|null
    {
        return $this->format($value, $field, $locale);
    }

    /**
     * The answer as it's kept in the entry's data: plain values
     */
    public function normalize(mixed $value, Field $field): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value === '' ? null : $value;
    }

    /**
     * The values to index in form_entry_values for one answer: one per item for lists
     *
     * @return array<int, array{value: string|null, number: int|float|null, date: Carbon|null}>
     */
    public function index(mixed $value, Field $field): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $items = is_array($value) ? array_values($value) : [$value];

        return array_map(fn (mixed $item) => [
            'value' => is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE),
            'number' => $this->storage() === self::STORE_NUMBER && is_numeric($item) ? $item + 0 : null,
            'date' => $this->storage() === self::STORE_DATE && is_string($item) && strtotime($item) !== false ? Carbon::parse($item) : null,
        ], $items);
    }

    /**
     * The type as the builder lists it
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => static::key(),
            'name' => $this->name(),
            'icon' => $this->icon(),
            'group' => $this->group(),
            'takes_input' => $this->takesInput(),
            'has_options' => $this->hasOptions(),
            'has_fields' => $this->hasFields(),
            'watchable' => $this->canBeWatched(),
            'checkbox' => $this->watchesAsCheckbox(),
        ];
    }
}
