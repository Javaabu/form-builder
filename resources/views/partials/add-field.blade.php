{{-- Choose a field type and open the new field's settings --}}
<form method="GET" action="{{ $url }}" class="flex flex-wrap items-end gap-2" data-form-builder-add-field>
    @foreach(\Illuminate\Support\Arr::except(parse_url($url, PHP_URL_QUERY) ? (function () use ($url) { parse_str(parse_url($url, PHP_URL_QUERY), $query); return $query; })() : [], ['type']) as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <div class="flex min-w-48 flex-col gap-1">
        <label for="{{ $id }}" class="text-sm font-medium">{{ $label }}</label>
        <select id="{{ $id }}" name="type" class="h-9 rounded-md border bg-background px-3" required>
            @foreach($types as $group)
                <optgroup label="{{ $group['label'] }}">
                    @foreach($group['types'] as $type)
                        <option value="{{ $type['key'] }}">{{ $type['name'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
    <x-forms::button type="submit" color="outline-secondary" start-icon="plus">{{ $strings['add_field'] }}</x-forms::button>
</form>
