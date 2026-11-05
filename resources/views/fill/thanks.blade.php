@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-thanks>
        @include('form-builder::partials.header', ['title' => $title])

        <x-forms::alert type="success" role="status">
            <p class="font-medium">{{ $strings['thanks'] }}</p>
            <p dir="auto">{{ $message }}</p>
            @if($entry)
                <p>{{ str_replace(':number', $entry, $strings['reference']) }}</p>
            @endif
        </x-forms::alert>

        @if($form_url)
            <div>
                <x-forms::link-button :url="$form_url" color="outline-secondary">{{ $strings['again'] }}</x-forms::link-button>
            </div>
        @endif
    </div>
@endsection
