<?php

namespace Javaabu\FormBuilder\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Javaabu\FormBuilder\Builder\BuilderData;
use Javaabu\FormBuilder\Builder\BuilderForms;
use Javaabu\FormBuilder\Builder\DraftEditor;
use Javaabu\FormBuilder\Definitions\DefinitionValidator;
use Javaabu\FormBuilder\Definitions\FormDefinition;
use Javaabu\FormBuilder\Definitions\Section;
use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\Http\Controllers\Concerns\InteractsWithForms;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Models\FormVersion;
use Javaabu\FormBuilder\Stencil\EntryForm;
use Javaabu\FormBuilder\Support\Strings;
use Javaabu\FormBuilder\Support\Text;
use Javaabu\FormBuilder\Support\Translations;
use Javaabu\FormBuilder\Transfer\Exporter;
use Javaabu\FormBuilder\Transfer\Importer;

/**
 * Forms: the list, creating and importing, the builder page, settings,
 * publishing, drafts, the preview, versions and export. Extend it and set
 * `config('form-builder.controllers.forms')` (or pass `controllers` to
 * Route::formBuilder()) to change what it does.
 */
class FormsController extends Controller
{
    use InteractsWithForms;

    public function __construct(
        protected BuilderData $data,
        protected BuilderForms $forms,
        protected DraftEditor $editor,
    ) {}

