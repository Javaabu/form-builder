<?php

namespace Javaabu\FormBuilder\Stencil;

use Closure;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Components\Group;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Renderers\JsonRenderer;
use Javaabu\Stencil\Rendering\Node;

/**
 * A group of fields filled any number of times. Its state is a list of rows,
 * each row the values of its fields:
 *
 * ```php
 * Repeater::make('references')
 *     ->schema(fn (string $row) => [
 *         TextInput::make('name')->required(),
 *         TextInput::make('phone')->tel(),
 *     ])
 *     ->minRows(1)
 *     ->maxRows(3);
 * ```
 *
 * The row's fields are nested under `references.{row}`: names `references[0][name]`,
 * validation `references.0.name`. The schema closure gets the row's state path
 * (`references.0`) for conditions on siblings: `visibleWhen("{$row}.type", 'other')`.
 * An array schema is copied for every row.
 *
 * It renders the rows, a template row (with `__ROW__` for the row) and add and
 * remove buttons: the form-builder::stencil.repeater view with the Blade script,
 * or the FormBuilderRepeater React component. The fields of rows aren't in the
 * form's getFields(); the repeater validates and dehydrates them itself.
 */
class Repeater extends Field
{
    public const string ROW = '__ROW__';

    protected string $type = 'repeater';

    /** @var array<int, Component|null>|Closure */
    protected array|Closure $row_schema = [];

    protected int|Closure|null $min_rows = null;

    protected int|Closure|null $max_rows = null;

    protected int|Closure|null $default_rows = null;

    protected string|Closure|null $add_label = null;

    protected string|Closure|null $row_label = null;

    protected ?int $row_columns = null;

    /**
     * The fields of a row: an array, copied for each row, or a closure that gets
     * the row's state path and returns them
     *
     * @param  array<int, Component|null>|Closure  $components
     */
    public function schema(array|Closure $components): static
    {
        $this->row_schema = $components;

        return $this;
    }

    public function minRows(int|Closure|null $rows): static
    {
        $this->min_rows = $rows;

        return $this;
    }

    public function maxRows(int|Closure|null $rows): static
    {
        $this->max_rows = $rows;

        return $this;
    }

    /**
     * Rows shown before anything is filled. Defaults to the minimum, or one.
     */
    public function defaultRows(int|Closure|null $rows): static
    {
        $this->default_rows = $rows;

        return $this;
    }

    public function addLabel(string|Closure|null $label): static
    {
        $this->add_label = $label;

        return $this;
    }

    /**
     * Each row's heading, with :number for its position: __('Reference :number')
     */
    public function rowLabel(string|Closure|null $label): static
    {
        $this->row_label = $label;

        return $this;
    }

    /**
     * Lay each row's fields out in columns
     *
     * @param  int|array<string, int>|Closure|null  $columns
     */
    public function columns(int|array|Closure|null $columns = 2): static
    {
        $this->row_columns = is_int($columns) ? $columns : null;

        return parent::columns($columns);
    }

    public function getMinRows(): ?int
    {
        $rows = $this->evaluate($this->min_rows);

        return $rows === null ? null : max(0, (int) $rows);
    }

    public function getMaxRows(): ?int
    {
        $rows = $this->evaluate($this->max_rows);

        return $rows === null ? null : max(1, (int) $rows);
    }

    public function getDefaultRows(): int
    {
        $rows = $this->evaluate($this->default_rows);

        if ($rows !== null) {
            return max(0, (int) $rows);
        }

        return max(1, $this->getMinRows() ?? 0);
    }

    public function getAddLabel(): string
    {
        return (string) ($this->evaluate($this->add_label) ?? __('Add'));
    }

    public function getRowLabel(int $number): string
    {
        return str_replace(':number', (string) $number, $this->getRowLabelTemplate());
    }

    /**
     * The row heading with :number left in, for the browser to fill
     */
    public function getRowLabelTemplate(): string
    {
        $label = (string) ($this->evaluate($this->row_label) ?? __(':label :number'));

        return str_replace(':label', $this->getLabel(), $label);
    }

    /*
    |--------------------------------------------------------------------------
    | Rows
    |--------------------------------------------------------------------------
    */

    /**
     * The fields of rows are nested under the repeater's path
     */
    public function getChildStatePath(): string
    {
        return $this->getStatePath();
    }

    /**
     * The keys of the rows in the state, in order
     *
     * @return array<int, int|string>
     */
    public function getRowKeys(): array
    {
        $state = $this->hasForm() ? $this->getState() : null;

        if (! is_array($state)) {
            return [];
        }

        return array_keys(array_filter($state, fn ($row) => is_array($row) || $row === null));
    }

    /**
     * One group per row, with the row's fields, made from the schema
     *
     * @return array<int, Component>
     */
    public function getChildComponents(): array
    {
        return array_map(fn (int|string $key) => $this->row($key), $this->getRowKeys());
    }

    /**
     * The group of fields for one row
     */
    public function row(int|string $key): Group
    {
        $row = Group::make($this->rowSchema((string) $key))
            ->statePath((string) $key)
            ->key($this->getId() . '-' . $key)
            ->parent($this);

        if ($this->row_columns && $this->row_columns > 1) {
            $row->columns($this->row_columns);
        }

        foreach ($row->getChildComponents() as $component) {
            $component->parent($row);
        }

        return $row;
    }

