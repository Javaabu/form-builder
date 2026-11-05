<?php

namespace Javaabu\FormBuilder\Definitions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\FormBuilderManager;

/**
 * Checks a definition (as an array: the JSON format, the builder's data, what
 * the AI assistant writes) before it's stored: keys, types, settings, options
 * and conditions. Errors are keyed by path: sections.0.fields.2.key.
 */
class DefinitionValidator
{
    /** Keys a field can't have: they clash with request fields */
    public const array RESERVED_KEYS = ['_token', '_method', '_stencil', '_stencil_changed', '_stencil_old', '_stencil_driver'];

    public const string FORM_KEY_PATTERN = '/^[a-z0-9][a-z0-9_-]*$/';

    public const string KEY_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public function __construct(
        protected FormBuilderManager $builder,
    ) {}

    /**
     * The errors, empty when the definition is valid
     *
     * @param  array<string, mixed>  $data
     */
    public function errors(array $data, bool $require_key = true): MessageBag
    {
        $validator = Validator::make($data, $this->rules($require_key), [], [
            'key' => __('key'),
            'name' => __('name'),
        ]);

        $errors = $validator->errors();

        if ($errors->isNotEmpty()) {
            return $errors;
        }

        $this->checkStructure($data, $errors);

        return $errors;
    }

