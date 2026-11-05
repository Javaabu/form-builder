<?php

namespace Javaabu\FormBuilder\Stencil;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Compiler\Compiler;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\Stencil\Form;

/**
 * An entry's answers, read-only: each section with its fields' answers as text
 * entries, repeating groups row by row, and fields that were hidden by their
 * conditions left out. Render it like any stencil form.
 *
 * ```php
 * $display = DisplayForm::forEntry($entry);
 * ```
 */
class DisplayForm extends Form
{
    /** @var array<string, mixed> */
    protected array $answers = [];

    protected ?FormDefinition $form_definition = null;

    protected ?FormVersion $form_version = null;

    public static function forEntry(FormEntry $entry): static
    {
        return static::forAnswers($entry->version, $entry->answers())
            ->id('form-builder-entry-' . $entry->getKey());
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    public static function forAnswers(FormVersion|FormDefinition $version, array $answers): static
    {
        $form = static::make('form-builder-entry')
            ->operation('view')
            ->model($answers)
            ->submit(false)
            ->embedded();

        $form->answers = $answers;

        if ($version instanceof FormVersion) {
            $form->form_version = $version;
            $form->form_definition = $version->definition();

            return $form;
        }

        $form->form_definition = $version;

        return $form;
    }

    protected function definition(): array
    {
        if (! $this->form_definition) {
            return [];
        }

        return app(Compiler::class)->compile(new CompileContext(
            definition: $this->form_definition,
            mode: CompileContext::DISPLAY,
            locale: app()->getLocale(),
            answers: $this->answers,
            version: $this->form_version,
        ));
    }
}
