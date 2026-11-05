<?php

namespace Javaabu\FormBuilder;

/**
 * The form builder's registry and entry point, behind the FormBuilder facade
 */
class FormBuilderManager
{
    /** Set to false (in a service provider's register()) to publish and run the migrations yourself */
    public static bool $runs_migrations = true;

    /**
     * Use your own migrations instead of the package's
     */
    public static function ignoreMigrations(): void
    {
        static::$runs_migrations = false;
    }
}
