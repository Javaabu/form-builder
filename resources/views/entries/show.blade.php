@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-entry="{{ $entry['id'] }}">
        @component('form-builder::partials.header', ['title' => str_replace(':number', $entry['id'], $strings['entry']), 'description' => $record['name'], 'back_url' => $urls['index']])
            @slot('actions')
                <x-forms::link-button :url="$urls['print']" color="outline" start-icon="printer" target="_blank">{{ $strings['print'] }}</x-forms::link-button>
                @if($can['delete'])
                    <form method="POST" action="{{ $urls['destroy'] }}" data-form-builder-confirm="{{ $strings['confirm_delete'] }}">
                        @csrf
                        @method('DELETE')
                        <x-forms::button type="submit" color="destructive">{{ $strings['delete'] }}</x-forms::button>
                    </form>
                @endif
            @endslot
        @endcomponent

        @include('form-builder::partials.status')

        @include('form-builder::partials.entry-details')

        <div class="form-builder-entry">
            {{ $display }}
        </div>

        @if($files !== [])
            <x-forms::card :title="$strings['files']">
                <ul class="flex flex-col gap-1">
                    @foreach($files as $file)
                        <li><span class="text-muted-foreground" dir="auto">{{ $file['label'] }}:</span> <a href="{{ $file['url'] }}" class="hover:underline" dir="auto">{{ $file['name'] }}</a></li>
                    @endforeach
                </ul>
            </x-forms::card>
        @endif
    </div>
@endsection
