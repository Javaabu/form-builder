<?php

namespace Javaabu\FormBuilder\Tests\TestSupport\Seeders;

use Illuminate\Database\Seeder;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Facades\FormBuilder;

class FormsSeeder extends Seeder
{
    public function run(): void
    {
        FormBuilder::define('complaint')
            ->name(['en' => 'Complaint', 'dv' => 'ޝަކުވާ'])
            ->locked()
            ->wizard()
            ->confirmation(['en' => 'We\'ve received your complaint.', 'dv' => 'ތިޔަ ޝަކުވާ ލިބިއްޖެ.'])
            ->sections([
                Section::make('you')->title(['en' => 'About you', 'dv' => 'ތިޔަބޭފުޅާގެ މަޢުލޫމާތު'])->fields([
                    Field::text('name')->label(['en' => 'Name', 'dv' => 'ނަން'])->required()->thaana(),
                    Field::phone('phone')->label(['en' => 'Phone', 'dv' => 'ފޯނު'])->required(),
                ]),
                Section::make('complaint')->title(['en' => 'Your complaint', 'dv' => 'ޝަކުވާ'])->fields([
                    Field::textarea('details')->label(['en' => 'Details', 'dv' => 'ތަފްޞީލު'])->required()->thaana()->rows(5),
                    Field::file('evidence')->label(['en' => 'Evidence', 'dv' => 'ހެކި'])->multiple(),
                ]),
            ])
            ->sync();
    }
}
