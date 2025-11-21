<?php

namespace App\Http\Controllers;

use App\Common\Constants\Platform\PlatformSettingFields;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Core\Controller;
use App\Service\PlatformSettingService;
use App\Http\Resources\PlatformSettingListResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformSettingController extends Controller
{
    public function __construct(
        protected PlatformSettingService $platformSettingService,
    ) {
    }

    public function index(Request $request)
    {
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
        $result = $this->platformSettingService->findByPlatform($platform);
        if ($result->isError()) {
            return response()->json(['data' => null], 404);
        }
        return response()->json(['data' => new PlatformSettingListResource($result->getData())]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'integer'],
            'config' => ['nullable', 'array'],
            'disabled' => ['nullable', 'boolean'],
        ]);

        $result = $this->platformSettingService->create($data);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.create_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }

    public function update(string $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform' => ['nullable', 'integer'],
            'config' => ['nullable', 'array'],
            'disabled' => ['nullable', 'boolean'],
        ]);

        $result = $this->platformSettingService->update($id, $data);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }

    public function toggle(string $id, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'disabled' => ['required', 'boolean'],
        ]);
        $result = $this->platformSettingService->toggleDisabled($id, $validated['disabled']);
        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.update_success'));
        } else {
            FlashMessage::error($result->getMessage());
        }
        return redirect()->back();
    }
}


