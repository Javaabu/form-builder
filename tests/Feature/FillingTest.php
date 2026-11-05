<?php

namespace Javaabu\FormBuilder\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\Entries\EntryRecorder;
use Javaabu\FormBuilder\Events\EntrySubmitted;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Stencil\EntryForm;
use Javaabu\FormBuilder\Stencil\Repeater;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Tests\TestSupport\Forms\JobApplication;
use Javaabu\FormBuilder\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class FillingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        JobApplication::definition()->sync();
    }

    /**
     * @return array<string, mixed>
     */
    protected function validInput(array $overrides = []): array
    {
        return array_replace([
            'full_name' => 'އަޙްމަދު ނާޒިމް',
            'age' => '30',
            'applicant_type' => 'individual',
            'atoll' => 'K',
            'island' => 'male',
            'skills' => ['php', 'design'],
            'available_from' => '2026-12-01',
            'references' => [
                ['name' => 'މަރިޔަމް', 'relationship' => 'work'],
                ['name' => 'ޙަސަން', 'relationship' => 'other', 'relationship_other' => 'ކުރީގެ ޓީޗަރު'],
            ],
            'agree' => '1',
        ], $overrides);
    }

    #[Test]
    public function it_compiles_the_version_to_a_stencil_form(): void
    {
        $form = FormBuilder::fill('job-application');

        $this->assertInstanceOf(EntryForm::class, $form);
        $this->assertEquals('form-builder-job-application', $form->getId());
        $this->assertEquals('Apply', $form->getSubmitLabel());

        $this->assertNotNull($form->getField('full_name'));
        $this->assertInstanceOf(Repeater::class, $form->getField('references'));
        $this->assertTrue($form->getField('full_name')->isThaana());
        $this->assertNull($form->getField('references_note'));
    }

    #[Test]
    public function it_labels_fields_in_the_current_locale(): void
    {
        $this->app->setLocale('dv');

        $form = FormBuilder::fill('job-application');

        $this->assertEquals('ފުރިހަމަ ނަން', $form->getField('full_name')->getLabel());
        $this->assertEquals('ހުށަހަޅާ', $form->getSubmitLabel());
        $this->assertEquals(['individual' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް'], $form->getField('applicant_type')->getOptions());
    }

    #[Test]
    public function it_renders_with_blade(): void
    {
        $html = FormBuilder::fill('job-application')->toHtml();

        $this->assertStringContainsString('name="full_name"', $html);
        $this->assertStringContainsString('Personal details', $html);
        $this->assertStringContainsString('data-form-builder-repeater', $html);
        $this->assertStringContainsString('name="references[0][name]"', $html);
        $this->assertStringContainsString('name="references[__ROW__][name]"', $html);
        $this->assertStringContainsString('Give up to three people we can call.', $html);
        $this->assertStringContainsString('Add a reference', $html);
        $this->assertStringContainsString('Reference 1', $html);

        // The repeater's markup, for the Blade script's tests
        if (getenv('FORM_BUILDER_UPDATE_FIXTURES')) {
            preg_match('/<div class="form-builder-repeater".*?<x-end-repeater>/s', str_replace('<div class="form-builder-repeater-footer">', '<x-end-repeater>', $html), $matches);
            file_put_contents(dirname(__DIR__) . '/js/fixtures/repeater.html', ($matches[0] ?? '') ? str_replace('<x-end-repeater>', '<div class="form-builder-repeater-footer"><button type="button" data-form-builder-repeater-add>Add a reference</button></div></div>', $matches[0]) . "\n" : '');
        }
    }

    #[Test]
    public function it_renders_as_json_for_react(): void
    {
        $schema = FormBuilder::fill('job-application')->toArray();

        $this->assertEquals(1, $schema['stencil']);

        $references = collect($schema['components'][1]['children'])->firstWhere('key', 'references');

        $this->assertEquals('repeater', $references['type']);
        $this->assertEquals('references', $references['name']);
        $this->assertEquals(3, $references['props']['maxRows']);
        $this->assertEquals('__ROW__', $references['props']['rowPlaceholder']);
        $this->assertEquals('references[__ROW__][name]', $references['props']['template']['children'][0]['name']);
        $this->assertEquals('references[0][name]', $references['children'][0]['children'][0]['name']);

        $other = $references['props']['template']['children'][2];
        $this->assertEquals('#references-__ROW__-relationship', $other['conditions'][0]['selector']);
    }

    #[Test]
    public function it_validates_a_submission(): void
    {
        $data = FormBuilder::fill('job-application')->validate($this->validInput());

        $this->assertEquals('އަޙްމަދު ނާޒިމް', $data['full_name']);
        $this->assertEquals('ކުރީގެ ޓީޗަރު', $data['references'][1]['relationship_other']);
        $this->assertArrayNotHasKey('relationship_other', $data['references'][0]);
        $this->assertArrayNotHasKey('company_name', $data);
    }

    #[Test]
    public function it_requires_fields_shown_by_conditions(): void
    {
        try {
            FormBuilder::fill('job-application')->validate($this->validInput(['applicant_type' => 'company']));
            $this->fail('Expected a validation error');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('company_name', $exception->errors());
        }
    }

    #[Test]
    public function it_validates_each_row_of_a_repeating_group(): void
    {
        try {
            FormBuilder::fill('job-application')->validate($this->validInput([
                'references' => [
                    ['name' => 'މަރިޔަމް'],
                    ['name' => '', 'relationship' => 'nope'],
                ],
            ]));
            $this->fail('Expected a validation error');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            $this->assertArrayHasKey('references.1.name', $errors);
            $this->assertArrayHasKey('references.1.relationship', $errors);
            $this->assertArrayNotHasKey('references.0.name', $errors);
            $this->assertStringContainsString('Reference 2', $errors['references.1.name'][0]);
        }
    }

    #[Test]
    public function it_limits_the_rows_of_a_repeating_group(): void
    {
        try {
            FormBuilder::fill('job-application')->validate($this->validInput([
                'references' => array_fill(0, 4, ['name' => 'މަރިޔަމް']),
            ]));
            $this->fail('Expected a validation error');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('references', $exception->errors());
        }
    }

    #[Test]
    public function it_validates_cascading_selects_against_the_parent(): void
    {
        try {
            FormBuilder::fill('job-application')->validate($this->validInput(['atoll' => 'S', 'island' => 'male']));
            $this->fail('Expected a validation error');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('island', $exception->errors());
        }

        $data = FormBuilder::fill('job-application')->validate($this->validInput(['atoll' => 'S', 'island' => 'hithadhoo']));

        $this->assertEquals('hithadhoo', $data['island']);
    }

    #[Test]
    public function it_stores_a_submission_as_an_entry_of_the_version(): void
    {
        Event::fake([EntrySubmitted::class]);

        $user = User::query()->create(['name' => 'Ahmed', 'email' => 'ahmed@example.com', 'password' => 'secret']);

        $entry = FormBuilder::submit('job-application', $this->validInput(), submitter: $user);

        $this->assertInstanceOf(FormEntry::class, $entry);
        $this->assertTrue($entry->isSubmitted());
        $this->assertEquals(FormBuilder::form('job-application')->published_version_id, $entry->form_version_id);
        $this->assertTrue($entry->submitter->is($user));
        $this->assertSame(30, $entry->answer('age'));
        $this->assertTrue($entry->answer('agree'));
        $this->assertEquals('ޙަސަން', $entry->answer('references.1.name'));
        $this->assertEquals(['php', 'design'], $entry->answer('skills'));
        $this->assertEquals('PHP, Design', $entry->formatted('skills'));
        $this->assertEquals('Other', $entry->formatted('references.1.relationship'));

        Event::assertDispatched(EntrySubmitted::class, fn (EntrySubmitted $event) => $event->entry->is($entry));
    }

    #[Test]
    public function it_keeps_entries_on_the_version_they_were_filled_with(): void
    {
        $entry = FormBuilder::submit('job-application', $this->validInput());

        $definition = JobApplication::definition();
        $definition->getSection('personal')->fields([]);
        $definition->sync();

        $entry->refresh();

        $this->assertEquals(1, $entry->version->number);
        $this->assertNotNull($entry->version->definition()->getField('full_name'));
        $this->assertEquals('އަޙްމަދު ނާޒިމް', $entry->formatted('full_name'));
    }

    #[Test]
    public function it_indexes_answers_for_queries(): void
    {
        FormBuilder::submit('job-application', $this->validInput());
        FormBuilder::submit('job-application', $this->validInput(['age' => '17', 'skills' => ['js'], 'references' => []]));

        $this->assertEquals(1, FormEntry::query()->whereAnswer('age', '>=', 18)->count());
        $this->assertEquals(2, FormEntry::query()->whereAnswer('age', '>', 10)->count());
        $this->assertEquals(1, FormEntry::query()->whereAnswer('skills', 'php')->count());
        $this->assertEquals(1, FormEntry::query()->whereAnswer('references.relationship', 'other')->count());
        $this->assertEquals(1, FormEntry::query()->whereAnswer('full_name', 'އަޙްމަދު ނާޒިމް')->where('id', 1)->count());

        $paths = FormEntry::query()->first()->values->pluck('path')->all();

        $this->assertContains('skills.1', $paths);
        $this->assertContains('references.1.relationship_other', $paths);
    }

    #[Test]
    public function it_updates_an_entry(): void
    {
        $entry = FormBuilder::submit('job-application', $this->validInput());

        app(EntryRecorder::class)->update($entry, $this->validInput(['age' => '45']));

        $this->assertSame(45, $entry->refresh()->answer('age'));
        $this->assertEquals(1, FormEntry::query()->whereAnswer('age', 45)->count());
    }
}
