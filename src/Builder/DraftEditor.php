<?php

namespace Javaabu\FormBuilder\Builder;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\Definitions\DefinitionValidator;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormVersion;

/**
 * Applies a builder change to a form's draft (made from the published version
 * when there's none), validates the whole definition, and saves it
 */
class DraftEditor
{
    public function __construct(
        protected DefinitionValidator $validator,
    ) {}

    /**
     * @param  Closure(FormDefinition): (FormDefinition|null)  $change
     * @param  string|null  $focus  the path whose errors are shown on the edited form, sections.0.fields.2
     */
    public function change(Form $form, Closure $change, ?Model $author = null, Closure|string|null $focus = null): FormVersion
    {
        $draft = $form->draft($author);
        $definition = FormDefinition::fromArray($draft->definition()->toArray());

        $definition = $change($definition) ?? $definition;

        $this->ensureValid($definition, $focus instanceof Closure ? $focus($definition) : $focus);

        return $draft->replaceStructure($definition);
    }

    /**
     * @param  string|null  $focus  errors under this path lose it (they're the edited form's); others go under `structure`
     */
    public function ensureValid(FormDefinition $definition, ?string $focus = null): void
    {
        $errors = $this->validator->errors($definition->toArray());

        if ($errors->isEmpty()) {
            return;
        }

        $messages = [];

        foreach ($errors->messages() as $path => $list) {
            if ($focus !== null && str_starts_with($path, "{$focus}.")) {
                $messages[substr($path, strlen($focus) + 1)] = $list;

                continue;
            }

            $messages['structure'] = [...($messages['structure'] ?? []), ...$list];
        }

        throw ValidationException::withMessages($messages);
    }

    /**
     * Where a section is in a definition: sections.1
     */
    public static function sectionPath(FormDefinition $definition, string $key): ?string
    {
        foreach ($definition->getSections() as $index => $section) {
            if ($section->getKey() === $key) {
                return "sections.{$index}";
            }
        }

        return null;
    }

    /**
     * Where a field is in a definition: sections.1.fields.0, sections.1.fields.0.fields.2
     */
    public static function fieldPath(FormDefinition $definition, string $path): ?string
    {
        [$key, $child] = array_pad(explode('.', $path, 2), 2, null);

        foreach ($definition->getSections() as $s => $section) {
            foreach ($section->getFields() as $f => $field) {
                if ($field->getKey() !== $key) {
                    continue;
                }

                if ($child === null) {
                    return "sections.{$s}.fields.{$f}";
                }

                foreach ($field->getFields() as $c => $nested) {
                    if ($nested->getKey() === $child) {
                        return "sections.{$s}.fields.{$f}.fields.{$c}";
                    }
                }
            }
        }

        return null;
    }
}
