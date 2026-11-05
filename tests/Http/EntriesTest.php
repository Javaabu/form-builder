<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EntriesTest extends TestCase
{
    use InteractsWithFormBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFormBuilder();
    }

    protected function entry(array $overrides = []): FormEntry
    {
        return FormBuilder::submit('job-application', array_replace([
            'full_name' => 'އަޙްމަދު ނާޒިމް',
            'applicant_type' => 'company',
            'company_name' => 'ޖާވާބު',
            'skills' => ['php'],
            'references' => [['name' => 'މަރިޔަމް', 'relationship' => 'work'], ['name' => 'ޙަސަން']],
            'agree' => '1',
        ], $overrides));
    }

    #[Test]
    public function it_lists_entries_with_their_first_answers(): void
    {
        $this->jobApplication();
        $entry = $this->entry();

        $this->actingAs($this->user())
            ->get('/forms/job-application/entries')
            ->assertOk()
            ->assertSee('Full name')
            ->assertSee('އަޙްމަދު ނާޒިމް')
            ->assertSee('Company')
            ->assertSee("data-entry=\"{$entry->id}\"", false);
    }

    #[Test]
    public function it_shows_an_entry_read_only_and_printable(): void
    {
        $this->jobApplication();
        $entry = $this->entry();

        $this->actingAs($this->user())
            ->get("/forms/job-application/entries/{$entry->id}")
            ->assertOk()
            ->assertSee('ޖާވާބު')
            ->assertSee('Reference 2')
            ->assertDontSee('name="full_name"', false);

        $this->actingAs($this->user())
            ->get("/forms/job-application/entries/{$entry->id}/print")
            ->assertOk()
            ->assertSee('window.print()', false)
            ->assertSee('@media print', false)
            ->assertSee('ޖާވާބު');
    }

    #[Test]
    public function it_exports_entries_as_csv(): void
    {
        $this->jobApplication();
        $this->entry();

        $response = $this->actingAs($this->user())->get('/forms/job-application/entries/export');

        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Full name', $csv);
        $this->assertStringContainsString('References: Name', $csv);
        $this->assertStringContainsString('މަރިޔަމް | ޙަސަން', $csv);
        $this->assertStringContainsString('Company', $csv);
    }

    #[Test]
    public function it_deletes_entries(): void
    {
        $this->jobApplication();
        $entry = $this->entry();

        $this->actingAs($this->user())->delete("/forms/job-application/entries/{$entry->id}")->assertRedirect('/forms/job-application/entries');

        $this->assertSoftDeleted($entry);
    }

    #[Test]
    public function it_stores_and_serves_uploads(): void
    {
        Storage::fake('local');
        config(['form-builder.files.disk' => 'local']);

        FormDefinition::make('cv')->sections([
            Section::make('main')->fields([
                Field::text('name')->required(),
                Field::file('cv')->label('CV')->required()->setting('kind', 'pdf'),
            ]),
        ])->sync();

        $this->post('/f/cv', ['name' => 'Aisha', 'cv' => UploadedFile::fake()->create('aisha.pdf', 20, 'application/pdf')])->assertRedirect();

        $entry = FormEntry::query()->sole();
        $path = $entry->answer('cv');

        $this->assertStringStartsWith('form-builder/cv/', $path);
        Storage::disk('local')->assertExists($path);

        $this->actingAs($this->user())->get("/forms/cv/entries/{$entry->id}")->assertSee("/forms/cv/entries/{$entry->id}/files/cv", false);
        $this->actingAs($this->user())->get("/forms/cv/entries/{$entry->id}/files/cv")->assertOk()->assertDownload(basename($path));
        $this->actingAs($this->user())->get("/forms/cv/entries/{$entry->id}/files/name")->assertNotFound();

        $this->post('/f/cv', ['name' => 'Ali', 'cv' => UploadedFile::fake()->create('ali.exe', 20, 'application/octet-stream')])->assertSessionHasErrors('cv');
    }
}
