<?php

namespace Javaabu\FormBuilder\Exceptions;

use RuntimeException;

class FormBuilderException extends RuntimeException
{
    public static function publishedVersionIsImmutable(): static
    {
        return new static('A published form version can\'t be changed. Edit the form\'s draft instead.');
    }

    public static function unknownInputType(string $type): static
    {
        return new static("There's no form builder input type [{$type}]. Register it with FormBuilder::inputType().");
    }

    public static function formNotFound(string $key): static
    {
        return new static("There's no form [{$key}].");
    }

    public static function notPublished(string $key): static
    {
        return new static("The form [{$key}] has no published version.");
    }

    public static function locked(string $key): static
    {
        return new static("The form [{$key}] is defined in code and can't be changed here.");
    }
}
