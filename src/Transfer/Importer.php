<?php

namespace Javaabu\FormBuilder\Transfer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\Definitions\DefinitionValidator;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\FormBuilderManager;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Support\Translations;

/**
 * Reads an export back: checks the format and the definition, then makes a new
 * form, or a new draft of the form with the same key
 */
class Importer
{
    public function __construct(
        protected FormBuilderManager $builder,
        protected DefinitionValidator $validator,
    ) {}

    /**
     * The definition in an export, validated
     *
     * @param  array<string, mixed>|string  $export  the export, or its JSON
     */
    public function read(array|string $export, ?string $key = null): FormDefinition
    {
        if (is_string($export)) {
            $decoded = json_decode($export, true);

            if (! is_array($decoded)) {
                throw ValidationException::withMessages(['file' => __('This isn\'t a form export: it isn\'t valid JSON.')]);
            }

            $export = $decoded;
        }

        $format = $export['form_builder'] ?? null;

        if (! is_int($format) || ! isset($export['form']) || ! is_array($export['form'])) {
            throw ValidationException::withMessages(['file' => __('This isn\'t a form export.')]);
        }

        if ($format > Exporter::FORMAT) {
            throw ValidationException::withMessages(['file' => __('This export is from a newer version of the form builder.')]);
        }

        $data = $export['form'];

        if ($key !== null && $key !== '') {
            $data['key'] = $key;
        }

        $data['sections'] ??= [];

        $errors = $this->validator->errors($data);

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages(['file' => [__('The form in this export has errors:'), ...array_map(
                fn (string $path, array $messages) => "{$path}: {$messages[0]}",
                array_keys($errors->messages()),
                $errors->messages(),
            )]]);
        }

        return FormDefinition::fromArray($data);
    }

    /**
     * Import an export: a new form, or a new draft of the form with its key
     *
     * @param  array<string, mixed>|string  $export
     */
    public function import(array|string $export, ?string $key = null, bool $publish = false, ?Model $author = null): Form
    {
        $definition = $this->read($export, $key);

        return $this->store($definition, $publish, $author);
    }

    /**
     * Store a definition as the draft of its form, making the form if there's none
     */
    public function store(FormDefinition $definition, bool $publish = false, ?Model $author = null): Form
    {
        return DB::transaction(function () use ($definition, $publish, $author) {
            /** @var Form|null $form */
            $form = $this->builder->model('form')::query()->withTrashed()->where('key', $definition->getKey())->first();

            if ($form?->is_locked) {
                throw ValidationException::withMessages(['file' => __('The form :key is defined in code and can\'t be changed here.', ['key' => $definition->getKey()])]);
            }

            if (! $form) {
                $form = $this->builder->model('form')::make(['key' => $definition->getKey()]);
            }

            if ($form->trashed()) {
                $form->restore();
            }

            $name = $definition->getName();
            $description = $definition->getDescription();

            $form->name = is_array($name) ? ($name[Translations::defaultLocale()] ?? reset($name) ?: $definition->getKey()) : ($name ?? $form->name ?? $definition->getKey());
            $form->description = is_array($description) ? ($description[Translations::defaultLocale()] ?? reset($description) ?: null) : ($description ?? $form->description);
            $form->save();

            Translations::saveModelText($form, ['name' => $name, 'description' => $description]);

            $form->ensureDraft($author)->replaceStructure($definition);

            if ($publish) {
                $form->publish($author);
            }

            return $form->refresh();
        });
    }
}
