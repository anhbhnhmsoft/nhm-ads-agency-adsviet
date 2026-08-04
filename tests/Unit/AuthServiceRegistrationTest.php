<?php

use App\Common\Constants\User\UserRole;
use App\Models\User;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserRepository;
use App\Service\AuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class);

function registrationData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'username' => 'test-user',
        'email' => 'test@example.com',
        'password' => 'Password@123',
        'role' => UserRole::CUSTOMER->value,
    ], $overrides);
}

function makeAuthService(
    UserRepository $userRepository,
    UserReferralRepository $userReferralRepository,
): AuthService {
    return new AuthService(
        $userRepository,
        Mockery::mock(UserDeviceRepository::class),
        $userReferralRepository,
    );
}

beforeEach(function () {
    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('commit')->once();
    DB::shouldReceive('rollBack')->zeroOrMoreTimes();
    Cache::shouldReceive('put')->once()->andReturnTrue();
});

it('registers without a referral code and does not create a referral relation', function () {
    $newUser = new User(registrationData());
    $newUser->id = 'new-user-id';

    $userRepository = Mockery::mock(UserRepository::class);
    $userRepository->shouldNotReceive('getUserToRegisterByReferCode');
    $userRepository->shouldReceive('create')->once()->andReturn($newUser);

    $referralRepository = Mockery::mock(UserReferralRepository::class);
    $referralRepository->shouldNotReceive('create');

    $result = makeAuthService($userRepository, $referralRepository)
        ->handleRegister(registrationData());

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getData()['user']->id)->toBe('new-user-id');
});

it('keeps creating the referral relation when a valid code is supplied', function () {
    $referrer = new User();
    $referrer->id = 'referrer-id';

    $newUser = new User(registrationData());
    $newUser->id = 'new-user-id';

    $userRepository = Mockery::mock(UserRepository::class);
    $userRepository->shouldReceive('getUserToRegisterByReferCode')
        ->once()
        ->with('VALID-REF')
        ->andReturn($referrer);
    $userRepository->shouldReceive('create')->once()->andReturn($newUser);

    $referralRepository = Mockery::mock(UserReferralRepository::class);
    $referralRepository->shouldReceive('create')
        ->once()
        ->with([
            'referrer_id' => 'referrer-id',
            'referred_id' => 'new-user-id',
        ]);

    $result = makeAuthService($userRepository, $referralRepository)
        ->handleRegister(registrationData(['refer_code' => 'VALID-REF']));

    expect($result->isSuccess())->toBeTrue();
});
