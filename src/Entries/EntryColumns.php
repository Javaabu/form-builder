<?php

namespace Javaabu\FormBuilder\Entries;

use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\FormBuilderManager;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\FormBuilder\Support\Text;

/**
 * The answer columns of a form's entries, across its versions: the fields of
 * the newest version first, then fields only older versions had. A field in a
 * repeating group is a column of its own (group.key), its rows joined.
 */
class EntryColumns
{
    public function __construct(
        protected FormBuilderManager $builder,
    ) {}

    /**
     * @return array<string, string> path => label
     */
    public function for(Form $form, ?FormVersion $only = null): array
    {
        $versions = $only ? collect([$only]) : $form->versions()->reorder()->latest('number')->get();

        $columns = [];

        foreach ($versions as $version) {
            foreach ($version->definition()->getFields() as $field) {
                $this->add($columns, $field);
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, string>  $columns
     */
    protected function add(array &$columns, Field $field, ?Field $group = null): void
    {
        if (! $this->builder->hasInputType($field->getType())) {
            return;
        }

        $type = $this->builder->inputType($field->getType());

        if (! $type->takesInput()) {
            return;
        }

        if ($type->hasFields()) {
            foreach ($field->getFields() as $child) {
                $this->add($columns, $child, $field);
            }

            return;
        }

        $label = Text::resolve($field->getLabel()) ?? str($field->getKey())->headline()->toString();
        $path = $group ? "{$group->getKey()}.{$field->getKey()}" : $field->getKey();

        if ($group) {
            $label = (Text::resolve($group->getLabel()) ?? str($group->getKey())->headline()->toString()) . ': ' . $label;
        }

        $columns[$path] ??= $label;
    }

    /**
     * An entry's answer for a column, as text (or a number or date for exports)
     */
    public function value(FormEntry $entry, string $path, bool $export = false): string|int|float|null
    {
        $definition = $entry->version->definition();
        $field = $definition->getField($path);

        if (! $field || ! $this->builder->hasInputType($field->getType())) {
            return null;
        }

        $type = $this->builder->inputType($field->getType());

        if (! str_contains($path, '.')) {
            $value = $entry->answer($path);

            return $export ? $type->export($value, $field) : $type->format($value, $field);
        }

        [$group, $key] = explode('.', $path, 2);

        $values = [];

        foreach ((array) $entry->answer($group, []) as $row) {
            $value = is_array($row) ? ($row[$key] ?? null) : null;
            $values[] = $type->format($value, $field) ?? '';
        }

        $values = array_filter($values, fn ($value) => $value !== '');

        return $values === [] ? null : implode(' | ', $values);
    }
}
