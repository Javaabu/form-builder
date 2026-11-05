<?php

namespace Javaabu\FormBuilder\Builder;

use Javaabu\FormBuilder\Definitions\Condition;
use Javaabu\FormBuilder\Definitions\DefinitionValidator;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\FormBuilderManager;
use Javaabu\FormBuilder\InputTypes\InputType;
use Javaabu\FormBuilder\Models\Form as FormModel;
use Javaabu\FormBuilder\Stencil\Repeater;
use Javaabu\FormBuilder\Support\Text;
use Javaabu\FormBuilder\Support\Translations;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Components\Group;
use Javaabu\Stencil\Components\Section as StencilSection;
use Javaabu\Stencil\Components\TextEntry;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\Field as StencilField;
use Javaabu\Stencil\Fields\Radio;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Translatable\Contracts\Translatable;

/**
 * The stencil forms of the builder pages: a form's settings, a section and a
 * field. Each comes with the model to bind (from the definition) and turns its
 * validated data back into definitions.
 */
class BuilderForms
{
    public function __construct(
        protected FormBuilderManager $builder,
    ) {}

    /**
     * The locales text is edited in: the active ones, and any the definition already has
     *
     * @return array<int, string>
     */
    public function locales(?FormDefinition $definition = null): array
    {
        $locales = Translations::locales();

        if ($definition) {
            array_push($locales, ...$this->localesIn($definition->toArray()));
        }

        return array_values(array_unique($locales));
    }

    /**
     * @param  array<mixed>  $data
     * @return array<int, string>
     */
    protected function localesIn(array $data): array
    {
        $found = [];

        foreach ($data as $value) {
            if (! is_array($value)) {
                continue;
            }

            if (Translations::isLocaleMap($value) && ! array_filter($value, 'is_array')) {
                array_push($found, ...array_keys($value));

                continue;
            }

            array_push($found, ...$this->localesIn($value));
        }

        return $found;
    }

    /**
     * An input for text in every locale: one input, or a group with one per locale
     *
     * @param  array<int, string>  $locales
     */
    public function localized(string $name, string $label, array $locales, string $kind = 'text', bool $required = false): Component
    {
        $make = function (string $input_name, string $input_label, ?string $locale, bool $is_required) use ($kind): StencilField {
            $input = $kind === 'textarea'
                ? Textarea::make($input_name)->rows(3)
                : TextInput::make($input_name)->maxLength(255);

            $input->label($input_label)->required($is_required);

            if ($locale !== null && Translations::isRtl($locale)) {
                $input->thaana($locale === 'dv')->extraAttributes(['dir' => 'rtl', 'lang' => $locale]);
            }

            return $input;
        };

        if (count($locales) <= 1) {
            return $make($name, $label, $locales[0] ?? null, $required);
        }

        $inputs = [];

        foreach ($locales as $index => $locale) {
            $inputs[] = $make($locale, "{$label} (" . Translations::localeName($locale) . ')', $locale, $required && $index === 0);
        }

        return Group::make($inputs)->statePath($name)->columns(min(count($inputs), 2))->columnSpanFull();
    }

    /**
     * Text for a localized input's model: a map when there are several locales
     *
     * @param  string|array<string, string>|null  $text
     * @param  array<int, string>  $locales
     * @return string|array<string, string|null>|null
     */
    public function localizedValue(string|array|null $text, array $locales): string|array|null
    {
        if (count($locales) <= 1) {
            return Text::resolve($text, $locales[0] ?? null);
        }

        return Text::locales($text, $locales);
    }

    /*
    |--------------------------------------------------------------------------
    | Form settings
    |--------------------------------------------------------------------------
    */

    public function settingsForm(?FormModel $form = null, ?FormDefinition $definition = null): Form
    {
        $definition ??= $form?->definition(draft: true);

        // The name and description are the model's: other languages need javaabu/translatable
        $model_locales = Translations::locales();
        $locales = $this->locales($definition);

        $stencil = Form::make('form-builder-settings')
            ->schema([
                StencilSection::make(__('Form'))->columns(2)->schema([
                    TextInput::make('key')
                        ->label(__('Key'))
                        ->required()
                        ->maxLength(100)
                        ->rules(['regex:' . DefinitionValidator::FORM_KEY_PATTERN])
                        ->rules([function (string $attribute, mixed $value, $fail) use ($form) {
                            $exists = $this->builder->model('form')::query()
                                ->withTrashed()
                                ->where('key', $value)
                                ->when($form, fn ($query) => $query->whereKeyNot($form->getKey()))
                                ->exists();

                            if ($exists) {
                                $fail(__('Another form already has this key.'));
                            }
                        }])
                        ->help(__('Lowercase letters, numbers, dashes and underscores. It\'s in the form\'s web address.')),
                    Radio::make('layout')
                        ->label(__('Layout'))
                        ->required()
                        ->default(FormDefinition::LAYOUT_SECTIONS)
                        ->options([
                            FormDefinition::LAYOUT_SECTIONS => __('Every section on one page'),
                            FormDefinition::LAYOUT_WIZARD => __('One section at a time (wizard)'),
                        ]),
                    $this->localized('name', __('Name'), $model_locales, required: true),
                    $this->localized('description', __('Description'), $model_locales, 'textarea'),
                    $this->localized('submit_label', __('Submit button label'), $locales),
                    $this->localized('confirmation', __('Message after submitting'), $locales, 'textarea'),
                    Checkbox::make('accepts_entries')->label(__('Accepting entries'))->default(true),
                ]),
            ]);

        if ($form) {
            $stencil->model($this->settingsModel($form, $definition, $locales, $model_locales));
        }

        return $stencil;
    }

