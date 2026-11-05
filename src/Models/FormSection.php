<?php

namespace Javaabu\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Javaabu\FormBuilder\Casts\LocalizedText;
use Javaabu\FormBuilder\Definitions\Condition;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\Concerns\GuardsPublishedVersion;
use Javaabu\FormBuilder\Support\Text;

/**
 * A section of a form version: a card, or a step of a wizard
 *
 * @property int $id
 * @property int $form_version_id
 * @property string $key
 * @property string|array<string, string>|null $title
 * @property string|array<string, string>|null $description
 * @property int|null $columns
 * @property array<int, array<string, mixed>>|null $conditions
 * @property int $position
 * @property-read FormVersion $version
 */
class FormSection extends Model
{
    use GuardsPublishedVersion;

    protected $fillable = [
        'key',
        'title',
        'description',
        'columns',
        'conditions',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'title' => LocalizedText::class,
            'description' => LocalizedText::class,
            'conditions' => 'array',
            'columns' => 'integer',
            'position' => 'integer',
        ];
    }

    public function getTable(): string
    {
        return config('form-builder.tables.form_sections', 'form_sections');
    }

    /**
     * @return BelongsTo<FormVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('version'), 'form_version_id');
    }

    /**
     * The section's own fields, not those in repeating groups
     *
     * @return HasMany<FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormBuilder::model('field'), 'form_section_id')
            ->whereNull('parent_id')
            ->orderBy('position');
    }

    /**
     * The title in the current locale
     */
    public function titleText(?string $locale = null): ?string
    {
        return Text::resolve($this->title, $locale);
    }

    public function toDefinition(): Section
    {
        return Section::make($this->key)
            ->id($this->getKey())
            ->title($this->title)
            ->description($this->description)
            ->columns($this->columns)
            ->conditions(array_map(fn (array $condition) => Condition::fromArray($condition), $this->conditions ?? []));
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributesFrom(Section $section): array
    {
        return [
            'key' => $section->getKey(),
            'title' => $section->getTitle(),
            'description' => $section->getDescription(),
            'columns' => $section->getColumns(),
            'conditions' => array_map(fn (Condition $condition) => $condition->toArray(), $section->getConditions()) ?: null,
        ];
    }
}
