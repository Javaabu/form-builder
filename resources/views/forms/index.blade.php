@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-forms>
        @component('form-builder::partials.header', ['title' => $strings['title'], 'description' => $strings['description']])
            @slot('actions')
                @if(isset($urls['import']) && $can['create'])
                    <x-forms::link-button :url="$urls['import']" color="outline" start-icon="upload">{{ $strings['import'] }}</x-forms::link-button>
                @endif
                @if(isset($urls['create']) && $can['create'])
                    <x-forms::link-button :url="$urls['create']" start-icon="plus">{{ $strings['create'] }}</x-forms::link-button>
                @endif
            @endslot
        @endcomponent

        @include('form-builder::partials.status')

        <form method="GET" action="{{ $urls['index'] }}" class="flex flex-wrap items-end gap-2" role="search">
            <div class="flex min-w-64 flex-col gap-1">
                <label for="form-builder-search" class="text-sm font-medium">{{ $strings['search'] }}</label>
                <input id="form-builder-search" type="search" name="search" value="{{ $search }}" dir="auto" class="h-9 rounded-md border bg-background px-3">
            </div>
            <x-forms::button type="submit" color="outline">{{ $strings['search_button'] }}</x-forms::button>
        </form>

        @if($forms === [])
            <x-forms::alert type="info">{{ $search !== '' ? $strings['no_results'] : $strings['empty'] }}</x-forms::alert>
        @else
            <div class="overflow-x-auto rounded-md border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['name'] }}</th>
                            <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['status'] }}</th>
                            <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['version'] }}</th>
                            <th scope="col" class="px-3 py-2 text-start font-medium">{{ $strings['entries'] }}</th>
                            <th scope="col" class="px-3 py-2 text-end font-medium"><span class="sr-only">{{ $strings['actions'] }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($forms as $form)
                            <tr class="border-t" data-form-key="{{ $form['key'] }}">
                                <td class="px-3 py-2">
                                    <a href="{{ $form['urls']['show'] }}" class="font-medium hover:underline" dir="auto">{{ $form['name'] }}</a>
                                    <div class="text-xs text-muted-foreground" dir="ltr">{{ $form['key'] }}</div>
                                </td>
                                <td class="px-3 py-2">
                                    <x-forms::status :label="$form['status_label']" :color="$form['status'] === 'published' ? 'success' : ($form['status'] === 'changed' ? 'warning' : 'secondary')" />
                                    @if($form['is_locked'])
                                        <span class="ms-1 text-xs text-muted-foreground">{{ $strings['locked'] }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ $form['published_version'] ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @isset($form['urls']['entries'])
                                        <a href="{{ $form['urls']['entries'] }}" class="hover:underline">{{ $form['entries_count'] }}</a>
                                    @else
                                        {{ $form['entries_count'] }}
                                    @endisset
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <x-forms::link-button :url="$form['urls']['show']" color="outline" size="sm">{{ $strings['open_builder'] }}</x-forms::link-button>
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
