<?php

namespace Javaabu\FormBuilder\Definitions;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

/**
 * A field of a form definition: an input, a display element (heading,
 * paragraph) or a repeating group of fields. Built fluently in seeders, read
 * from a stored version, or imported from JSON; the input type turns it into a
 * stencil component.
 *
 * Text (label, help, placeholder, option labels) is a string, or a map of
 * locale => string when it's translated: ['en' => 'Name', 'dv' => 'ނަން'].
 *
 * ```php
 * Field::text('full_name')->label(__('Full name'))->required()->thaana();
 * Field::select('atoll')->options(['K' => 'Kaafu', 'S' => 'Addu'])->required();
 * Field::repeater('references')->fields([Field::text('name'), Field::phone('phone')])->maxRows(3);
 * ```
 *
 * @implements Arrayable<string, mixed>
 */
class Field implements Arrayable
{
    use Conditionable;
    use Macroable;
    use Tappable;

    /** @var string|array<string, string>|null */
    protected string|array|null $label = null;

    /** @var string|array<string, string>|null */
    protected string|array|null $help = null;

    /** @var string|array<string, string>|null */
    protected string|array|null $placeholder = null;

    protected bool $is_required = false;

    /** @var array<string, mixed> */
    protected array $settings = [];

    /** @var array<int, Condition> */
    protected array $conditions = [];

    protected int|string|null $span = null;

    /** @var array<int, Field> */
    protected array $fields = [];

    /** The stored field's id, when it was read from the database */
    protected ?int $id = null;

    final public function __construct(
        protected string $type,
        protected string $key,
    ) {}

    public static function make(string $type, string $key): static
    {
        return new static($type, $key);
    }

    /*
    |--------------------------------------------------------------------------
    | Built-in types
    |--------------------------------------------------------------------------
    */

    public static function text(string $key): static
    {
        return static::make('text', $key);
    }

    public static function textarea(string $key): static
    {
        return static::make('textarea', $key);
    }

    public static function email(string $key): static
    {
        return static::make('email', $key);
    }

    public static function number(string $key): static
    {
        return static::make('number', $key);
    }

    public static function phone(string $key): static
    {
        return static::make('phone', $key);
    }

    public static function url(string $key): static
    {
        return static::make('url', $key);
    }

    public static function select(string $key): static
    {
        return static::make('select', $key);
    }

    public static function radio(string $key): static
    {
        return static::make('radio', $key);
    }

    public static function checkboxes(string $key): static
    {
        return static::make('checkboxes', $key);
    }

    public static function checkbox(string $key): static
    {
        return static::make('checkbox', $key);
    }

    public static function date(string $key): static
    {
        return static::make('date', $key);
    }

    public static function time(string $key): static
    {
        return static::make('time', $key);
    }

    public static function datetime(string $key): static
    {
        return static::make('datetime', $key);
    }

    public static function file(string $key): static
    {
        return static::make('file', $key);
    }

    /**
     * A heading between fields. It takes no input.
     */
    public static function heading(string $key): static
    {
        return static::make('heading', $key);
    }

    /**
     * A paragraph of text between fields. It takes no input.
     */
    public static function paragraph(string $key): static
    {
        return static::make('paragraph', $key);
    }

    /**
     * A group of fields filled any number of times: references, dependants, past jobs.
     */
    public static function repeater(string $key): static
    {
        return static::make('repeater', $key);
    }