    /**
     * @param  array<int, string>  $locales
     * @param  array<int, string>  $model_locales
     * @return array<string, mixed>
     */
    protected function settingsModel(FormModel $form, ?FormDefinition $definition, array $locales, array $model_locales): array
    {
        return [
            'key' => $form->key,
            'layout' => $definition?->getLayout() ?? FormDefinition::LAYOUT_SECTIONS,
            'name' => $this->localizedValue($this->modelText($form, 'name', $model_locales), $model_locales),
            'description' => $this->localizedValue($this->modelText($form, 'description', $model_locales), $model_locales),
            'submit_label' => $this->localizedValue($definition?->getSetting('submit_label'), $locales),
            'confirmation' => $this->localizedValue($definition?->getSetting('confirmation'), $locales),
            'accepts_entries' => $form->accepts_entries,
        ];
    }

    /**
     * A model attribute in every locale: its translations with javaabu/translatable
     *
     * @param  array<int, string>  $locales
     * @return string|array<string, string>|null
     */
    protected function modelText(FormModel $form, string $attribute, array $locales): string|array|null
    {
        $value = $form->getAttribute($attribute);

        if (count($locales) <= 1 || ! $form instanceof Translatable || ! $form->isTranslatable($attribute)) {
            return $value;
        }

        $values = [];

        foreach ($locales as $locale) {
            $values[$locale] = $form->translate($attribute, $locale, false);
        }

        return array_filter($values, fn ($text) => $text !== null && $text !== '');
    }

    /*
    |--------------------------------------------------------------------------
    | Sections
    |--------------------------------------------------------------------------
    */

