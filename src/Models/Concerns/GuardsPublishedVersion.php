<?php

namespace Javaabu\FormBuilder\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Javaabu\FormBuilder\Exceptions\FormBuilderException;
use Javaabu\FormBuilder\Models\FormVersion;

/**
 * Sections and fields of a published version can't be added, changed or removed
 */
trait GuardsPublishedVersion
{
    protected static function bootGuardsPublishedVersion(): void
    {
        $guard = function (Model $model) {
            $version = $model->relationLoaded('version')
                ? $model->getRelation('version')
                : FormVersion::resolveVersion($model->getAttribute('form_version_id'));

            if ($version?->isPublished()) {
                throw FormBuilderException::publishedVersionIsImmutable();
            }
        };

        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }
}
