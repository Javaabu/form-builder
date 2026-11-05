<?php

namespace Javaabu\FormBuilder\Entries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Events\EntrySubmitted;
use Javaabu\FormBuilder\Events\EntryUpdated;
use Javaabu\FormBuilder\FormBuilderManager;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\FormBuilder\Stencil\EntryForm;

/**
 * Validates submissions with the version's stencil form and stores them as
 * entries, with their answers indexed in form_entry_values
 */
class EntryRecorder
{
    public function __construct(
        protected FormBuilderManager $builder,
    ) {}

    /**
     * @param  Request|array<string, mixed>  $input
     */
    public function submit(
        EntryForm $stencil,
        FormVersion $version,
        Request|array $input,
        ?Model $submitter = null,
        ?Model $subject = null,
        string $status = FormEntry::SUBMITTED,
    ): FormEntry {
        $answers = $this->answers($version->definition(), $stencil->validate($input));

        $entry = DB::transaction(function () use ($version, $answers, $submitter, $subject, $status, $input) {
            /** @var FormEntry $entry */
            $entry = $this->builder->model('entry')::make([
                'status' => $status,
                'data' => $answers,
                'locale' => app()->getLocale(),
                'ip_address' => $input instanceof Request && config('form-builder.entries.store_ip', false) ? $input->ip() : null,
                'submitted_at' => $status === FormEntry::SUBMITTED ? now() : null,
            ]);

            $entry->form()->associate($version->form_id);
            $entry->version()->associate($version);

            if ($submitter) {
                $entry->submitter()->associate($submitter);
            }

            if ($subject) {
                $entry->subject()->associate($subject);
            }

            $entry->save();

            $this->index($entry, $version->definition());

            return $entry;
        });

        EntrySubmitted::dispatch($entry);

        return $entry;
    }

    /**
     * Change an entry's answers. It keeps its version.
     *
     * @param  Request|array<string, mixed>  $input
     */
    public function update(FormEntry $entry, Request|array $input, ?EntryForm $stencil = null): FormEntry
    {
        $version = $entry->version;
        $stencil ??= EntryForm::for($version)->model($entry->answers());

        $answers = $this->answers($version->definition(), $stencil->validate($input));

        DB::transaction(function () use ($entry, $answers, $version) {
            $entry->data = $answers;

            if (! $entry->isSubmitted() && $entry->status === FormEntry::DRAFT) {
                $entry->submitted_at = null;
            }

            $entry->save();

            $this->index($entry, $version->definition());
        });

        EntryUpdated::dispatch($entry);

        return $entry;
    }

    /**
     * The validated data as answers: every field that takes one, in order, normalized by its type
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function answers(FormDefinition $definition, array $data): array
    {
        return $this->answersFor($definition->getFields(), $data);
    }

    /**
     * @param  array<int, Field>  $fields
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function answersFor(array $fields, array $data): array
    {
        $answers = [];

        foreach ($fields as $field) {
            $type = $this->builder->inputType($field->getType());

            if (! $type->takesInput() || ! array_key_exists($field->getKey(), $data)) {
                continue;
            }

            $value = $data[$field->getKey()];

            if ($type->hasFields()) {
                $answers[$field->getKey()] = array_values(array_map(
                    fn ($row) => $this->answersFor($field->getFields(), (array) $row),
                    is_array($value) ? $value : [],
                ));

                continue;
            }

            $answers[$field->getKey()] = $type->normalize($value, $field);
        }

        return $answers;
    }

    /**
     * Index an entry's answers in form_entry_values
     */
    public function index(FormEntry $entry, FormDefinition $definition): void
    {
        $entry->values()->delete();

        $rows = [];
        $now = now();

        $this->collect($rows, $definition->getFields(), $entry->answers(), '', '');

        foreach ($rows as &$row) {
            $row['form_entry_id'] = $entry->getKey();
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->builder->model('value')::query()->insert($chunk);
        }

        $entry->unsetRelation('values');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, Field>  $fields
     * @param  array<string, mixed>  $answers
     */
    protected function collect(array &$rows, array $fields, array $answers, string $key_prefix, string $path_prefix): void
    {
        foreach ($fields as $field) {
            $type = $this->builder->inputType($field->getType());

            if (! $type->takesInput()) {
                continue;
            }

            $value = $answers[$field->getKey()] ?? null;
            $key = $key_prefix . $field->getKey();
            $path = $path_prefix . $field->getKey();

            if ($type->hasFields()) {
                foreach (array_values(is_array($value) ? $value : []) as $index => $row) {
                    $this->collect($rows, $field->getFields(), (array) $row, "{$key}.", "{$path}.{$index}.");
                }

                continue;
            }

            foreach ($type->index($value, $field) as $index => $item) {
                $rows[] = [
                    'form_field_id' => $field->getId(),
                    'field_key' => $key,
                    'path' => $type->isMultiple($field) ? "{$path}.{$index}" : $path,
                    'value' => $item['value'] === null ? null : mb_substr($item['value'], 0, 65535),
                    'number' => $item['number'],
                    'date' => $item['date'],
                ];
            }
        }
    }
}
