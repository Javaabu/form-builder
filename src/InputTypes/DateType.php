<?php

namespace Javaabu\FormBuilder\InputTypes;

use Illuminate\Support\Carbon;
use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\TextInput;
use Throwable;

class DateType extends InputType
{
    public static function key(): string
    {
        return 'date';
    }

    public function name(): string
    {
        return __('Date');
    }

    public function icon(): string
    {
        return 'calendar';
    }

    public function group(): string
    {
        return 'date';
    }

    public function storage(): string
    {
        return self::STORE_DATE;
    }

    protected function picker(string $name): DatePicker
    {
        return DatePicker::make($name);
    }

    /**
     * How answers are shown: a PHP date format, translated
     */
    protected function displayFormat(): string
    {
        return (string) config('form-builder.date_format', 'j F Y');
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        return $this->picker($context->name($field->getKey()))
            ->minDate($this->dateSetting($field, 'min'))
            ->maxDate($this->dateSetting($field, 'max'));
    }

    protected function dateSetting(Field $field, string $key): ?string
    {
        $value = $field->getSetting($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->locale($locale ?? app()->getLocale())->translatedFormat($this->displayFormat());
        } catch (Throwable) {
            return $value;
        }
    }

    public function export(mixed $value, Field $field, ?string $locale = null): string|int|float|null
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function settings(): array
    {
        return [
            TextInput::make('min')->label(__('Earliest date'))->help(__('A date (2026-01-31) or words such as "today" or "-18 years".')),
            TextInput::make('max')->label(__('Latest date'))->help(__('A date (2026-01-31) or words such as "today" or "-18 years".')),
        ];
    }

    public function settingsRules(): array
    {
        return [
            'min' => ['nullable', 'string', 'max:64'],
            'max' => ['nullable', 'string', 'max:64'],
        ];
    }
}
