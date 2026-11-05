@extends($layout)

@section($layout_section)
    <div class="flex flex-col gap-6" data-form-builder-import>
        @include('form-builder::partials.header', ['title' => $strings['import_title'], 'description' => $strings['import_description'], 'back_url' => $back_url])
        @include('form-builder::partials.status')

        <x-forms::form :action="$action" method="POST" files>
            <x-forms::card>
                <div class="flex flex-col gap-4">
                    <x-forms::file name="file" :label="$strings['import_file']" mimetypes="application/json" extensions="json" />
                    <x-forms::textarea name="json" :label="$strings['import_json']" rows="8" dir="ltr" />
                    <x-forms::text name="key" :label="$strings['import_key']" dir="ltr">
                        <x-slot:help>{{ $strings['import_key_help'] }}</x-slot:help>
                    </x-forms::text>
                </div>
            </x-forms::card>
            <x-forms::submit>{{ $strings['import_submit'] }}</x-forms::submit>
        </x-forms::form>
    </div>
@endsection
