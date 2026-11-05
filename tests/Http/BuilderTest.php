<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BuilderTest extends TestCase
{
    use InteractsWithFormBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFormBuilder();
    }

    #[Test]
    public function it_lists_forms(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())
            ->get('/forms')
            ->assertOk()
            ->assertSee('Job application')
            ->assertSee('job-application')
            ->assertSee('Published');

        $this->actingAs($this->user())
            ->getJson('/forms?search=nothing')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_creates_a_form_with_a_first_section(): void
    {
        $this->actingAs($this->user())->get('/forms/create')->assertOk()->assertSee('name="key"', false);

        $this->actingAs($this->user())
            ->post('/forms', ['key' => 'feedback', 'name' => 'ފީޑްބެކް', 'layout' => 'sections', 'accepts_entries' => '1'])
            ->assertRedirect('/forms/feedback');

        $form = FormBuilder::form('feedback');

        $this->assertEquals('ފީޑްބެކް', $form->name);
        $this->assertFalse($form->isPublished());
        $this->assertCount(1, $form->draftVersion->definition()->getSections());
    }

    #[Test]
    public function it_refuses_a_key_another_form_has(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())
            ->post('/forms', ['key' => 'job-application', 'name' => 'Again', 'layout' => 'sections'])
            ->assertSessionHasErrors('key');

        $this->actingAs($this->user())
            ->post('/forms', ['key' => 'Not A Key', 'name' => 'Again', 'layout' => 'sections'])
            ->assertSessionHasErrors('key');
    }

    #[Test]
    public function it_shows_the_builder(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())
            ->get('/forms/job-application')
            ->assertOk()
            ->assertSee('Personal details')
            ->assertSee('Full name')
            ->assertSee('references.name')
            ->assertSee('Applicant type is one of Company')
            ->assertSee('data-form-builder-sortable', false);
    }

    #[Test]
    public function it_adds_a_field_to_a_draft(): void
    {
        $form = $this->jobApplication();

        $this->actingAs($this->user())
            ->get('/forms/job-application/sections/personal/fields/create?type=email')
            ->assertOk()
            ->assertSee('name="key"', false)
            ->assertSee('value="email"', false);

        $this->actingAs($this->user())
            ->post('/forms/job-application/sections/personal/fields?type=email', [
                'key' => 'email',
                'label' => ['en' => 'އީމެއިލް'],
                'required' => '1',
                'section' => 'personal',
            ])
            ->assertRedirect('/forms/job-application');

        $form->refresh();

        $this->assertEquals(1, $form->publishedVersion->number);
        $this->assertNull($form->publishedVersion->definition()->getField('email'));

        $field = $form->draftVersion->definition()->getField('email');

        $this->assertEquals('އީމެއިލް', $field->getLabel());
        $this->assertTrue($field->isRequired());
    }

    #[Test]
    public function it_adds_a_field_to_a_repeating_group(): void
    {
        $form = $this->jobApplication();

        $this->actingAs($this->user())
            ->post('/forms/job-application/sections/references/fields?type=phone&group=references', [
                'key' => 'phone',
                'label' => ['en' => 'Phone'],
            ])
            ->assertRedirect();

        $this->assertNotNull($form->refresh()->draftVersion->definition()->getField('references.phone'));
    }

    #[Test]
    public function it_refuses_duplicate_field_keys(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())
            ->post('/forms/job-application/sections/personal/fields?type=text', ['key' => 'full_name', 'label' => ['en' => 'Again'], 'section' => 'personal'])
            ->assertSessionHasErrors('key');
    }

    #[Test]
    public function it_edits_a_field_with_options_and_conditions(): void
    {
        $form = $this->jobApplication();

        $this->actingAs($this->user())
            ->get('/forms/job-application/fields/applicant_type/edit')
            ->assertOk()
            ->assertSee('options[0][value]', false);

        $this->actingAs($this->user())
            ->patch('/forms/job-application/fields/company_name', [
                'key' => 'organisation',
                'label' => ['en' => 'Organisation'],
                'required' => '1',
                'section' => 'personal',
                'conditions' => [['field' => 'applicant_type', 'operator' => 'in', 'values' => 'company, individual']],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/forms/job-application');

        $definition = $form->refresh()->draftVersion->definition();
        $field = $definition->getField('organisation');

        $this->assertNull($definition->getField('company_name'));
        $this->assertEquals(['company', 'individual'], $field->getConditions()[0]->values);

        $this->actingAs($this->user())
            ->patch('/forms/job-application/fields/applicant_type', [
                'key' => 'kind',
                'label' => ['en' => 'Kind'],
                'section' => 'personal',
                'options' => [['value' => 'individual', 'label' => ['en' => 'Individual']], ['value' => 'company', 'label' => ['en' => 'Company', 'dv' => 'ކުންފުނި']]],
            ])
            ->assertSessionHasNoErrors();

        $definition = $form->refresh()->draftVersion->definition();

        $this->assertEquals('kind', $definition->getField('organisation')->getConditions()[0]->field);
        $this->assertCount(2, $definition->getField('kind')->getOptions());
    }

    #[Test]
    public function it_refuses_conditions_on_fields_that_dont_exist(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())
            ->patch('/forms/job-application/fields/company_name', [
                'key' => 'company_name',
                'label' => ['en' => 'Company name'],
                'section' => 'personal',
                'conditions' => [['field' => 'nope', 'operator' => 'in', 'values' => 'x']],
            ])
            ->assertSessionHasErrors('conditions.0.field');
    }

    #[Test]
    public function it_moves_orders_and_removes_fields_and_sections(): void
    {
        $form = $this->jobApplication();
        $user = $this->user();

        $this->actingAs($user)->post('/forms/job-application/fields/age/move', ['direction' => 'up'])->assertRedirect();
        $keys = fn () => array_map(fn ($field) => $field->getKey(), $form->refresh()->draftVersion->definition()->getSection('personal')->getFields());
        $this->assertEquals('age', $keys()[0]);

        $this->actingAs($user)->putJson('/forms/job-application/sections/personal/fields/order', ['fields' => ['skills', 'full_name']])->assertOk();
        $this->assertEquals(['skills', 'full_name'], array_slice($keys(), 0, 2));

        $this->actingAs($user)->post('/forms/job-application/fields/skills/move', ['section' => 'references'])->assertRedirect();
        $this->assertNotContains('skills', $keys());

        $this->actingAs($user)->putJson('/forms/job-application/sections/order', ['sections' => ['references', 'personal']])->assertOk();
        $this->assertEquals('references', $form->refresh()->draftVersion->definition()->getSections()[0]->getKey());

        $this->actingAs($user)->delete('/forms/job-application/fields/references.relationship_other')->assertRedirect();
        $this->assertNull($form->refresh()->draftVersion->definition()->getField('references.relationship_other'));

        $this->actingAs($user)->delete('/forms/job-application/sections/references')->assertRedirect();
        $this->assertCount(1, $form->refresh()->draftVersion->definition()->getSections());

        $this->actingAs($user)->delete('/forms/job-application/sections/personal')->assertSessionHasErrors('section');
    }

    #[Test]
    public function removing_a_watched_field_is_refused(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())
            ->delete('/forms/job-application/fields/applicant_type')
            ->assertSessionHasErrors('structure');
    }

    #[Test]
    public function it_adds_and_edits_sections(): void
    {
        $form = $this->jobApplication();

        $this->actingAs($this->user())->get('/forms/job-application/sections/create')->assertOk();

        $this->actingAs($this->user())
            ->post('/forms/job-application/sections', ['key' => 'extra', 'title' => ['en' => 'Extra', 'dv' => 'ތަފްޞީލު'], 'columns' => '2'])
            ->assertRedirect('/forms/job-application');

        $this->actingAs($this->user())
            ->patch('/forms/job-application/sections/extra', [
                'key' => 'extra',
                'title' => ['en' => 'More'],
                'columns' => '1',
                'conditions' => [['field' => 'agree', 'operator' => 'checked']],
            ])
            ->assertSessionHasNoErrors();

        $section = $form->refresh()->draftVersion->definition()->getSection('extra');

        $this->assertEquals('More', $section->getTitle());
        $this->assertEquals('checked', $section->getConditions()[0]->operator);
    }

    #[Test]
    public function it_publishes_and_discards_drafts(): void
    {
        $form = $this->jobApplication();
        $user = $this->user();

        $this->actingAs($user)->post('/forms/job-application/sections', ['key' => 'extra', 'title' => ['en' => 'Extra'], 'columns' => '1']);

        $this->actingAs($user)->get('/forms/job-application')->assertSee('version 1 until you publish', false);

        $this->actingAs($user)->delete('/forms/job-application/draft')->assertRedirect();
        $this->assertFalse($form->refresh()->hasDraft());

        $this->actingAs($user)->post('/forms/job-application/sections', ['key' => 'extra', 'title' => ['en' => 'Extra'], 'columns' => '1']);
        $this->actingAs($user)->post('/forms/job-application/publish')->assertRedirect();

        $this->assertEquals(2, $form->refresh()->publishedVersion->number);
        $this->assertNotNull($form->publishedVersion->definition()->getSection('extra'));

        $this->actingAs($user)->post('/forms/job-application/publish')->assertSessionHasErrors('form');
    }

    #[Test]
    public function it_saves_settings(): void
    {
        $form = $this->jobApplication();

        $this->actingAs($this->user())->get('/forms/job-application/edit')->assertOk()->assertSee('Job application');

        $this->actingAs($this->user())
            ->patch('/forms/job-application', [
                'key' => 'job-application',
                'name' => 'Jobs',
                'layout' => 'wizard',
                'submit_label' => ['en' => 'Send'],
                'confirmation' => ['en' => 'We\'ll call you.'],
            ])
            ->assertRedirect('/forms/job-application');

        $form->refresh();

        $this->assertEquals('Jobs', $form->name);
        $this->assertFalse($form->accepts_entries);
        $this->assertTrue($form->draftVersion->definition()->isWizard());
        $this->assertEquals('Send', $form->draftVersion->definition()->getSetting('submit_label'));
    }

    #[Test]
    public function it_replaces_the_structure_with_json(): void
    {
        $form = $this->jobApplication();

        $structure = $form->publishedVersion->definition()->toArray();
        $structure['sections'] = array_reverse($structure['sections']);

        $this->actingAs($this->user())
            ->putJson('/forms/job-application/structure', ['structure' => $structure])
            ->assertOk()
            ->assertJsonPath('structure.sections.0.key', 'references');

        $structure['sections'][0]['fields'][0]['type'] = 'nope';

        $this->actingAs($this->user())
            ->putJson('/forms/job-application/structure', ['structure' => $structure])
            ->assertUnprocessable();
    }

    #[Test]
    public function it_previews_without_saving(): void
    {
        $form = $this->jobApplication();

        $this->actingAs($this->user())->get('/forms/job-application/preview')->assertOk()->assertSee('name="full_name"', false);

        $this->actingAs($this->user())->post('/forms/job-application/preview', [])->assertSessionHasErrors('full_name');

        $this->assertEquals(0, $form->entries()->count());
    }

    #[Test]
    public function it_lists_versions(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())->get('/forms/job-application/versions')->assertOk()->assertSee('data-version="1"', false);
    }
}
