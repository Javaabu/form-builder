<?php

namespace Javaabu\FormBuilder\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Javaabu\FormBuilder\Facades\FormBuilder;

/**
 * Someone's answers to a form, tied to the version they filled. `data` holds
 * every answer; form_entry_values indexes them for queries.
 *
 * @property int $id
 * @property int $form_id
 * @property int $form_version_id
 * @property string $status
 * @property array<string, mixed>|null $data
 * @property string|null $locale
 * @property string|null $ip_address
 * @property Carbon|null $submitted_at
 * @property-read Form $form
 * @property-read FormVersion $version
 * @property-read Model|null $submitter
 * @property-read Model|null $subject
 */
class FormEntry extends Model
{
    use SoftDeletes;

    public const string SUBMITTED = 'submitted';

    /** Saved but not submitted yet, for multi-step processes */
    public const string DRAFT = 'draft';

    protected $fillable = [
        'status',
        'data',
        'locale',
        'ip_address',
        'submitted_at',
    ];

    protected $attributes = [
        'status' => self::SUBMITTED,
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('form-builder.tables.form_entries', 'form_entries');
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('form'), 'form_id')->withTrashed();
    }

    /**
     * The version the entry was filled with
     *
     * @return BelongsTo<FormVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('version'), 'form_version_id');
    }

    /**
     * Who filled it, if they were signed in
     *
     * @return MorphTo<Model, $this>
     */
    public function submitter(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * What it's about: an application, a workflow step, any model
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<FormEntryValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(FormBuilder::model('value'), 'form_entry_id');
    }

    /**
     * Every answer, keyed by field: repeating groups are lists of rows
     *
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        return $this->data ?? [];
    }

    /**
     * One answer by path: full_name, references.0.name
     */
    public function answer(string $path, mixed $default = null): mixed
    {
        return data_get($this->answers(), $path, $default);
    }

    /**
     * An answer as text, as the read-only entry shows it: option labels, dates, Yes/No
     */
    public function formatted(string $path, ?string $locale = null): ?string
    {
        $key = preg_replace('/\.\d+\./', '.', $path);
        $field = $this->version->definition()->getField((string) $key);

        if (! $field) {
            return null;
        }

        return FormBuilder::inputType($field->getType())->format($this->answer($path), $field, $locale);
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::SUBMITTED;
    }

    /**
     * Entries with an answer: whereAnswer('age', '>=', 18), whereAnswer('skills', 'php').
     * Numbers and dates compare as numbers and dates; inside a repeating group,
     * use group.key: whereAnswer('references.relationship', 'friend').
     *
     * @param  Builder<static>  $query
     */
    public function scopeWhereAnswer(Builder $query, string $field, mixed $operator, mixed $value = null): void
    {
        if (func_num_args() === 3) {
            $value = $operator;
            $operator = '=';
        }

        $column = match (true) {
            is_int($value) || is_float($value) => 'number',
            $value instanceof DateTimeInterface => 'date',
            default => 'value',
        };

        $query->whereHas('values', function (Builder $values) use ($field, $operator, $value, $column) {
            $values->where('field_key', $field)->where($column, $operator, $value);
        });
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeForSubject(Builder $query, Model $subject): void
    {
        $query->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->getKey());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeSubmittedBy(Builder $query, Model $submitter): void
    {
        $query->where('submitter_type', $submitter->getMorphClass())->where('submitter_id', $submitter->getKey());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeSubmitted(Builder $query): void
    {
        $query->where('status', self::SUBMITTED);
    }
}
