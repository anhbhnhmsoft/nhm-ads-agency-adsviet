<?php

namespace App\Http\Controllers;

use App\Common\Constants\Platform\PlatformSettingFields;
use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\QueueKey\QueueKey;
use App\Common\Constants\User\UserRole;
use App\Core\FlashMessage;
use App\Core\Controller;
use App\Http\Requests\PlatformSetting\PlatformSettingStoreRequest;
use App\Http\Requests\PlatformSetting\PlatformSettingUpdateRequest;
use App\Http\Requests\PlatformSetting\PlatformSettingToggleRequest;
use App\Service\AuthService;
use App\Service\MetaBusinessService;
use App\Service\PlatformSettingService;
use App\Service\GoogleAdsService;
use App\Service\MetaService;
use App\Service\PlatformTokenHealthService;
use App\Http\Resources\PlatformSettingListResource;
use App\Jobs\GoogleAds\SyncGooglePlatformJob;
use App\Jobs\MetaApi\SyncMetaPlatformJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Core\Logging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PlatformSettingController extends Controller
{
    private const PLATFORM_SYNC_COOLDOWN_SECONDS = 300;

    public function __construct(
        protected PlatformSettingService $platformSettingService,
        protected AuthService $authService,
        protected MetaBusinessService $metaBusinessService,
        protected GoogleAdsService $googleAdsService,
        protected MetaService $metaService,
        protected PlatformTokenHealthService $platformTokenHealthService,
    ) {
    }

    public function index()
    {
        $result = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->back();
        }
        return $this->rendering(
            view: 'config/list-platform-settings',
            data: [
                'googleFields' => PlatformSettingFields::getGoogleFields(),
                'metaFields' => PlatformSettingFields::getMetaFields(),
            ]
        );
    }

    public function getByPlatform(int $platform)
    {
        $result = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->back();
        }
        $result = $this->platformSettingService->findByPlatform($platform);
        if ($result->isError()) {
            return response()->json(['data' => []], 404);
        }
        return response()->json(['data' => PlatformSettingListResource::collection($result->getData())]);
    }

    public function store(PlatformSettingStoreRequest $request): RedirectResponse
    {
        $result = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->back();
        }
        $validated = $request->validated();
        $result = $this->platformSettingService->create($validated);
        if ($result->isSuccess()) {
            $settingId = (string)$result->getData()->id;
            $platform = (int) ($validated['platform'] ?? 0);
            $config = $validated['config'] ?? [];
            $this->syncPlatformAccounts($platform, $config, $settingId);

            FlashMessage::success($result->getMessage() ?? __('common_success.create_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }

    public function update(string $id, PlatformSettingUpdateRequest $request): RedirectResponse
    {
        $result = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->back();
        }
        $validated = $request->validated();
        $result = $this->platformSettingService->update($id, $validated);
        if ($result->isSuccess()) {
            $settingId = (string)$result->getData()->id;
            $platform = (int) ($validated['platform'] ?? 0);
            $config = $validated['config'] ?? [];
            $this->syncPlatformAccounts($platform, $config, $settingId);

            FlashMessage::success($result->getMessage() ?? __('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }

    public function toggle(string $id, PlatformSettingToggleRequest $request): RedirectResponse
    {
        $result = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->back();
        }
        $result = $this->platformSettingService->toggleDisabled($id, $request->validated()['disabled']);
        if ($result->isSuccess()) {
            FlashMessage::success($result->getMessage() ?? __('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }

    public function destroy(string $id): RedirectResponse
    {
        $result = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->back();
        }
        $result = $this->platformSettingService->delete($id);
        if ($result->isSuccess()) {
            FlashMessage::success($result->getMessage() ?? __('common_success.delete_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }

    public function checkToken(string $id): JsonResponse
    {
        $result = $this->authService->checkAccess([UserRole::ADMIN->value]);
        if ($result->isError()) {
            return response()->json([
                'message' => $result->getMessage(),
            ], 403);
        }

        $result = $this->platformSettingService->find($id);
        if ($result->isError()) {
            return response()->json([
                'message' => $result->getMessage(),
            ], 404);
        }

        $setting = $result->getData();
        $config = (array) ($setting->config ?? []);
        $tokenStatus = $this->platformTokenHealthService->check((int) $setting->platform, $config);

        $config['token_status'] = $tokenStatus;
        $setting->update(['config' => $config]);

        return response()->json([
            'data' => [
                'id' => (string) $setting->id,
                'token_status' => $tokenStatus,
            ],
            'message' => $tokenStatus['message'] ?? __('common_success.update_success'),
        ]);
    }

    /**
     * Chuyển đổi ngữ cảnh BM/MCC đang quản lý (lưu vào session)
     */
    public function switchContext(Request $request): RedirectResponse
    {
        $platform = (int) $request->input('platform');
        $id = (string) $request->input('id');

        $result = $this->authService->checkAccess([UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::EMPLOYEE->value]);
        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->back();
        }

        $check = $this->platformSettingService->findPlatformActive($platform, $id);
        if ($check->isError()) {
            FlashMessage::error(__('platform_setting.not_found_or_disabled'));
            return redirect()->back();
        }

        if ($platform === PlatformType::META->value) {
            session(['active_meta_setting_id' => $id]);
        } elseif ($platform === PlatformType::GOOGLE->value) {
            session(['active_google_setting_id' => $id]);
        }

        FlashMessage::success(__('platform_setting.switch_success'));
        return redirect()->back();
    }

    /**
     * Đồng bộ tài khoản quảng cáo từ cấu hình nền tảng
     * Được gọi ngay sau khi lưu / cập nhật cấu hình platform.
     */
    private function syncPlatformAccounts(int $platform, array $config, ?string $settingId = null): void
    {
        try {
            if (!$this->reservePlatformSyncSlot($platform, $settingId ?: 'default')) {
                Logging::web('PlatformSettingController@syncPlatformAccounts: skipped because sync cooldown is active', [
                    'platform' => $platform,
                    'setting_id' => $settingId,
                ]);
                return;
            }

            if ($platform === PlatformType::GOOGLE->value) {
                $loginCustomerId = $config['login_customer_id'] ?? null;
                if ($loginCustomerId) {
                    SyncGooglePlatformJob::dispatch((string) $loginCustomerId, $settingId);
                } else {
                    Logging::web(
                        'PlatformSettingController@syncPlatformAccounts: No login_customer_id, skipping Google sync'
                    );
                }
            } elseif ($platform === PlatformType::META->value) {
                $bmId = $this->platformSettingService->getMetaScopedBusinessManagerId($config);
                SyncMetaPlatformJob::dispatch($bmId ? (string) $bmId : null, $settingId);
            }
        } catch (\Throwable $e) {
            Logging::error(
                message: 'PlatformSettingController@syncPlatformAccounts error: ' . $e->getMessage(),
                context: [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
                exception: $e
            );
        }
    }

    private function reservePlatformSyncSlot(int $platform, string $scope): bool
    {
        $platformKey = match ($platform) {
            PlatformType::META->value => 'meta',
            PlatformType::GOOGLE->value => 'google',
            default => (string) $platform,
        };

        return Cache::add(
            "platform-sync-cooldown:{$platformKey}:{$scope}",
            now()->toDateTimeString(),
            self::PLATFORM_SYNC_COOLDOWN_SECONDS,
        );
    }
}
