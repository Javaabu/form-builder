<?php

namespace Javaabu\FormBuilder\Models\Translatable;

use Javaabu\FormBuilder\Models\Form as BaseForm;
use Javaabu\Translatable\Contracts\JsonTranslatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

/**
 * The form model with javaabu/translatable: its name and description in every
 * language. Used when javaabu/translatable is on and config('form-builder.models.form')
 * is null. PHP only loads it when it's used, so it's safe without the package.
 */
class Form extends BaseForm implements JsonTranslatable
{
    use IsJsonTranslatable;

    /** @var array<int, string> */
    protected array $translatable = ['name', 'description'];

    /**
     * Keep morph types pointing at the base model
     */
    public function getMorphClass(): string
    {
        return BaseForm::class;
    }
}
