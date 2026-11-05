<?php

namespace Javaabu\FormBuilder\Ai;

use ArrayAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\Builder\BuilderForms;
use Javaabu\FormBuilder\FormBuilderManager;
use Javaabu\FormBuilder\InputTypes\InputType;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Transfer\Exporter;
use Javaabu\FormBuilder\Transfer\Importer;
use Laravel\Ai\Contracts\Agent;
use RuntimeException;

/**
 * Builds a form, or changes one, from a description, with laravel/ai. The
 * answer goes through the importer's validation (with one retry that shows the
 * agent its errors) and is saved as a draft for someone to review and publish.
 */
class FormAssistant
{
    public function __construct(
        protected FormBuilderManager $builder,
        protected Importer $importer,
        protected Exporter $exporter,
    ) {}

    /**
     * Whether laravel/ai is installed and the assistant is on
     */
    public static function available(): bool
    {
        return interface_exists(Agent::class) && (bool) config('form-builder.assistant.enabled', true);
    }

    /**
     * Build a new form from a description, or change $form as it describes
     *
     * @return array{form: Form, summary: string}
     */
    public function build(string $description, ?Form $form = null, ?Model $author = null): array
    {
        if (! static::available()) {
            throw new RuntimeException('Install laravel/ai to use the form builder assistant.');
        }

        $current = $form ? $this->exporter->toJson($form, draft: true) : null;
        $agent = new FormAssistantAgent($this->instructions());

        $prompt = $this->prompt($description, $current);
        $attempts = max(1, (int) config('form-builder.assistant.attempts', 2));
        $errors = [];

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $response = $agent->prompt(
                $prompt,
                provider: config('form-builder.assistant.provider'),
                model: config('form-builder.assistant.model'),
                timeout: config('form-builder.assistant.timeout'),
            );

            $answer = $response instanceof ArrayAccess ? $response : [];
            $json = (string) ($answer['form'] ?? '');

            try {
                $definition = $this->importer->read($json, $form?->key);
            } catch (ValidationException $exception) {
                $errors = array_merge(...array_values($exception->errors()));
                $prompt = $this->retryPrompt($description, $current, $json, $errors);

                continue;
            }

            $saved = $this->importer->store($definition, author: $author);

            return [
                'form' => $saved,
                'summary' => trim((string) ($answer['summary'] ?? '')) ?: __('The assistant changed the draft.'),
            ];
        }

        throw ValidationException::withMessages([
            'description' => [__('The assistant couldn\'t make a valid form. Try describing it differently.'), ...array_slice($errors, 0, 5)],
        ]);
    }

    protected function prompt(string $description, ?string $current): string
    {
        if ($current === null) {
            return "Build this form:\n\n{$description}";
        }

        return "Change this form as described. Keep everything that isn't mentioned.\n\nThe form now:\n{$current}\n\nThe changes:\n{$description}";
    }

    /**
     * @param  array<int, string>  $errors
     */
    protected function retryPrompt(string $description, ?string $current, string $answer, array $errors): string
    {
        return $this->prompt($description, $current)
            . "\n\nYour last answer had these errors. Fix them and answer with the whole form again:\n- "
            . implode("\n- ", $errors)
            . "\n\nYour last answer:\n{$answer}";
    }

    /**
     * What the agent knows: the format, the field types and the rules
     */
    public function instructions(): string
    {
        $locales = app(BuilderForms::class)->locales();
        $types = implode("\n", array_map(fn (InputType $type) => $this->describeType($type), $this->builder->inputTypes()));

        $text = count($locales) > 1
            ? 'Write every label, title, help text, option label and message in each of these languages, as an object of language code => text: ' . implode(', ', $locales) . '. For Dhivehi (dv) write Thaana.'
            : 'Write labels, titles, help text, option labels and messages as plain strings in the language of the request.';

        return <<<TEXT
        You design forms for javaabu/form-builder, a Laravel form builder. You answer with the whole form as JSON in its export format.

        The format:
        {"form_builder": 1, "form": {"key": "job-application", "name": "Job application", "description": "…", "layout": "sections", "settings": {"submit_label": "Apply", "confirmation": "We'll call you."}, "sections": [
          {"key": "personal", "title": "Personal details", "description": "…", "columns": 2, "conditions": [], "fields": [
            {"key": "full_name", "type": "text", "label": "Full name", "help": "…", "placeholder": "…", "required": true, "span": "full", "settings": {"thaana": true, "max_length": 100}, "conditions": []},
            {"key": "applicant_type", "type": "select", "label": "Applicant type", "required": true, "settings": {"options": [{"value": "individual", "label": "Individual"}, {"value": "company", "label": "Company"}]}},
            {"key": "company_name", "type": "text", "label": "Company name", "required": true, "conditions": [{"field": "applicant_type", "operator": "in", "values": ["company"]}]},
            {"key": "references", "type": "repeater", "label": "References", "settings": {"max_rows": 3, "add_label": "Add a reference"}, "fields": [
              {"key": "name", "type": "text", "label": "Name", "required": true}
            ]}
          ]}
        ]}}

        Rules:
        - Keys are lowercase snake_case, start with a letter, and are unique: section keys among sections, field keys across the whole form, and a repeating group's field keys within the group. The form key may also have dashes.
        - layout is "sections" (every section on one page) or "wizard" (one section at a time). Use a wizard for long forms.
        - span is 1, 2 or "full": the columns a field takes in its section's grid. columns is 1 to 4.
        - conditions show a section or field only when another field matches: operator "in" or "not_in" with "values" (option values), or "checked" / "not_checked" for a checkbox or a filled field. Several conditions must all hold. Inside a repeating group, a condition can watch a field of the same group by its key.
        - A select depends on another select with settings.parent (its key); each option then has "parent": the parent option's value.
        - A repeating group (type "repeater") holds "fields"; groups can't be nested.
        - Only use these field types and settings:
        {$types}
        - {$text}
        - Ask only what the description needs, in a sensible order, with clear labels and help where it helps. Mark fields required when the description implies it.
        TEXT;
    }

    protected function describeType(InputType $type): string
    {
        $settings = array_keys($type->settingsRules() + $type->localizedSettings());

        if ($type->hasOptions()) {
            $settings[] = 'options';
        }

        $notes = [];

        if (! $type->takesInput()) {
            $notes[] = 'display only, its text in settings.content';
        }

        if ($type->hasFields()) {
            $notes[] = 'holds fields';
        }

        return "  - {$type::key()} ({$type->name()})"
            . ($settings ? ': settings ' . implode(', ', array_unique($settings)) : '')
            . ($notes ? '; ' . implode('; ', $notes) : '');
    }
}
