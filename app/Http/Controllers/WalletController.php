<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\FlashMessage;
use App\Service\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
    ) {}

    public function create(string $userId, Request $request): RedirectResponse
    {
        $password = $request->string('password')->toString() ?: null;
        $result = $this->walletService->create($userId, $password);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function topUp(string $userId, Request $request): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required','numeric','gt:0']]);
        $result = $this->walletService->topUp($userId, (float)$data['amount']);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function withdraw(string $userId, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required','numeric','gt:0'],
            'password' => ['nullable','string']
        ]);
        $result = $this->walletService->withdraw($userId, (float)$data['amount'], $data['password'] ?? null);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function lock(string $userId): RedirectResponse
    {
        $result = $this->walletService->lock($userId);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function unlock(string $userId): RedirectResponse
    {
        $result = $this->walletService->unlock($userId);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }

    public function resetPassword(string $userId, Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required','string','min:6']]);
        $result = $this->walletService->resetPassword($userId, $data['password']);
        $result->isSuccess() ? FlashMessage::success(__('common_success.update_success')) : FlashMessage::error($result->getMessage());
        return redirect()->back();
    }
}


