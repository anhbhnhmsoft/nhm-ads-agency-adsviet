<?php

namespace App\Http\Controllers;

use App\Common\Constants\Platform\PlatformSettingFields;
use App\Common\Constants\Platform\PlatformType;
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
use App\Http\Resources\PlatformSettingListResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Core\Logging;

class PlatformSettingController extends Controller
{
    public function __construct(
        protected PlatformSettingService $platformSettingService,
        protected AuthService            $authService,
        protected MetaBusinessService   $metaBusinessService,
        protected GoogleAdsService      $googleAdsService,
        protected MetaService           $metaService,
    )
    {
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
            return response()->json(['data' => null], 404);
        }
        return response()->json(['data' => new PlatformSettingListResource($result->getData())]);
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
            $platform = (int) ($validated['platform'] ?? 0);
            $config = $validated['config'] ?? [];
            $this->syncPlatformAccounts($platform, $config);

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
            $platform = (int) ($validated['platform'] ?? 0);
            $config = $validated['config'] ?? [];
            $this->syncPlatformAccounts($platform, $config);

            FlashMessage::success(__('common_success.update_success'));
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

    /**
     * Đồng bộ tài khoản quảng cáo từ cấu hình nền tảng
     * Được gọi ngay sau khi lưu / cập nhật cấu hình platform.
     */
    private function syncPlatformAccounts(int $platform, array $config): void
    {
        try {
            if ($platform === PlatformType::GOOGLE->value) {
                $loginCustomerId = $config['login_customer_id'] ?? null;
                if ($loginCustomerId) {
                    $this->googleAdsService->syncFromManagerId((string) $loginCustomerId);
                }
            } elseif ($platform === PlatformType::META->value) {
                $bmId = $config['business_manager_id'] ?? null;
                if ($bmId) {
                    $this->metaService->syncFromBusinessManagerId((string) $bmId);
                }
            }
        } catch (\Throwable $e) {
            Logging::error(
                message: 'PlatformSettingController@syncPlatformAccounts error: ' . $e->getMessage(),
                exception: $e
            );
        }
    }
}


