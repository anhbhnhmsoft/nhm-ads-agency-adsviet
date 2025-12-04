<?php

namespace App\Service;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\GoogleAccountRepository;
use Illuminate\Support\Carbon;

class GoogleAdsNotificationService
{
    public function __construct(
        protected GoogleAccountRepository $googleAccountRepository,
        protected TelegramService $telegramService,
        protected MailService $mailService,
    ) {
    }

    public function sendLowBalanceAlerts(float $thresholdUsd = 100): ServiceReturn
    {
        try {
            $accounts = $this->googleAccountRepository->getAccountsWithLowBalance($thresholdUsd);
            $found = $accounts->count();
            
            if ($accounts->isEmpty()) {
                return ServiceReturn::success(data: ['sent' => 0, 'found' => 0, 'skipped' => 0]);
            }

            $sent = 0;
            $skipped = 0;
            $today = now()->toDateString();
            $expireMinutes = $this->minutesUntilEndOfDay();

            foreach ($accounts as $account) {
                $serviceUser = $account->serviceUser;
                if (!$serviceUser) {
                    $skipped++;
                    Logging::web('GoogleAdsNotificationService: Skipped account (no serviceUser)', [
                        'account_id' => $account->id,
                        'account_name' => $account->account_name,
                    ]);
                    continue;
                }

                $user = $serviceUser->user;
                if (!$user) {
                    $skipped++;
                    Logging::web('GoogleAdsNotificationService: Skipped account (no user)', [
                        'account_id' => $account->id,
                        'service_user_id' => $serviceUser->id,
                    ]);
                    continue;
                }

                $hasTelegram = !empty($user->telegram_id);
                $hasVerifiedEmail = !empty($user->email) && !empty($user->email_verified_at);

                if (!$hasTelegram && !$hasVerifiedEmail) {
                    $skipped++;
                    Logging::web('GoogleAdsNotificationService: Skipped account (no telegram/email)', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'has_telegram' => $hasTelegram,
                        'has_email' => !empty($user->email),
                        'email_verified' => !empty($user->email_verified_at),
                    ]);
                    continue;
                }

                // Cache để tránh gửi nhiều lần trong ngày
                $cacheValue = Caching::getCache(CacheKey::CACHE_GOOGLE_ACCOUNT_LOW_BALANCE_NOTIFIED, (string) $account->id . '_' . $user->id);
                if ($cacheValue === $today) {
                    $skipped++;
                    Logging::web('GoogleAdsNotificationService: Skipped account (already notified today)', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                    ]);
                    continue;
                }

                $balanceFormatted = number_format((float) $account->balance, 2);
                $thresholdFormatted = number_format($thresholdUsd, 2);
                $currency = $account->currency ?? 'USD';
                $result = null;
                $method = null;

                // Ưu tiên Telegram, nếu fail thì fallback sang email
                if ($hasTelegram) {
                    $message = __('google_ads.telegram.low_balance', [
                        'accountName' => $account->account_name ?? $account->account_id,
                        'balance' => $balanceFormatted,
                        'currency' => $currency,
                        'threshold' => $thresholdFormatted,
                    ]);
                    $result = $this->telegramService->sendNotification($user->telegram_id, $message);
                    $method = 'telegram';
                    
                    // Nếu Telegram fail và có email verified, fallback sang email
                    if (!$result->isSuccess() && $hasVerifiedEmail) {
                        Logging::web('GoogleAdsNotificationService: Telegram failed, falling back to email', [
                            'account_id' => $account->id,
                            'user_id' => $user->id,
                            'telegram_error' => $result->getMessage(),
                        ]);
                        $result = $this->mailService->sendGoogleAdsLowBalanceAlert(
                            email: $user->email,
                            username: $user->name ?? $user->username,
                            accountName: $account->account_name ?? $account->account_id,
                            balance: (float) $account->balance,
                            currency: $currency,
                            threshold: $thresholdUsd,
                        );
                        $method = 'email (fallback)';
                    }
                } elseif ($hasVerifiedEmail) {
                    // Chỉ có email, gửi email
                    $result = $this->mailService->sendGoogleAdsLowBalanceAlert(
                        email: $user->email,
                        username: $user->name ?? $user->username,
                        accountName: $account->account_name ?? $account->account_id,
                        balance: (float) $account->balance,
                        currency: $currency,
                        threshold: $thresholdUsd,
                    );
                    $method = 'email';
                }

                if ($result && $result->isSuccess()) {
                    Caching::setCache(CacheKey::CACHE_GOOGLE_ACCOUNT_LOW_BALANCE_NOTIFIED, $today, (string) $account->id . '_' . $user->id, $expireMinutes);
                    $sent++;
                    Logging::web('GoogleAdsNotificationService: Successfully sent notification', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'method' => $method,
                    ]);
                } else {
                    $skipped++;
                    Logging::web('GoogleAdsNotificationService: Failed to send notification', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'method' => $method ?? 'unknown',
                        'result' => $result ? ($result->isSuccess() ? 'success' : $result->getMessage()) : 'null',
                    ]);
                }
            }

            return ServiceReturn::success(data: ['sent' => $sent, 'found' => $found, 'skipped' => $skipped]);
        } catch (\Throwable $e) {
            Logging::error(message: 'GoogleAdsNotificationService@sendLowBalanceAlerts error: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function minutesUntilEndOfDay(): int
    {
        $now = Carbon::now();
        return $now->diffInMinutes($now->copy()->endOfDay()) ?: 60;
    }
}

