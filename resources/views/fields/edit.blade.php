@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-field-edit="{{ $type['key'] }}">
        @include('form-builder::partials.header', ['title' => $title, 'description' => $record['name'], 'back_url' => $back_url])
        @include('form-builder::partials.status')

        {{ $form }}
    </div>
@endsection
