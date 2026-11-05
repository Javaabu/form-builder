<?php

namespace Javaabu\FormBuilder\Transfer;

use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\FormBuilder\Support\Translations;
use Javaabu\Translatable\Contracts\Translatable;

/**
 * A form's structure as JSON, to move it between apps or keep it in a repository:
 *
 * ```json
 * { "form_builder": 1, "form": { "key": "feedback", "name": "Feedback", "layout": "sections", "sections": [...] } }
 * ```
 */
class Exporter
{
    /** The format version. It changes only with a breaking change. */
    public const int FORMAT = 1;

    /**
     * The export of a form's version: the published one by default, or the draft
     *
     * @return array<string, mixed>
     */
    public function export(Form|FormVersion $form, bool $draft = false): array
    {
        $version = $form instanceof FormVersion
            ? $form
            : ($draft ? $form->currentVersion() : ($form->publishedVersion ?? $form->currentVersion()));

        $model = $form instanceof FormVersion ? $form->form : $form;
        $definition = $version?->definition() ?? FormDefinition::make($model->key)->name($model->name);

        $data = $definition->toArray();

        $data['name'] = $this->translated($model, 'name') ?? $data['name'] ?? null;
        $data['description'] = $this->translated($model, 'description') ?? $data['description'] ?? null;

        unset($data['locked']);

        return [
            'form_builder' => self::FORMAT,
            'form' => array_filter($data, fn ($value) => $value !== null),
        ];
    }

    public function toJson(Form|FormVersion $form, bool $draft = false): string
    {
        return json_encode($this->export($form, $draft), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * A model attribute in every locale, when javaabu/translatable keeps translations of it
     *
     * @return array<string, string>|null
     */
    protected function translated(Form $form, string $attribute): ?array
    {
        if (! Translations::enabled() || ! $form instanceof Translatable || ! $form->isTranslatable($attribute)) {
            return null;
        }

        $values = [];

        foreach (Translations::locales() as $locale) {
            $value = $form->translate($attribute, $locale, false);

            if ($value !== null && $value !== '') {
                $values[$locale] = (string) $value;
            }
        }

        return count($values) > 1 ? $values : null;
    }
}
