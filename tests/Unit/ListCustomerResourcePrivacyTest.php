<?php

use App\Common\Constants\User\UserRole;
use App\Http\Resources\ListCustomerResource;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

function customerResourceForRole(int $role): array
{
    $viewer = new User(['role' => $role]);
    $customer = new User([
        'email' => 'customer@example.test',
        'telegram_id' => '123456789',
        'email_verified_at' => now(),
    ]);
    $customer->setRelation('referredBy', null);
    $customer->setRelation('wallet', null);

    $request = Request::create('/customers');
    $request->setUserResolver(fn () => $viewer);

    return (new ListCustomerResource($customer))->toArray($request);
}

it('hides customer email and Telegram from managers and employees', function (int $role) {
    $data = customerResourceForRole($role);

    expect($data['email'])->toBeNull()
        ->and($data['telegram_id'])->toBeNull()
        ->and($data['email_verified_at'])->toBeNull()
        ->and($data['using_telegram'])->toBeFalse();
})->with([
    UserRole::MANAGER->value,
    UserRole::EMPLOYEE->value,
]);

it('keeps customer email and Telegram visible to admins', function () {
    $data = customerResourceForRole(UserRole::ADMIN->value);

    expect($data['email'])->toBe('customer@example.test')
        ->and($data['telegram_id'])->toBe('123456789')
        ->and($data['email_verified_at'])->not->toBeNull()
        ->and($data['using_telegram'])->toBeTrue();
});