    /**
     * Every form, newest first, with search
     */
    public function index(Request $request): View|Responsable|JsonResponse
    {
        $this->authorizeFor($request, 'viewAny', FormBuilder::model('form'));

        $search = trim((string) $request->query('search', ''));

        $forms = FormBuilder::model('form')::query()
            ->with(['publishedVersion', 'draftVersion'])
            ->withCount('entries')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('key', 'like', "%{$search}%")))
            ->latest('updated_at')
            ->paginate((int) config('form-builder.per_page', 20))
            ->withQueryString();

        $items = collect($forms->items())->map(fn (Form $form) => $this->data->form($request, $form))->all();

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $items, 'meta' => ['current_page' => $forms->currentPage(), 'last_page' => $forms->lastPage(), 'total' => $forms->total()]]);
        }

        return $this->views()->render('forms.index', $this->page($request, [
            'forms' => $items,
            'pagination' => ['current_page' => $forms->currentPage(), 'last_page' => $forms->lastPage(), 'per_page' => $forms->perPage(), 'total' => $forms->total()],
            'search' => $search,
            'can' => [
                'create' => $this->hasRoute($request, 'create') && $this->allowsFor($request, 'create', FormBuilder::model('form')),
            ],
            'urls' => array_filter([
                'index' => $this->url($request, 'index'),
                'create' => $this->hasRoute($request, 'create') ? $this->url($request, 'create') : null,
                'import' => $this->hasRoute($request, 'import') ? $this->url($request, 'import') : null,
            ]),
            'strings' => Strings::forms(),
        ]));
    }

    public function create(Request $request): View|Responsable|JsonResponse
    {
        $this->authorizeFor($request, 'create', FormBuilder::model('form'));

        $form = $this->forms->settingsForm()
            ->action($this->url($request, 'store'))
            ->method('POST')
            ->submit(__('Create form'));

        if ($request->expectsJson()) {
            return new JsonResponse(['form' => $form->render('json')]);
        }

        return $this->views()->render('forms.edit', $this->page($request, [
            'form' => $this->views()->isInertia() ? $form->render('json') : $form,
            'record' => null,
            'title' => __('New form'),
            'back_url' => $this->url($request, 'index'),
            'strings' => Strings::forms(),
        ]));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeFor($request, 'create', FormBuilder::model('form'));

        $data = $this->forms->settingsForm()->validate($request);

        $form = DB::transaction(function () use ($request, $data) {
            /** @var Form $form */
            $form = FormBuilder::model('form')::make();

            $this->fillSettings($form, $data);
            $form->save();

            Translations::saveModelText($form, ['name' => $data['name'] ?? null, 'description' => $data['description'] ?? null]);

            $draft = $form->ensureDraft($this->user($request));
            $draft->replaceStructure($this->definitionWithSettings(
                FormDefinition::make($form->key)->sections([
                    Section::make('section')->title(__('Section 1')),
                ]),
                $data,
            ));

            return $form;
        });

        return $this->done($request, __('Form created.'), $this->url($request, 'show', $form->key), ['form' => $this->data->form($request, $form)], 201);
    }

    /**
     * The builder: the form's sections and fields, with every action
     */
    public function show(Request $request, string $form): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'view', $model);

        $version = $model->currentVersion();
        $definition = $version?->definition() ?? FormDefinition::make($model->key);
        $editable = $this->hasRoute($request, 'edit') && $this->allowsFor($request, 'update', $model);

        $data = [
            'form' => $this->data->form($request, $model),
            'version' => $version ? $this->data->version($version) : null,
            'form_layout' => $definition->getLayout(),
            'sections' => $this->data->sections($request, $model, $definition, $editable),
            'types' => $this->data->types(),
            'group_types' => $this->data->types(in_group: true),
            'can' => [
                'update' => $editable,
                'publish' => $this->hasRoute($request, 'publish') && $model->hasDraft() && $this->allowsFor($request, 'publish', $model),
                'delete' => $this->hasRoute($request, 'destroy') && $this->allowsFor($request, 'delete', $model),
                'view_entries' => $this->allowsFor($request, 'viewEntries', $model),
            ],
            'urls' => array_filter([
                'index' => $this->url($request, 'index'),
                'edit' => $this->hasRoute($request, 'edit') ? $this->url($request, 'edit', $model->key) : null,
                'preview' => $this->url($request, 'preview', $model->key),
                'versions' => $this->url($request, 'versions', $model->key),
                'export' => $this->url($request, 'export', $model->key),
                'entries' => $this->url($request, 'entries.index', $model->key),
                'publish' => $this->hasRoute($request, 'publish') ? $this->url($request, 'publish', $model->key) : null,
                'discard' => $this->hasRoute($request, 'draft.destroy') ? $this->url($request, 'draft.destroy', $model->key) : null,
                'destroy' => $this->hasRoute($request, 'destroy') ? $this->url($request, 'destroy', $model->key) : null,
                'create_section' => $this->hasRoute($request, 'sections.create') ? $this->url($request, 'sections.create', $model->key) : null,
                'order_sections' => $this->hasRoute($request, 'sections.order') ? $this->url($request, 'sections.order', $model->key) : null,
                'structure' => $this->hasRoute($request, 'structure') ? $this->url($request, 'structure', $model->key) : null,
                'assistant' => $this->hasRoute($request, 'assistant') && $editable ? $this->url($request, 'assistant', $model->key) : null,
            ]),
        ];

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => [...$data, 'structure' => $definition->toArray()]]);
        }

        return $this->views()->render('forms.show', $this->page($request, [...$data, 'strings' => Strings::builder()]));
    }

    /**
     * The form's settings
     */
    public function edit(Request $request, string $form): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $stencil = $this->forms->settingsForm($model)
            ->action($this->url($request, 'update', $model->key))
            ->method('PATCH');

        if ($request->expectsJson()) {
            return new JsonResponse(['form' => $stencil->render('json')]);
        }

        return $this->views()->render('forms.edit', $this->page($request, [
            'form' => $this->views()->isInertia() ? $stencil->render('json') : $stencil,
            'record' => $this->data->form($request, $model),
            'title' => __('Settings of :form', ['form' => $this->data->formName($model)]),
            'back_url' => $this->url($request, 'show', $model->key),
            'strings' => Strings::forms(),
        ]));
    }

    public function update(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $data = $this->forms->settingsForm($model)->validate($request);

        DB::transaction(function () use ($request, $model, $data) {
            $this->fillSettings($model, $data);
            $model->save();

            Translations::saveModelText($model, ['name' => $data['name'] ?? null, 'description' => $data['description'] ?? null]);

            $this->editor->change(
                $model,
                fn (FormDefinition $definition) => $this->definitionWithSettings($definition, $data),
                $this->user($request),
            );
        });

        return $this->done($request, __('Settings saved.'), $this->url($request, 'show', $model->key), ['form' => $this->data->form($request, $model->refresh())]);
    }

    public function destroy(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'delete', $model);

        $model->delete();

        return $this->done($request, __('Form deleted.'), $this->url($request, 'index'));
    }

    public function publish(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'publish', $model);

        if (! $model->hasDraft()) {
            throw ValidationException::withMessages(['form' => __('There are no changes to publish.')]);
        }

        $this->editor->ensureValid($model->draftVersion->definition());

        $version = $model->publish($this->user($request));

        return $this->done($request, __('Version :number published.', ['number' => $version->number]), $this->url($request, 'show', $model->key), ['version' => $this->data->version($version)]);
    }

    public function discardDraft(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        if (! $model->isPublished()) {
            throw ValidationException::withMessages(['form' => __('A form that was never published has only its draft.')]);
        }

        $model->discardDraft();

        return $this->done($request, __('Changes discarded.'), $this->url($request, 'show', $model->key));
    }

    /**
     * Replace the draft's whole structure: `structure` is the export format's `form`
     */
    public function structure(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'update', $model);

        $structure = $request->input('structure');

        if (is_string($structure)) {
            $structure = json_decode($structure, true);
        }

        if (! is_array($structure)) {
            throw ValidationException::withMessages(['structure' => __('Send the structure as JSON.')]);
        }

        $definition = app(DefinitionValidator::class)->validate([...$structure, 'key' => $model->key]);

        $this->editor->change($model, function (FormDefinition $current) use ($definition) {
            return $definition
                ->name($current->getName())
                ->description($current->getDescription());
        }, $this->user($request));

        return $this->done($request, __('Form saved.'), $this->url($request, 'show', $model->key), [
            'structure' => $model->refresh()->currentVersion()?->definition()->toArray(),
        ]);
    }

    /**
     * The draft (or published version) as people will see it. Submitting checks
     * the answers without saving them.
     */
    public function preview(Request $request, string $form): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'view', $model);

        $version = $model->currentVersion() ?? abort(404);

        $stencil = $this->previewForm($request, $model, $version);

        if ($request->expectsJson()) {
            return new JsonResponse(['form' => $stencil->render('json')]);
        }

        return $this->views()->render('forms.preview', $this->page($request, [
            'form' => $this->views()->isInertia() ? $stencil->render('json') : $stencil,
            'record' => $this->data->form($request, $model),
            'version' => $this->data->version($version),
            'title' => Text::resolve($version->definition()->getName()) ?? $model->name,
            'description' => $model->description,
            'back_url' => $this->url($request, 'show', $model->key),
            'strings' => Strings::builder(),
        ]));
    }

    public function checkPreview(Request $request, string $form): RedirectResponse|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'view', $model);

        $version = $model->currentVersion() ?? abort(404);

        $this->previewForm($request, $model, $version)->validate($request);

        $message = __('Everything is filled in correctly. Previews aren\'t saved.');

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message]);
        }

        return redirect()->to($this->url($request, 'preview', $model->key))->with('status', $message)->withInput();
    }

    protected function previewForm(Request $request, Form $form, FormVersion $version): EntryForm
    {
        return EntryForm::for($version)
            ->action($this->url($request, 'preview.check', $form->key))
            ->method('POST');
    }

    public function versions(Request $request, string $form): View|Responsable|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'view', $model);

        $versions = $model->versions()->reorder()->latest('number')->withCount('entries')->with(['author', 'form'])->get()
            ->map(fn (FormVersion $version) => $this->data->version($version))
            ->all();

        if ($request->expectsJson()) {
            return new JsonResponse(['data' => $versions]);
        }

        return $this->views()->render('forms.versions', $this->page($request, [
            'record' => $this->data->form($request, $model),
            'versions' => $versions,
            'back_url' => $this->url($request, 'show', $model->key),
            'strings' => Strings::builder(),
        ]));
    }

    /**
     * Download the structure as JSON: the published version, or ?draft=1
     */
    public function export(Request $request, string $form): Response|JsonResponse
    {
        $model = $this->findForm($form);

        $this->authorizeFor($request, 'view', $model);

        $exporter = app(Exporter::class);
        $draft = $request->boolean('draft');

        if ($request->expectsJson() && ! $request->boolean('download')) {
            return new JsonResponse($exporter->export($model, $draft));
        }

        return new Response($exporter->toJson($model, $draft), 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $model->key . '.json"',
        ]);
    }

    public function importForm(Request $request): View|Responsable|JsonResponse
    {
        $this->authorizeFor($request, 'create', FormBuilder::model('form'));

        return $this->views()->render('forms.import', $this->page($request, [
            'action' => $this->url($request, 'import.store'),
            'back_url' => $this->url($request, 'index'),
            'strings' => Strings::forms(),
        ]));
    }

    /**
     * Import an export (an uploaded file or pasted JSON) as a new form, or as a
     * new draft of the form with the same key
     */
    public function import(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeFor($request, 'create', FormBuilder::model('form'));

        $request->validate([
            'file' => ['required_without:json', 'nullable', 'file', 'max:2048'],
            'json' => ['required_without:file', 'nullable', 'string', 'max:2000000'],
            'key' => ['nullable', 'string', 'max:100', 'regex:' . DefinitionValidator::FORM_KEY_PATTERN],
        ], [], ['file' => __('file'), 'json' => __('JSON'), 'key' => __('key')]);

        $json = $request->hasFile('file') ? (string) $request->file('file')->get() : (string) $request->input('json');

        $importer = app(Importer::class);
        $definition = $importer->read($json, $request->input('key'));

        $existing = FormBuilder::model('form')::query()->withTrashed()->where('key', $definition->getKey())->first();

        if ($existing && ! $this->allowsFor($request, 'update', $existing)) {
            throw ValidationException::withMessages(['key' => __('A form with the key :key exists and you can\'t change it. Import it with another key.', ['key' => $definition->getKey()])]);
        }

        $form = $importer->store($definition, author: $this->user($request));

        return $this->done(
            $request,
            $existing ? __('Imported as a new draft of :form.', ['form' => $form->name]) : __('Form imported.'),
            $this->url($request, 'show', $form->key),
            ['form' => $this->data->form($request, $form)],
            $existing ? 200 : 201,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function fillSettings(Form $form, array $data): void
    {
        $name = $data['name'] ?? null;
        $description = $data['description'] ?? null;

        $form->key = (string) $data['key'];
        $form->name = (string) (Text::resolve(Text::compact($name), Translations::defaultLocale()) ?? $data['key']);
        $form->description = Text::resolve(Text::compact($description), Translations::defaultLocale());
        $form->accepts_entries = (bool) ($data['accepts_entries'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function definitionWithSettings(FormDefinition $definition, array $data): FormDefinition
    {
        return $definition
            ->name(Text::compact($data['name'] ?? null))
            ->description(Text::compact($data['description'] ?? null))
            ->layout((string) ($data['layout'] ?? FormDefinition::LAYOUT_SECTIONS))
            ->submitLabel(Text::compact($data['submit_label'] ?? null))
            ->confirmation(Text::compact($data['confirmation'] ?? null));
    }
}
