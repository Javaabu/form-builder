<?php

namespace Javaabu\FormBuilder\Stencil;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Compiler\Compiler;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\FormBuilder\Support\Text;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Form;
use RuntimeException;

/**
 * The stencil form people fill a form version with. Render it like any stencil
 * form ({{ $form }} in Blade, a prop for <StencilForm> in React), validate a
 * submission with validate(), and store it with FormBuilder::submit().
 *
 * ```php
 * $form = EntryForm::for($version)->action(route('applications.store'));
 * $form = EntryForm::for($version)->model($entry->data); // edit an entry's answers
 * ```
 */
class EntryForm extends Form
{
    public static function for(FormVersion $version): static
    {
        $definition = $version->definition();

        return static::make('form-builder-' . $definition->getKey())
            ->with(['version' => $version])
            ->submit(Text::resolve($definition->getSetting('submit_label')) ?? __('Submit'));
    }

    /**
     * A form for a definition that isn't stored: previews and tests
     */
    public static function forDefinition(FormDefinition $definition): static
    {
        return static::make('form-builder-' . ($definition->getKey() ?: 'preview'))
            ->with(['definition' => $definition->toArray()])
            ->submit(Text::resolve($definition->getSetting('submit_label')) ?? __('Submit'));
    }

    public function getVersion(): ?FormVersion
    {
        $version = $this->context('version');

        if ($version instanceof FormVersion) {
            return $version;
        }

        if (is_numeric($version)) {
            return FormBuilder::model('version')::query()->find($version);
        }

        return null;
    }

    public function getDefinition(): FormDefinition
    {
        $version = $this->getVersion();

        if ($version) {
            return $version->definition();
        }

        $definition = $this->context('definition');

        if (is_array($definition)) {
            return FormDefinition::fromArray($definition);
        }

        throw new RuntimeException('Make an EntryForm with EntryForm::for($version) or EntryForm::forDefinition($definition).');
    }

    protected function definition(): array
    {
        $context = new CompileContext(
            definition: $this->getDefinition(),
            mode: CompileContext::FILL,
            locale: app()->getLocale(),
            version: $this->getVersion(),
        );

        return app(Compiler::class)->compile($context);
    }

    /**
     * Fields inside repeating groups are found by their full path too: references.0.name
     */
    public function getField(string $name): ?Field
    {
        $field = parent::getField($name);

        if ($field) {
            return $field;
        }

        $path = Field::normalizeName($name);

        foreach ($this->getFields() as $candidate) {
            if (! $candidate instanceof Repeater) {
                continue;
            }

            $found = $candidate->findRowField($path);

            if ($found) {
                return $found;
            }
        }

        return null;
    }
}
