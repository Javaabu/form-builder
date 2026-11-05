<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \Javaabu\FormBuilder\Support\Translations::isRtl(app()->getLocale()) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $record['name'] }} · {{ str_replace(':number', $entry['id'], $strings['entry']) }}</title>
    @manifestStyles
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem auto; max-width: 48rem; padding-inline: 1rem; color: #111; }
        .form-builder-print-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-block-end: 1.5rem; }
        .form-builder-print-header h1 { margin: 0; font-size: 1.5rem; }
        .form-builder-print-header p { margin: 0.25rem 0 0; color: #555; }
        .form-builder-print dl { margin-block-end: 1.5rem; }
        @media print {
            .form-builder-print-actions { display: none; }
            body { margin: 0; max-width: none; }
            .stencil-section, fieldset { break-inside: avoid; }
        }
    </style>
</head>
<body class="form-builder-print">
    <div class="form-builder-print-header">
        <div>
            <h1 dir="auto">{{ $record['name'] }}</h1>
            <p>{{ str_replace(':number', $entry['id'], $strings['entry']) }}</p>
        </div>
        <button type="button" class="form-builder-print-actions" onclick="window.print()">{{ $strings['print'] }}</button>
    </div>

    @include('form-builder::partials.entry-details')

    {{ $display }}
</body>
</html>
