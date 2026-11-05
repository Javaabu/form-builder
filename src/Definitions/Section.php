<?php

namespace Javaabu\FormBuilder\Definitions;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

/**
 * A section of a form: a card with a title, or a step of a wizard form.
 *
 * ```php
 * Section::make('personal')->title(__('Personal details'))->columns(2)->fields([
 *     Field::text('full_name')->required()->thaana(),
 *     Field::date('date_of_birth'),
 * ]);
 * ```
 *
 * @implements Arrayable<string, mixed>
 */
class Section implements Arrayable
{
    use Conditionable;
    use Macroable;
    use Tappable;

    /** @var string|array<string, string>|null */
    protected string|array|null $title = null;

    /** @var string|array<string, string>|null */
    protected string|array|null $description = null;

    protected ?int $columns = null;

    /** @var array<int, Field> */
    protected array $fields = [];

    /** @var array<int, Condition> */
    protected array $conditions = [];

    protected ?int $id = null;

    final public function __construct(
        protected string $key,
    ) {}

    public static function make(string $key): static
    {
        return new static($key);
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
     * @param  string|array<string, string>|null  $title
     */
    public function title(string|array|null $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|array<string, string>|null
     */
    public function getTitle(): string|array|null
    {
        return $this->title;
    }

    /**
     * @param  string|array<string, string>|null  $description
     */
    public function description(string|array|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|array<string, string>|null
     */
    public function getDescription(): string|array|null
    {
        return $this->description;
    }

    /**
     * Lay the fields out in columns from the md breakpoint
     */
    public function columns(?int $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function getColumns(): ?int
    {
        return $this->columns;
    }

    /**
     * @param  array<int, Field|null>  $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = array_values(array_filter($fields));

        return $this;
    }

    public function field(Field $field): static
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return array<int, Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

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

    public function visibleWhen(string $field, mixed $values): static
    {
        return $this->condition(Condition::in($field, $values));
    }

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

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return static::make((string) $data['key'])
            ->title($data['title'] ?? null)
            ->description($data['description'] ?? null)
            ->columns(isset($data['columns']) ? (int) $data['columns'] : null)
            ->conditions(array_map(fn (array $condition) => Condition::fromArray($condition), (array) ($data['conditions'] ?? [])))
            ->fields(array_map(fn (array $field) => Field::fromArray($field), (array) ($data['fields'] ?? [])));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'columns' => $this->columns,
            'conditions' => $this->conditions ? array_map(fn (Condition $condition) => $condition->toArray(), $this->conditions) : null,
            'fields' => array_map(fn (Field $field) => $field->toArray(), $this->fields),
        ], fn ($value) => $value !== null);
    }
}
