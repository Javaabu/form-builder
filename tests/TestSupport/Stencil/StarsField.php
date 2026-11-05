<?php

namespace Javaabu\FormBuilder\Tests\TestSupport\Stencil;

use Closure;
use Javaabu\Stencil\Fields\Field;

/**
 * A stencil field of the app's own, with its own Blade view and React component
 */
class StarsField extends Field
{
    protected string $type = 'stars';

    protected ?string $view = 'stencil.stars';

    protected int|Closure $max = 5;

    public function max(int|Closure $max): static
    {
        $this->max = $max;

        return $this;
    }

    protected function getTypeRules(): array
    {
        return ['integer', 'min:1', 'max:' . $this->evaluate($this->max)];
    }

    protected function getFieldProps(): array
    {
        return ['max' => $this->evaluate($this->max)];
    }
}
