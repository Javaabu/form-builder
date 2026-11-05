@extends($layout)

@section($layout_section)
    @manifestRequire('form-builder::builder')
    @php($client_strings = \Illuminate\Support\Arr::only($strings, ['picked_up', 'moved', 'dropped', 'cancelled', 'order_saved', 'order_failed']))
    @php($editable = $can['update'])

    <div class="flex flex-col gap-6" data-form-builder-builder data-strings="{{ json_encode($client_strings, JSON_UNESCAPED_UNICODE) }}" data-csrf="{{ csrf_token() }}">
        @component('form-builder::partials.header', ['title' => $form['name'], 'description' => $form['description'], 'back_url' => $urls['index'], 'back_label' => $strings['forms']])
            @slot('actions')
                <x-forms::status :label="$form['status_label']" :color="$form['status'] === 'published' ? 'success' : ($form['status'] === 'changed' ? 'warning' : 'secondary')" />
                @if($editable && isset($urls['edit']))
                    <x-forms::link-button :url="$urls['edit']" color="outline" start-icon="settings">{{ $strings['settings'] }}</x-forms::link-button>
                @endif
                <x-forms::link-button :url="$urls['preview']" color="outline" start-icon="eye">{{ $strings['preview'] }}</x-forms::link-button>
                @if($can['view_entries'])
                    <x-forms::link-button :url="$urls['entries']" color="outline">{{ $strings['entries'] }} ({{ $form['entries_count'] }})</x-forms::link-button>
                @endif
                <x-forms::link-button :url="$urls['versions']" color="outline">{{ $strings['versions'] }}</x-forms::link-button>
                <x-forms::link-button :url="$urls['export']" color="outline" start-icon="download">{{ $strings['export'] }}</x-forms::link-button>
                @isset($form['urls']['fill'])
                    <x-forms::link-button :url="$form['urls']['fill']" color="outline" start-icon="external-link">{{ $strings['fill'] }}</x-forms::link-button>
                @endisset
                @if($can['publish'])
                    <form method="POST" action="{{ $urls['publish'] }}" data-form-builder-confirm="{{ $strings['confirm_publish'] }}">
                        @csrf
                        <x-forms::button type="submit" start-icon="check">{{ $strings['publish'] }}</x-forms::button>
                    </form>
                @endif
            @endslot
        @endcomponent

        @include('form-builder::partials.status')

        @if($form['is_locked'])
            <x-forms::alert type="info">{{ $strings['locked_notice'] }}</x-forms::alert>
        @elseif($form['has_draft'] && $form['published_version'])
            <x-forms::alert type="warning">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>{{ str_replace(':number', $form['published_version'], $strings['draft_notice']) }}</span>
                    @if($editable && isset($urls['discard']))
                        <form method="POST" action="{{ $urls['discard'] }}" data-form-builder-confirm="{{ $strings['confirm_discard'] }}">
                            @csrf
                            @method('DELETE')
                            <x-forms::button type="submit" color="outline" size="sm">{{ $strings['discard'] }}</x-forms::button>
                        </form>
                    @endif
                </div>
            </x-forms::alert>
        @elseif(! $form['published_version'])
            <x-forms::alert type="info">{{ $strings['unpublished_notice'] }}</x-forms::alert>
        @endif

        @if($form_layout === 'wizard')
            <p class="text-sm text-muted-foreground">{{ $strings['wizard'] }}</p>
        @endif

        <p id="form-builder-instructions" class="sr-only">{{ $strings['instructions'] }}</p>
        <div class="sr-only" aria-live="assertive" aria-atomic="true" data-form-builder-announcer></div>

        <ol class="flex flex-col gap-4" @if($editable && isset($urls['order_sections'])) data-form-builder-sortable data-order-url="{{ $urls['order_sections'] }}" data-order-name="sections" @endif aria-label="{{ $strings['section'] }}">
            @foreach($sections as $index => $section)
                <li data-form-builder-item="{{ $section['key'] }}" data-form-builder-item-label="{{ $section['title'] }}" data-form-builder-section="{{ $section['key'] }}">
                    <x-forms::card>
                        <div class="flex flex-col gap-4">
                            <div class="flex items-start gap-3">
                                @if($editable)
                                    <button type="button"
                                            class="flex size-8 shrink-0 cursor-grab items-center justify-center rounded-md text-muted-foreground hover:bg-muted focus-visible:outline-2"
                                            data-form-builder-handle
                                            aria-describedby="form-builder-instructions"
                                            aria-label="{{ str_replace(':item', $section['title'], $strings['move']) }}">
                                        <x-forms::icon name="grip-vertical" />
                                    </button>
                                @endif
                                <div class="flex min-w-0 flex-1 flex-col gap-1">
                                    <h2 class="text-lg font-semibold" dir="auto">
                                        @if($form_layout === 'wizard')
                                            <span class="text-muted-foreground">{{ str_replace(':number', $index + 1, $strings['step']) }} ·</span>
                                        @endif
                                        {{ $section['title'] }}
                                    </h2>
                                    @if($section['description'])
                                        <p class="text-sm text-muted-foreground" dir="auto">{{ $section['description'] }}</p>
                                    @endif
                                    @foreach($section['conditions'] as $condition)
                                        <p class="text-sm text-muted-foreground" dir="auto">{{ $strings['shown_when'] }}: {{ $condition }}</p>
                                    @endforeach
                                </div>
                                @if($editable)
                                    <div class="flex shrink-0 flex-wrap items-center gap-1">
                                        @include('form-builder::partials.move-buttons', ['item' => $section])
                                        <x-forms::link-button :url="$section['urls']['edit']" color="outline" size="sm">{{ $strings['edit'] }}</x-forms::link-button>
                                        @if(count($sections) > 1)
                                            <x-forms::button type="submit" color="destructive" size="sm" form="form-builder-delete-section-{{ $section['key'] }}">{{ $strings['delete'] }}</x-forms::button>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ $section['urls']['destroy'] }}" id="form-builder-delete-section-{{ $section['key'] }}" data-form-builder-confirm="{{ $strings['confirm_delete'] }}" hidden>
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </div>

                            @if($section['fields'] === [])
                                <p class="text-sm text-muted-foreground">{{ $strings['no_fields'] }}</p>
                            @else
                                <ol class="flex flex-col gap-2" @if($editable) data-form-builder-sortable data-order-url="{{ $section['urls']['order_fields'] }}" data-order-name="fields" @endif aria-label="{{ $section['title'] }}">
                                    @foreach($section['fields'] as $field)
                                        @include('form-builder::partials.field', ['field' => $field])
                                    @endforeach
                                </ol>
                            @endif

                            @if($editable)
                                @include('form-builder::partials.add-field', ['url' => $section['urls']['create_field'], 'types' => $types, 'label' => str_replace(':section', $section['title'], $strings['add_field_to']), 'id' => 'form-builder-add-' . $section['key']])
                            @endif
                        </div>
                    </x-forms::card>
                </li>
            @endforeach
        </ol>

        @if($editable && isset($urls['create_section']))
            <div>
                <x-forms::link-button :url="$urls['create_section']" color="outline" start-icon="plus">{{ $strings['add_section'] }}</x-forms::link-button>
            </div>
        @endif

        @isset($urls['assistant'])
            @include('form-builder::partials.assistant', ['url' => $urls['assistant']])
        @endisset
    </div>
@endsection
