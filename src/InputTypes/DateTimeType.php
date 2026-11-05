<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\DateTimePicker;

class DateTimeType extends DateType
{
    public static function key(): string
    {
        return 'datetime';
    }

    public function name(): string
    {
        return __('Date and time');
    }

    public function icon(): string
    {
        return 'calendar-clock';
    }

    protected function picker(string $name): DatePicker
    {
        return DateTimePicker::make($name);
    }

    protected function displayFormat(): string
    {
        return (string) config('form-builder.datetime_format', 'j F Y, H:i');
    }
}
