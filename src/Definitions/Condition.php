<?php

namespace Javaabu\FormBuilder\Definitions;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * Show a section or field only when another field has (or hasn't) one of some
 * values, or is (or isn't) checked. Several conditions must all hold.
 *
 * ```php
 * Condition::in('applicant_type', ['company']);
 * Condition::notIn('island', ['male']);
 * Condition::checked('has_references');
 * ```
 *
 * @implements Arrayable<string, mixed>
 */
class Condition implements Arrayable
{
    public const string IN = 'in';

    public const string NOT_IN = 'not_in';

    public const string CHECKED = 'checked';

    public const string NOT_CHECKED = 'not_checked';

    /** @var array<int, string> */
    public const array OPERATORS = [self::IN, self::NOT_IN, self::CHECKED, self::NOT_CHECKED];

    /**
     * @param  string  $field  the key of the watched field; inside a repeating group, a sibling's key
     * @param  array<int, string>  $values
     */
    final public function __construct(
        public readonly string $field,
        public readonly string $operator = self::IN,
        public readonly array $values = [],
    ) {
        if (! in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException("Unknown condition operator [{$operator}].");
        }
    }

    /**
     * The field has one of the values
     */
    public static function in(string $field, mixed $values): static
    {
        return new static($field, self::IN, static::normalizeValues($values));
    }

    /**
     * The field has none of the values
     */
    public static function notIn(string $field, mixed $values): static
    {
        return new static($field, self::NOT_IN, static::normalizeValues($values));
    }

    public static function checked(string $field): static
    {
        return new static($field, self::CHECKED);
    }

    public static function notChecked(string $field): static
    {
        return new static($field, self::NOT_CHECKED);
    }

    /**
     * @param  array{field: string, operator?: string, values?: mixed}  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            (string) $data['field'],
            (string) ($data['operator'] ?? self::IN),
            static::normalizeValues($data['values'] ?? []),
        );
    }

    public function watchesCheckbox(): bool
    {
        return in_array($this->operator, [self::CHECKED, self::NOT_CHECKED], true);
    }

    /**
     * @return array{field: string, operator: string, values?: array<int, string>}
     */
    public function toArray(): array
    {
        $data = ['field' => $this->field, 'operator' => $this->operator];

        if (! $this->watchesCheckbox()) {
            $data['values'] = $this->values;
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    protected static function normalizeValues(mixed $values): array
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return array_values(array_map(function (mixed $value) {
            if ($value instanceof BackedEnum) {
                return (string) $value->value;
            }

            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            return (string) $value;
        }, is_array($values) ? $values : [$values]));
    }
}
