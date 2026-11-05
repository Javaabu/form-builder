<?php

namespace Javaabu\FormBuilder\Commands;

use Illuminate\Console\Command;
use Javaabu\FormBuilder\Exceptions\FormBuilderException;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Transfer\Exporter;

class ExportCommand extends Command
{
    protected $signature = 'form-builder:export
        {form : The form\'s key}
        {path? : Where to write the JSON; prints it when left out}
        {--draft : Export the draft instead of the published version}';

    protected $description = 'Export a form\'s structure as JSON';

    public function handle(Exporter $exporter): int
    {
        try {
            $form = FormBuilder::form((string) $this->argument('form'));
        } catch (FormBuilderException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $json = $exporter->toJson($form, (bool) $this->option('draft'));
        $path = $this->argument('path');

        if (! $path) {
            $this->line($json);

            return self::SUCCESS;
        }

        file_put_contents($path, $json . "\n");

        $this->info("Exported {$form->key} to {$path}.");

        return self::SUCCESS;
    }
}
