<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Tests\TestSupport\Forms\JobApplication;
use PHPUnit\Framework\Attributes\Test;

class FillTest extends TestCase
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
    protected function answers(array $overrides = []): array
    {
        return array_replace([
            'full_name' => 'އަޙްމަދު ނާޒިމް',
            'applicant_type' => 'individual',
            'references' => [['name' => 'މަރިޔަމް', 'relationship' => 'work']],
            'agree' => '1',
        ], $overrides);
    }

    #[Test]
    public function guests_fill_a_published_form(): void
    {
        $this->jobApplication();

        $this->get('/f/job-application')
            ->assertOk()
            ->assertSee('Job application')
            ->assertSee('name="full_name"', false)
            ->assertSee('Apply');

        $this->post('/f/job-application', $this->answers())->assertRedirect('/f/job-application/thanks');

        $entry = FormEntry::query()->sole();

        $this->assertNull($entry->submitter);
        $this->assertEquals('އަޙްމަދު ނާޒިމް', $entry->answer('full_name'));

        $this->get('/f/job-application/thanks')->assertOk()->assertSee('Thank you');
    }

    #[Test]
    public function it_keeps_who_submitted(): void
    {
        $this->jobApplication();
        $user = $this->user([]);

        $this->actingAs($user)->post('/f/job-application', $this->answers())->assertRedirect();

        $this->assertTrue(FormEntry::query()->sole()->submitter->is($user));
    }

    #[Test]
    public function it_shows_validation_errors(): void
    {
        $this->jobApplication();

        $this->post('/f/job-application', $this->answers(['full_name' => '']))->assertSessionHasErrors('full_name');

        $this->postJson('/f/job-application', $this->answers(['references' => [['name' => '']]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('references.0.name');

        $this->assertEquals(0, FormEntry::query()->count());
    }

    #[Test]
    public function it_shows_the_confirmation_message(): void
    {
        $this->jobApplication();

        $form = JobApplication::definition()
            ->confirmation(['en' => 'We\'ll call you.', 'dv' => 'ގުޅާނަން.'])
            ->sync();

        $this->postJson('/f/job-application', $this->answers())->assertCreated()->assertJsonPath('message', 'We\'ll call you.');

        $this->app->setLocale('dv');

        $this->getJson('/f/job-application/thanks')->assertJsonPath('message', 'ގުޅާނަން.');
    }

    #[Test]
    public function closed_and_unpublished_forms_cant_be_filled(): void
    {
        $form = $this->jobApplication();
        $form->update(['accepts_entries' => false]);

        $this->get('/f/job-application')->assertOk()->assertSee('This form isn&#039;t accepting entries.', false);
        $this->post('/f/job-application', $this->answers())->assertForbidden();

        $this->jobApplication(publish: false);
        FormBuilder::define('draft-only')->sync(publish: false);

        $this->get('/f/draft-only')->assertNotFound();
        $this->get('/f/nope')->assertNotFound();
    }

    #[Test]
    public function it_serves_cascading_options(): void
    {
        $this->jobApplication();

        $html = $this->get('/f/job-application')->getContent();

        $this->assertStringContainsString('ajax-url="/f/job-application/options/island', $html);

        $this->getJson('/f/job-application/options/island?filter[parent]=K')
            ->assertOk()
            ->assertExactJson(['data' => [['id' => 'male', 'name' => 'Malé'], ['id' => 'hulhumale', 'name' => 'Hulhumalé']]]);

        $this->getJson('/f/job-application/options/island')->assertExactJson(['data' => []]);
        $this->getJson('/f/job-application/options/full_name?filter[parent]=K')->assertNotFound();
    }

    #[Test]
    public function it_fills_with_json(): void
    {
        $this->jobApplication();

        $this->getJson('/f/job-application')->assertOk()->assertJsonPath('open', true)->assertJsonPath('form.stencil', 1);
    }
}
