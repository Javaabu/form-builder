<div class="form-builder-repeater-row" data-form-builder-repeater-row data-row="{{ $row->key }}">
    <div class="form-builder-repeater-row-header">
        <span class="form-builder-repeater-row-label" data-form-builder-repeater-row-label>{{ $label }}</span>
        <button type="button" class="btn btn-sm btn-outline-danger form-builder-repeater-remove" data-form-builder-repeater-remove>
            {{ __('Remove') }}
        </button>
    </div>
    {{ $stencil->children($row->children, $columns) }}
</div>
