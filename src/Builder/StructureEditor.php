<?php

namespace Javaabu\FormBuilder\Builder;

use Javaabu\FormBuilder\Definitions\Condition;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The builder's changes, as changes to a definition: add, replace, remove and
 * move sections and fields, found by key (fields in a repeating group by
 * group.key). Keys stay the same across versions, so builder URLs do too.
 */
class StructureEditor
{
    /*
    |--------------------------------------------------------------------------
    | Sections
    |--------------------------------------------------------------------------
    */

    public function findSection(FormDefinition $definition, string $key): Section
    {
        return $definition->getSection($key) ?? throw new NotFoundHttpException;
    }

    public function sectionIndex(FormDefinition $definition, string $key): int
    {
        foreach ($definition->getSections() as $index => $section) {
            if ($section->getKey() === $key) {
                return $index;
            }
        }

        throw new NotFoundHttpException;
    }

    public function addSection(FormDefinition $definition, Section $section, ?int $position = null): FormDefinition
    {
        $sections = $definition->getSections();

        array_splice($sections, $this->clamp($position, count($sections)), 0, [$section]);

        return $definition->sections($sections);
    }

    /**
     * Replace a section's own settings, keeping its fields
     */
    public function updateSection(FormDefinition $definition, string $key, Section $section): FormDefinition
    {
        $index = $this->sectionIndex($definition, $key);
        $sections = $definition->getSections();

        $section->fields($sections[$index]->getFields());
        $sections[$index] = $section;

        $definition->sections($sections);

        if ($section->getKey() !== $key) {
            $this->renameSectionReferences($definition, $key, $section->getKey());
        }

        return $definition;
    }

    public function removeSection(FormDefinition $definition, string $key): FormDefinition
    {
        $index = $this->sectionIndex($definition, $key);
        $sections = $definition->getSections();

        array_splice($sections, $index, 1);

        return $definition->sections($sections);
    }

    public function moveSection(FormDefinition $definition, string $key, int $position): FormDefinition
    {
        $index = $this->sectionIndex($definition, $key);
        $sections = $definition->getSections();
        $section = $sections[$index];

        array_splice($sections, $index, 1);
        array_splice($sections, $this->clamp($position, count($sections)), 0, [$section]);

        return $definition->sections($sections);
    }

    /**
     * Put sections in the order of the keys; sections left out keep their order after them
     *
     * @param  array<int, string>  $keys
     */
    public function orderSections(FormDefinition $definition, array $keys): FormDefinition
    {
        return $definition->sections($this->ordered($definition->getSections(), $keys, fn (Section $section) => $section->getKey()));
    }

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    /**
     * Where a field is: its section, its repeating group (or null) and itself
     *
     * @return array{0: Section, 1: Field|null, 2: Field}
     */
    public function locate(FormDefinition $definition, string $path): array
    {
        [$key, $child] = array_pad(explode('.', $path, 2), 2, null);

        foreach ($definition->getSections() as $section) {
            foreach ($section->getFields() as $field) {
                if ($field->getKey() !== $key) {
                    continue;
                }

                if ($child === null) {
                    return [$section, null, $field];
                }

                foreach ($field->getFields() as $nested) {
                    if ($nested->getKey() === $child) {
                        return [$section, $field, $nested];
                    }
                }
            }
        }

        throw new NotFoundHttpException;
    }

    public function findField(FormDefinition $definition, string $path): Field
    {
        return $this->locate($definition, $path)[2];
    }

    /**
     * Add a field to a section, or to a repeating group in it
     */
    public function addField(FormDefinition $definition, string $section_key, Field $field, ?string $group = null, ?int $position = null): FormDefinition
    {
        $section = $this->findSection($definition, $section_key);

        if ($group !== null) {
            [$group_section, , $parent] = $this->locate($definition, $group);

            $fields = $parent->getFields();
            array_splice($fields, $this->clamp($position, count($fields)), 0, [$field]);
            $parent->fields($fields);

            return $definition;
        }

        $fields = $section->getFields();
        array_splice($fields, $this->clamp($position, count($fields)), 0, [$field]);
        $section->fields($fields);

        return $definition;
    }

    /**
     * Replace a field, keeping the fields of a repeating group
     */
    public function updateField(FormDefinition $definition, string $path, Field $field): FormDefinition
    {
        [$section, $group, $current] = $this->locate($definition, $path);

        if ($field->getFields() === []) {
            $field->fields($current->getFields());
        }

        $container = $group ?? $section;
        $fields = array_map(fn (Field $item) => $item === $current ? $field : $item, $container->getFields());
        $container->fields($fields);

        if ($field->getKey() !== $current->getKey()) {
            $this->renameFieldReferences($definition, $current->getKey(), $field->getKey(), $group);
        }

        return $definition;
    }