    public function sectionForm(FormDefinition $definition, ?Section $section = null): Form
    {
        $locales = $this->locales($definition);

        $stencil = Form::make('form-builder-section')
            ->schema([
                StencilSection::make(__('Section'))->columns(2)->schema([
                    TextInput::make('key')
                        ->label(__('Key'))
                        ->required()
                        ->maxLength(64)
                        ->rules(['regex:' . DefinitionValidator::KEY_PATTERN])
                        ->help(__('Lowercase letters, numbers and underscores, starting with a letter.')),
                    Select::make('columns')->label(__('Columns'))->options([
                        '1' => __('One column'),
                        '2' => __('Two columns'),
                        '3' => __('Three columns'),
                        '4' => __('Four columns'),
                    ])->default('1'),
                    $this->localized('title', __('Title'), $locales),
                    $this->localized('description', __('Description'), $locales, 'textarea'),
                ]),
                $this->conditionsSection($definition, $this->watchableFields($definition), __('Show this section only when…')),
            ]);

        $stencil->model($section ? [
            'key' => $section->getKey(),
            'columns' => (string) ($section->getColumns() ?? 1),
            'title' => $this->localizedValue($section->getTitle(), $locales),
            'description' => $this->localizedValue($section->getDescription(), $locales),
            'conditions' => $this->conditionsModel($section->getConditions()),
        ] : ['key' => $this->editor()->uniqueSectionKey($definition, 'section'), 'columns' => '1', 'conditions' => []]);

        return $stencil;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sectionFrom(array $data): Section
    {
        $columns = (int) ($data['columns'] ?? 1);

        return Section::make((string) $data['key'])
            ->title(Text::compact($data['title'] ?? null))
            ->description(Text::compact($data['description'] ?? null))
            ->columns($columns > 1 ? $columns : null)
            ->conditions($this->conditionsFrom((array) ($data['conditions'] ?? [])));
    }

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    /**
     * @param  string|null  $group  the repeating group the field is in
     */
    public function fieldForm(FormDefinition $definition, InputType $type, ?Field $field = null, ?string $section = null, ?string $group = null): Form
    {
        $locales = $this->locales($definition);

        $basics = [
            TextInput::make('key')
                ->label(__('Key'))
                ->required()
                ->maxLength(64)
                ->rules(['regex:' . DefinitionValidator::KEY_PATTERN])
                ->help(__('Lowercase letters, numbers and underscores, starting with a letter. Answers are stored under it.')),
            TextEntry::make('type_name')->label(__('Type'))->value($type->name()),
            $this->localized('label', __('Label'), $locales, required: $type->takesInput()),
        ];

        if ($type->takesInput()) {
            $basics[] = $this->localized('help', __('Help text'), $locales, 'textarea');
            $basics[] = $this->localized('placeholder', __('Placeholder'), $locales);
            $basics[] = Checkbox::make('required')->label(__('Required'));
        }

        $basics[] = Select::make('span')->label(__('Width'))->options([
            '' => __('One column'),
            '2' => __('Two columns'),
            'full' => __('Full width'),
        ]);

        if ($group === null && $section !== null && count($definition->getSections()) > 1) {
            $basics[] = Select::make('section')->label(__('Section'))->required()->options(
                collect($definition->getSections())->mapWithKeys(fn (Section $item) => [
                    $item->getKey() => Text::resolve($item->getTitle()) ?? $item->getKey(),
                ])->all(),
            );
        }

        $components = [StencilSection::make(__('Field'))->columns(2)->schema($basics)];

        $settings = [
            ...$type->settings(),
            ...array_map(
                fn (string $kind, string $key) => $this->localized($key, $this->settingLabel($key), $locales, $kind),
                $type->localizedSettings(),
                array_keys($type->localizedSettings()),
            ),
        ];

        if ($settings !== []) {
            $components[] = StencilSection::make(__('Settings'))->columns(2)->schema([
                Group::make($settings)->statePath('settings')->columns(2)->columnSpanFull(),
            ]);
        }

        if ($type->hasOptions()) {
            $components[] = StencilSection::make(__('Options'))->schema([
                Repeater::make('options')
                    ->label(__('Options'))
                    ->hiddenLabel()
                    ->addLabel(__('Add an option'))
                    ->rowLabel(__('Option :number'))
                    ->columns(3)
                    ->schema(fn () => [
                        TextInput::make('value')->label(__('Value'))->required()->maxLength(255),
                        $this->localized('label', __('Label'), $locales, required: true)->columnSpan(1),
                        TextInput::make('parent')->label(__('Parent value'))->maxLength(255)->help(__('For a select that depends on another field.')),
                    ]),
            ]);
        }

        if ($type->takesInput() || $type->hasFields()) {
            $watchable = $this->watchableFields($definition, $group, $field?->getKey());

            $components[] = $this->conditionsSection($definition, $watchable, __('Show this field only when…'));
        }

        $stencil = Form::make('form-builder-field')->schema($components);

        $stencil->model($this->fieldModel($definition, $type, $field, $section, $group, $locales));

        return $stencil;
    }

    protected function settingLabel(string $key): string
    {
        return match ($key) {
            'content' => __('Text'),
            'add_label' => __('Add button label'),
            'row_label' => __('Row heading'),
            default => str($key)->headline()->toString(),
        };
    }

    /**
     * @param  array<int, string>  $locales
     * @return array<string, mixed>
     */
    protected function fieldModel(FormDefinition $definition, InputType $type, ?Field $field, ?string $section, ?string $group, array $locales): array
    {
        if (! $field) {
            return [
                'key' => $this->editor()->uniqueFieldKey($definition, $type::key(), $group),
                'section' => $section,
                'span' => '',
                'settings' => $type->defaults(),
                'options' => $type->hasOptions() ? [['value' => '', 'label' => null]] : [],
                'conditions' => [],
            ];
        }

        $settings = $field->getSettings();

        foreach (array_keys($type->localizedSettings()) as $key) {
            $settings[$key] = $this->localizedValue($settings[$key] ?? null, $locales);
        }

        unset($settings['options']);

        return [
            'key' => $field->getKey(),
            'section' => $section,
            'label' => $this->localizedValue($field->getLabel(), $locales),
            'help' => $this->localizedValue($field->getHelp(), $locales),
            'placeholder' => $this->localizedValue($field->getPlaceholder(), $locales),
            'required' => $field->isRequired(),
            'span' => $field->getSpan() === null ? '' : (string) $field->getSpan(),
            'settings' => $settings,
            'options' => array_map(fn (array $option) => [
                'value' => $option['value'],
                'label' => $this->localizedValue($option['label'] ?? null, $locales),
                'parent' => $option['parent'] ?? null,
            ], $field->getOptions()),
            'conditions' => $this->conditionsModel($field->getConditions()),
        ];
    }

    /**
     * A field from the field form's validated data, keeping settings the form doesn't show
     *
     * @param  array<string, mixed>  $data
     */
    public function fieldFrom(InputType $type, array $data, ?Field $current = null): Field
    {
        $span = $data['span'] ?? null;

        $settings = [...($current?->getSettings() ?? $type->defaults()), ...(array) ($data['settings'] ?? [])];

        foreach (array_keys($type->localizedSettings()) as $key) {
            $settings[$key] = Text::compact($settings[$key] ?? null);
        }

        foreach ($settings as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                unset($settings[$key]);
            }
        }

        $field = Field::make($type::key(), (string) $data['key'])
            ->label(Text::compact($data['label'] ?? null))
            ->help(Text::compact($data['help'] ?? null))
            ->placeholder(Text::compact($data['placeholder'] ?? null))
            ->required((bool) ($data['required'] ?? false))
            ->span($span === null || $span === '' ? null : (is_numeric($span) ? (int) $span : (string) $span))
            ->settings($settings)
            ->conditions($this->conditionsFrom((array) ($data['conditions'] ?? [])));

        if ($type->hasOptions()) {
            $field->setting('options', null);

            $options = array_values(array_filter(array_map(fn (array $option) => array_filter([
                'value' => (string) ($option['value'] ?? ''),
                'label' => Text::compact($option['label'] ?? null) ?? (string) ($option['value'] ?? ''),
                'parent' => filled($option['parent'] ?? null) ? (string) $option['parent'] : null,
            ], fn ($value) => $value !== null), (array) ($data['options'] ?? [])), fn (array $option) => $option['value'] !== ''));

            if ($options !== []) {
                $field->options($options);
            }
        }

        return $field;
    }

