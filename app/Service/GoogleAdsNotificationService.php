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

    /**
     * Gửi thông báo số dư thấp cho 1 account (dùng khi auto-pause do balance < threshold)
     * @param \App\Models\GoogleAccount $account
     * @param float $threshold
     * @return ServiceReturn
     */
    public function sendLowBalanceAlert(\App\Models\GoogleAccount $account, float $threshold = 100.0): ServiceReturn
    {
        try {
            $serviceUser = $account->serviceUser;
            if (!$serviceUser) {
                return ServiceReturn::error(message: __('google_ads.error.service_not_found'));
            }

            $user = $serviceUser->user;
            if (!$user) {
                return ServiceReturn::error(message: __('google_ads.error.user_not_found'));
            }

            $hasTelegram = !empty($user->telegram_id);
            $hasVerifiedEmail = !empty($user->email) && !empty($user->email_verified_at);

            if (!$hasTelegram && !$hasVerifiedEmail) {
                return ServiceReturn::error(message: __('google_ads.error.no_contact_method'));
            }

            $balance = (float) ($account->balance ?? 0);
            $balanceFormatted = number_format($balance, 2);
            $thresholdFormatted = number_format($threshold, 2);
            $currency = $account->currency ?? 'USD';

            $result = null;
            $method = null;

            if ($hasTelegram) {
                $message = __('google_ads.telegram.low_balance', [
                    'accountName' => $account->account_name ?? $account->account_id,
                    'balance' => $balanceFormatted,
                    'currency' => $currency,
                    'threshold' => $thresholdFormatted,
                ]);
                $result = $this->telegramService->sendNotification($user->telegram_id, $message);
                $method = 'telegram';

                if (!$result->isSuccess() && $hasVerifiedEmail) {
                    $result = $this->mailService->sendGoogleAdsLowBalanceAlert(
                        email: $user->email,
                        username: $user->name ?? $user->username,
                        accountName: $account->account_name ?? $account->account_id,
                        balance: $balance,
                        currency: $currency,
                        threshold: $threshold,
                    );
                    $method = 'email (fallback)';
                }
            } elseif ($hasVerifiedEmail) {
                $result = $this->mailService->sendGoogleAdsLowBalanceAlert(
                    email: $user->email,
                    username: $user->name ?? $user->username,
                    accountName: $account->account_name ?? $account->account_id,
                    balance: $balance,
                    currency: $currency,
                    threshold: $threshold,
                );
                $method = 'email';
            }

            if ($result && $result->isSuccess()) {
                Logging::web('GoogleAdsNotificationService: Low balance alert sent (auto-pause)', [
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'method' => $method,
                ]);
                return ServiceReturn::success();
            }

            return ServiceReturn::error(message: $result ? $result->getMessage() : __('common_error.server_error'));
        } catch (\Throwable $e) {
            Logging::error(message: 'GoogleAdsNotificationService@sendLowBalanceAlert error: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Gửi thông báo khi spending > balance + threshold
     * @param \App\Models\GoogleAccount $account
     * @param float $spending
     * @param float $threshold
     * @return ServiceReturn
     */
    public function sendSpendingExceededAlert(\App\Models\GoogleAccount $account, float $spending, float $threshold = 100.0): ServiceReturn
    {
        try {
            $serviceUser = $account->serviceUser;
            if (!$serviceUser) {
                return ServiceReturn::error(message: __('google_ads.error.service_not_found'));
            }

            $user = $serviceUser->user;
            if (!$user) {
                return ServiceReturn::error(message: __('google_ads.error.user_not_found'));
            }

            $hasTelegram = !empty($user->telegram_id);
            $hasVerifiedEmail = !empty($user->email) && !empty($user->email_verified_at);

            if (!$hasTelegram && !$hasVerifiedEmail) {
                return ServiceReturn::error(message: __('google_ads.error.no_contact_method'));
            }

            $balance = (float) ($account->balance ?? 0);
            $thresholdAmount = $balance + $threshold;
            $balanceFormatted = number_format($balance, 2);
            $spendingFormatted = number_format($spending, 2);
            $thresholdFormatted = number_format($threshold, 2); // Ngưỡng an toàn riêng (100)
            $limitFormatted = number_format($thresholdAmount, 2); // Giới hạn tổng (balance + threshold)
            $currency = $account->currency ?? 'USD';

            $result = null;
            $method = null;

            if ($hasTelegram) {
                $message = __('google_ads.telegram.spending_exceeded', [
                    'accountName' => $account->account_name ?? $account->account_id,
                    'spending' => $spendingFormatted,
                    'balance' => $balanceFormatted,
                    'threshold' => $thresholdFormatted, // Ngưỡng an toàn (100)
                    'limit' => $limitFormatted, // Giới hạn tổng (150)
                    'currency' => $currency,
                ]);
                $result = $this->telegramService->sendNotification($user->telegram_id, $message);
                $method = 'telegram';

                if (!$result->isSuccess() && $hasVerifiedEmail) {
                    $result = $this->mailService->sendGoogleAdsSpendingExceededAlert(
                        email: $user->email,
                        username: $user->name ?? $user->username,
                        accountName: $account->account_name ?? $account->account_id,
                        spending: $spending,
                        balance: $balance,
                        threshold: $threshold, // Ngưỡng an toàn (100)
                        limit: $thresholdAmount, // Giới hạn tổng (150)
                        currency: $currency,
                    );
                    $method = 'email (fallback)';
                }
            } elseif ($hasVerifiedEmail) {
                $result = $this->mailService->sendGoogleAdsSpendingExceededAlert(
                    email: $user->email,
                    username: $user->name ?? $user->username,
                    accountName: $account->account_name ?? $account->account_id,
                    spending: $spending,
                    balance: $balance,
                    threshold: $threshold, // Ngưỡng an toàn (100)
                    limit: $thresholdAmount, // Giới hạn tổng (150)
                    currency: $currency,
                );
                $method = 'email';
            }

            if ($result && $result->isSuccess()) {
                Logging::web('GoogleAdsNotificationService: Spending exceeded alert sent', [
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'method' => $method,
                ]);
                return ServiceReturn::success();
            }

            return ServiceReturn::error(message: $result ? $result->getMessage() : __('common_error.server_error'));
        } catch (\Throwable $e) {
            Logging::error(message: 'GoogleAdsNotificationService@sendSpendingExceededAlert error: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}

