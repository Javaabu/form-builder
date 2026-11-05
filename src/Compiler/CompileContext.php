<?php

namespace Javaabu\FormBuilder\Compiler;

use Javaabu\FormBuilder\Definitions\Condition;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Models\FormVersion;

/**
 * What the compiler knows while it turns a definition into stencil components:
 * the mode, the locale, the answers (for read-only entries) and, inside a
 * repeating group, the row the fields are in.
 */
class CompileContext
{
    /** A form people fill */
    public const string FILL = 'fill';

    /** A read-only entry */
    public const string DISPLAY = 'display';

    /**
     * @param  array<string, mixed>  $answers  the entry's answers, for display
     * @param  string  $prefix  the state path fields are nested under: references.0
     * @param  array<int, Field>  $siblings  the fields of the repeating group, inside one
     */
    public function __construct(
        public readonly FormDefinition $definition,
        public readonly string $mode = self::FILL,
        public readonly ?string $locale = null,
        public readonly array $answers = [],
        public readonly ?FormVersion $version = null,
        public readonly string $prefix = '',
        public readonly array $siblings = [],
        public readonly ?Field $group = null,
    ) {}

    public function isDisplay(): bool
    {
        return $this->mode === self::DISPLAY;
    }

    /**
     * The context for the fields of one row of a repeating group
     */
    public function forRow(Field $group, string $path): static
    {
        return new static(
            definition: $this->definition,
            mode: $this->mode,
            locale: $this->locale,
            answers: $this->answers,
            version: $this->version,
            prefix: $path,
            siblings: $group->getFields(),
            group: $group,
        );
    }

    /**
     * A field's state path: its key, under the row inside a repeating group
     */
    public function path(string $key): string
    {
        return $this->prefix === '' ? $key : "{$this->prefix}.{$key}";
    }

    /**
     * The name a field's component is made with. Inside a repeating group the
     * repeater nests its rows' fields under the row, so it's the key alone.
     */
    public function name(string $key): string
    {
        return $this->isDisplay() ? $this->path($key) : $key;
    }

    /**
     * The state path of the field a condition watches: a sibling in the same
     * row, or a field outside the group
     */
    public function watchedPath(string $key): string
    {
        foreach ($this->siblings as $sibling) {
            if ($sibling->getKey() === $key) {
                return $this->path($key);
            }
        }

        return $key;
    }

    /**
     * The definition of the field a condition watches
     */
    public function watchedField(string $key): ?Field
    {
        foreach ($this->siblings as $sibling) {
            if ($sibling->getKey() === $key) {
                return $sibling;
            }
        }

        return $this->definition->getField($key);
    }

    /**
     * An answer by state path
     */
    public function answer(string $path): mixed
    {
        return data_get($this->answers, $path);
    }

    /**
     * Whether the conditions hold for the answers (read-only entries)
     *
     * @param  array<int, Condition>  $conditions
     */
    public function passes(array $conditions): bool
    {
        foreach ($conditions as $condition) {
            if (! static::matches($condition, $this->answer($this->watchedPath($condition->field)))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a value meets a condition
     */
    public static function matches(Condition $condition, mixed $value): bool
    {
        if ($condition->watchesCheckbox()) {
            $checked = static::isChecked($value);

            return $condition->operator === Condition::CHECKED ? $checked : ! $checked;
        }

        $values = array_map(fn ($item) => is_bool($item) ? ($item ? '1' : '0') : (string) $item, is_array($value) ? $value : [$value]);
        $found = array_intersect($values, $condition->values) !== [];

        return $condition->operator === Condition::IN ? $found : ! $found;
    }

    protected static function isChecked(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        if (is_string($value)) {
            return ! in_array($value, ['', '0', 'false', 'off'], true);
        }

        return (bool) $value;
    }
}
