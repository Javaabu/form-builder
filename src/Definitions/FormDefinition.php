<?php

namespace Javaabu\FormBuilder\Definitions;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\Form;
use JsonSerializable;

/**
 * A whole form: its name, layout, settings and sections. It's what a stored
 * version holds, what JSON exports and imports, what the AI assistant writes,
 * and how seeders define forms:
 *
 * ```php
 * FormBuilder::define('job-application')
 *     ->name(['en' => 'Job application', 'dv' => 'ވަޒީފާއަށް އެދޭ ފޯމު'])
 *     ->wizard()
 *     ->sections([
 *         Section::make('personal')->title(__('Personal details'))->fields([...]),
 *         Section::make('references')->title(__('References'))->fields([...]),
 *     ])
 *     ->sync();
 * ```
 *
 * @implements Arrayable<string, mixed>
 */
class FormDefinition implements Arrayable, JsonSerializable
{
    use Conditionable;
    use Macroable;
    use Tappable;

    /** Every section on one page */
    public const string LAYOUT_SECTIONS = 'sections';

    /** One section at a time, with Back and Next */
    public const string LAYOUT_WIZARD = 'wizard';

    /** @var string|array<string, string>|null */
    protected string|array|null $name = null;

    /** @var string|array<string, string>|null */
    protected string|array|null $description = null;

    protected string $layout = self::LAYOUT_SECTIONS;

    /** @var array<string, mixed> */
    protected array $settings = [];

    /** @var array<int, Section> */
    protected array $sections = [];

    protected bool $is_locked = false;

    final public function __construct(
        protected string $key,
    ) {}

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param  string|array<string, string>|null  $name
     */
    public function name(string|array|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|array<string, string>|null
     */
    public function getName(): string|array|null
    {
        return $this->name;
    }

    /**
     * @param  string|array<string, string>|null  $description
     */
    public function description(string|array|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|array<string, string>|null
     */
    public function getDescription(): string|array|null
    {
        return $this->description;
    }

    public function layout(string $layout): static
    {
        $this->layout = $layout === self::LAYOUT_WIZARD ? self::LAYOUT_WIZARD : self::LAYOUT_SECTIONS;

        return $this;
    }

    /**
     * Show one section at a time, as the steps of a wizard
     */
    public function wizard(bool $condition = true): static
    {
        return $this->layout($condition ? self::LAYOUT_WIZARD : self::LAYOUT_SECTIONS);
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function isWizard(): bool
    {
        return $this->layout === self::LAYOUT_WIZARD;
    }

    /**
     * Defined in code only: the builder shows it but doesn't change it
     */
    public function locked(bool $condition = true): static
    {
        $this->is_locked = $condition;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function setting(string $key, mixed $value): static
    {
        if ($value === null) {
            unset($this->settings[$key]);

            return $this;
        }

        $this->settings[$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function settings(array $settings): static
    {
        foreach ($settings as $key => $value) {
            $this->setting($key, $value);
        }

        return $this;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * The submit button's label
     *
     * @param  string|array<string, string>|null  $label
     */
    public function submitLabel(string|array|null $label): static
    {
        return $this->setting('submit_label', $label);
    }

    /**
     * What people see after they submit
     *
     * @param  string|array<string, string>|null  $message
     */
    public function confirmation(string|array|null $message): static
    {
        return $this->setting('confirmation', $message);
    }

    /**
     * @param  array<int, Section|null>  $sections
     */
    public function sections(array $sections): static
    {
        $this->sections = array_values(array_filter($sections));

        return $this;
    }

    public function section(Section $section): static
    {
        $this->sections[] = $section;

        return $this;
    }

    /**
     * @return array<int, Section>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    public function getSection(string $key): ?Section
    {
        foreach ($this->sections as $section) {
            if ($section->getKey() === $key) {
                return $section;
            }
        }

        return null;
    }

    /**
     * The fields of every section, in order, not those inside repeating groups
     *
     * @return array<int, Field>
     */
    public function getFields(): array
    {
        $fields = [];

        foreach ($this->sections as $section) {
            array_push($fields, ...$section->getFields());
        }

        return $fields;
    }

    /**
     * A field by key, or by `group.key` for a field inside a repeating group
     */
    public function getField(string $key): ?Field
    {
        [$first, $rest] = array_pad(explode('.', $key, 2), 2, null);

        foreach ($this->getFields() as $field) {
            if ($field->getKey() !== $first) {
                continue;
            }

            if ($rest === null) {
                return $field;
            }

            foreach ($field->getFields() as $child) {
                if ($child->getKey() === $rest) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * Save the definition: create the form, or publish a new version when the
     * structure changed. Running it again with the same definition does nothing.
     */
    public function sync(?bool $publish = null): Form
    {
        return FormBuilder::sync($this, $publish);
    }

    /**
     * The structure a version stores: layout, settings and sections
     *
     * @return array<string, mixed>
     */
    public function structure(): array
    {
        return [
            'layout' => $this->layout,
            'settings' => $this->settings,
            'sections' => array_map(fn (Section $section) => $section->toArray(), $this->sections),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return static::make((string) ($data['key'] ?? ''))
            ->name($data['name'] ?? null)
            ->description($data['description'] ?? null)
            ->layout((string) ($data['layout'] ?? self::LAYOUT_SECTIONS))
            ->locked((bool) ($data['locked'] ?? false))
            ->settings((array) ($data['settings'] ?? []))
            ->sections(array_map(fn (array $section) => Section::fromArray($section), (array) ($data['sections'] ?? [])));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'locked' => $this->is_locked ?: null,
            ...$this->structure(),
            'settings' => $this->settings ?: null,
        ], fn ($value) => $value !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
