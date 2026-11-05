<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Policies\FormPolicy;
use Javaabu\FormBuilder\Routing\FormBuilderRoutes;
use Javaabu\FormBuilder\Tests\TestSupport\Forms\JobApplication;
use Javaabu\FormBuilder\Tests\TestSupport\Models\Admin;
use Javaabu\FormBuilder\Tests\TestSupport\Models\User;
use Javaabu\Manifest\Facades\Manifest;

trait InteractsWithFormBuilder
{
    protected function defineRoutes($router): void
    {
        FormBuilderRoutes::flush();

        $router->middleware('web')->group(function ($router) {
            $router->formBuilder();
            $router->formBuilder(['guard' => 'web_admin', 'prefix' => 'admin/forms', 'name' => 'admin.forms']);
            $router->formBuilderFill();
        });
    }

    protected function setUpFormBuilder(): void
    {
        Manifest::fake();

        $this->artisan('permissions:sync');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function user(?array $permissions = null): User
    {
        $user = User::create([
            'name' => 'އަޙްމަދު',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        $user->givePermissionTo($permissions ?? array_keys(FormPolicy::permissions()));

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function admin(?array $permissions = null): Admin
    {
        $admin = Admin::create([
            'name' => 'Aisha',
            'email' => uniqid() . '@example.com',
            'password' => 'secret',
        ]);

        $admin->givePermissionTo($permissions ?? array_keys(FormPolicy::permissions()));

        return $admin;
    }

    protected function jobApplication(bool $publish = true): Form
    {
        return JobApplication::definition()->sync($publish);
    }
}
