<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Models\Translatable\Form as TranslatableForm;
use Javaabu\FormBuilder\Support\Translations;
use Javaabu\FormBuilder\Tests\Concerns\InteractsWithTranslatable;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Transfer\Exporter;
use Javaabu\Translatable\Facades\Languages;
use Javaabu\Translatable\Translatable;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;

#[RequiresMethod(Translatable::class, 'enabled')]
class TranslatableTest extends TestCase
{
    use InteractsWithFormBuilder;
    use InteractsWithTranslatable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFormBuilder();
        $this->setUpLanguages(['en', 'dv']);
        FormBuilder::forgetModels();
    }

    #[Test]
    public function it_uses_the_translatable_form_model(): void
    {
        $this->assertTrue(Translations::enabled());
        $this->assertEquals(TranslatableForm::class, FormBuilder::model('form'));
        $this->assertEquals(['en', 'dv'], Translations::locales());
    }

    #[Test]
    public function synced_forms_keep_their_name_in_every_language(): void
    {
        $form = $this->jobApplication();

        $this->assertInstanceOf(TranslatableForm::class, $form);
        $this->assertEquals('Job application', $form->translate('name', 'en'));
        $this->assertEquals('ވަޒީފާއަށް އެދޭ ފޯމު', $form->translate('name', 'dv'));

        $export = app(Exporter::class)->export($form);

        $this->assertEquals(['en' => 'Job application', 'dv' => 'ވަޒީފާއަށް އެދޭ ފޯމު'], $export['form']['name']);
    }

    #[Test]
    public function the_builder_asks_for_every_language(): void
    {
        $this->jobApplication();

        $this->actingAs($this->user())
            ->get('/forms/job-application/edit')
            ->assertOk()
            ->assertSee('name="name[en]"', false)
            ->assertSee('name="name[dv]"', false)
            ->assertSee('ވަޒީފާއަށް އެދޭ ފޯމު');

        $this->actingAs($this->user())
            ->get('/forms/job-application/sections/personal/fields/create?type=text')
            ->assertOk()
            ->assertSee('name="label[en]"', false)
            ->assertSee('name="label[dv]"', false)
            ->assertSee('Label (ދިވެހި)');
    }

    #[Test]
    public function it_saves_names_and_labels_in_every_language(): void
    {
        $this->actingAs($this->user())
            ->post('/forms', [
                'key' => 'feedback',
                'name' => ['en' => 'Feedback', 'dv' => 'ފީޑްބެކް'],
                'layout' => 'sections',
                'accepts_entries' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/forms/feedback');

        $form = FormBuilder::form('feedback');

        $this->assertEquals('Feedback', $form->translate('name', 'en'));
        $this->assertEquals('ފީޑްބެކް', $form->translate('name', 'dv'));

        $this->actingAs($this->user())
            ->post('/forms/feedback/sections/section/fields?type=text', [
                'key' => 'comment',
                'label' => ['en' => 'Comment', 'dv' => 'ޚިޔާލު'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals(['en' => 'Comment', 'dv' => 'ޚިޔާލު'], $form->refresh()->draftVersion->definition()->getField('comment')->getLabel());

        $this->actingAs($this->user())->post('/forms/feedback/sections/section/fields?type=text', ['key' => 'other', 'label' => ['dv' => 'އެހެން']])
            ->assertSessionHasErrors('label.en');
    }

    #[Test]
    public function people_fill_the_form_in_their_language(): void
    {
        $this->jobApplication();

        Languages::setCurrentLocale('dv');
        $this->app->setLocale('dv');

        $this->get('/f/job-application')
            ->assertOk()
            ->assertSee('ވަޒީފާއަށް އެދޭ ފޯމު')
            ->assertSee('ފުރިހަމަ ނަން')
            ->assertSee('ހުށަހަޅާ');
    }

    #[Test]
    public function it_works_with_translatable_turned_off(): void
    {
        $this->withoutTranslatable(function () {
            FormBuilder::forgetModels();

            $this->assertFalse(Translations::enabled());
            $this->assertNotEquals(TranslatableForm::class, FormBuilder::model('form'));

            $form = $this->jobApplication();

            $this->assertEquals('Job application', $form->name);
        });
    }
}
