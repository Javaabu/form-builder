---
title: AI assistant
sidebar_position: 6
---

With [laravel/ai](https://github.com/laravel/ai) installed and configured, the builder has an assistant:
- on the forms list, it builds a new form from a description
- on a form, it changes the draft as described

It never publishes. It saves a draft for someone to review, preview and publish.

```bash
composer require laravel/ai
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"   # then set your provider's key, e.g. ANTHROPIC_API_KEY
```

Without laravel/ai, or with `FORM_BUILDER_ASSISTANT=false`, the assistant's routes and cards aren't there.

# How it works

1. **The prompt.** `Ai\FormAssistant` prompts `Ai\FormAssistantAgent`, a laravel/ai agent with structured output. Its instructions describe:
   - the [JSON format](json-format.md)
   - every registered input type with its settings (your own types too)
   - the rules for keys, conditions, cascading selects and repeating groups
   - the languages to write text in: each active language with javaabu/translatable, Thaana for Dhivehi

   When it changes a form, the prompt holds the current draft's export.
2. **The answer** is `{summary, form}`: a sentence for the person, and the whole form as an export.
3. **Validation.** The answer goes through the importer's validation. If it has errors, the agent gets them and one more try (`config('form-builder.assistant.attempts')`). If it still fails, the person sees "The assistant couldn't make a valid form", with the first errors.
4. **Saving.** A valid answer becomes the form's draft (a new form for a new key), like an import. The summary shows as the page's status.

| Route | Name | Body |
| --- | --- | --- |
| `POST forms/assistant` | `forms.assistant.create` | `description`: a new form; needs `create` |
| `POST forms/{form}/assistant` | `forms.assistant` | `description`: change its draft; needs `update` |

```php
// In code
$result = app(\Javaabu\FormBuilder\Ai\FormAssistant::class)->build('A leave request form with dates and a reason', $form, $user);
$result['form']; $result['summary'];
```

Configure the provider, model, timeout, attempts and the longest description under `form-builder.assistant` (see [Configuration](configuration.md)).

# Testing

Use laravel/ai's fakes; nothing leaves your app:

```php
use Javaabu\FormBuilder\Ai\FormAssistantAgent;
use Laravel\Ai\Prompts\AgentPrompt;

FormAssistantAgent::fake([
    ['summary' => 'A feedback form with a rating.', 'form' => json_encode(['form_builder' => 1, 'form' => [/* … */]])],
]);

$this->post('/forms/assistant', ['description' => 'A feedback form'])->assertRedirect('/forms/feedback');

FormAssistantAgent::assertPrompted(fn (AgentPrompt $prompt) => str_contains($prompt->prompt, 'A feedback form'));
```
