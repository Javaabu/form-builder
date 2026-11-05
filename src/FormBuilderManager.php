<?php

namespace Javaabu\FormBuilder;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Entries\EntryRecorder;
use Javaabu\FormBuilder\Exceptions\FormBuilderException;
use Javaabu\FormBuilder\InputTypes\InputType;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Models\FormEntryValue;
use Javaabu\FormBuilder\Models\FormField;
use Javaabu\FormBuilder\Models\FormSection;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\FormBuilder\Stencil\DisplayForm;
use Javaabu\FormBuilder\Stencil\EntryForm;

/**
 * The form builder's registry and entry point, behind the FormBuilder facade:
 * models, input types, defining and syncing forms, filling and submitting them,
 * and showing entries.
 */
class FormBuilderManager
{
    /** Set to false (in a service provider's register()) to publish and run the migrations yourself */
    public static bool $runs_migrations = true;

    /** @var array<string, class-string<Model>> */
    public const array MODELS = [
        'form' => Form::class,
        'version' => FormVersion::class,
        'section' => FormSection::class,
        'field' => FormField::class,
        'entry' => FormEntry::class,
        'value' => FormEntryValue::class,
    ];

    /** @var array<string, InputType|class-string<InputType>> */
    protected array $input_types = [];

    /** @var class-string<Form>|null */
    protected ?string $translatable_form = null;

    /** @var array<string, array<int, Closure>> */
    protected array $extensions = [];

    /**
     * Use your own migrations instead of the package's
     */
    public static function ignoreMigrations(): void
    {
        static::$runs_migrations = false;
    }

    /**
     * A model class by name (form, version, section, field, entry, value), from config('form-builder.models')
     *
     * @return class-string<Model>
     */
    public function model(string $name): string
    {
        $model = config("form-builder.models.{$name}");

        if ($model) {
            return $model;
        }

        // The translatable form model when javaabu/translatable is on. It reads
        // the languages, so it's decided when a model is first needed.
        if ($name === 'form') {
            return $this->translatable_form ??= Support\Translations::enabled() ? Models\Translatable\Form::class : Form::class;
        }

        return self::MODELS[$name];
    }

