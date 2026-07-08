<?php

use App\Common\Constants\Platform\PlatformType;
use App\Http\Resources\ServiceOrderResource;
use App\Models\MetaAccount;
use App\Models\ServicePackage;
use App\Models\ServiceUser;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

uses(Tests\TestCase::class);

it('prefers linked meta accounts when resolving edit dialog assignments', function () {
    $serviceUser = new ServiceUser([
        'id' => '1',
        'status' => 2,
        'budget' => '0',
        'config_account' => [
            'bm_id' => 'legacy-bm',
            'account_id' => 'act_legacy',
            'account_ids' => ['act_legacy'],
        ],
    ]);

    $serviceUser->setRelation('package', new ServicePackage([
        'id' => 'pkg-1',
        'name' => 'Meta package',
        'platform' => PlatformType::META->value,
        'payment_type' => 'prepay',
    ]));

    $serviceUser->setRelation('metaAccount', new Collection([
        new MetaAccount([
            'account_id' => 'act_1',
            'business_manager_id' => 'bm_1',
        ]),
        new MetaAccount([
            'account_id' => 'act_2',
            'business_manager_id' => 'bm_2',
        ]),
    ]));

    $data = (new ServiceOrderResource($serviceUser))->toArray(new Request());

    expect($data['config_account']['resolved_account_ids'])->toBe(['act_1', 'act_2'])
        ->and($data['config_account']['resolved_bm_ids'])->toBe(['bm_1', 'bm_2']);
});
