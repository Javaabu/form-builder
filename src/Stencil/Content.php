<?php

namespace Javaabu\FormBuilder\Stencil;

use Closure;
use Javaabu\Stencil\Components\Component;

/**
 * A heading or a paragraph between fields. Renders with the
 * form-builder::stencil.content view and the FormBuilderContent React component.
 */
class Content extends Component
{
    protected string $type = 'form-builder-content';

    protected string|Closure|null $text = null;

    protected string $variant = 'paragraph';

    final public function __construct() {}

    public static function heading(string|Closure|null $text): static
    {
        return app(static::class)->text($text)->variant('heading');
    }

    public static function paragraph(string|Closure|null $text): static
    {
        return app(static::class)->text($text)->variant('paragraph');
    }

    public function text(string|Closure|null $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant === 'heading' ? 'heading' : 'paragraph';

        return $this;
    }

    public function getProps(): array
    {
        return array_filter([
            'text' => $this->evaluate($this->text),
            'variant' => $this->variant,
        ], fn ($value) => $value !== null);
    }
}
