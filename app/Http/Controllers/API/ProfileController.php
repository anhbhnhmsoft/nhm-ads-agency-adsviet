<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\RestResponse;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $result = $this->userService->changePassword(
            user: $request->user(),
            currentPassword: $request->validated('current_password'),
            newPassword: $request->validated('new_password')
        );

        if ($result->isSuccess()) {
            return RestResponse::success(
                message: __('profile.password_changed_success')
            );
        }

        return RestResponse::error(message: $result->getMessage());
    }
}

