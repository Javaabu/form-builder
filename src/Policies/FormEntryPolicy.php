<?php

namespace Javaabu\FormBuilder\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\FormBuilder\Models\FormEntry;

/**
 * Who may see and delete an entry: the form's entry permissions
 *
 * Override it with `config('form-builder.policies.entry')` or `Gate::policy()`.
 */
class FormEntryPolicy
{
    public function view(Model $user, FormEntry $entry): bool
    {
        return $user->can(FormPolicy::VIEW_ENTRIES);
    }

    public function delete(Model $user, FormEntry $entry): bool
    {
        return $user->can(FormPolicy::DELETE_ENTRIES);
    }
}