    public function removeField(FormDefinition $definition, string $path): FormDefinition
    {
        [$section, $group, $current] = $this->locate($definition, $path);

        $container = $group ?? $section;
        $container->fields(array_values(array_filter($container->getFields(), fn (Field $item) => $item !== $current)));

        return $definition;
    }

    /**
     * Move a field to a position in a section (fields in a repeating group move within it)
     */
    public function moveField(FormDefinition $definition, string $path, ?string $section_key, int $position): FormDefinition
    {
        [$section, $group, $current] = $this->locate($definition, $path);

        if ($group !== null) {
            $fields = array_values(array_filter($group->getFields(), fn (Field $item) => $item !== $current));
            array_splice($fields, $this->clamp($position, count($fields)), 0, [$current]);
            $group->fields($fields);

            return $definition;
        }

        $section->fields(array_values(array_filter($section->getFields(), fn (Field $item) => $item !== $current)));

        $target = $section_key === null ? $section : $this->findSection($definition, $section_key);
        $fields = $target->getFields();
        array_splice($fields, $this->clamp($position, count($fields)), 0, [$current]);
        $target->fields($fields);

        return $definition;
    }

    /**
     * Put the fields of a section (or of a repeating group, by its path) in the order of the keys
     *
     * @param  array<int, string>  $keys
     */
    public function orderFields(FormDefinition $definition, string $section_key, array $keys, ?string $group = null): FormDefinition
    {
        $container = $group !== null ? $this->findField($definition, $group) : $this->findSection($definition, $section_key);

        $container->fields($this->ordered($container->getFields(), $keys, fn (Field $field) => $field->getKey()));

        return $definition;
    }

    /**
     * A key for a new field that no other field has: full_name, full_name_2
     */
    public function uniqueFieldKey(FormDefinition $definition, string $base, ?string $group = null): string
    {
        $taken = array_map(
            fn (Field $field) => $field->getKey(),
            $group !== null ? $this->findField($definition, $group)->getFields() : $definition->getFields(),
        );

        $base = $this->slug($base, 'field');
        $key = $base;
        $number = 2;

        while (in_array($key, $taken, true)) {
            $key = "{$base}_{$number}";
            $number++;
        }

        return $key;
    }

    public function uniqueSectionKey(FormDefinition $definition, string $base): string
    {
        $taken = array_map(fn (Section $section) => $section->getKey(), $definition->getSections());

        $base = $this->slug($base, 'section');
        $key = $base;
        $number = 2;

        while (in_array($key, $taken, true)) {
            $key = "{$base}_{$number}";
            $number++;
        }

        return $key;
    }

    protected function slug(string $value, string $fallback): string
    {
        $slug = str($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(48, '')->toString();

        if ($slug === '' || ! preg_match('/^[a-z]/', $slug)) {
            $slug = $fallback . ($slug === '' ? '' : "_{$slug}");
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | References
    |--------------------------------------------------------------------------
    */

    /**
     * Point conditions and cascading selects that watched a field at its new key
     */
    protected function renameFieldReferences(FormDefinition $definition, string $old, string $new, ?Field $group): void
    {
        $rename = function (array $conditions) use ($old, $new): array {
            return array_map(
                fn (Condition $condition) => $condition->field === $old ? new Condition($new, $condition->operator, $condition->values) : $condition,
                $conditions,
            );
        };

        $fix = function (Field $field) use ($rename, $old, $new): void {
            $field->conditions($rename($field->getConditions()));

            if ($field->getSetting('parent') === $old) {
                $field->setting('parent', $new);
            }
        };

        if ($group !== null) {
            foreach ($group->getFields() as $sibling) {
                $fix($sibling);
            }

            return;
        }

        foreach ($definition->getSections() as $section) {
            $section->conditions($rename($section->getConditions()));

            foreach ($section->getFields() as $field) {
                $fix($field);

                foreach ($field->getFields() as $child) {
                    $sibling_keys = array_map(fn (Field $item) => $item->getKey(), $field->getFields());

                    if (! in_array($old, $sibling_keys, true)) {
                        $fix($child);
                    }
                }
            }
        }
    }

    protected function renameSectionReferences(FormDefinition $definition, string $old, string $new): void
    {
        // Nothing refers to sections by key yet.
    }

    /**
     * @template T
     *
     * @param  array<int, T>  $items
     * @param  array<int, string>  $keys
     * @return array<int, T>
     */
    protected function ordered(array $items, array $keys, callable $key_of): array
    {
        $by_key = [];

        foreach ($items as $item) {
            $by_key[$key_of($item)] = $item;
        }

        $ordered = [];

        foreach ($keys as $key) {
            if (isset($by_key[$key])) {
                $ordered[] = $by_key[$key];
                unset($by_key[$key]);
            }
        }

        return [...$ordered, ...array_values($by_key)];
    }

    protected function clamp(?int $position, int $count): int
    {
        if ($position === null) {
            return $count;
        }

        return max(0, min($position, $count));
    }
}
