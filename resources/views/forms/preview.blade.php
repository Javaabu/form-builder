@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-preview>
        @include('form-builder::partials.header', ['title' => str_replace(':form', $title, $strings['preview_title']), 'back_url' => $back_url])

        <x-forms::alert type="info">
            {{ $strings['preview_notice'] }}
            {{ $version['is_draft'] ? $strings['draft'] : str_replace(':number', $version['number'], $strings['version_number']) }}
        </x-forms::alert>

        @include('form-builder::partials.status')

        @if($description)
            <p class="text-muted-foreground" dir="auto">{{ $description }}</p>
        @endif

        {{ $form }}
    </div>
@endsection
