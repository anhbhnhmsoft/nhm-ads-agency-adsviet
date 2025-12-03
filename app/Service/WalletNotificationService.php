<?php

namespace App\Service;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\WalletRepository;
use Illuminate\Support\Carbon;

class WalletNotificationService
{
    public function __construct(
        protected WalletRepository $walletRepository,
        protected TelegramService $telegramService,
        protected MailService $mailService,
    ) {
    }

    public function sendLowBalanceAlerts(float $thresholdUsd = 100): ServiceReturn
    {
        try {
            $wallets = $this->walletRepository->getCustomersWithLowBalance($thresholdUsd);
            if ($wallets->isEmpty()) {
                return ServiceReturn::success(data: ['sent' => 0]);
            }

            $sent = 0;
            $today = now()->toDateString();
            $expireMinutes = $this->minutesUntilEndOfDay();

            foreach ($wallets as $wallet) {
                $user = $wallet->user;
                if (!$user) {
                    continue;
                }

                $hasTelegram = !empty($user->telegram_id);
                $hasVerifiedEmail = !empty($user->email) && !empty($user->email_verified_at);

                if (!$hasTelegram && !$hasVerifiedEmail) {
                    continue;
                }

                $cacheValue = Caching::getCache(CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED, (string) $user->id);
                if ($cacheValue === $today) {
                    continue;
                }

                $balanceFormatted = number_format((float) $wallet->balance, 2);
                $thresholdFormatted = number_format($thresholdUsd, 2);
                $result = null;

                if ($hasTelegram) {
                    $message = __('wallet.telegram.low_balance', [
                        'name' => $user->name ?? $user->username,
                        'balance' => $balanceFormatted,
                        'threshold' => $thresholdFormatted,
                    ]);
                    $result = $this->telegramService->sendNotification($user->telegram_id, $message);
                } elseif ($hasVerifiedEmail) {
                    $result = $this->mailService->sendWalletLowBalanceAlert(
                        email: $user->email,
                        username: $user->name ?? $user->username,
                        balance: (float) $wallet->balance,
                        threshold: $thresholdUsd,
                    );
                }

                if ($result && $result->isSuccess()) {
                    Caching::setCache(CacheKey::CACHE_WALLET_LOW_BALANCE_NOTIFIED, $today, (string) $user->id, $expireMinutes);
                    $sent++;
                }
            }

            return ServiceReturn::success(data: ['sent' => $sent]);
        } catch (\Throwable $e) {
            Logging::error(message: 'WalletNotificationService@sendLowBalanceAlerts error: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function minutesUntilEndOfDay(): int
    {
        $now = Carbon::now();
        return $now->diffInMinutes($now->copy()->endOfDay()) ?: 60;
    }
}

