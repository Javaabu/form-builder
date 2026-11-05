<?php

namespace Javaabu\FormBuilder\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\FormBuilder\Models\Form;

/**
 * Who may build forms, see their entries and fill them
 *
 * | Ability                        | Permission                                  |
 * | ------------------------------ | ------------------------------------------- |
 * | viewAny, view                  | view_forms, or any other form permission    |
 * | create, update                 | edit_forms (update: not for locked forms)   |
 * | delete                         | delete_forms                                |
 * | publish                        | publish_forms                               |
 * | viewEntries                    | view_form_entries                           |
 * | exportEntries                  | export_form_entries                         |
 * | deleteEntries                  | delete_form_entries                         |
 * | fill (guests too)              | the form is published and accepting entries |
 *
 * Override it with `config('form-builder.policies.form')`, `config('permissions.policies')`
 * or `Gate::policy()` for the form model in the app.
 */
class FormPolicy
{
    public const string VIEW = 'view_forms';

    public const string EDIT = 'edit_forms';

    public const string DELETE = 'delete_forms';

    public const string PUBLISH = 'publish_forms';

    public const string VIEW_ENTRIES = 'view_form_entries';

    public const string EXPORT_ENTRIES = 'export_form_entries';

    public const string DELETE_ENTRIES = 'delete_form_entries';

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            self::VIEW => 'View forms',
            self::EDIT => 'Create and edit forms',
            self::DELETE => 'Delete forms',
            self::PUBLISH => 'Publish forms',
            self::VIEW_ENTRIES => 'View form entries',
            self::EXPORT_ENTRIES => 'Export form entries',
            self::DELETE_ENTRIES => 'Delete form entries',
        ];
    }

    public function viewAny(Model $user): bool
    {
        return $this->allows($user, ...array_keys(static::permissions()));
    }

    public function view(Model $user, Form $form): bool
    {
        return $this->viewAny($user);
    }

    public function create(Model $user): bool
    {
        return $this->allows($user, self::EDIT);
    }

    /**
     * Change the form's settings and structure. Forms defined in code are locked.
     */
    public function update(Model $user, Form $form): bool
    {
        return ! $form->is_locked && $this->allows($user, self::EDIT);
    }

    public function delete(Model $user, Form $form): bool
    {
        return ! $form->is_locked && $this->allows($user, self::DELETE);
    }

    public function publish(Model $user, Form $form): bool
    {
        return ! $form->is_locked && $this->allows($user, self::PUBLISH);
    }

    public function viewEntries(Model $user, Form $form): bool
    {
        return $this->allows($user, self::VIEW_ENTRIES);
    }

    public function exportEntries(Model $user, Form $form): bool
    {
        return $this->allows($user, self::EXPORT_ENTRIES);
    }

    public function deleteEntries(Model $user, Form $form): bool
    {
        return $this->allows($user, self::DELETE_ENTRIES);
    }

    /**
     * Fill the form: anyone (guests too) while it's published and accepting entries
     */
    public function fill(?Model $user, Form $form): bool
    {
        return $form->isOpen();
    }

    /**
     * Whether the user has any of the permissions
     */
    protected function allows(Model $user, string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
