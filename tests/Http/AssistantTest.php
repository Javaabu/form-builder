<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Javaabu\FormBuilder\Ai\FormAssistant;
use Javaabu\FormBuilder\Ai\FormAssistantAgent;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Policies\FormPolicy;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Transfer\Exporter;
use Laravel\Ai\Promptable;
use Laravel\Ai\Prompts\AgentPrompt;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

#[RequiresMethod(Promptable::class, 'fake')]
class AssistantTest extends TestCase
{
    use InteractsWithFormBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFormBuilder();
    }

    /**
     * @return array<string, mixed>
     */
    protected function feedback(array $fields = []): array
    {
        return ['form_builder' => 1, 'form' => [
            'key' => 'feedback',
            'name' => 'ފީޑްބެކް',
            'layout' => 'sections',
            'sections' => [[
                'key' => 'main',
                'title' => 'Your feedback',
                'fields' => $fields ?: [
                    ['key' => 'rating', 'type' => 'radio', 'label' => 'Rating', 'required' => true, 'settings' => ['options' => [['value' => '1', 'label' => 'Bad'], ['value' => '5', 'label' => 'Good']]]],
                    ['key' => 'comment', 'type' => 'textarea', 'label' => 'ޚިޔާލު', 'settings' => ['thaana' => true]],
                ],
            ]],
        ]];
    }

    #[Test]
    public function it_is_available_with_laravel_ai(): void
    {
        $this->assertTrue(FormAssistant::available());

        $this->actingAs($this->user())->get('/forms')->assertSee('Build with the assistant');
        $this->jobApplication();
        $this->actingAs($this->user())->get('/forms/job-application')->assertSee('/forms/job-application/assistant', false);
    }

    #[Test]
    public function it_builds_a_new_form_from_a_description(): void
    {
        FormAssistantAgent::fake([
            ['summary' => 'A feedback form with a rating and a comment.', 'form' => json_encode($this->feedback())],
        ]);

        $this->actingAs($this->user())
            ->post('/forms/assistant', ['description' => 'A short feedback form: a rating and a comment in Dhivehi'])
            ->assertRedirect('/forms/feedback')
            ->assertSessionHas('status', 'A feedback form with a rating and a comment.');

        $form = FormBuilder::form('feedback');

        $this->assertFalse($form->isPublished());
        $this->assertEquals(['rating', 'comment'], array_map(fn ($field) => $field->getKey(), $form->draftVersion->definition()->getFields()));

        FormAssistantAgent::assertPrompted(fn (AgentPrompt $prompt) => str_contains($prompt->prompt, 'A short feedback form'));
    }

    #[Test]
    public function it_changes_a_forms_draft(): void
    {
        $form = $this->jobApplication();

        $export = app(Exporter::class)->export($form);
        $export['form']['sections'][0]['fields'][] = ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true];

        FormAssistantAgent::fake([['summary' => 'Added an email field.', 'form' => json_encode($export)]]);

        $this->actingAs($this->user())
            ->post('/forms/job-application/assistant', ['description' => 'Ask for an email address too'])
            ->assertRedirect('/forms/job-application');

        $form->refresh();

        $this->assertEquals(1, $form->publishedVersion->number);
        $this->assertNotNull($form->draftVersion->definition()->getField('email'));

        FormAssistantAgent::assertPrompted(fn (AgentPrompt $prompt) => str_contains($prompt->prompt, '"full_name"') && str_contains($prompt->prompt, 'Ask for an email address too'));
    }

    #[Test]
    public function it_shows_the_agent_its_errors_once(): void
    {
        FormAssistantAgent::fake([
            ['summary' => 'Oops', 'form' => json_encode($this->feedback([['key' => 'Bad Key', 'type' => 'nope']]))],
            ['summary' => 'Fixed.', 'form' => json_encode($this->feedback())],
        ]);

        $this->actingAs($this->user())
            ->post('/forms/assistant', ['description' => 'Feedback'])
            ->assertRedirect('/forms/feedback');

        FormAssistantAgent::assertPromptedTimes(2);
        FormAssistantAgent::assertPrompted(fn (AgentPrompt $prompt) => str_contains($prompt->prompt, 'Your last answer had these errors'));
    }

    #[Test]
    public function it_gives_up_on_answers_that_stay_invalid(): void
    {
        FormAssistantAgent::fake([
            ['summary' => 'Oops', 'form' => 'not json'],
            ['summary' => 'Oops', 'form' => 'still not json'],
        ]);

        $this->actingAs($this->user())
            ->post('/forms/assistant', ['description' => 'Feedback'])
            ->assertSessionHasErrors('description');

        $this->assertEquals(0, FormBuilder::model('form')::query()->count());
    }

    #[Test]
    public function it_tells_the_agent_the_types_and_rules(): void
    {
        $instructions = app(FormAssistant::class)->instructions();

        $this->assertStringContainsString('"form_builder": 1', $instructions);
        $this->assertStringContainsString('repeater (Repeating group)', $instructions);
        $this->assertStringContainsString('select (Dropdown): settings multiple, searchable, parent', $instructions);
    }

    #[Test]
    public function it_needs_the_edit_permission(): void
    {
        $this->jobApplication();

        FormAssistantAgent::fake();

        $user = $this->user([FormPolicy::VIEW]);

        $this->actingAs($user)->post('/forms/assistant', ['description' => 'x'])->assertForbidden();
        $this->actingAs($user)->post('/forms/job-application/assistant', ['description' => 'x'])->assertForbidden();

        FormAssistantAgent::assertNeverPrompted();
    }
}
