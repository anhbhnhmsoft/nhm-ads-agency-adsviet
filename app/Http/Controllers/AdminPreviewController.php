<?php

namespace App\Http\Controllers;

use App\Core\FlashMessage;
use App\Service\UserPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminPreviewController
{
    public function __construct(
        protected UserPreviewService $userPreviewService,
    ) {
    }

    public function start(Request $request, string $userId): RedirectResponse
    {
        $actor = $request->user();
        if (!$this->userPreviewService->canManagePreview($actor)) {
            FlashMessage::error(__('common_error.permission_denied'));

            return redirect()->route('user_list');
        }

        $target = $this->userPreviewService->findPreviewableUser($userId, $actor);
        if (!$target) {
            FlashMessage::error(__('user.preview.cannot_open'));

            return redirect()->route('user_list');
        }

        $this->userPreviewService->start($request, $target);
        FlashMessage::info(__('user.preview.started', ['name' => $target->name]));

        return redirect()->route('dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $returnUrl = $this->userPreviewService->pullReturnUrl($request) ?? route('user_list');
        $this->userPreviewService->stop($request);
        FlashMessage::success(__('user.preview.stopped'));

        return redirect()->to($returnUrl);
    }
}
