---
title: Permissions
sidebar_position: 8
---

The package registers seven permissions through javaabu/permissions, under the **Forms** group:

| Permission | Allows |
| --- | --- |
| `view_forms` | The forms list, the builder (read-only), previews, versions, export |
| `edit_forms` | Creating forms, changing settings, sections and fields, importing, the assistant |
| `delete_forms` | Deleting forms |
| `publish_forms` | Publishing drafts |
| `view_form_entries` | The entries list, entries and their files |
| `export_form_entries` | The CSV export |
| `delete_form_entries` | Deleting entries |

Run `php artisan permissions:sync` to create them for your guards (`config('permissions.guards')`), then give them to roles as usual. Any of the seven also lets someone see the forms list.

# The policies

`FormPolicy` (for the form model) and `FormEntryPolicy` (for entries) check them:

| Ability | Needs |
| --- | --- |
| `viewAny`, `view` | any form permission |
| `create` | `edit_forms` |
| `update` | `edit_forms`, and the form isn't locked |
| `delete` | `delete_forms`, and the form isn't locked |
| `publish` | `publish_forms`, and the form isn't locked |
| `viewEntries`, `exportEntries`, `deleteEntries` | `view_form_entries`, `export_form_entries`, `delete_form_entries` |
| `fill` (guests too) | the form is published and accepting entries |
| entry `view`, `delete` | `view_form_entries`, `delete_form_entries` |

Controllers authorize with the user of the route's guard (`Route::formBuilder(['guard' => 'web_admin'])`). Nothing assumes the `web` guard or `App\Models\User`, and authors and submitters are morphs.

# Your own rules

Replace a policy in config, in `config('permissions.policies')`, or with `Gate::policy()`:

```php
// config/form-builder.php
'policies' => [
    'form' => App\Policies\FormPolicy::class,     // extend Javaabu\FormBuilder\Policies\FormPolicy
    'entry' => App\Policies\FormEntryPolicy::class,
],
```

```php
// Only HR edits the job application form
class FormPolicy extends \Javaabu\FormBuilder\Policies\FormPolicy
{
    public function update(Model $user, Form $form): bool
    {
        if ($form->key === 'job-application' && ! $user->hasRole('hr')) {
            return false;
        }

        return parent::update($user, $form);
    }
}
```

To restrict who can fill a form (members only, one entry per person), override `fill()`. `$user` is null for guests.

Turn `config('form-builder.register_permissions')` off to register permissions yourself.
