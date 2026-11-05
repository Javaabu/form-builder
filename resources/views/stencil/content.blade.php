@if($node->prop('variant') === 'heading')
    <h3 class="form-builder-heading">{{ $node->prop('text') }}</h3>
@else
    <p class="form-builder-paragraph">{!! nl2br(e($node->prop('text'))) !!}</p>
@endif
