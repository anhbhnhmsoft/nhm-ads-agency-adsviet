<?php

namespace App\Http\Controllers;

use App\Common\Constants\Config\ConfigName;
use App\Common\Constants\Config\ConfigType;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Service\ConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function __construct(
        protected ConfigService $configService,
    ) {
    }

    public function index(Request $request)
    {
        $result = $this->configService->getAll();
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            $configs = [];
        } else {
            $configs = $result->getData();
        }

        $allConfigs = [];
        foreach (ConfigName::cases() as $configName) {
            $key = $configName->value;
            $allConfigs[$key] = $configs[$key] ?? [
                'id' => null,
                'key' => $key,
                'type' => ConfigType::STRING->value,
                'value' => '',
            ];
        }

        return $this->rendering(
            view: 'config/index',
            data: [
                'configs' => $allConfigs,
            ]
        );
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'configs' => ['required', 'array'],
            'configs.*' => ['nullable', 'string'],
        ]);

        $result = $this->configService->update($validated['configs']);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }
}

