<?php

namespace Javaabu\FormBuilder\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * The laravel/ai agent that writes forms: it gets a description (and the
 * current form, when editing one) and answers with the whole form in the
 * export format. Only loaded when laravel/ai is installed.
 *
 * Fake it in tests: FormAssistantAgent::fake([['summary' => '…', 'form' => '{…}']]).
 */
class FormAssistantAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        protected string $instructions,
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->instructions;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->description('One or two sentences on what you built or changed, in the language of the request.')->required(),
            'form' => $schema->string()->description('The whole form as JSON in the export format: {"form_builder": 1, "form": {...}}.')->required(),
        ];
    }
}
