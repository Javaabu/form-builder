<?php

namespace Javaabu\FormBuilder\Tests\Feature;

use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Stencil\DisplayForm;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Tests\TestSupport\Forms\JobApplication;
use PHPUnit\Framework\Attributes\Test;

class DisplayTest extends TestCase
{
    protected function entry(array $overrides = [])
    {
        JobApplication::definition()->sync();

        return FormBuilder::submit('job-application', array_replace([
            'full_name' => 'އަޙްމަދު ނާޒިމް',
            'age' => '30',
            'applicant_type' => 'company',
            'company_name' => 'ޖާވާބު',
            'skills' => ['php', 'design'],
            'available_from' => '2026-12-01',
            'references' => [
                ['name' => 'މަރިޔަމް', 'relationship' => 'work'],
                ['name' => 'ޙަސަން', 'relationship' => 'other', 'relationship_other' => 'ކުރީގެ ޓީޗަރު'],
            ],
            'agree' => '1',
        ], $overrides));
    }

    #[Test]
    public function it_shows_the_answers_read_only(): void
    {
        $display = FormBuilder::display($this->entry());

        $this->assertInstanceOf(DisplayForm::class, $display);

        $html = $display->toHtml();

        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('<input', $html);
        $this->assertStringContainsString('އަޙްމަދު ނާޒިމް', $html);
        $this->assertStringContainsString('PHP, Design', $html);
        $this->assertStringContainsString('1 December 2026', $html);
        $this->assertStringContainsString('Reference 2', $html);
        $this->assertStringContainsString('ކުރީގެ ޓީޗަރު', $html);
        $this->assertStringContainsString('Yes', $html);
    }

    #[Test]
    public function it_leaves_out_fields_their_conditions_hid(): void
    {
        $html = FormBuilder::display($this->entry(['applicant_type' => 'individual', 'company_name' => null]))->toHtml();

        $this->assertStringNotContainsString('Company name', $html);

        $html = FormBuilder::display($this->entry())->toHtml();

        $this->assertStringContainsString('Company name', $html);
        $this->assertStringContainsString('ޖާވާބު', $html);
    }

    #[Test]
    public function it_shows_answers_in_dhivehi(): void
    {
        $entry = $this->entry();

        $this->app->setLocale('dv');

        $html = FormBuilder::display($entry)->toHtml();

        $this->assertStringContainsString('ފަރުދީ މަޢުލޫމާތު', $html);
        $this->assertStringContainsString('ކުންފުންޏެއް', $html);
    }

    #[Test]
    public function it_serializes_for_react(): void
    {
        $schema = FormBuilder::display($this->entry())->toArray();

        $this->assertTrue($schema['embedded']);
        $this->assertEquals('view', $schema['operation']);
        $this->assertEquals('text-entry', $schema['components'][0]['children'][0]['type']);
        $this->assertEquals('އަޙްމަދު ނާޒިމް', $schema['components'][0]['children'][0]['props']['value']);
    }
}
