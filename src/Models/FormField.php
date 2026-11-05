<?php

namespace Javaabu\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Javaabu\FormBuilder\Casts\LocalizedText;
use Javaabu\FormBuilder\Definitions\Condition;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\Concerns\GuardsPublishedVersion;
use Javaabu\FormBuilder\Support\Text;

/**
 * A field of a form version. Fields of a repeating group have a parent.
 *
 * @property int $id
 * @property int $form_version_id
 * @property int $form_section_id
 * @property int|null $parent_id
 * @property string $key
 * @property string $type
 * @property string|array<string, string>|null $label
 * @property string|array<string, string>|null $help
 * @property string|array<string, string>|null $placeholder
 * @property bool $is_required
 * @property string|null $span
 * @property array<string, mixed>|null $settings
 * @property array<int, array<string, mixed>>|null $conditions
 * @property int $position
 * @property-read FormVersion $version
 * @property-read FormSection $section
 * @property-read FormField|null $parent
 */
class FormField extends Model
{
    use GuardsPublishedVersion;

    protected $fillable = [
        'key',
        'type',
        'label',
        'help',
        'placeholder',
        'is_required',
        'span',
        'settings',
        'conditions',
        'position',
        'form_section_id',
        'parent_id',
    ];

    protected $attributes = [
        'is_required' => false,
    ];

    protected function casts(): array
    {
        return [
            'label' => LocalizedText::class,
            'help' => LocalizedText::class,
            'placeholder' => LocalizedText::class,
            'is_required' => 'boolean',
            'settings' => 'array',
            'conditions' => 'array',
            'position' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return config('form-builder.tables.form_fields', 'form_fields');
    }

    /**
     * @return BelongsTo<FormVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('version'), 'form_version_id');
    }

    /**
     * @return BelongsTo<FormSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('section'), 'form_section_id');
    }

    /**
     * The repeating group this field is in
     *
     * @return BelongsTo<FormField, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * The fields of a repeating group
     *
     * @return HasMany<FormField, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('position');
    }

    /**
     * The label in the current locale
     */
    public function labelText(?string $locale = null): ?string
    {
        return Text::resolve($this->label, $locale);
    }

    /**
     * The path of the field's answers: its key, or group.key inside a repeating group
     */
    public function path(): string
    {
        return $this->parent ? "{$this->parent->key}.{$this->key}" : $this->key;
    }

    /**
     * @param  array<int, FormField>  $children
     */
    public function toDefinition(array $children = []): Field
    {
        $span = $this->span;

        return Field::make($this->type, $this->key)
            ->id($this->getKey())
            ->label($this->label)
            ->help($this->help)
            ->placeholder($this->placeholder)
            ->required($this->is_required)
            ->span(is_numeric($span) ? (int) $span : $span)
            ->settings($this->settings ?? [])
            ->conditions(array_map(fn (array $condition) => Condition::fromArray($condition), $this->conditions ?? []))
            ->fields(array_map(fn (FormField $child) => $child->toDefinition(), $children));
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributesFrom(Field $field): array
    {
        $span = $field->getSpan();

        return [
            'key' => $field->getKey(),
            'type' => $field->getType(),
            'label' => $field->getLabel(),
            'help' => $field->getHelp(),
            'placeholder' => $field->getPlaceholder(),
            'is_required' => $field->isRequired(),
            'span' => $span === null ? null : (string) $span,
            'settings' => $field->getSettings() ?: null,
            'conditions' => array_map(fn (Condition $condition) => $condition->toArray(), $field->getConditions()) ?: null,
        ];
    }
}
