<?php

namespace App\Http\Controllers\API;

use App\Common\Constants\Config\ConfigName;
use App\Core\Controller;
use App\Core\RestResponse;
use App\Service\ConfigService;

class ConfigController extends Controller
{
    public function __construct(
        protected ConfigService $configService,
    ) {
    }

    public function getPostpayMinBalance(): \Illuminate\Http\JsonResponse
    {
        $value = $this->configService->getValue(ConfigName::POSTPAY_MIN_BALANCE->value);
        $postpayMinBalance = is_numeric($value) ? (float) $value : null;

        return RestResponse::success([
            'postpay_min_balance' => $postpayMinBalance,
        ]);
    }
}

