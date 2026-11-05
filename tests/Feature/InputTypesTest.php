<?php

namespace Javaabu\FormBuilder\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\Builder\BuilderData;
use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\DefinitionValidator;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Exceptions\FormBuilderException;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\InputTypes\TextType;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Tests\TestSupport\InputTypes\RatingType;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\TextInput;
use PHPUnit\Framework\Attributes\Test;

class InputTypesTest extends TestCase
{
    protected function feedback(): FormDefinition
    {
        return FormDefinition::make('feedback')->sections([
            Section::make('main')->fields([
                Field::text('comment')->label('Comment'),
                Field::make('rating', 'stars')->label('Stars')->setting('max', 3)->required(),
            ]),
        ]);
    }

    #[Test]
    public function it_has_the_built_in_types(): void
    {
        $this->assertEquals(
            ['text', 'textarea', 'email', 'number', 'phone', 'url', 'select', 'radio', 'checkboxes', 'checkbox', 'date', 'time', 'datetime', 'file', 'heading', 'paragraph', 'repeater'],
            array_keys(FormBuilder::inputTypes()),
        );
    }

    #[Test]
    public function apps_add_their_own_types(): void
    {
        FormBuilder::registerInputType(RatingType::class);

        $this->feedback()->sync();

        $form = FormBuilder::fill('feedback');

        $this->assertEquals(3, $form->getField('stars')->toNode()->meta['stars']);

        try {
            $form->validate(['stars' => 4]);
            $this->fail('Expected a validation error');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('stars', $exception->errors());
        }

        $entry = FormBuilder::submit('feedback', ['stars' => '2']);

        $this->assertEquals('★★', $entry->formatted('stars'));
        $this->assertEquals(1, FormEntry::query()->whereAnswer('stars', '>=', 2)->count());
    }

    #[Test]
    public function the_builder_offers_and_validates_custom_types(): void
    {
        FormBuilder::registerInputType(RatingType::class);

        $errors = app(DefinitionValidator::class)->errors(
            $this->feedback()->tap(fn ($definition) => $definition->getField('stars')->setting('max', 50))->toArray(),
        );

        $this->assertArrayHasKey('sections.0.fields.1.settings.max', $errors->messages());
        $this->assertContains('rating', array_column(collect(app(BuilderData::class)->types())->pluck('types')->flatten(1)->all(), 'key'));
    }

    #[Test]
    public function unknown_types_fail_clearly(): void
    {
        $this->expectException(FormBuilderException::class);
        $this->expectExceptionMessage('There\'s no form builder input type [rating]');

        FormBuilder::inputType('rating');
    }

    #[Test]
    public function built_in_types_can_be_changed(): void
    {
        FormBuilder::extendInputType('text', function (Component $component, Field $field, CompileContext $context) {
            return $component instanceof TextInput ? $component->rules(['alpha']) : $component;
        });

        $this->feedback()->tap(fn ($definition) => $definition->getSection('main')->fields([Field::text('comment')]))->sync();

        $this->expectException(ValidationException::class);

        FormBuilder::fill('feedback')->validate(['comment' => 'has spaces 123']);
    }

    #[Test]
    public function built_in_types_can_be_replaced_and_removed(): void
    {
        FormBuilder::registerInputType(new class extends TextType
        {
            public function name(): string
            {
                return 'Line';
            }
        });

        $this->assertEquals('Line', FormBuilder::inputType('text')->name());

        FormBuilder::forgetInputType('time');

        $this->assertFalse(FormBuilder::hasInputType('time'));
    }
}
