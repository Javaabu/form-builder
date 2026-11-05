<x-forms::form-group :name="$node->name" :label="$node->prop('label', '')" :required="(bool) $node->prop('required')">
    <span role="radiogroup" data-stars="{{ $node->prop('max') }}">
        @for($star = 1; $star <= $node->prop('max'); $star++)
            <label><input type="radio" name="{{ $node->name }}" value="{{ $star }}" @checked((string) $stencil->value($node) === (string) $star)> {{ $star }}</label>
        @endfor
    </span>
    <x-forms::errors :name="$node->name" />
</x-forms::form-group>
