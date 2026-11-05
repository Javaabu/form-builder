<?php

namespace Javaabu\FormBuilder\Tests\Http;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Javaabu\FormBuilder\Models\Form;
use Javaabu\FormBuilder\Policies\FormPolicy;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\FormBuilder\Tests\TestSupport\Forms\JobApplication;
use Javaabu\FormBuilder\Tests\TestSupport\Http\CustomFormsController;
use Javaabu\FormBuilder\Tests\TestSupport\Policies\DenyAllPolicy;
use Javaabu\Permissions\Permissions;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationTest extends TestCase
{
    use InteractsWithFormBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFormBuilder();
    }

    #[Test]
    public function it_registers_the_permissions(): void
    {
        foreach (array_keys(FormPolicy::permissions()) as $permission) {
            $this->assertTrue(Permissions::has($permission), $permission);
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web_admin']);
        }

        $this->assertEquals('Forms', Permissions::label('forms'));
    }

    #[Test]
    public function it_rejects_guests(): void
    {
        $this->jobApplication();

        $this->getJson('/forms')->assertUnauthorized();
        $this->getJson('/forms/job-application')->assertUnauthorized();
        $this->postJson('/forms', ['key' => 'x'])->assertUnauthorized();
        $this->getJson('/forms/job-application/entries')->assertUnauthorized();
        $this->getJson('/admin/forms')->assertUnauthorized();
    }

    #[Test]
    public function it_forbids_users_without_permissions(): void
    {
        $this->jobApplication();
        $user = $this->user([]);

        $this->actingAs($user)->get('/forms')->assertForbidden();
        $this->actingAs($user)->get('/forms/job-application')->assertForbidden();
        $this->actingAs($user)->get('/forms/create')->assertForbidden();
        $this->actingAs($user)->post('/forms/job-application/publish')->assertForbidden();
        $this->actingAs($user)->get('/forms/job-application/entries')->assertForbidden();
    }

    #[Test]
    public function viewers_see_the_builder_but_cant_change_it(): void
    {
        $this->jobApplication();
        $user = $this->user([FormPolicy::VIEW]);

        $this->actingAs($user)->get('/forms/job-application')->assertOk()->assertDontSee('data-form-builder-sortable', false);
        $this->actingAs($user)->get('/forms/job-application/sections/personal/fields/create?type=text')->assertForbidden();
        $this->actingAs($user)->delete('/forms/job-application/fields/age')->assertForbidden();
        $this->actingAs($user)->get('/forms/job-application/entries')->assertForbidden();
        $this->actingAs($user)->get('/forms/job-application/entries/export')->assertForbidden();
    }

    #[Test]
    public function publishing_needs_its_own_permission(): void
    {
        $form = $this->jobApplication();
        $user = $this->user([FormPolicy::EDIT]);

        $this->actingAs($user)->post('/forms/job-application/sections', ['key' => 'extra', 'title' => ['en' => 'Extra'], 'columns' => '1'])->assertRedirect();
        $this->actingAs($user)->post('/forms/job-application/publish')->assertForbidden();

        $this->assertEquals(1, $form->refresh()->publishedVersion->number);
    }

    #[Test]
    public function entry_permissions_are_separate(): void
    {
        $this->jobApplication();
        $user = $this->user([FormPolicy::VIEW_ENTRIES]);

        $this->actingAs($user)->get('/forms/job-application/entries')->assertOk()->assertDontSee('Export CSV');
        $this->actingAs($user)->get('/forms/job-application/entries/export')->assertForbidden();
    }

    #[Test]
    public function locked_forms_cant_be_changed(): void
    {
        JobApplication::definition()->locked()->sync();
        $user = $this->user();

        $this->actingAs($user)->get('/forms/job-application')->assertOk()->assertSee('defined in code');
        $this->actingAs($user)->get('/forms/job-application/edit')->assertForbidden();
        $this->actingAs($user)->delete('/forms/job-application/fields/age')->assertForbidden();
        $this->actingAs($user)->delete('/forms/job-application')->assertForbidden();
    }

    #[Test]
    public function it_works_for_another_guard_and_user_model(): void
    {
        $this->jobApplication();
        $admin = $this->admin();

        $this->actingAs($admin, 'web_admin')
            ->get('/admin/forms/job-application')
            ->assertOk()
            ->assertSee('/admin/forms/job-application/sections/personal/fields/create', false);

        $this->actingAs($admin, 'web_admin')
            ->post('/admin/forms/job-application/sections', ['key' => 'extra', 'title' => ['en' => 'Extra'], 'columns' => '1'])
            ->assertRedirect('/admin/forms/job-application');

        $this->actingAs($admin, 'web_admin')->post('/admin/forms/job-application/publish')->assertRedirect();

        $version = Form::query()->sole()->publishedVersion;

        $this->assertEquals(2, $version->number);
        $this->assertTrue($version->author->is($admin));
    }

    #[Test]
    public function the_policy_can_be_replaced(): void
    {
        $this->jobApplication();

        Gate::policy(Form::class, DenyAllPolicy::class);

        $this->actingAs($this->user())->get('/forms')->assertForbidden();
    }

    #[Test]
    public function the_controller_can_be_replaced(): void
    {
        Route::middleware('web')->group(function () {
            Route::formBuilder(['prefix' => 'custom/forms', 'name' => 'custom.forms', 'controllers' => ['forms' => CustomFormsController::class]]);
        });

        $this->actingAs($this->user())->getJson('/custom/forms')->assertOk()->assertExactJson(['custom' => true]);
    }
}