    /*
    |--------------------------------------------------------------------------
    | Conditions
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, string>  $watchable
     */
    protected function conditionsSection(FormDefinition $definition, array $watchable, string $description): Component
    {
        return StencilSection::make(__('Conditions'))->description($description)->schema([
            Repeater::make('conditions')
                ->label(__('Conditions'))
                ->hiddenLabel()
                ->defaultRows(0)
                ->addLabel(__('Add a condition'))
                ->rowLabel(__('Condition :number'))
                ->columns(3)
                ->schema(fn (string $row) => [
                    Select::make('field')->label(__('Field'))->required()->options($watchable),
                    Select::make('operator')->label(__('Is'))->required()->default(Condition::IN)->options([
                        Condition::IN => __('one of'),
                        Condition::NOT_IN => __('not one of'),
                        Condition::CHECKED => __('checked or filled'),
                        Condition::NOT_CHECKED => __('not checked or empty'),
                    ]),
                    TextInput::make('values')
                        ->label(__('Values'))
                        ->maxLength(1000)
                        ->help(__('Option values, separated by commas.'))
                        ->hiddenWhen("{$row}.operator", [Condition::CHECKED, Condition::NOT_CHECKED]),
                ]),
        ]);
    }

    /**
     * The fields a condition can watch: key => label (fields of the same group first)
     *
     * @return array<string, string>
     */
    public function watchableFields(FormDefinition $definition, ?string $group = null, ?string $except = null): array
    {
        $fields = [];

        $add = function (Field $field) use (&$fields, $except) {
            if ($field->getKey() === $except || ! $this->builder->hasInputType($field->getType())) {
                return;
            }

            if (! $this->builder->inputType($field->getType())->canBeWatched()) {
                return;
            }

            $fields[$field->getKey()] ??= (Text::resolve($field->getLabel()) ?? $field->getKey()) . " ({$field->getKey()})";
        };

        if ($group !== null) {
            foreach ($this->editor()->findField($definition, $group)->getFields() as $sibling) {
                $add($sibling);
            }
        }

        foreach ($definition->getFields() as $field) {
            $add($field);
        }

        return $fields;
    }

    /**
     * @param  array<int, Condition>  $conditions
     * @return array<int, array<string, string>>
     */
    protected function conditionsModel(array $conditions): array
    {
        return array_map(fn (Condition $condition) => [
            'field' => $condition->field,
            'operator' => $condition->operator,
            'values' => implode(', ', $condition->values),
        ], $conditions);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, Condition>
     */
    public function conditionsFrom(array $rows): array
    {
        $conditions = [];

        foreach ($rows as $row) {
            if (! filled($row['field'] ?? null)) {
                continue;
            }

            $operator = (string) ($row['operator'] ?? Condition::IN);
            $values = array_values(array_filter(array_map('trim', explode(',', (string) ($row['values'] ?? ''))), fn ($value) => $value !== ''));

            $conditions[] = new Condition((string) $row['field'], $operator, in_array($operator, [Condition::CHECKED, Condition::NOT_CHECKED], true) ? [] : $values);
        }

        return $conditions;
    }

    protected function editor(): StructureEditor
    {
        return app(StructureEditor::class);
    }
}
