<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Javaabu\FormBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BuilderOffTest extends TestCase
{
    use InteractsWithFormBuilder;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('form-builder.builder', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFormBuilder();
    }

    #[Test]
    public function without_the_builder_forms_come_from_code_and_entries_still_show(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())->get('/forms')->assertOk()->assertDontSee('New form');
        $this->actingAs($this->user())->get('/forms/job-application')->assertOk()->assertDontSee('data-form-builder-sortable', false);
        $this->actingAs($this->user())->get('/forms/create')->assertNotFound();
        $this->actingAs($this->user())->post('/forms/job-application/sections', [])->assertNotFound();
        $this->actingAs($this->user())->get('/forms/job-application/entries')->assertOk();
    }
}
