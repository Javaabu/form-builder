<dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
    <div>
        <dt class="text-muted-foreground">{{ $strings['submitted_at'] }}</dt>
        <dd>{{ $entry['submitted_at_label'] ?? '—' }}</dd>
    </div>
    <div>
        <dt class="text-muted-foreground">{{ $strings['submitter'] }}</dt>
        <dd dir="auto">{{ $entry['submitter'] ?? $strings['guest'] }}</dd>
    </div>
    <div>
        <dt class="text-muted-foreground">{{ $strings['version'] }}</dt>
        <dd>{{ $entry['version'] }}</dd>
    </div>
</dl>
