{{-- One field in the builder's list, with its actions; a repeating group lists its own fields --}}
<li class="flex flex-col gap-2 rounded-md border bg-background p-3"
    data-form-builder-item="{{ $field['key'] }}"
    data-form-builder-item-label="{{ $field['label'] }}">
    <div class="flex items-center gap-3">
        @if($editable)
            <button type="button"
                    class="flex size-8 shrink-0 cursor-grab items-center justify-center rounded-md text-muted-foreground hover:bg-muted focus-visible:outline-2"
                    data-form-builder-handle
                    aria-describedby="form-builder-instructions"
                    aria-label="{{ str_replace(':item', $field['label'], $strings['move']) }}">
                <x-forms::icon name="grip-vertical" />
            </button>
        @endif

        <div class="flex min-w-0 flex-1 flex-col">
            <span class="truncate font-medium" dir="auto">
                {{ $field['label'] }}
                @if($field['required'])
                    <span class="text-destructive" aria-hidden="true">*</span>
                    <span class="sr-only">({{ $strings['required'] }})</span>
                @endif
            </span>
            <span class="text-sm text-muted-foreground">
                {{ $field['type_name'] }} · <span dir="ltr">{{ $field['path'] }}</span>
                @if($field['depends_on'])
                    · {{ str_replace(':field', $field['depends_on'], $strings['depends_on']) }}
                @endif
            </span>
            @if($field['missing_type'])
                <span class="text-sm text-destructive">{{ $strings['missing_type'] }}</span>
            @endif
            @foreach($field['conditions'] as $condition)
                <span class="text-sm text-muted-foreground" dir="auto">{{ $strings['shown_when'] }}: {{ $condition }}</span>
            @endforeach
        </div>

        @if($editable)
            <div class="flex shrink-0 flex-wrap items-center gap-1">
                @include('form-builder::partials.move-buttons', ['item' => $field])
                <x-forms::link-button :url="$field['urls']['edit']" color="outline" size="sm">{{ $strings['edit'] }}</x-forms::link-button>
                <x-forms::button type="submit" color="destructive" size="sm" form="form-builder-delete-field-{{ str_replace('.', '-', $field['path']) }}">{{ $strings['delete'] }}</x-forms::button>
            </div>
            <form method="POST" action="{{ $field['urls']['destroy'] }}" id="form-builder-delete-field-{{ str_replace('.', '-', $field['path']) }}" data-form-builder-confirm="{{ $strings['confirm_delete'] }}" hidden>
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>

    @if($field['has_fields'])
        <div class="flex flex-col gap-2 border-s-2 ps-4">
            @if($field['fields'] === [])
                <p class="text-sm text-muted-foreground">{{ $strings['no_fields'] }}</p>
            @else
                <ol class="flex flex-col gap-2" @if($editable) data-form-builder-sortable data-order-url="{{ $field['urls']['order_fields'] }}" data-order-name="fields" @endif aria-label="{{ $field['label'] }}">
                    @foreach($field['fields'] as $child)
                        @include('form-builder::partials.field', ['field' => $child])
                    @endforeach
                </ol>
            @endif

            @if($editable)
                @include('form-builder::partials.add-field', ['url' => $field['urls']['create_field'], 'types' => $group_types, 'label' => str_replace(':group', $field['label'], $strings['add_field_to_group']), 'id' => 'form-builder-add-' . $field['key']])
            @endif
        </div>
    @endif
</li>
