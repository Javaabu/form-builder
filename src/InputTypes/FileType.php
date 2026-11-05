<?php

namespace Javaabu\FormBuilder\InputTypes;

use Javaabu\FormBuilder\Compiler\CompileContext;
use Javaabu\FormBuilder\Definitions\Field;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\FileUpload;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;

/**
 * An upload, stored on config('form-builder.files.disk'). The answer is the
 * stored path (or paths).
 */
class FileType extends InputType
{
    /** @var array<string, array<int, string>> */
    public const array KINDS = [
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain', 'image/jpeg', 'image/png'],
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'pdf' => ['application/pdf'],
    ];

    public static function key(): string
    {
        return 'file';
    }

    public function name(): string
    {
        return __('File upload');
    }

    public function icon(): string
    {
        return 'paperclip';
    }

    public function group(): string
    {
        return 'file';
    }

    public function isMultiple(Field $field): bool
    {
        return (bool) $field->getSetting('multiple');
    }

    public function canBeWatched(): bool
    {
        return false;
    }

    protected function make(Field $field, CompileContext $context): Component
    {
        $kind = (string) $field->getSetting('kind', 'document');
        $max = $field->getSetting('max_size');
        $accept = $field->getSetting('accept');

        $form = $context->definition->getKey() ?: 'form';

        return FileUpload::make($context->name($field->getKey()))
            ->fileType($kind === 'image' ? 'image' : 'document')
            ->acceptedFileTypes(is_array($accept) && $accept !== [] ? $accept : (self::KINDS[$kind] ?? null))
            ->maxSize(is_numeric($max) ? (int) $max : (int) config('form-builder.files.max_size', 10240))
            ->multiple($this->isMultiple($field))
            ->disk(config('form-builder.files.disk'))
            ->directory(trim((string) config('form-builder.files.directory', 'form-builder'), '/') . "/{$form}");
    }

    public function format(mixed $value, Field $field, ?string $locale = null): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        return implode(', ', array_map(fn ($path) => basename((string) $path), (array) $value));
    }

    public function settings(): array
    {
        return [
            Select::make('kind')->label(__('Kind of file'))->options([
                'document' => __('Document or image'),
                'image' => __('Image'),
                'pdf' => __('PDF'),
            ]),
            TextInput::make('max_size')->label(__('Largest file (KB)'))->integer()->minValue(1),
            Checkbox::make('multiple')->label(__('Allow several files')),
        ];
    }

    public function settingsRules(): array
    {
        return [
            'kind' => ['nullable', 'in:' . implode(',', array_keys(self::KINDS))],
            'max_size' => ['nullable', 'integer', 'min:1'],
            'multiple' => ['nullable', 'boolean'],
            'accept' => ['nullable', 'array'],
            'accept.*' => ['string', 'max:255'],
        ];
    }

    public function defaults(): array
    {
        return ['kind' => 'document'];
    }
}
