---
title: Import and export
sidebar_position: 7
---

A form's structure exports to JSON and imports back. Use it to move forms between apps, keep them in a repository, or start from a template.

```json
{
    "form_builder": 1,
    "form": {
        "key": "feedback",
        "name": { "en": "Feedback", "dv": "ފީޑްބެކް" },
        "layout": "sections",
        "settings": { "submit_label": "Send" },
        "sections": [
            {
                "key": "main",
                "title": "Your feedback",
                "fields": [
                    { "key": "rating", "type": "radio", "label": "Rating", "required": true, "settings": { "options": [{ "value": "1", "label": "Bad" }, { "value": "5", "label": "Good" }] } },
                    { "key": "comment", "type": "textarea", "label": "Comment", "settings": { "thaana": true } }
                ]
            }
        ]
    }
}
```

The format is described in full in [JSON format](../advanced-usage/json-format.md). `form_builder` is the format version. It changes only with a breaking change, and imports from a newer format are refused.

# In the builder

- **Export** on the builder page downloads the published version (`?draft=1`: the draft).
- **Import** on the forms list takes a file or pasted JSON, and optionally another key. It's checked like any change, and the errors are listed:
  - An export of a key that doesn't exist yet makes a new form, as a draft.
  - An export of a form that exists becomes a new draft of it. The published version and entries are untouched.
  - Locked forms can't be imported over. Neither can forms you can't edit: import those under another key.

# On the command line

```bash
php artisan form-builder:export job-application                    # prints the JSON
php artisan form-builder:export job-application forms/job.json     # writes a file
php artisan form-builder:export job-application --draft
php artisan form-builder:import forms/job.json                     # a new form, or a new draft
php artisan form-builder:import forms/job.json --key=job-copy --publish
```

# In code

```php
use Javaabu\FormBuilder\Transfer\Exporter;
use Javaabu\FormBuilder\Transfer\Importer;

$json = app(Exporter::class)->toJson($form);            // or ->export($form): the array
$form = app(Importer::class)->import($json, key: 'job-copy', publish: true, author: $user);
$definition = app(Importer::class)->read($json);       // validate only; throws ValidationException
```

Form names and descriptions export in every language when javaabu/translatable keeps their translations. Labels, options and messages always export as they're stored.
