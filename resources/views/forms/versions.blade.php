@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-versions>
        @include('form-builder::partials.header', ['title' => $strings['versions'], 'description' => $record['name'], 'back_url' => $back_url])

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-start font-medium">{{ str_replace(':number', '', $strings['version_number']) }}</th>
                        <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['published_at'] }}</th>
                        <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['author'] }}</th>
                        <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['fields_count'] }}</th>
                        <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['entries_count'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $version)
                        <tr class="border-t" data-version="{{ $version['number'] }}">
                            <td class="px-3 py-2">
                                {{ $version['number'] }}
                                @if($version['is_draft'])
                                    <x-forms::status :label="$strings['draft']" color="secondary" />
                                @elseif($version['is_current'])
                                    <x-forms::status :label="$strings['current']" color="success" />
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $version['published_at'] ? \Illuminate\Support\Carbon::parse($version['published_at'])->locale(app()->getLocale())->translatedFormat(config('form-builder.datetime_format', 'j F Y, H:i')) : '—' }}</td>
                            <td class="px-3 py-2" dir="auto">{{ $version['author'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $version['fields_count'] }}</td>
                            <td class="px-3 py-2">{{ $version['entries_count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
