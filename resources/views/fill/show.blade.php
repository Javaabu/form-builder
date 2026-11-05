@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-fill>
        @include('form-builder::partials.header', ['title' => $title, 'description' => $description])

        @if($open)
            {{ $form }}
        @else
            <x-forms::alert type="info">{{ $strings['closed'] }}</x-forms::alert>
        @endif
    </div>
@endsection
