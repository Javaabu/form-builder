{{-- The AI assistant: a description, sent to the assistant route --}}
<x-forms::card :title="$strings['assistant_title']" data-form-builder-assistant>
    <x-forms::form :action="$url" method="POST">
        <div class="flex flex-col gap-3">
            <p class="text-sm text-muted-foreground">{{ $strings['assistant_help'] }}</p>
            <x-forms::textarea name="description" :label="$strings['assistant_prompt']" rows="4" required dir="auto" />
            <div>
                <x-forms::submit>{{ $strings['assistant_submit'] }}</x-forms::submit>
            </div>
        </div>
    </x-forms::form>
</x-forms::card>