    /**
     * Validate, or throw a ValidationException with the errors
     *
     * @param  array<string, mixed>  $data
     */
    public function validate(array $data, bool $require_key = true): FormDefinition
    {
        $errors = $this->errors($data, $require_key);

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->toArray());
        }

        return FormDefinition::fromArray($data);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(bool $require_key): array
    {
        $text = ['nullable', function (string $attribute, mixed $value, $fail) {
            if (! static::isText($value)) {
                $fail(__('The :attribute must be text, or text for each language.'));
            }
        }];

        return [
            'key' => [$require_key ? 'required' : 'nullable', 'string', 'max:100', 'regex:' . self::FORM_KEY_PATTERN],
            'name' => $text,
            'description' => $text,
            'layout' => ['nullable', 'in:' . FormDefinition::LAYOUT_SECTIONS . ',' . FormDefinition::LAYOUT_WIZARD],
            'settings' => ['nullable', 'array'],
            'settings.submit_label' => $text,
            'settings.confirmation' => $text,
            'sections' => ['present', 'array'],
            'sections.*' => ['array'],
            'sections.*.key' => ['required', 'string', 'max:64', 'regex:' . self::KEY_PATTERN, 'distinct'],
            'sections.*.title' => $text,
            'sections.*.description' => $text,
            'sections.*.columns' => ['nullable', 'integer', 'min:1', 'max:4'],
            'sections.*.conditions' => ['nullable', 'array'],
            'sections.*.fields' => ['nullable', 'array'],
            'sections.*.fields.*' => ['array'],
            'sections.*.fields.*.key' => ['required', 'string', 'max:64', 'regex:' . self::KEY_PATTERN],
            'sections.*.fields.*.type' => ['required', 'string'],
            'sections.*.fields.*.label' => $text,
            'sections.*.fields.*.help' => $text,
            'sections.*.fields.*.placeholder' => $text,
            'sections.*.fields.*.required' => ['nullable', 'boolean'],
            'sections.*.fields.*.span' => ['nullable', 'in:1,2,3,4,full'],
            'sections.*.fields.*.settings' => ['nullable', 'array'],
            'sections.*.fields.*.conditions' => ['nullable', 'array'],
            'sections.*.fields.*.fields' => ['nullable', 'array'],
        ];
    }

    /**
     * Text: a string, or a map of locale => string
     */
    public static function isText(mixed $value): bool
    {
        if ($value === null || is_string($value)) {
            return true;
        }

        if (! is_array($value) || array_is_list($value)) {
            return false;
        }

        foreach ($value as $locale => $text) {
            if (! is_string($locale) || ! preg_match('/^[a-z]{2,3}([_-][A-Za-z0-9]+)?$/', $locale)) {
                return false;
            }

            if ($text !== null && ! is_string($text)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function checkStructure(array $data, MessageBag $errors): void
    {
        $sections = (array) ($data['sections'] ?? []);

        /** @var array<string, array{type: string, path: string}> $top */
        $top = [];

        foreach ($sections as $s => $section) {
            foreach ((array) ($section['fields'] ?? []) as $f => $field) {
                $key = (string) $field['key'];

                if (isset($top[$key])) {
                    $errors->add("sections.{$s}.fields.{$f}.key", __('Another field already has the key :key.', ['key' => $key]));

                    continue;
                }

                $top[$key] = ['type' => (string) $field['type'], 'path' => "sections.{$s}.fields.{$f}", 'field' => $field];
            }
        }

        foreach ($sections as $s => $section) {
            $this->checkConditions((array) ($section['conditions'] ?? []), "sections.{$s}.conditions", null, $top, [], $errors);

            foreach ((array) ($section['fields'] ?? []) as $f => $field) {
                $this->checkField((array) $field, "sections.{$s}.fields.{$f}", $top, [], $errors, false);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, array<string, mixed>>  $top
     * @param  array<string, array<string, mixed>>  $siblings
     */
    protected function checkField(array $field, string $path, array $top, array $siblings, MessageBag $errors, bool $in_group): void
    {
        $key = (string) $field['key'];
        $type_key = (string) $field['type'];

        if (in_array($key, self::RESERVED_KEYS, true)) {
            $errors->add("{$path}.key", __('The key :key is reserved.', ['key' => $key]));
        }

        if (! $this->builder->hasInputType($type_key)) {
            $errors->add("{$path}.type", __('There\'s no field type :type.', ['type' => $type_key]));

            return;
        }

        $type = $this->builder->inputType($type_key);
        $settings = (array) ($field['settings'] ?? []);

        $rules = $type->settingsRules();

        if ($type->hasOptions() && empty($settings['options_url'])) {
            $rules = [...$rules, ...$type->optionRules()];
        }

        if ($rules !== []) {
            $validator = Validator::make($settings, $rules);

            foreach ($validator->errors()->messages() as $setting => $messages) {
                foreach ($messages as $message) {
                    $errors->add("{$path}.settings.{$setting}", $message);
                }
            }
        }

        foreach ((array) ($settings['options'] ?? []) as $o => $option) {
            if (is_array($option) && ! static::isText($option['label'] ?? null)) {
                $errors->add("{$path}.settings.options.{$o}.label", __('The :attribute must be text, or text for each language.', ['attribute' => __('label')]));
            }
        }

        $children = (array) ($field['fields'] ?? []);

        if ($children !== [] && ! $type->hasFields()) {
            $errors->add("{$path}.fields", __('Only repeating groups hold fields.'));
        }

        if ($type->hasFields() && $in_group) {
            $errors->add("{$path}.type", __('A repeating group can\'t be inside another one.'));
        }

        $parent = $settings['parent'] ?? null;

        if (is_string($parent) && $parent !== '') {
            $watched = $siblings[$parent] ?? $top[$parent] ?? null;

            if (! $watched || $parent === $key || ! $this->builder->hasInputType((string) $watched['type']) || ! $this->builder->inputType((string) $watched['type'])->hasOptions()) {
                $errors->add("{$path}.settings.parent", __('A select can only depend on another field with options.'));
            }
        }

        $this->checkConditions((array) ($field['conditions'] ?? []), "{$path}.conditions", $key, $top, $siblings, $errors);

        if (! $type->hasFields()) {
            return;
        }

        $group = [];

        foreach ($children as $c => $child) {
            $child_key = (string) ($child['key'] ?? '');

            if (isset($group[$child_key])) {
                $errors->add("{$path}.fields.{$c}.key", __('Another field already has the key :key.', ['key' => $child_key]));

                continue;
            }

            $group[$child_key] = ['type' => (string) ($child['type'] ?? ''), 'field' => $child];
        }

        foreach ($children as $c => $child) {
            $child_validator = Validator::make((array) $child, [
                'key' => ['required', 'string', 'max:64', 'regex:' . self::KEY_PATTERN],
                'type' => ['required', 'string'],
            ]);

            if ($child_validator->fails()) {
                foreach ($child_validator->errors()->messages() as $attribute => $messages) {
                    $errors->add("{$path}.fields.{$c}.{$attribute}", $messages[0]);
                }

                continue;
            }

            $this->checkField((array) $child, "{$path}.fields.{$c}", $top, $group, $errors, true);
        }
    }

    /**
     * @param  array<int, mixed>  $conditions
     * @param  array<string, array<string, mixed>>  $top
     * @param  array<string, array<string, mixed>>  $siblings
     */
    protected function checkConditions(array $conditions, string $path, ?string $own_key, array $top, array $siblings, MessageBag $errors): void
    {
        foreach ($conditions as $c => $condition) {
            if (! is_array($condition) || ! is_string($condition['field'] ?? null)) {
                $errors->add("{$path}.{$c}.field", __('Choose the field the condition watches.'));

                continue;
            }

            $operator = (string) ($condition['operator'] ?? Condition::IN);

            if (! in_array($operator, Condition::OPERATORS, true)) {
                $errors->add("{$path}.{$c}.operator", __('Choose how the condition compares.'));
            }

            $field = $condition['field'];
            $watched = $siblings[$field] ?? $top[$field] ?? null;

            if (! $watched || $field === $own_key) {
                $errors->add("{$path}.{$c}.field", __('There\'s no field :key to watch.', ['key' => $field]));

                continue;
            }

            $type = (string) $watched['type'];

            if (! $this->builder->hasInputType($type) || ! $this->builder->inputType($type)->canBeWatched()) {
                $errors->add("{$path}.{$c}.field", __('Conditions can\'t watch the field :key.', ['key' => $field]));

                continue;
            }

            if (! in_array($operator, [Condition::CHECKED, Condition::NOT_CHECKED], true) && empty($condition['values'])) {
                $errors->add("{$path}.{$c}.values", __('Give at least one value.'));
            }
        }
    }
}
