<?php

namespace Javaabu\FormBuilder\Commands;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\Transfer\Importer;

class ImportCommand extends Command
{
    protected $signature = 'form-builder:import
        {path : The exported JSON file}
        {--key= : Import under another key}
        {--publish : Publish the imported version}';

    protected $description = 'Import a form exported as JSON: a new form, or a new draft of the form with the same key';

    public function handle(Importer $importer): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error("There's no file at {$path}.");

            return self::FAILURE;
        }

        try {
            $form = $importer->import((string) file_get_contents($path), $this->option('key') ?: null, (bool) $this->option('publish'));
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->info($this->option('publish')
            ? "Imported and published {$form->key} (version {$form->publishedVersion->number})."
            : "Imported {$form->key} as a draft.");

        return self::SUCCESS;
    }
}