    /**
     * The fields of one row, at any depth
     *
     * @return array<int, Field>
     */
    public function getRowFields(int|string $key): array
    {
        return $this->row($key)->getFlatFields();
    }

    /**
     * The form doesn't see the fields of rows: their number depends on the state
     *
     * @return array<int, Field>
     */
    public function getFlatFields(): array
    {
        return [];
    }

    /**
     * @return array<int, Component>
     */
    protected function rowSchema(string $key): array
    {
        $path = $this->getStatePath() . '.' . $key;

        if ($this->row_schema instanceof Closure) {
            return array_values(array_filter((array) $this->evaluate($this->row_schema, ['row' => $path, 'key' => $key])));
        }

        return array_values(array_map(fn (Component $component) => static::copy($component), array_filter($this->row_schema)));
    }

    /**
     * A deep copy of a component, so every row has its own
     */
    protected static function copy(Component $component): Component
    {
        $copy = clone $component;
        $copy->parent = null;
        $copy->resolved_children = null;

        if (is_array($copy->child_components)) {
            $copy->child_components = array_map(
                fn (?Component $child) => $child ? static::copy($child) : null,
                $copy->child_components,
            );
        }

        return $copy;
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    public function hydrateState(mixed $record): mixed
    {
        $state = parent::hydrateState($record);

        if (is_array($state)) {
            return $state;
        }

        return array_fill(0, $this->getDefaultRows(), []);
    }

    public function fillStateFrom(array &$state, array $input): void
    {
        $value = data_get($input, $this->getStatePath());

        $rows = is_array($value)
            ? array_filter($value, fn ($row) => is_array($row))
            : [];

        data_set($state, $this->getStatePath(), $this->normalizeState($rows));
    }

    public function dehydrateInto(array &$data, array $state): void
    {
        $rows = [];

        foreach ($this->getRowKeys() as $key) {
            $row_data = [];

            foreach ($this->getRowFields($key) as $field) {
                if (! $field->isDehydrated()) {
                    continue;
                }

                $field->dehydrateInto($row_data, $state);
            }

            $rows[] = (array) (data_get($row_data, $this->getStatePath() . '.' . $key) ?? []);
        }

        data_set($data, $this->getStatePath(), $this->dehydrateState($rows));
    }

    protected function castDehydratedState(mixed $state): mixed
    {
        return array_values((array) ($state ?? []));
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    protected function getTypeRules(): array
    {
        $min = $this->getMinRows();
        $max = $this->getMaxRows();

        return array_values(array_filter([
            'array',
            $min ? "min:{$min}" : null,
            $max ? "max:{$max}" : null,
        ]));
    }

    public function getAllValidationRules(): array
    {
        $rules = parent::getAllValidationRules();

        foreach ($this->getRowKeys() as $key) {
            foreach ($this->getRowFields($key) as $field) {
                if ($field->isHidden() || $field->isDisabled()) {
                    continue;
                }

                $rules = array_merge($rules, $field->getAllValidationRules());
            }
        }

        return $rules;
    }

    public function getValidationAttributes(): array
    {
        $attributes = parent::getValidationAttributes();

        foreach ($this->getRowKeys() as $position => $key) {
            $row = $this->getRowLabel($position + 1);

            foreach ($this->getRowFields($key) as $field) {
                foreach ($field->getValidationAttributes() as $path => $attribute) {
                    $attributes[$path] = "{$attribute} ({$row})";
                }
            }
        }

        return $attributes;
    }

    public function getValidationMessages(): array
    {
        $messages = parent::getValidationMessages();

        foreach ($this->getRowKeys() as $key) {
            foreach ($this->getRowFields($key) as $field) {
                $messages = array_merge($messages, $field->getValidationMessages());
            }
        }

        return $messages;
    }

    /**
     * A field of a row by its full path: references.0.name
     */
    public function findRowField(string $path): ?Field
    {
        $prefix = $this->getStatePath() . '.';

        if (! str_starts_with($path, $prefix)) {
            return null;
        }

        $key = explode('.', substr($path, strlen($prefix)), 2)[0];

        foreach ($this->getRowFields($key) as $field) {
            if ($field->getStatePath() === $path) {
                return $field;
            }

            if ($field instanceof self && ($found = $field->findRowField($path))) {
                return $found;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    /**
     * The row new rows are copied from, with __ROW__ for the row's key
     */
    public function templateNode(): Node
    {
        return $this->row(self::ROW)->toNode();
    }

    protected function getFieldProps(): array
    {
        $keys = $this->getRowKeys();
        $numeric = array_filter($keys, fn ($key) => is_int($key) || ctype_digit((string) $key));

        return [
            'minRows' => $this->getMinRows(),
            'maxRows' => $this->getMaxRows(),
            'addLabel' => $this->getAddLabel(),
            'removeLabel' => __('Remove'),
            'rowLabel' => $this->getRowLabelTemplate(),
            'rowLabels' => array_map(fn (int $position) => $this->getRowLabel($position + 1), array_keys($keys)),
            'nextRow' => $numeric === [] ? 0 : max(array_map('intval', $numeric)) + 1,
            'rowPlaceholder' => self::ROW,
        ];
    }

    public function toNode(): Node
    {
        $node = parent::toNode();

        if ($node->hidden) {
            return $node;
        }

        $node->props['template'] = app(JsonRenderer::class)->node($this->templateNode());

        return $node;
    }
}
