<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\FlashMessage;
use App\Http\Requests\LoginRequest;
use App\Service\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{


    public function __construct(
        protected AuthService $authService,
    )
    {
    }

    public function loginScreen(): \Inertia\Response
    {
        return $this->rendering('auth/login');
    }

    /**
     * Handle login with username
     * @param LoginRequest $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function handleLoginUsername(LoginRequest $request): RedirectResponse
    {
        $result = $this->authService->handleLoginUsername($request->validated());

        if ($result->isSuccess()) {
            FlashMessage::success(__('common_success.login_success'));
            return redirect()->route('dashboard');
        } else {
            throw ValidationException::withMessages([
                'username' => $result->getMessage(),
            ]);
        }
    }

}
