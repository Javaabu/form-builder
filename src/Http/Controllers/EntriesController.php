<?php

namespace Javaabu\FormBuilder\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Javaabu\FormBuilder\Builder\BuilderData;
use Javaabu\FormBuilder\Entries\EntriesExport;
use Javaabu\FormBuilder\Entries\EntryColumns;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Http\Controllers\Concerns\InteractsWithForms;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormEntry;
use Javaabu\FormBuilder\Stencil\DisplayForm;
use Javaabu\FormBuilder\Support\Strings;
use Javaabu\FormBuilder\Support\Text;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A form's entries: the list, CSV export, one entry read-only and printable,
 * its files, and deleting it
 */
class EntriesController extends Controller
{
    use InteractsWithForms;

    public function __construct(
        protected BuilderData $data,
        protected EntryColumns $columns,
    ) {}

    public function index(Request $request, string $form): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'viewEntries', $model);

        $version = $request->filled('version') ? $model->versions()->where('number', $request->integer('version'))->first() : null;

        $entries = $this->query($request, $model)
            ->when($version, fn ($query) => $query->where('form_version_id', $version->getKey()))
            ->with(['version.form', 'submitter'])
            ->latest('id')
            ->paginate((int) config('form-builder.per_page', 20))
            ->withQueryString();

        $columns = array_slice($this->columns->for($model, $version), 0, (int) config('form-builder.entries.columns', 4), true);

        $items = collect($entries->items())->map(fn (FormEntry $entry) => $this->entryData($request, $model, $entry, $columns))->all();

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $items, 'columns' => $columns, 'meta' => ['current_page' => $entries->currentPage(), 'last_page' => $entries->lastPage(), 'total' => $entries->total()]]);
        }

        return $this->views()->render('entries.index', $this->page($request, [
            'record' => $this->data->form($request, $model),
            'entries' => $items,
            'columns' => $columns,
            'versions' => $model->versions()->reorder()->whereNotNull('published_at')->latest('number')->pluck('number')->all(),
            'version' => $version?->number,
            'pagination' => ['current_page' => $entries->currentPage(), 'last_page' => $entries->lastPage(), 'per_page' => $entries->perPage(), 'total' => $entries->total()],
            'can' => [
                'export' => $this->allowsFor($request, 'exportEntries', $model),
                'delete' => $this->allowsFor($request, 'deleteEntries', $model),
            ],
            'urls' => [
                'index' => $this->url($request, 'entries.index', $model->key),
                'export' => $this->url($request, 'entries.export', [$model->key, 'version' => $version?->number]),
                'form' => $this->url($request, 'show', $model->key),
            ],
            'strings' => Strings::entries(),
        ]));
    }

    public function export(Request $request, string $form): StreamedResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'exportEntries', $model);

        $query = $this->query($request, $model);

        if ($request->filled('version')) {
            $version = $model->versions()->where('number', $request->integer('version'))->first();

            $query->where('form_version_id', $version?->getKey() ?? 0);
        }

        return app(EntriesExport::class)->download($model, $query);
    }

    public function show(Request $request, string $form, string $entry): View|Responsable|JsonResponse
    {
        [$model, $record] = $this->findEntry($form, $entry);

        $this->authorizeFor($request, 'view', $record);

        $display = DisplayForm::forEntry($record);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'entry' => $this->entryData($request, $model, $record),
                'answers' => $record->answers(),
                'display' => $display->render('json'),
            ]);
        }

        return $this->views()->render('entries.show', $this->page($request, [
            'record' => $this->data->form($request, $model),
            'entry' => $this->entryData($request, $model, $record),
            'display' => $this->views()->isInertia() ? $display->render('json') : $display,
            'files' => $this->files($request, $model, $record),
            'can' => ['delete' => $this->allowsFor($request, 'delete', $record)],
            'urls' => [
                'index' => $this->url($request, 'entries.index', $model->key),
                'print' => $this->url($request, 'entries.print', [$model->key, $record->getKey()]),
                'destroy' => $this->url($request, 'entries.destroy', [$model->key, $record->getKey()]),
            ],
            'strings' => Strings::entries(),
        ]));
    }

    /**
     * The entry on a page of its own, for printing. It's Blade for both stacks:
     * a standalone document prints the same in every app.
     */
    public function print(Request $request, string $form, string $entry): View
    {
        [$model, $record] = $this->findEntry($form, $entry);

        $this->authorizeFor($request, 'view', $record);

        return view('form-builder::entries.print', [
            'record' => $this->data->form($request, $model),
            'entry' => $this->entryData($request, $model, $record),
            'display' => DisplayForm::forEntry($record),
            'strings' => Strings::entries(),
        ]);
    }

    /**
     * Download a file answer: files/{field}/{index?} (field is group.0.key inside a repeating group)
     */
    public function file(Request $request, string $form, string $entry, string $field, ?string $index = null): StreamedResponse
    {
        [, $record] = $this->findEntry($form, $entry);

        $this->authorizeFor($request, 'view', $record);

        $key = (string) preg_replace('/\.\d+\./', '.', $field);
        $definition = $record->version->definition()->getField($key);

        if (! $definition || $definition->getType() !== 'file') {
            throw new NotFoundHttpException;
        }

        $value = $record->answer($field);
        $path = is_array($value) ? ($value[(int) $index] ?? null) : $value;

        if (! is_string($path) || $path === '') {
            throw new NotFoundHttpException;
        }

        $disk = Storage::disk(config('form-builder.files.disk'));

        if (! $disk->exists($path)) {
            throw new NotFoundHttpException;
        }

        return $disk->download($path, basename($path));
    }

    public function destroy(Request $request, string $form, string $entry): RedirectResponse|JsonResponse
    {
        [$model, $record] = $this->findEntry($form, $entry);

        $this->authorizeFor($request, 'delete', $record);

        $record->delete();

        return $this->done($request, __('Entry deleted.'), $this->url($request, 'entries.index', $model->key));
    }

    /**
     * The entries the list shows. Override to scope them (by subject, by submitter).
     */
    protected function query(Request $request, Form $form)
    {
        return FormBuilder::model('entry')::query()->where('form_id', $form->getKey());
    }

    /**
     * @return array{0: Form, 1: FormEntry}
     */
    protected function findEntry(string $form, string $entry): array
    {
        $model = $this->findForm($form);

        /** @var FormEntry|null $record */
        $record = FormBuilder::model('entry')::query()->where('form_id', $model->getKey())->find($entry);

        return [$model, $record ?? throw new NotFoundHttpException];
    }

    /**
     * @param  array<string, string>  $columns
     * @return array<string, mixed>
     */
    protected function entryData(Request $request, Form $form, FormEntry $entry, array $columns = []): array
    {
        $submitter = $entry->submitter;

        return [
            'id' => $entry->getKey(),
            'status' => $entry->status,
            'version' => $entry->version->number,
            'submitted_at' => $entry->submitted_at?->toIso8601String(),
            'submitted_at_label' => $entry->submitted_at?->locale(app()->getLocale())->translatedFormat((string) config('form-builder.datetime_format', 'j F Y, H:i')),
            'submitter' => $submitter ? (string) ($submitter->getAttribute('name') ?? $submitter->getKey()) : null,
            'values' => array_map(fn (string $path) => $this->columns->value($entry, $path), array_combine(array_keys($columns), array_keys($columns))),
            'urls' => [
                'show' => $this->url($request, 'entries.show', [$form->key, $entry->getKey()]),
                'print' => $this->url($request, 'entries.print', [$form->key, $entry->getKey()]),
                'destroy' => $this->url($request, 'entries.destroy', [$form->key, $entry->getKey()]),
            ],
        ];
    }

    /**
     * Links to the entry's uploaded files
     *
     * @return array<int, array{label: string, name: string, url: string}>
     */
    protected function files(Request $request, Form $form, FormEntry $entry): array
    {
        $files = [];

        $add = function (string $label, string $path, mixed $value) use (&$files, $request, $form, $entry) {
            foreach (array_values((array) $value) as $index => $file) {
                if (! is_string($file) || $file === '') {
                    continue;
                }

                $files[] = [
                    'label' => $label,
                    'name' => basename($file),
                    'url' => $this->url($request, 'entries.file', [$form->key, $entry->getKey(), $path, is_array($value) ? $index : null]),
                ];
            }
        };

        foreach ($entry->version->definition()->getFields() as $field) {
            $label = Text::resolve($field->getLabel()) ?? $field->getKey();

            if ($field->getType() === 'file') {
                $add($label, $field->getKey(), $entry->answer($field->getKey()));

                continue;
            }

            foreach ($field->getFields() as $child) {
                if ($child->getType() !== 'file') {
                    continue;
                }

                foreach (array_values((array) $entry->answer($field->getKey(), [])) as $row => $answers) {
                    $add((Text::resolve($child->getLabel()) ?? $child->getKey()) . ' (' . ($row + 1) . ')', "{$field->getKey()}.{$row}.{$child->getKey()}", $answers[$child->getKey()] ?? null);
                }
            }
        }

        return $files;
    }
}
