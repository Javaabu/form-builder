<?php

namespace Javaabu\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Javaabu\FormBuilder\Facades\FormBuilder;

/**
 * One indexed answer of an entry, for queries: a value per item for lists, a
 * row per field per row for repeating groups
 *
 * @property int $id
 * @property int $form_entry_id
 * @property int|null $form_field_id
 * @property string $field_key
 * @property string $path
 * @property string|null $value
 * @property string|null $number
 * @property Carbon|null $date
 */
class FormEntryValue extends Model
{
    protected $fillable = [
        'form_field_id',
        'field_key',
        'path',
        'value',
        'number',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('form-builder.tables.form_entry_values', 'form_entry_values');
    }

    /**
     * @return BelongsTo<FormEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('entry'), 'form_entry_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::model('field'), 'form_field_id');
    }
}
