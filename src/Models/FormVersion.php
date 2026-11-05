<?php

namespace Javaabu\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Exceptions\FormBuilderException;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Stencil\EntryForm;

/**
 * One version of a form's structure. A draft can change; a published version
 * can't, so the entries filled with it always match it.
 *
 * @property int $id
 * @property int $form_id
 * @property int $number
 * @property string $layout
 * @property array<string, mixed>|null $settings
 * @property string|null $hash
 * @property Carbon|null $published_at
 * @property-read Form $form
 */
class FormVersion extends Model
{
    protected $fillable = [
        'number',
        'layout',
        'settings',
    ];

    protected ?FormDefinition $resolved_definition = null;

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'number' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('form-builder.tables.form_versions', 'form_versions');
    }

    protected static function booted(): void
    {
        static::updating(function (FormVersion $version) {
            if ($version->getOriginal('published_at') !== null) {
                throw FormBuilderException::publishedVersionIsImmutable();
            }
        });

        static::deleting(function (FormVersion $version) {
            if ($version->getOriginal('published_at') !== null) {
                throw FormBuilderException::publishedVersionIsImmutable();
            }
        });
    }

    /**
     * @internal the version a section or field belongs to
     */
    public static function resolveVersion(mixed $id): ?FormVersion
    {
        if ($id === null) {
            return null;
        }

        return FormBuilder::model('version')::query()->find($id);
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('form'), 'form_id')->withTrashed();
    }

    /**
     * @return HasMany<FormSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(FormBuilder::model('section'), 'form_version_id')->orderBy('position');
    }

    /**
     * Every field, those in repeating groups included
     *
     * @return HasMany<FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormBuilder::model('field'), 'form_version_id')->orderBy('position');
    }

    /**
     * @return HasMany<FormEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(FormBuilder::model('entry'), 'form_version_id');
    }

    /**
     * Who made this version
     *
     * @return MorphTo<Model, $this>
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    /**
     * Whether this is the version people fill now
     */
    public function isCurrent(): bool
    {
        return $this->form->published_version_id === $this->getKey();
    }

    /**
     * The form's structure in this version
     */
    public function definition(): FormDefinition
    {
        if ($this->resolved_definition) {
            return $this->resolved_definition;
        }

        $form = $this->form;

        $this->loadMissing(['sections', 'fields']);

        $children = $this->fields->whereNotNull('parent_id')->groupBy('parent_id');
        $by_section = $this->fields->whereNull('parent_id')->groupBy('form_section_id');

        $sections = $this->sections->map(function (FormSection $section) use ($by_section, $children) {
            $fields = ($by_section[$section->getKey()] ?? collect())->map(
                fn (FormField $field) => $field->toDefinition(($children[$field->getKey()] ?? collect())->all())
            );

            return $section->toDefinition()->fields($fields->all());
        });

        return $this->resolved_definition = FormDefinition::make($form->key)
            ->name($form->name)
            ->description($form->description)
            ->locked($form->is_locked)
            ->layout($this->layout)
            ->settings($this->settings ?? [])
            ->sections($sections->all());
    }

    /**
     * Replace the draft's layout, settings, sections and fields with a definition's
     */
    public function replaceStructure(FormDefinition $definition): static
    {
        if ($this->isPublished()) {
            throw FormBuilderException::publishedVersionIsImmutable();
        }

        DB::transaction(function () use ($definition) {
            $this->fields()->whereNotNull('parent_id')->delete();
            $this->fields()->delete();
            $this->sections()->delete();

            foreach ($definition->getSections() as $position => $section) {
                $this->createSection($section, $position);
            }

            $this->layout = $definition->getLayout();
            $this->settings = $definition->getSettings() ?: null;
            $this->hash = static::hashOf($definition);
            $this->save();
        });

        $this->resolved_definition = null;
        $this->unsetRelation('sections');
        $this->unsetRelation('fields');

        return $this;
    }

    /**
     * A digest of a definition's structure, to tell whether it changed
     */
    public static function hashOf(FormDefinition $definition): string
    {
        return hash('sha256', json_encode($definition->structure(), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Forget the definition read so far, after changing sections or fields
     */
    public function refreshDefinition(): static
    {
        $this->resolved_definition = null;
        $this->unsetRelation('sections');
        $this->unsetRelation('fields');

        return $this;
    }

    /**
     * Update the hash after the builder changed a section or field
     */
    public function touchStructure(): static
    {
        $this->refreshDefinition();

        $this->hash = static::hashOf($this->definition());
        $this->save();

        return $this;
    }

    /**
     * The stencil form people fill this version with
     */
    public function stencil(): EntryForm
    {
        return EntryForm::for($this);
    }

    protected function createSection(Section $section, int $position): FormSection
    {
        /** @var FormSection $model */
        $model = $this->sections()->create([
            ...FormSection::attributesFrom($section),
            'position' => $position,
        ]);

        foreach ($section->getFields() as $index => $field) {
            $this->createField($field, $model, $index);
        }

        return $model;
    }

    protected function createField(Field $field, FormSection $section, int $position, ?FormField $parent = null): FormField
    {
        /** @var FormField $model */
        $model = $this->fields()->create([
            ...FormField::attributesFrom($field),
            'form_section_id' => $section->getKey(),
            'parent_id' => $parent?->getKey(),
            'position' => $position,
        ]);

        foreach ($field->getFields() as $index => $child) {
            $this->createField($child, $section, $index, $model);
        }

        return $model;
    }
}
