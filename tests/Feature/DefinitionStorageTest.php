<?php

namespace Javaabu\FormBuilder\Tests\Feature;

use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Exceptions\FormBuilderException;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Tests\TestSupport\Forms\JobApplication;
use PHPUnit\Framework\Attributes\Test;

class DefinitionStorageTest extends TestCase
{
    #[Test]
    public function it_stores_a_definition_as_a_published_version(): void
    {
        $form = JobApplication::definition()->sync();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertEquals('job-application', $form->key);
        $this->assertEquals('Job application', $form->name);
        $this->assertTrue($form->isPublished());
        $this->assertCount(1, $form->versions);

        $version = $form->publishedVersion;

        $this->assertEquals(1, $version->number);
        $this->assertCount(2, $version->sections);
        $this->assertEqualsCanonicalizing(['full_name', 'age', 'applicant_type', 'company_name', 'atoll', 'island', 'skills', 'available_from', 'references_note', 'references', 'agree', 'name', 'relationship', 'relationship_other'], $version->fields->pluck('key')->all());
        $this->assertEquals(['name', 'relationship', 'relationship_other'], $version->fields->firstWhere('key', 'references')->children->pluck('key')->all());
    }

    #[Test]
    public function it_reads_back_the_same_definition(): void
    {
        $definition = JobApplication::definition();

        $form = $definition->sync();

        $stored = $form->publishedVersion->definition()->structure();

        $this->assertEquals($definition->structure(), $stored);
        $this->assertEquals('ފުރިހަމަ ނަން', $form->publishedVersion->definition()->getField('full_name')->getLabel()['dv']);
        $this->assertEquals('relationship', $form->publishedVersion->definition()->getField('references.relationship')->getKey());
    }

    #[Test]
    public function it_only_makes_a_new_version_when_the_structure_changes(): void
    {
        JobApplication::definition()->sync();
        JobApplication::definition()->sync();

        $form = FormBuilder::form('job-application');

        $this->assertCount(1, $form->versions);

        JobApplication::definition()->tap(fn (FormDefinition $definition) => $definition->getSection('personal')->field(Field::email('email')))->sync();

        $form->refresh();

        $this->assertCount(2, $form->versions);
        $this->assertEquals(2, $form->publishedVersion->number);
        $this->assertNotNull($form->publishedVersion->definition()->getField('email'));
        $this->assertNull($form->versions->first()->definition()->getField('email'));
    }

    #[Test]
    public function published_versions_cant_change(): void
    {
        $version = JobApplication::definition()->sync()->publishedVersion;

        $this->expectException(FormBuilderException::class);

        $version->sections->first()->update(['key' => 'changed']);
    }

    #[Test]
    public function published_versions_cant_be_given_a_new_structure(): void
    {
        $version = JobApplication::definition()->sync()->publishedVersion;

        $this->expectException(FormBuilderException::class);

        $version->replaceStructure(FormDefinition::make('job-application'));
    }

    #[Test]
    public function editing_a_published_form_copies_it_into_a_draft(): void
    {
        $form = JobApplication::definition()->sync();

        $draft = $form->draft();

        $this->assertTrue($draft->isDraft());
        $this->assertEquals(2, $draft->number);
        $this->assertEquals($form->publishedVersion->definition()->structure(), $draft->definition()->structure());
        $this->assertSame($draft->getKey(), $form->draft()->getKey());

        $draft->replaceStructure(FormDefinition::make('job-application')->sections([]));

        $this->assertEquals(1, $form->refresh()->publishedVersion->number);

        $form->publish();

        $this->assertEquals(2, $form->refresh()->publishedVersion->number);
        $this->assertFalse($form->hasDraft());
    }

    #[Test]
    public function drafts_can_be_discarded(): void
    {
        $form = JobApplication::definition()->sync();
        $form->draft();

        $form->discardDraft();

        $this->assertFalse($form->refresh()->hasDraft());
        $this->assertCount(1, $form->versions);
    }

    #[Test]
    public function locked_forms_have_no_draft(): void
    {
        $form = JobApplication::definition()->locked()->sync();

        $this->assertTrue($form->is_locked);

        $this->expectException(FormBuilderException::class);

        $form->draft();
    }

    #[Test]
    public function syncing_without_publishing_leaves_a_draft(): void
    {
        $form = JobApplication::definition()->sync(publish: false);

        $this->assertFalse($form->isPublished());
        $this->assertTrue($form->hasDraft());

        $form = JobApplication::definition()->sync();

        $this->assertTrue($form->isPublished());
        $this->assertCount(1, $form->versions);
    }
}
