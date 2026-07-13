<?php

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServicePackage\ServicePackagePaymentType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\User\UserRole;
use App\Common\Constants\Wallet\WalletStatus;
use App\Common\Constants\Wallet\WalletTransactionType;
use App\Models\ServicePackage;
use App\Models\ServicePackageAllowedUser;
use App\Models\ServiceUser;
use App\Models\User;
use App\Models\UserReferral;
use App\Models\UserWallet;
use App\Models\UserWalletTransaction;

function makePurchaseUser(int $role, string $prefix, bool $disabled = false): User
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

function makeWallet(User $user, float $balance): UserWallet
{
    return UserWallet::create([
        'user_id' => $user->id,
        'balance' => $balance,
        'status' => WalletStatus::ACTIVE->value,
    ]);
}

function makePackage(array $overrides = []): ServicePackage
{
    return ServicePackage::create(array_merge([
        'name' => 'Test Package',
        'description' => 'Test package description',
        'platform' => PlatformType::META->value,
        'payment_type' => ServicePackagePaymentType::PREPAY->value,
        'billing_source' => 'adviet_card',
        'features' => [],
        'monthly_spending_fee_structure' => [],
        'open_fee' => 10,
        'top_up_fee' => 0,
        'spending_fee' => 0,
        'supplier_fee_percent' => 0,
        'set_up_time' => 24,
        'disabled' => false,
        'range_min_top_up' => 20,
        'cashback_percent' => 0,
        'refund_open_fee' => false,
        'min_spend_for_refund' => null,
    ], $overrides));
}

it('allows an employee to purchase a package for an assigned customer and deducts the customer wallet', function () {
    $manager = makePurchaseUser(UserRole::MANAGER->value, 'manager');
    $employee = makePurchaseUser(UserRole::EMPLOYEE->value, 'employee');
    $customer = makePurchaseUser(UserRole::CUSTOMER->value, 'customer');

    UserReferral::create([
        'referrer_id' => $manager->id,
        'referred_id' => $employee->id,
    ]);
    UserReferral::create([
        'referrer_id' => $employee->id,
        'referred_id' => $customer->id,
    ]);

    $customerWallet = makeWallet($customer, 100);
    makeWallet($employee, 5);

    $package = makePackage();

    $response = $this
        ->actingAs($employee)
        ->from(route('service_purchase_index'))
        ->post(route('service_purchase_purchase'), [
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'payment_type' => 'prepay',
            'top_up_amount' => 20,
        ]);

    $response->assertRedirect(route('service_orders_index'));

    $serviceUser = ServiceUser::query()->where('package_id', $package->id)->first();

    expect($serviceUser)->not->toBeNull();
    expect((string) $serviceUser->user_id)->toBe((string) $customer->id);
    expect((int) $serviceUser->status)->toBe(ServiceUserStatus::PENDING->value);

    $customerWallet->refresh();
    expect((float) $customerWallet->balance)->toBe(70.0);

    $walletTransaction = UserWalletTransaction::query()
        ->where('wallet_id', $customerWallet->id)
        ->where('type', WalletTransactionType::SERVICE_PURCHASE->value)
        ->first();

    expect($walletTransaction)->not->toBeNull();
    expect((float) $walletTransaction->amount)->toBe(-30.0);
});

it('prevents a manager from purchasing for a customer outside their scope', function () {
    $manager = makePurchaseUser(UserRole::MANAGER->value, 'manager_out');
    $employee = makePurchaseUser(UserRole::EMPLOYEE->value, 'employee_out');
    $managedCustomer = makePurchaseUser(UserRole::CUSTOMER->value, 'managed_customer');
    $outsideCustomer = makePurchaseUser(UserRole::CUSTOMER->value, 'outside_customer');

    UserReferral::create([
        'referrer_id' => $manager->id,
        'referred_id' => $employee->id,
    ]);
    UserReferral::create([
        'referrer_id' => $employee->id,
        'referred_id' => $managedCustomer->id,
    ]);

    $outsideWallet = makeWallet($outsideCustomer, 100);
    $package = makePackage();

    $response = $this
        ->actingAs($manager)
        ->from(route('service_purchase_index'))
        ->post(route('service_purchase_purchase'), [
            'customer_id' => $outsideCustomer->id,
            'package_id' => $package->id,
            'payment_type' => 'prepay',
            'top_up_amount' => 20,
        ]);

    $response->assertRedirect(route('service_purchase_index'));

    expect(ServiceUser::query()->where('user_id', $outsideCustomer->id)->exists())->toBeFalse();

    $outsideWallet->refresh();
    expect((float) $outsideWallet->balance)->toBe(100.0);
});

