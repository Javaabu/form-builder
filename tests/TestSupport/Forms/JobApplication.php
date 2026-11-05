<?php

namespace Javaabu\FormBuilder\Tests\TestSupport\Forms;

use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;

/**
 * A form with every structural feature, used across the tests
 */
class JobApplication
{
    public static function definition(): FormDefinition
    {
        return FormDefinition::make('job-application')
            ->name(['en' => 'Job application', 'dv' => 'ވަޒީފާއަށް އެދޭ ފޯމު'])
            ->description('Apply for a position')
            ->submitLabel(['en' => 'Apply', 'dv' => 'ހުށަހަޅާ'])
            ->sections([
                Section::make('personal')
                    ->title(['en' => 'Personal details', 'dv' => 'ފަރުދީ މަޢުލޫމާތު'])
                    ->columns(2)
                    ->fields([
                        Field::text('full_name')->label(['en' => 'Full name', 'dv' => 'ފުރިހަމަ ނަން'])->required()->thaana(),
                        Field::number('age')->label('Age')->integer()->min(16)->max(100),
                        Field::select('applicant_type')->label('Applicant type')->required()->options([
                            'individual' => ['en' => 'Individual', 'dv' => 'ފަރުދެއް'],
                            'company' => ['en' => 'Company', 'dv' => 'ކުންފުންޏެއް'],
                        ]),
                        Field::text('company_name')->label('Company name')->required()->visibleWhen('applicant_type', 'company'),
                        Field::select('atoll')->label('Atoll')->options(['K' => 'Kaafu', 'S' => 'Addu']),
                        Field::select('island')->label('Island')->dependsOn('atoll')->options([
                            ['value' => 'male', 'label' => 'Malé', 'parent' => 'K'],
                            ['value' => 'hulhumale', 'label' => 'Hulhumalé', 'parent' => 'K'],
                            ['value' => 'hithadhoo', 'label' => 'Hithadhoo', 'parent' => 'S'],
                        ]),
                        Field::checkboxes('skills')->label('Skills')->options(['php' => 'PHP', 'js' => 'JavaScript', 'design' => 'Design']),
                        Field::date('available_from')->label('Available from'),
                    ]),
                Section::make('references')
                    ->title('References')
                    ->fields([
                        Field::paragraph('references_note')->content('Give up to three people we can call.'),
                        Field::repeater('references')->label('References')->maxRows(3)->addLabel('Add a reference')->setting('row_label', 'Reference')->fields([
                            Field::text('name')->label('Name')->required()->thaana(),
                            Field::select('relationship')->label('Relationship')->options(['work' => 'Work', 'other' => 'Other']),
                            Field::text('relationship_other')->label('Describe the relationship')->visibleWhen('relationship', 'other'),
                        ]),
                        Field::checkbox('agree')->label('I agree to be contacted')->required(),
                    ]),
            ]);
    }
}
