<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

/**
 * The Inertia pages and their props. With FORM_BUILDER_UPDATE_FIXTURES=1 it
 * writes the props to tests/js/fixtures, which the React page tests render.
 */
#[RequiresMethod(InertiaServiceProvider::class, 'register')]
class InertiaPagesTest extends TestCase
{
    use InteractsWithFormBuilder;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), InertiaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('form-builder.views', 'inertia');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFormBuilder();
        $this->travelTo('2026-11-06 10:00:00');
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function writeFixture(string $name, array $props): void
    {
        unset($props['errors']);

        if (getenv('FORM_BUILDER_UPDATE_FIXTURES')) {
            file_put_contents(
                __DIR__ . "/../js/fixtures/{$name}.json",
                json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }
    }

    protected function entry()
    {
        return FormBuilder::submit('job-application', [
            'full_name' => 'އަޙްމަދު ނާޒިމް',
            'applicant_type' => 'company',
            'company_name' => 'ޖާވާބު',
            'references' => [['name' => 'މަރިޔަމް', 'relationship' => 'work']],
            'agree' => '1',
        ]);
    }

    #[Test]
    public function it_renders_the_forms_list(): void
    {
        $this->jobApplication();

        $response = $this->actingAs($this->user())
            ->get('/forms')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('form-builder::Forms/Index')
                ->where('forms.0.key', 'job-application')
                ->where('forms.0.status', 'published')
                ->where('can.create', true)
                ->where('strings.title', 'Forms')
                ->etc());

        $this->writeFixture('forms-index', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_builder(): void
    {
        $this->jobApplication();
        $user = $this->user();

        $this->actingAs($user)->post('/forms/job-application/sections', ['key' => 'extra', 'title' => ['en' => 'Extra'], 'columns' => '1']);

        $response = $this->actingAs($user)
            ->get('/forms/job-application')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('form-builder::Forms/Show')
                ->where('form.status', 'changed')
                ->where('sections.0.key', 'personal')
                ->where('sections.0.fields.0.key', 'full_name')
                ->where('sections.1.fields.1.fields.0.path', 'references.name')
                ->where('sections.0.urls.order_fields', 'http://localhost/forms/job-application/sections/personal/fields/order')
                ->where('can.publish', true)
                ->etc());

        $this->writeFixture('forms-show', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_field_and_settings_pages(): void
    {
        $this->jobApplication();

        $response = $this->actingAs($this->user())
            ->get('/forms/job-application/fields/references/edit')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('form-builder::Fields/Edit')
                ->where('type.key', 'repeater')
                ->where('form.stencil', 1)
                ->etc());

        $this->writeFixture('fields-edit', $response->viewData('page')['props']);

        $this->actingAs($this->user())->get('/forms/job-application/edit')->assertInertia(fn (AssertableInertia $page) => $page->component('form-builder::Forms/Edit')->etc());
        $this->actingAs($this->user())->get('/forms/create')->assertInertia(fn (AssertableInertia $page) => $page->component('form-builder::Forms/Edit')->where('record', null)->etc());
        $this->actingAs($this->user())->get('/forms/import')->assertInertia(fn (AssertableInertia $page) => $page->component('form-builder::Forms/Import')->etc());
        $this->actingAs($this->user())->get('/forms/job-application/sections/personal/edit')->assertInertia(fn (AssertableInertia $page) => $page->component('form-builder::Sections/Edit')->etc());
        $this->actingAs($this->user())->get('/forms/job-application/versions')->assertInertia(fn (AssertableInertia $page) => $page->component('form-builder::Forms/Versions')->where('versions.0.number', 1)->etc());
        $this->actingAs($this->user())->get('/forms/job-application/preview')->assertInertia(fn (AssertableInertia $page) => $page->component('form-builder::Forms/Preview')->where('form.stencil', 1)->etc());
    }

    #[Test]
    public function it_renders_the_entries_pages(): void
    {
        $this->jobApplication();
        $entry = $this->entry();

        $response = $this->actingAs($this->user())
            ->get('/forms/job-application/entries')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('form-builder::Entries/Index')
                ->where('entries.0.id', $entry->id)
                ->where('entries.0.values.full_name', 'އަޙްމަދު ނާޒިމް')
                ->etc());

        $this->writeFixture('entries-index', $response->viewData('page')['props']);

        $response = $this->actingAs($this->user())
            ->get("/forms/job-application/entries/{$entry->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('form-builder::Entries/Show')
                ->where('display.embedded', true)
                ->etc());

        $this->writeFixture('entries-show', $response->viewData('page')['props']);
    }

    #[Test]
    public function it_renders_the_fill_pages(): void
    {
        $this->jobApplication();

        $response = $this->get('/f/job-application')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('form-builder::Fill/Show')
                ->where('open', true)
                ->where('form.submit', 'Apply')
                ->etc());

        $this->writeFixture('fill-show', $response->viewData('page')['props']);

        $this->get('/f/job-application/thanks')->assertInertia(fn (AssertableInertia $page) => $page->component('form-builder::Fill/Thanks')->etc());
    }
}
