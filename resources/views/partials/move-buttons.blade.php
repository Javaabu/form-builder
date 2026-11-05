{{-- Up and down buttons for an item of the builder, for moving without drag and drop --}}
@unless($item['is_first'])
    <form method="POST" action="{{ $item['urls']['move'] }}" class="inline">
        @csrf
        <input type="hidden" name="direction" value="up">
        <button type="submit" class="flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted" aria-label="{{ $strings['move_up'] }}: {{ $item['label'] ?? $item['title'] }}" title="{{ $strings['move_up'] }}">
            <x-forms::icon name="arrow-up" />
        </button>
    </form>
@endunless
@unless($item['is_last'])
    <form method="POST" action="{{ $item['urls']['move'] }}" class="inline">
        @csrf
        <input type="hidden" name="direction" value="down">
        <button type="submit" class="flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted" aria-label="{{ $strings['move_down'] }}: {{ $item['label'] ?? $item['title'] }}" title="{{ $strings['move_down'] }}">
            <x-forms::icon name="arrow-down" />
        </button>
    </form>
@endunless
