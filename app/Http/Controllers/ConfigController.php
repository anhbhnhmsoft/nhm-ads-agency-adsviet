<?php

namespace App\Http\Controllers;

use App\Common\Constants\Config\ConfigName;
use App\Common\Constants\Config\ConfigType;
use App\Common\Constants\User\UserRole;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Service\AuthService;
use App\Service\ConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function __construct(
        protected ConfigService $configService,
        protected AuthService $authService,
    ) {}

    public function index(Request $request)
    {
        $access = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($access->isError()) {
            FlashMessage::error($access->getMessage());

            return redirect()->back();
        }

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
        $access = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($access->isError()) {
            FlashMessage::error($access->getMessage());

            return redirect()->back();
        }

        $validated = $request->validate([
            'configs' => ['required', 'array'],
            'configs.*' => ['nullable', 'string'],
        ]);

        $methodKey = ConfigName::CRYPTO_DEPOSIT_METHOD->value;
        if (
            isset($validated['configs'][$methodKey])
            && ! in_array($validated['configs'][$methodKey], ['manual', 'coinremitter', 'paymento'], true)
        ) {
            $validated['configs'][$methodKey] = 'manual';
        }
        unset($validated['configs'][ConfigName::DASHBOARD_GUIDE_CONTENT->value]);

        $result = $this->configService->update($validated['configs']);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }

        return redirect()->back();
    }

    public function dashboardGuide()
    {
        $access = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($access->isError()) {
            FlashMessage::error($access->getMessage());

            return redirect()->back();
        }

        return $this->rendering(
            view: 'config/dashboard-guide',
            data: [
                'content' => $this->configService->getValue(ConfigName::DASHBOARD_GUIDE_CONTENT, ''),
            ],
        );
    }

    public function updateDashboardGuide(Request $request): RedirectResponse
    {
        $access = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($access->isError()) {
            FlashMessage::error($access->getMessage());

            return redirect()->back();
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:50000'],
        ]);

        $result = $this->configService->update([
            ConfigName::DASHBOARD_GUIDE_CONTENT->value => $this->sanitizeDashboardGuideContent($validated['content'] ?? ''),
        ]);

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
            ConfigName::DASHBOARD_GUIDE_CONTENT => '',
            default => '',
        };
    }

    private function sanitizeDashboardGuideContent(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? '';
        $html = preg_replace('/<\s*div\b([^>]*)>/i', '<p$1>', $html) ?? '';
        $html = preg_replace('/<\s*\/\s*div\s*>/i', '</p>', $html) ?? '';

        $allowedTags = '<p><br><strong><b><em><i><u><s><ul><ol><li><h2><h3><h4><blockquote><a><img><figure><figcaption><iframe>';
        $html = strip_tags($html, $allowedTags);
        $html = preg_replace('/\s(on\w+|style|class|id)=(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/(href|src)=("|\')\s*javascript:[^"\']*("|\')/i', '$1=""', $html) ?? '';

        $html = preg_replace_callback('/<a\b([^>]*)>/i', function (array $matches): string {
            $href = $this->extractHtmlAttribute($matches[1], 'href');
            if (! $this->isSafeContentUrl($href, allowRelative: true)) {
                $href = '';
            }

            return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer">';
        }, $html) ?? '';

        $html = preg_replace_callback('/<img\b([^>]*)>/i', function (array $matches): string {
            $src = $this->extractHtmlAttribute($matches[1], 'src');
            if (! $this->isSafeContentUrl($src, allowRelative: true)) {
                return '';
            }

            $alt = $this->extractHtmlAttribute($matches[1], 'alt');

            return '<img src="'.e($src).'" alt="'.e($alt).'">';
        }, $html) ?? '';

        $html = preg_replace_callback('/<iframe\b([^>]*)>\s*<\/iframe>/i', function (array $matches): string {
            $src = $this->extractHtmlAttribute($matches[1], 'src');
            if (! $this->isYouTubeEmbedUrl($src)) {
                return '';
            }

            $title = $this->extractHtmlAttribute($matches[1], 'title') ?: 'Instruction video';

            return '<iframe src="'.e($src).'" title="'.e($title).'" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
        }, $html) ?? '';

        return trim($html);
    }

    private function extractHtmlAttribute(string $attributes, string $name): string
    {
        if (! preg_match('/\b'.preg_quote($name, '/').'\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attributes, $matches)) {
            return '';
        }

        return html_entity_decode($matches[2] ?? $matches[3] ?? $matches[4] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function isSafeContentUrl(string $url, bool $allowRelative = false): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        if ($allowRelative && str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true);
    }

    private function isYouTubeEmbedUrl(string $url): bool
    {
        if (! $this->isSafeContentUrl($url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        return in_array($host, ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com'], true)
            && preg_match('#^/embed/[A-Za-z0-9_-]{6,}$#', $path) === 1;
    }

    private function defaultDepositMethod(array $configs): string
    {
        $hasManualWallet = ! empty($configs[ConfigName::BEP20_WALLET_ADDRESS->value]['value'] ?? null)
            || ! empty($configs[ConfigName::TRC20_WALLET_ADDRESS->value]['value'] ?? null);

        if ($hasManualWallet) {
            return 'manual';
        }

        if (count($this->coinRemitterNetworks()) > 0) {
            return 'coinremitter';
        }

        $hasPaymento = ! empty($configs[ConfigName::PAYMENTO_API_KEY->value]['value'] ?? null)
            || ! empty(config('services.paymento.api_key'));

        return $hasPaymento ? 'paymento' : 'manual';
    }

    private function coinRemitterNetworks(): array
    {
        return collect((array) config('services.coinremitter.networks', []))
            ->filter(fn ($credentials) => ! empty($credentials['coin'] ?? null))
            ->keys()
            ->map(fn ($network) => strtoupper((string) $network))
            ->values()
            ->all();
    }
}