    /**
     * @internal forget the form model chosen, after javaabu/translatable's languages change
     */
    public function forgetModels(): void
    {
        $this->translatable_form = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Input types
    |--------------------------------------------------------------------------
    */

    /**
     * Add input types, or replace the ones with the same key
     *
     * @param  InputType|class-string<InputType>|array<int, InputType|class-string<InputType>>  $types
     */
    public function registerInputType(InputType|string|array $types): static
    {
        foreach (is_array($types) ? $types : [$types] as $type) {
            $key = $type instanceof InputType ? $type::key() : $type::key();

            $this->input_types[$key] = $type;
        }

        return $this;
    }

    /**
     * Remove an input type, so the builder doesn't offer it
     */
    public function forgetInputType(string $key): static
    {
        unset($this->input_types[$key]);

        return $this;
    }

    /**
     * Change the stencil component of an input type, after it's made:
     * fn (Component $component, Field $field, CompileContext $context) => $component->rules(['alpha'])
     */
    public function extendInputType(string $key, Closure $extension): static
    {
        $this->extensions[$key][] = $extension;

        return $this;
    }

    /**
     * @return array<int, Closure>
     */
    public function inputTypeExtensions(string $key): array
    {
        return $this->extensions[$key] ?? [];
    }

    public function hasInputType(string $key): bool
    {
        return isset($this->input_types[$key]);
    }

    public function inputType(string $key): InputType
    {
        $type = $this->input_types[$key] ?? throw FormBuilderException::unknownInputType($key);

        if (is_string($type)) {
            $type = $this->input_types[$key] = app($type);
        }

        return $type;
    }

    /**
     * Every input type, by key
     *
     * @return array<string, InputType>
     */
    public function inputTypes(): array
    {
        $types = [];

        foreach (array_keys($this->input_types) as $key) {
            $types[$key] = $this->inputType($key);
        }

        return $types;
    }

    /*
    |--------------------------------------------------------------------------
    | Defining forms
    |--------------------------------------------------------------------------
    */

    /**
     * Start a definition, for a seeder: FormBuilder::define('feedback')->name(…)->sections([...])->sync()
     */
    public function define(string $key): FormDefinition
    {
        return FormDefinition::make($key);
    }

    /**
     * Save a definition: create the form, or give it a new version when the
     * structure changed. Publishes the new version unless $publish is false
     * (config('form-builder.sync.publish') by default).
     */
    public function sync(FormDefinition $definition, ?bool $publish = null): Form
    {
        $publish ??= (bool) config('form-builder.sync.publish', true);

        return DB::transaction(function () use ($definition, $publish) {
            /** @var Form $form */
            $form = $this->model('form')::query()->withTrashed()->firstOrNew(['key' => $definition->getKey()]);

            $form->fill([
                'name' => $this->plainText($definition->getName()) ?? $definition->getKey(),
                'description' => $this->plainText($definition->getDescription()),
                'is_locked' => $definition->isLocked(),
            ]);

            if ($form->trashed()) {
                $form->restore();
            }

            $form->save();

            $this->syncTranslations($form, $definition);

            $hash = FormVersion::hashOf($definition);
            $latest = $form->versions()->reorder()->latest('number')->first();

            if ($latest?->hash === $hash) {
                if ($publish && $latest->isDraft()) {
                    $form->publish();
                }

                return $form->refresh();
            }

            $form->ensureDraft()->replaceStructure($definition);

            if ($publish) {
                $form->publish();
            }

            return $form->refresh();
        });
    }

    /**
     * The form with the key
     */
    public function form(string $key): Form
    {
        return $this->model('form')::query()->where('key', $key)->first()
            ?? throw FormBuilderException::formNotFound($key);
    }

    /**
     * The stencil form people fill: the form's published version, or the version given
     */
    public function fill(Form|FormVersion|string $form): EntryForm
    {
        return EntryForm::for($this->versionToFill($form));
    }

    /**
     * Validate a submission and store it as an entry of the version
     *
     * @param  Request|array<string, mixed>|null  $input
     */
    public function submit(
        Form|FormVersion|string $form,
        Request|array|null $input = null,
        ?Model $submitter = null,
        ?Model $subject = null,
        ?EntryForm $stencil = null,
    ): FormEntry {
        $version = $this->versionToFill($form);

        return app(EntryRecorder::class)->submit(
            stencil: $stencil ?? EntryForm::for($version),
            version: $version,
            input: $input ?? request(),
            submitter: $submitter,
            subject: $subject,
        );
    }

    /**
     * An entry's answers, read-only, as a stencil form for Blade or React
     */
    public function display(FormEntry $entry): DisplayForm
    {
        return DisplayForm::forEntry($entry);
    }

    protected function versionToFill(Form|FormVersion|string $form): FormVersion
    {
        if ($form instanceof FormVersion) {
            return $form;
        }

        $form = is_string($form) ? $this->form($form) : $form;

        return $form->publishedVersion ?? throw FormBuilderException::notPublished($form->key);
    }

    /**
     * @param  string|array<string, string>|null  $text
     */
    protected function plainText(string|array|null $text): ?string
    {
        if (! is_array($text)) {
            return $text;
        }

        $default = Support\Translations::defaultLocale();

        return $text[$default] ?? (reset($text) ?: null);
    }

    /**
     * Name and description in other languages, when javaabu/translatable is on
     */
    protected function syncTranslations(Form $form, FormDefinition $definition): void
    {
        Support\Translations::saveModelText($form, [
            'name' => $definition->getName(),
            'description' => $definition->getDescription(),
        ]);
    }
}
