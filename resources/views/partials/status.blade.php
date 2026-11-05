@if(! empty($status))
    <x-forms::alert type="success" role="status">{{ $status }}</x-forms::alert>
@endif
@if($errors->any() && $errors->hasAny(['form', 'section', 'field', 'structure', 'position']))
    <x-forms::alert type="danger" role="alert">
        @foreach(['form', 'section', 'field', 'structure', 'position'] as $key)
            @foreach($errors->get($key) as $message)
                <p>{{ $message }}</p>
            @endforeach
        @endforeach
    </x-forms::alert>
@endif
