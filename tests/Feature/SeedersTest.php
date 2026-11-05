<?php

namespace Javaabu\FormBuilder\Tests\Feature;

use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Tests\Http\InteractsWithFormBuilder;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Tests\TestSupport\Seeders\FormsSeeder;
use Javaabu\FormBuilder\Transfer\Exporter;
use PHPUnit\Framework\Attributes\Test;

class SeedersTest extends TestCase
{
    use InteractsWithFormBuilder;

    #[Test]
    public function seeders_define_forms_and_running_them_again_changes_nothing(): void
    {
        $this->seed(FormsSeeder::class);
        $this->seed(FormsSeeder::class);

        $form = FormBuilder::form('complaint');

        $this->assertTrue($form->is_locked);
        $this->assertTrue($form->isPublished());
        $this->assertCount(1, $form->versions);
        $this->assertTrue($form->publishedVersion->definition()->isWizard());

        $this->app->setLocale('dv');

        $this->assertEquals('ނަން', FormBuilder::fill('complaint')->getField('name')->getLabel());
    }

    #[Test]
    public function the_builder_can_be_turned_off(): void
    {
        $this->setUpFormBuilder();

        $this->seed(FormsSeeder::class);

        $this->actingAs($this->user())->get('/forms/complaint')->assertOk()->assertDontSee('/forms/complaint/sections/create');
    }

    #[Test]
    public function the_commands_export_and_import(): void
    {
        $this->seed(FormsSeeder::class);

        $path = sys_get_temp_dir() . '/form-builder-complaint.json';

        $this->artisan('form-builder:export', ['form' => 'complaint', 'path' => $path])->assertSuccessful();

        $this->assertEquals(Exporter::FORMAT, json_decode(file_get_contents($path), true)['form_builder']);

        $this->artisan('form-builder:import', ['path' => $path, '--key' => 'complaint-copy', '--publish' => true])
            ->expectsOutputToContain('Imported and published complaint-copy')
            ->assertSuccessful();

        $this->assertTrue(FormBuilder::form('complaint-copy')->isPublished());
        $this->assertFalse(FormBuilder::form('complaint-copy')->is_locked);

        $this->artisan('form-builder:import', ['path' => $path])->expectsOutputToContain('defined in code')->assertFailed();
        $this->artisan('form-builder:export', ['form' => 'nope'])->assertFailed();

        unlink($path);
    }
}
