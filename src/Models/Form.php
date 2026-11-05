<?php

namespace Javaabu\FormBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Exceptions\FormBuilderException;
use Javaabu\FormBuilder\Facades\FormBuilder;

/**
 * A form: its key, name and settings, and its versions. People fill the
 * published version; the builder edits the draft. Publishing freezes the draft,
 * so entries keep pointing at the version they were filled with.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property array<string, mixed>|null $settings
 * @property bool $is_locked
 * @property bool $accepts_entries
 * @property int|null $published_version_id
 * @property-read FormVersion|null $publishedVersion
 * @property-read FormVersion|null $draftVersion
 */
class Form extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'description',
        'settings',
        'is_locked',
        'accepts_entries',
    ];

    protected $attributes = [
        'is_locked' => false,
        'accepts_entries' => true,
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_locked' => 'boolean',
            'accepts_entries' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return config('form-builder.tables.forms', 'forms');
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * @return HasMany<FormVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(FormBuilder::model('version'), 'form_id')->orderBy('number');
    }

    /**
     * @return BelongsTo<FormVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('version'), 'published_version_id');
    }

    /**
     * The version the builder edits, if there is one
     *
     * @return HasOne<FormVersion, $this>
     */
    public function draftVersion(): HasOne
    {
        return $this->hasOne(FormBuilder::model('version'), 'form_id')
            ->whereNull('published_at')
            ->latest('number');
    }

    /**
     * @return HasMany<FormEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(FormBuilder::model('entry'), 'form_id');
    }

    /**
     * Forms with a published version
     *
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_version_id');
    }

    public function isPublished(): bool
    {
        return $this->published_version_id !== null;
    }

    public function hasDraft(): bool
    {
        return $this->draftVersion !== null;
    }

    /**
     * Whether people can fill it now: published and accepting entries
     */
    public function isOpen(): bool
    {
        return $this->isPublished() && $this->accepts_entries;
    }

    /**
     * The draft, the published version, or the latest version
     */
    public function currentVersion(): ?FormVersion
    {
        return $this->draftVersion
            ?? $this->publishedVersion
            ?? $this->versions()->reorder()->latest('number')->first();
    }

    /**
     * The draft the builder edits, made from the published version when there's none
     */
    public function draft(?Model $author = null): FormVersion
    {
        if ($this->is_locked) {
            throw FormBuilderException::locked($this->key);
        }

        return $this->ensureDraft($author);
    }

    /**
     * @internal the draft, without checking whether the form is locked
     */
    public function ensureDraft(?Model $author = null): FormVersion
    {
        $draft = $this->draftVersion()->first();

        if ($draft) {
            return $draft;
        }

        return DB::transaction(function () use ($author) {
            $source = $this->publishedVersion ?? $this->versions()->reorder()->latest('number')->first();

            /** @var FormVersion $draft */
            $draft = $this->versions()->make([
                'number' => $this->nextVersionNumber(),
                'layout' => FormDefinition::LAYOUT_SECTIONS,
            ]);

            if ($author) {
                $draft->author()->associate($author);
            }

            $draft->save();

            if ($source) {
                $draft->replaceStructure($source->definition());
            }

            $this->unsetRelation('draftVersion');

            return $draft;
        });
    }

    /**
     * Publish the draft: it becomes the version people fill, and can't change any more
     */
    public function publish(?Model $author = null): FormVersion
    {
        $draft = $this->draftVersion()->first();

        if (! $draft) {
            return $this->publishedVersion ?? throw FormBuilderException::notPublished($this->key);
        }

        return DB::transaction(function () use ($draft, $author) {
            if ($author && ! $draft->author_id) {
                $draft->author()->associate($author);
            }

            $draft->published_at = now();
            $draft->save();

            $this->published_version_id = $draft->getKey();
            $this->save();

            $this->unsetRelation('draftVersion');
            $this->setRelation('publishedVersion', $draft);

            return $draft;
        });
    }

    /**
     * Throw the draft away, keeping the published version
     */
    public function discardDraft(): void
    {
        $draft = $this->draftVersion()->first();

        if (! $draft) {
            return;
        }

        $draft->delete();

        $this->unsetRelation('draftVersion');
    }

    public function nextVersionNumber(): int
    {
        return ((int) $this->versions()->reorder()->max('number')) + 1;
    }

    /**
     * The definition of the version people see: the published one, or the draft
     *
     * @param  bool  $draft  the draft's definition when there's one
     */
    public function definition(bool $draft = false): ?FormDefinition
    {
        $version = $draft ? $this->currentVersion() : ($this->publishedVersion ?? $this->currentVersion());

        return $version?->definition();
    }
}
