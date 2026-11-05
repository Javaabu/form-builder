<?php

namespace Javaabu\FormBuilder\Facades;

use Illuminate\Support\Facades\Facade;
use Javaabu\FormBuilder\FormBuilderManager;

/**
 * @see FormBuilderManager
 */
class FormBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FormBuilderManager::class;
    }
}