it('allows staff to buy a private package only when the selected customer is allowed', function () {
    $employee = makePurchaseUser(UserRole::EMPLOYEE->value, 'employee_private');
    $allowedCustomer = makePurchaseUser(UserRole::CUSTOMER->value, 'allowed_customer');
    $otherCustomer = makePurchaseUser(UserRole::CUSTOMER->value, 'other_customer');

    UserReferral::create([
        'referrer_id' => $employee->id,
        'referred_id' => $allowedCustomer->id,
    ]);
    UserReferral::create([
        'referrer_id' => $employee->id,
        'referred_id' => $otherCustomer->id,
    ]);

    makeWallet($allowedCustomer, 100);
    $otherWallet = makeWallet($otherCustomer, 100);

    $privatePackage = makePackage([
        'name' => 'Private Package',
    ]);

    ServicePackageAllowedUser::create([
        'service_package_id' => $privatePackage->id,
        'user_id' => $allowedCustomer->id,
    ]);

    $allowedResponse = $this
        ->actingAs($employee)
        ->from(route('service_purchase_index', ['customer_id' => $allowedCustomer->id]))
        ->post(route('service_purchase_purchase'), [
            'customer_id' => $allowedCustomer->id,
            'package_id' => $privatePackage->id,
            'payment_type' => 'prepay',
            'top_up_amount' => 20,
        ]);

    $allowedResponse->assertRedirect(route('service_orders_index'));
    expect(ServiceUser::query()
        ->where('package_id', $privatePackage->id)
        ->where('user_id', $allowedCustomer->id)
        ->exists())->toBeTrue();

    $deniedResponse = $this
        ->actingAs($employee)
        ->from(route('service_purchase_index', ['customer_id' => $otherCustomer->id]))
        ->post(route('service_purchase_purchase'), [
            'customer_id' => $otherCustomer->id,
            'package_id' => $privatePackage->id,
            'payment_type' => 'prepay',
            'top_up_amount' => 20,
        ]);

    $deniedResponse->assertRedirect(route('service_purchase_index', ['customer_id' => $otherCustomer->id]));
    expect(ServiceUser::query()
        ->where('package_id', $privatePackage->id)
        ->where('user_id', $otherCustomer->id)
        ->exists())->toBeFalse();

    $otherWallet->refresh();
    expect((float) $otherWallet->balance)->toBe(100.0);
});

it('forbids customer wallet balance access outside employee scope', function () {
    $employee = makePurchaseUser(UserRole::EMPLOYEE->value, 'employee_wallet');
    $assignedCustomer = makePurchaseUser(UserRole::CUSTOMER->value, 'assigned_wallet_customer');
    $outsideCustomer = makePurchaseUser(UserRole::CUSTOMER->value, 'outside_wallet_customer');

    UserReferral::create([
        'referrer_id' => $employee->id,
        'referred_id' => $assignedCustomer->id,
    ]);

    makeWallet($assignedCustomer, 88);
    makeWallet($outsideCustomer, 77);

    $forbidden = $this
        ->actingAs($employee)
        ->getJson(route('wallet_customer_balance', ['userId' => $outsideCustomer->id]));

    $forbidden->assertStatus(403);

    $allowed = $this
        ->actingAs($employee)
        ->getJson(route('wallet_customer_balance', ['userId' => $assignedCustomer->id]));

    $allowed
        ->assertOk()
        ->assertJsonPath('data.balance', 88);
});
