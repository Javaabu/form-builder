<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\InputTypes\Concerns\HasOptions;
use Javaabu\FormBuilder\Routing\FormBuilderRoutes;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Get;

/**
 * A dropdown of options. With `parent`, a cascading select: its options are
 * those under the parent field's value, loaded again when the parent changes.
 */
class SelectType extends InputType
{
    use HasOptions;

    public static function key(): string
    {
        return 'select';
    }

    public function name(): string
    {
        return __('Dropdown');
    }

    public function icon(): string
    {
        return 'chevron-down';
    }

    public function isMultiple(Field $field): bool
    {
        return (bool) $field->getSetting('multiple');
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        $locale = $context->locale;
        $select = Select::make($context->name($field->getKey()))
            ->multiple($this->isMultiple($field))
            ->searchable((bool) $field->getSetting('searchable'));

        $parent = $field->getSetting('parent');

        if (! is_string($parent) || $parent === '') {
            return $select->options($this->optionLabels($field, $locale));
        }

        $parent_path = $context->watchedPath($parent);
        $external = $field->getSetting('options_url');
        $url = is_string($external) && $external !== '' ? $external : $this->optionsUrl($field, $context);

        $select->searchable();

        if (is_string($external) && $external !== '') {
            $select->allowAnyOption()->options([]);
        } else {
            $select->options(function (Get $get) use ($field, $locale, $parent_path) {
                $value = $get($parent_path);

                if ($value === null || $value === '' || is_array($value)) {
                    return [];
                }

                return $this->optionLabels($field, $locale, (string) $value);
            });
        }

        if ($url === null) {
            return $select;
        }

        return $select->extraAttributes([
            'parent' => '#' . str_replace('.', '-', $parent_path),
            'ajaxUrl' => $url,
            'filterField' => $field->getSetting('filter_field') ?: 'parent',
        ]);
    }

    /**
     * The package's options endpoint for a cascading select, when its route is registered
     */
    protected function optionsUrl(Field $field, CompileContext $context): ?string
    {
        $route = FormBuilderRoutes::optionsRoute(request());
        $form = $context->definition->getKey();

        if ($form === '' || $route === null) {
            return null;
        }

        $path = $context->group ? "{$context->group->getKey()}.{$field->getKey()}" : $field->getKey();

        return route($route, array_filter([
            'form' => $form,
            'field' => $path,
            'version' => $context->version?->getKey(),
        ]), false);
    }

    public function settings(): array
    {
        return [
            Checkbox::make('multiple')->label(__('Allow choosing several options')),
            Checkbox::make('searchable')->label(__('Searchable')),
            TextInput::make('parent')->label(__('Depends on (field key)'))->help(__('Show only the options whose parent is this field\'s value.')),
            TextInput::make('options_url')->label(__('Options URL'))->url()->help(__('Load the options from this URL, filtered by the parent\'s value, instead of the options below.')),
        ];
    }

    public function settingsRules(): array
    {
        return [
            'multiple' => ['nullable', 'boolean'],
            'searchable' => ['nullable', 'boolean'],
            'parent' => ['nullable', 'string', 'max:64'],
            'options_url' => ['nullable', 'string', 'max:2048'],
            'filter_field' => ['nullable', 'string', 'max:64'],
        ];
    }
}
