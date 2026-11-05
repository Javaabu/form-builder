@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-entries>
        @component('form-builder::partials.header', ['title' => str_replace(':form', $record['name'], $strings['title']), 'back_url' => $urls['form']])
            @slot('actions')
                @if($can['export'])
                    <x-forms::link-button :url="$urls['export']" color="outline-secondary" start-icon="download">{{ $strings['export'] }}</x-forms::link-button>
                @endif
            @endslot
        @endcomponent

        @include('form-builder::partials.status')

        @if(count($versions) > 1)
            <form method="GET" action="{{ $urls['index'] }}" class="flex flex-wrap items-end gap-2">
                <div class="flex min-w-48 flex-col gap-1">
                    <label for="form-builder-version" class="text-sm font-medium">{{ $strings['version'] }}</label>
                    <select id="form-builder-version" name="version" class="h-9 rounded-md border bg-background px-3">
                        <option value="">{{ $strings['all_versions'] }}</option>
                        @foreach($versions as $number)
                            <option value="{{ $number }}" @selected($version === $number)>{{ str_replace(':number', $number, $strings['version_number']) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-forms::button type="submit" color="outline-secondary">{{ $strings['filter'] }}</x-forms::button>
            </form>
        @endif

        @if($entries === [])
            <x-forms::alert type="info">{{ $strings['empty'] }}</x-forms::alert>
        @else
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['number'] }}</th>
                            <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['submitted_at'] }}</th>
                            <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['submitter'] }}</th>
                            @foreach($columns as $label)
                                <th scope="col" class="px-3 py-2 text-start font-medium" dir="auto">{{ $label }}</th>
                            @endforeach
                            <th scope="col" class="px-3 py-2 text-end font-medium"><span class="sr-only">{{ $strings['actions'] }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr class="border-t" data-entry="{{ $entry['id'] }}">
                                <td class="px-3 py-2"><a href="{{ $entry['urls']['show'] }}" class="font-medium hover:underline">{{ $entry['id'] }}</a></td>
                                <td class="px-3 py-2">{{ $entry['submitted_at_label'] ?? '—' }}</td>
                                <td class="px-3 py-2" dir="auto">{{ $entry['submitter'] ?? $strings['guest'] }}</td>
                                @foreach(array_keys($columns) as $path)
                                    <td class="px-3 py-2" dir="auto">{{ $entry['values'][$path] ?? '—' }}</td>
                                @endforeach
                                <td class="px-3 py-2 text-end">
                                    <x-forms::link-button :url="$entry['urls']['show']" color="outline-secondary" size="sm">{{ $strings['view'] }}</x-forms::link-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('form-builder::partials.pagination')
        @endif
    </div>
@endsection
