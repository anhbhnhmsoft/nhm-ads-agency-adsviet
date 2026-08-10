<?php

use App\Common\Constants\User\UserRole;
use App\Models\User;
use App\Models\UserReferral;
use App\Service\UserPreviewService;
use Inertia\Testing\AssertableInertia as Assert;

function makePreviewUser(int $role, string $prefix, bool $disabled = false): User
{
    static $counter = 0;
    $counter++;

    return User::create([
        'name' => ucfirst($prefix).' '.$counter,
        'username' => "{$prefix}_{$counter}",
        'email' => "{$prefix}_{$counter}@example.test",
        'email_verified_at' => now(),
        'phone' => null,
        'password' => bcrypt('password'),
        'role' => $role,
        'disabled' => $disabled,
        'referral_code' => strtoupper($prefix)."_REF_{$counter}",
    ]);
}

it('lets an admin start preview and uses the preview user on customer-facing routes', function () {
    $admin = makePreviewUser(UserRole::ADMIN->value, 'admin_preview');
    $customer = makePreviewUser(UserRole::CUSTOMER->value, 'customer_preview');

    $this->actingAs($admin)
        ->from(route('user_list'))
        ->post(route('admin_preview_start', ['userId' => $customer->id]), [
            'return_url' => route('user_list'),
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(UserPreviewService::SESSION_KEY, (string) $customer->id)
        ->assertSessionHas(UserPreviewService::RETURN_URL_SESSION_KEY, route('user_list'));

    $this->actingAs($admin)
        ->withSession([UserPreviewService::SESSION_KEY => (string) $customer->id])
        ->get(route('contact_index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('contact/index')
            ->where('auth.id', (string) $customer->id)
            ->where('auth.role', UserRole::CUSTOMER->value)
            ->where('auth_actor.id', (string) $admin->id)
            ->where('preview_context.is_active', true)
            ->where('preview_context.target.id', (string) $customer->id));
});

it('keeps the real admin auth on admin routes even when preview mode is active', function () {
    $admin = makePreviewUser(UserRole::ADMIN->value, 'admin_list');
    $customer = makePreviewUser(UserRole::CUSTOMER->value, 'customer_list');

    $this->actingAs($admin)
        ->withSession([UserPreviewService::SESSION_KEY => (string) $customer->id])
        ->get(route('user_list'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('user/list-customer')
            ->where('auth.id', (string) $admin->id)
            ->where('auth_actor.id', (string) $admin->id)
            ->where('preview_context.is_active', true)
            ->where('preview_context.target.id', (string) $customer->id));
});

it('lets an admin start preview for an employee account', function () {
    $admin = makePreviewUser(UserRole::ADMIN->value, 'admin_preview_employee');
    $employee = makePreviewUser(UserRole::EMPLOYEE->value, 'employee_preview');

    $this->actingAs($admin)
        ->from(route('user_list_employee'))
        ->post(route('admin_preview_start', ['userId' => $employee->id]), [
            'return_url' => route('user_list_employee'),
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(UserPreviewService::SESSION_KEY, (string) $employee->id)
        ->assertSessionHas(UserPreviewService::RETURN_URL_SESSION_KEY, route('user_list_employee'));

    $this->actingAs($admin)
        ->withSession([UserPreviewService::SESSION_KEY => (string) $employee->id])
        ->get(route('contact_index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('contact/index')
            ->where('auth.id', (string) $employee->id)
            ->where('auth.role', UserRole::EMPLOYEE->value)
            ->where('auth_actor.id', (string) $admin->id)
            ->where('preview_context.is_active', true)
            ->where('preview_context.target.id', (string) $employee->id));
});

it('shows managers the full customer list without ownership scope', function () {
    $manager = makePreviewUser(UserRole::MANAGER->value, 'manager_full_list');
    $customer = makePreviewUser(UserRole::CUSTOMER->value, 'unowned_customer');
    $agency = makePreviewUser(UserRole::AGENCY->value, 'unowned_agency');
    $disabledCustomer = makePreviewUser(UserRole::CUSTOMER->value, 'disabled_list_customer', true);
    makePreviewUser(UserRole::EMPLOYEE->value, 'employee_not_customer');

    $expectedIds = collect([
        (string) $customer->id,
        (string) $agency->id,
        (string) $disabledCustomer->id,
    ])->sort()->values()->all();

    $this->actingAs($manager)
        ->get(route('user_list'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('user/list-customer')
            ->has('paginator.data', 3)
            ->where('paginator.data', function ($customers) use ($expectedIds): bool {
                return collect($customers)
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->sort()
                    ->values()
                    ->all() === $expectedIds;
            }));
});

it('keeps the employee filter available on the full manager customer list', function () {
    $manager = makePreviewUser(UserRole::MANAGER->value, 'manager_filter');
    $employee = makePreviewUser(UserRole::EMPLOYEE->value, 'filter_employee');
    $otherEmployee = makePreviewUser(UserRole::EMPLOYEE->value, 'other_filter_employee');
    $customer = makePreviewUser(UserRole::CUSTOMER->value, 'filtered_customer');
    $otherCustomer = makePreviewUser(UserRole::CUSTOMER->value, 'other_filtered_customer');

    UserReferral::create([
        'referrer_id' => $manager->id,
        'referred_id' => $employee->id,
    ]);
    UserReferral::create([
        'referrer_id' => $employee->id,
        'referred_id' => $customer->id,
    ]);
    UserReferral::create([
        'referrer_id' => $otherEmployee->id,
        'referred_id' => $otherCustomer->id,
    ]);

    $this->actingAs($manager)
        ->get(route('user_list', [
            'filter' => ['employee_id' => $employee->id],
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('user/list-customer')
            ->has('paginator.data', 1)
            ->where('paginator.data.0.id', (string) $customer->id));
});

it('lets a manager preview any active customer-facing account', function (int $role) {
    $manager = makePreviewUser(UserRole::MANAGER->value, 'manager_any_preview');
    $target = makePreviewUser($role, 'global_preview_target');

    $this->actingAs($manager)
        ->post(route('admin_preview_start', ['userId' => $target->id]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(UserPreviewService::SESSION_KEY, (string) $target->id);
})->with([
    'customer' => UserRole::CUSTOMER->value,
    'agency' => UserRole::AGENCY->value,
]);

it('uses the manager preview target on customer-facing routes', function () {
    $manager = makePreviewUser(UserRole::MANAGER->value, 'manager_preview_context');
    $customer = makePreviewUser(UserRole::CUSTOMER->value, 'global_context_customer');

    $this->actingAs($manager)
        ->withSession([UserPreviewService::SESSION_KEY => (string) $customer->id])
        ->get(route('contact_index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('contact/index')
            ->where('auth.id', (string) $customer->id)
            ->where('auth.role', UserRole::CUSTOMER->value)
            ->where('auth_actor.id', (string) $manager->id)
            ->where('preview_context.is_active', true)
            ->where('preview_context.target.id', (string) $customer->id));
});

it('forbids a manager from previewing disabled customer-facing accounts', function (int $role) {
    $manager = makePreviewUser(UserRole::MANAGER->value, 'manager_disabled_preview');
    $target = makePreviewUser($role, 'disabled_preview_target', true);

    $this->actingAs($manager)
        ->post(route('admin_preview_start', ['userId' => $target->id]))
        ->assertRedirect(route('user_list'))
        ->assertSessionMissing(UserPreviewService::SESSION_KEY);
})->with([
    'customer' => UserRole::CUSTOMER->value,
    'agency' => UserRole::AGENCY->value,
]);

it('forbids a manager from previewing an employee account', function () {
    $manager = makePreviewUser(UserRole::MANAGER->value, 'manager_staff');
    $employee = makePreviewUser(UserRole::EMPLOYEE->value, 'manager_staff_employee');

    UserReferral::create([
        'referrer_id' => $manager->id,
        'referred_id' => $employee->id,
    ]);

    $this->actingAs($manager)
        ->post(route('admin_preview_start', ['userId' => $employee->id]))
        ->assertRedirect(route('user_list'))
        ->assertSessionMissing(UserPreviewService::SESSION_KEY);
});

it('blocks mutations while preview mode is active until the admin exits preview', function () {
    $admin = makePreviewUser(UserRole::ADMIN->value, 'admin_mutation');
    $customer = makePreviewUser(UserRole::CUSTOMER->value, 'customer_mutation');

    $this->actingAs($admin)
        ->withSession([UserPreviewService::SESSION_KEY => (string) $customer->id])
        ->from(route('profile'))
        ->put(route('profile_update'), [
            'name' => 'Changed Name',
        ])
        ->assertRedirect(route('profile'))
        ->assertSessionHas('warning');

    $admin->refresh();
    expect($admin->name)->not->toBe('Changed Name');

    $this->actingAs($admin)
        ->withSession([
            UserPreviewService::SESSION_KEY => (string) $customer->id,
            UserPreviewService::RETURN_URL_SESSION_KEY => route('user_list'),
        ])
        ->post(route('admin_preview_stop'))
        ->assertRedirect(route('user_list'))
        ->assertSessionMissing(UserPreviewService::SESSION_KEY)
        ->assertSessionMissing(UserPreviewService::RETURN_URL_SESSION_KEY);
});

it('forbids employees from starting preview mode', function () {
    $employee = makePreviewUser(UserRole::EMPLOYEE->value, 'employee_preview');
    $customer = makePreviewUser(UserRole::CUSTOMER->value, 'customer_preview_blocked');

    UserReferral::create([
        'referrer_id' => $employee->id,
        'referred_id' => $customer->id,
    ]);

    $this->actingAs($employee)
        ->post(route('admin_preview_start', ['userId' => $customer->id]))
        ->assertRedirect(route('user_list'))
        ->assertSessionMissing(UserPreviewService::SESSION_KEY);
});
