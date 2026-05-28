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
                'value' => $this->defaultValue($configName, $configs),
            ];
        }

        return $this->rendering(
            view: 'config/index',
            data: [
                'configs' => $allConfigs,
                'coinRemitterNetworks' => $this->coinRemitterNetworks(),
                'paymentoWebhookUrl' => route('paymento_webhook'),
            ]
        );
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'configs' => ['required', 'array'],
            'configs.*' => ['nullable', 'string'],
        ]);

        $methodKey = ConfigName::CRYPTO_DEPOSIT_METHOD->value;
        if (
            isset($validated['configs'][$methodKey])
            && !in_array($validated['configs'][$methodKey], ['manual', 'coinremitter', 'paymento'], true)
        ) {
            $validated['configs'][$methodKey] = 'manual';
        }

        $result = $this->configService->update($validated['configs']);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }

    private function defaultValue(ConfigName $configName, array $configs = []): string
    {
        return match ($configName) {
            ConfigName::CRYPTO_DEPOSIT_METHOD => $this->defaultDepositMethod($configs),
            default => '',
        };
    }

    private function defaultDepositMethod(array $configs): string
    {
        $hasManualWallet = !empty($configs[ConfigName::BEP20_WALLET_ADDRESS->value]['value'] ?? null)
            || !empty($configs[ConfigName::TRC20_WALLET_ADDRESS->value]['value'] ?? null);

        if ($hasManualWallet) {
            return 'manual';
        }

        if (count($this->coinRemitterNetworks()) > 0) {
            return 'coinremitter';
        }

        $hasPaymento = !empty($configs[ConfigName::PAYMENTO_API_KEY->value]['value'] ?? null)
            || !empty(config('services.paymento.api_key'));

        return $hasPaymento ? 'paymento' : 'manual';
    }

    private function coinRemitterNetworks(): array
    {
        return collect((array) config('services.coinremitter.networks', []))
            ->filter(fn($credentials) => !empty($credentials['coin'] ?? null))
            ->keys()
            ->map(fn($network) => strtoupper((string) $network))
            ->values()
            ->all();
    }
}
