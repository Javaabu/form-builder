<?php

namespace Javaabu\FormBuilder\Entries;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormEntry;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A form's entries as CSV, one row per entry and one column per answer,
 * streamed so large forms don't fill memory
 */
class EntriesExport
{
    public function __construct(
        protected EntryColumns $columns,
    ) {}

    /**
     * The header row
     *
     * @param  array<string, string>  $columns
     * @return array<int, string>
     */
    public function headings(array $columns): array
    {
        return [__('Entry'), __('Submitted at'), __('Version'), __('Submitted by'), ...array_values($columns)];
    }

    /**
     * One entry's row
     *
     * @param  array<string, string>  $columns
     * @return array<int, string|int|float|null>
     */
    public function row(FormEntry $entry, array $columns): array
    {
        $submitter = $entry->submitter;

        return [
            $entry->getKey(),
            $entry->submitted_at?->toDateTimeString(),
            $entry->version->number,
            $submitter ? (string) ($submitter->getAttribute('name') ?? $submitter->getKey()) : null,
            ...array_map(fn (string $path) => $this->columns->value($entry, $path, export: true), array_keys($columns)),
        ];
    }

    /**
     * @param  Builder<FormEntry>  $query
     */
    public function download(Form $form, Builder $query, ?string $filename = null): StreamedResponse
    {
        $columns = $this->columns->for($form);
        $filename ??= $form->key . '-entries-' . now()->format('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($query, $columns) {
            $out = fopen('php://output', 'w');

            // So spreadsheets read UTF-8 (Thaana) correctly
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, $this->headings($columns), escape: '');

            $query->with(['version.form', 'submitter'])->chunkById(200, function ($entries) use ($out, $columns) {
                foreach ($entries as $entry) {
                    fputcsv($out, array_map(fn ($value) => $value ?? '', $this->row($entry, $columns)), escape: '');
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