    /*
    |--------------------------------------------------------------------------
    | Common
    |--------------------------------------------------------------------------
    */

    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function id(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param  string|array<string, string>|null  $label
     */
    public function label(string|array|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string|array<string, string>|null
     */
    public function getLabel(): string|array|null
    {
        return $this->label;
    }

    /**
     * Help text under the field
     *
     * @param  string|array<string, string>|null  $help
     */
    public function help(string|array|null $help): static
    {
        $this->help = $help;

        return $this;
    }

    /**
     * @return string|array<string, string>|null
     */
    public function getHelp(): string|array|null
    {
        return $this->help;
    }

    /**
     * @param  string|array<string, string>|null  $placeholder
     */
    public function placeholder(string|array|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @return string|array<string, string>|null
     */
    public function getPlaceholder(): string|array|null
    {
        return $this->placeholder;
    }

    public function required(bool $condition = true): static
    {
        $this->is_required = $condition;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Columns the field takes in its section's grid: a number, or 'full'
     */
    public function span(int|string|null $span): static
    {
        $this->span = $span;

        return $this;
    }

    public function getSpan(): int|string|null
    {
        return $this->span;
    }

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    /**
     * A setting of the input type: setting('max_length', 100)
     */
    public function setting(string $key, mixed $value): static
    {
        if ($value === null) {
            unset($this->settings[$key]);

            return $this;
        }

        $this->settings[$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function settings(array $settings): static
    {
        foreach ($settings as $key => $value) {
            $this->setting($key, $value);
        }

        return $this;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * The value the field starts with
     */
    public function default(mixed $value): static
    {
        return $this->setting('default', $value);
    }

    /**
     * Take Dhivehi typed with a Latin keyboard (text and textarea)
     */
    public function thaana(bool $condition = true): static
    {
        return $this->setting('thaana', $condition ?: null);
    }

    public function minLength(?int $length): static
    {
        return $this->setting('min_length', $length);
    }

    public function maxLength(?int $length): static
    {
        return $this->setting('max_length', $length);
    }

    /**
     * The smallest number, or the earliest date (Y-m-d)
     */
    public function min(int|float|string|null $value): static
    {
        return $this->setting('min', $value);
    }

    /**
     * The largest number, or the latest date (Y-m-d)
     */
    public function max(int|float|string|null $value): static
    {
        return $this->setting('max', $value);
    }

    /**
     * Whole numbers only (number)
     */
    public function integer(bool $condition = true): static
    {
        return $this->setting('integer', $condition ?: null);
    }

    /**
     * Rows of a textarea
     */
    public function rows(?int $rows): static
    {
        return $this->setting('rows', $rows);
    }

    /**
     * Choose several options (select), or upload several files (file)
     */
    public function multiple(bool $condition = true): static
    {
        return $this->setting('multiple', $condition ?: null);
    }

    /**
     * Search the options (select)
     */
    public function searchable(bool $condition = true): static
    {
        return $this->setting('searchable', $condition ?: null);
    }

    /**
     * The options of a select, radio or checkboxes field: value => label, or a
     * list of ['value' => …, 'label' => …, 'parent' => …] (parent for cascading selects).
     *
     * @param  array<int|string, mixed>  $options
     */
    public function options(array $options): static
    {
        return $this->setting('options', static::normalizeOptions($options));
    }

    /**
     * @return array<int, array{value: string, label: string|array<string, string>, parent?: string}>
     */
    public function getOptions(): array
    {
        return (array) $this->getSetting('options', []);
    }

    /**
     * Make this select depend on another one: its options are those whose
     * `parent` is the other field's value, or come from `url` (any endpoint that
     * filters with `filter[{filter_field}]`, such as an API of islands by atoll).
     */
    public function dependsOn(string $field, ?string $url = null, ?string $filter_field = null): static
    {
        return $this->settings([
            'parent' => $field,
            'options_url' => $url,
            'filter_field' => $filter_field,
        ]);
    }

    /**
     * Accepted file types (file): mime types or extensions
     *
     * @param  array<int, string>|string|null  $types
     */
    public function accept(array|string|null $types): static
    {
        return $this->setting('accept', $types === null ? null : array_values((array) $types));
    }

    /**
     * The largest file in kilobytes (file)
     */
    public function maxSize(?int $kilobytes): static
    {
        return $this->setting('max_size', $kilobytes);
    }

    /**
     * What a heading or paragraph says
     *
     * @param  string|array<string, string>|null  $content
     */
    public function content(string|array|null $content): static
    {
        return $this->setting('content', $content);
    }

    /*
    |--------------------------------------------------------------------------
    | Repeating groups
    |--------------------------------------------------------------------------
    */

    /**
     * The fields of a repeating group
     *
     * @param  array<int, Field|null>  $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = array_values(array_filter($fields));

        return $this;
    }

    /**
     * @return array<int, Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function minRows(?int $rows): static
    {
        return $this->setting('min_rows', $rows);
    }

    public function maxRows(?int $rows): static
    {
        return $this->setting('max_rows', $rows);
    }

    /**
     * The add button of a repeating group
     *
     * @param  string|array<string, string>|null  $label
     */
    public function addLabel(string|array|null $label): static
    {
        return $this->setting('add_label', $label);
    }

    /*
    |--------------------------------------------------------------------------
    | Conditions
    |--------------------------------------------------------------------------
    */

    public function condition(Condition $condition): static
    {
        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * @param  array<int, Condition>  $conditions
     */
    public function conditions(array $conditions): static
    {
        $this->conditions = array_values($conditions);

        return $this;
    }

    /**
     * Show the field only while another field has one of the values
     */
    public function visibleWhen(string $field, mixed $values): static
    {
        return $this->condition(Condition::in($field, $values));
    }

    /**
     * Hide the field while another field has one of the values
     */
    public function hiddenWhen(string $field, mixed $values): static
    {
        return $this->condition(Condition::notIn($field, $values));
    }

    public function visibleWhenChecked(string $field): static
    {
        return $this->condition(Condition::checked($field));
    }

    public function hiddenWhenChecked(string $field): static
    {
        return $this->condition(Condition::notChecked($field));
    }

    /**
     * @return array<int, Condition>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /*
    |--------------------------------------------------------------------------
    | Arrays
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        $field = static::make((string) $data['type'], (string) $data['key'])
            ->label($data['label'] ?? null)
            ->help($data['help'] ?? null)
            ->placeholder($data['placeholder'] ?? null)
            ->required((bool) ($data['required'] ?? false))
            ->span($data['span'] ?? null)
            ->settings((array) ($data['settings'] ?? []))
            ->conditions(array_map(fn (array $condition) => Condition::fromArray($condition), (array) ($data['conditions'] ?? [])))
            ->fields(array_map(fn (array $child) => static::fromArray($child), (array) ($data['fields'] ?? [])));

        if (isset($field->settings['options'])) {
            $field->options((array) $field->settings['options']);
        }

        return $field;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'help' => $this->help,
            'placeholder' => $this->placeholder,
            'required' => $this->is_required ?: null,
            'span' => $this->span,
            'settings' => $this->settings ?: null,
            'conditions' => $this->conditions ? array_map(fn (Condition $condition) => $condition->toArray(), $this->conditions) : null,
            'fields' => $this->fields ? array_map(fn (Field $field) => $field->toArray(), $this->fields) : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  array<int|string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $value => $label) {
            if (is_array($label) && array_key_exists('value', $label)) {
                $normalized[] = array_filter([
                    'value' => static::stringValue($label['value']),
                    'label' => $label['label'] ?? static::stringValue($label['value']),
                    'parent' => isset($label['parent']) ? static::stringValue($label['parent']) : null,
                ], fn ($item) => $item !== null);

                continue;
            }

            if (is_int($value) && array_is_list($options) && ! is_array($label)) {
                $normalized[] = ['value' => static::stringValue($label), 'label' => static::stringValue($label)];

                continue;
            }

            $normalized[] = ['value' => (string) $value, 'label' => $label];
        }

        return $normalized;
    }

    protected static function stringValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
