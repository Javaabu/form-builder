@php
    /** @var \Javaabu\Stencil\Rendering\Node $node */
    /** @var \Javaabu\FormBuilder\Stencil\Repeater $repeater */
    $repeater = $node->source;
    $base = $repeater->getId();
    $labels = $node->prop('rowLabels', []);
    $columns = $repeater->getColumns();
    $template = $repeater->templateNode();
@endphp
<div class="form-builder-repeater"
     id="{{ $base }}"
     data-form-builder-repeater
     data-min="{{ $node->prop('minRows') }}"
     data-max="{{ $node->prop('maxRows') }}"
     data-next="{{ $node->prop('nextRow', 0) }}"
     data-placeholder="{{ $node->prop('rowPlaceholder') }}"
     data-row-label="{{ $node->prop('rowLabel') }}"
     role="group"
     aria-labelledby="{{ $base }}-label">
    <div class="form-builder-repeater-header">
        <span class="form-builder-repeater-label" id="{{ $base }}-label">
            {{ $node->prop('label') }}
            @if($node->prop('required'))
                <span class="form-builder-required" aria-hidden="true">*</span>
            @endif
        </span>
        @if($node->prop('help'))
            <p class="form-builder-repeater-help">{{ $node->prop('help') }}</p>
        @endif
    </div>

    <div class="form-builder-repeater-rows" data-form-builder-repeater-rows>
        @foreach($node->children as $index => $row)
            @include('form-builder::stencil.repeater-row', ['row' => $row, 'label' => $labels[$index] ?? '', 'columns' => $columns])
        @endforeach
    </div>

    <template data-form-builder-repeater-template>
        @include('form-builder::stencil.repeater-row', ['row' => $template, 'label' => $node->prop('rowLabel'), 'columns' => $columns])
    </template>

    <div class="form-builder-repeater-footer">
        <button type="button" class="btn btn-outline-primary form-builder-repeater-add" data-form-builder-repeater-add>
            {{ $node->prop('addLabel') }}
        </button>
    </div>

    <x-forms::errors :name="$node->name" />
</div>
