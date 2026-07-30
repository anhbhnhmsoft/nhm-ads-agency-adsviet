<?php

use App\Service\MetaAdsNotificationService;
use App\Service\MetaService;

it('converts Meta minor units before evaluating spending alerts', function (string $serviceClass) {
    $service = (new ReflectionClass($serviceClass))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($serviceClass, 'normalizeMetaAccountMoney');

    expect($method->invoke($service, '1101', 'USD'))->toBe(11.01)
        ->and($method->invoke($service, '189', 'USD'))->toBe(1.89)
        ->and($method->invoke($service, '1101', 'VND'))->toBe(1101.0);
})->with([
    MetaService::class,
    MetaAdsNotificationService::class,
]);
