<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Illuminate\Http\UploadedFile;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Tests\TestSupport\Forms\JobApplication;
use Javaabu\FormBuilder\Transfer\Exporter;
use PHPUnit\Framework\Attributes\Test;

class TransferTest extends TestCase
{
    use InteractsWithFormBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFormBuilder();
    }

    #[Test]
    public function it_exports_a_form_as_json(): void
    {
        $this->jobApplication();

        $response = $this->actingAs($this->user())->get('/forms/job-application/export');

        $response->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="job-application.json"');

        $export = json_decode($response->getContent(), true);

        $this->assertEquals(Exporter::FORMAT, $export['form_builder']);
        $this->assertEquals('job-application', $export['form']['key']);
        $this->assertEquals('ފުރިހަމަ ނަން', $export['form']['sections'][0]['fields'][0]['label']['dv']);
    }

    #[Test]
    public function it_imports_an_export_as_a_new_form(): void
    {
        $export = app(Exporter::class)->export($this->jobApplication());

        $this->actingAs($this->user())
            ->post('/forms/import', ['json' => json_encode($export), 'key' => 'job-application-copy'])
            ->assertRedirect('/forms/job-application-copy');

        $copy = FormBuilder::form('job-application-copy');

        $this->assertFalse($copy->isPublished());
        $this->assertEquals(
            FormBuilder::form('job-application')->publishedVersion->definition()->structure(),
            $copy->draftVersion->definition()->structure(),
        );
    }

    #[Test]
    public function importing_a_form_that_exists_makes_a_draft(): void
    {
        $form = $this->jobApplication();
        $export = app(Exporter::class)->export($form);
        $export['form']['sections'] = array_slice($export['form']['sections'], 0, 1);
        $export['form']['sections'][0]['fields'] = array_values(array_filter($export['form']['sections'][0]['fields'], fn ($field) => $field['key'] === 'full_name'));

        $file = UploadedFile::fake()->createWithContent('job-application.json', json_encode($export));

        $this->actingAs($this->user())->post('/forms/import', ['file' => $file])->assertRedirect('/forms/job-application');

        $form->refresh();

        $this->assertEquals(1, $form->publishedVersion->number);
        $this->assertCount(1, $form->draftVersion->definition()->getFields());
    }

    #[Test]
    public function it_refuses_invalid_imports(): void
    {
        $this->actingAs($this->user())->post('/forms/import', ['json' => 'not json'])->assertSessionHasErrors('file');
        $this->actingAs($this->user())->post('/forms/import', ['json' => '{"form": {}}'])->assertSessionHasErrors('file');
        $this->actingAs($this->user())->post('/forms/import', ['json' => '{"form_builder": 99, "form": {"key": "x"}}'])->assertSessionHasErrors('file');

        $this->actingAs($this->user())
            ->post('/forms/import', ['json' => json_encode(['form_builder' => 1, 'form' => ['key' => 'x', 'sections' => [['key' => 'a', 'fields' => [['key' => 'b', 'type' => 'nope']]]]]])])
            ->assertSessionHasErrors('file');

        $this->assertEquals(0, FormBuilder::model('form')::query()->count());
    }

    #[Test]
    public function locked_forms_cant_be_imported_over(): void
    {
        $form = JobApplication::definition()->locked()->sync();

        $this->actingAs($this->user())
            ->post('/forms/import', ['json' => app(Exporter::class)->toJson($form)])
            ->assertSessionHasErrors('key');
    }
}
