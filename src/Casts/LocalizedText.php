<?php

namespace Javaabu\FormBuilder\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Text stored as JSON: a string, or a map of locale => string when it's
 * translated. Read it in the current locale with Text::resolve().
 *
 * @implements CastsAttributes<string|array<string, string>|null, string|array<string, string>|null>
 */
class LocalizedText implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): string|array|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return (string) $value;
        }

        return is_array($decoded) || is_string($decoded) ? $decoded : (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
