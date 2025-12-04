<?php

namespace App\Service;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\MetaAccountRepository;
use Illuminate\Support\Carbon;

class MetaAdsNotificationService
{
    public function __construct(
        protected MetaAccountRepository $metaAccountRepository,
        protected TelegramService $telegramService,
        protected MailService $mailService,
    ) {
    }

    public function sendLowBalanceAlerts(float $thresholdUsd = 100): ServiceReturn
    {
        try {
            $accounts = $this->metaAccountRepository->getAccountsWithLowBalance($thresholdUsd);
            $found = $accounts->count();

            if ($found === 0) {
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
                    Logging::web('MetaAdsNotificationService: Skipped account (no serviceUser)', [
                        'account_id' => $account->id,
                        'account_name' => $account->account_name,
                    ]);
                    continue;
                }

                $user = $serviceUser->user;
                if (!$user) {
                    $skipped++;
                    Logging::web('MetaAdsNotificationService: Skipped account (no user)', [
                        'account_id' => $account->id,
                        'service_user_id' => $serviceUser->id,
                    ]);
                    continue;
                }

                $hasTelegram = !empty($user->telegram_id);
                $hasVerifiedEmail = !empty($user->email) && !empty($user->email_verified_at);

                if (!$hasTelegram && !$hasVerifiedEmail) {
                    $skipped++;
                    Logging::web('MetaAdsNotificationService: Skipped account (no telegram/email)', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'has_telegram' => $hasTelegram,
                        'has_email' => !empty($user->email),
                        'email_verified' => !empty($user->email_verified_at),
                    ]);
                    continue;
                }

                $balanceValue = $account->balance !== null ? (float) $account->balance : null;
                $spendCap = $account->spend_cap !== null ? (float) $account->spend_cap : null;
                $amountSpent = $account->amount_spent !== null ? (float) $account->amount_spent : null;

                $balanceLow = $balanceValue !== null && $balanceValue <= $thresholdUsd;
                $spendCapReached = $spendCap !== null && $spendCap > 0 && $amountSpent !== null && $amountSpent >= $spendCap;

                if (!$balanceLow && !$spendCapReached) {
                    continue;
                }

                // Cache để tránh gửi nhiều lần trong ngày
                $cacheKey = (string) $account->id . '_' . $user->id;
                $cacheValue = Caching::getCache(CacheKey::CACHE_META_ACCOUNT_LOW_BALANCE_NOTIFIED, $cacheKey);
                if ($cacheValue === $today) {
                    $skipped++;
                    Logging::web('MetaAdsNotificationService: Skipped account (already notified today)', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                    ]);
                    continue;
                }

                $balanceFormatted = number_format($balanceValue ?? 0, 2);
                $thresholdFormatted = number_format($thresholdUsd, 2);
                $currency = $account->currency ?? 'USD';
                $result = null;
                $method = null;

                if ($hasTelegram) {
                    $message = __('meta.telegram.low_balance', [
                        'accountName' => $account->account_name ?? $account->account_id,
                        'balance' => $balanceFormatted,
                        'currency' => $currency,
                        'threshold' => $thresholdFormatted,
                    ]);
                    $result = $this->telegramService->sendNotification($user->telegram_id, $message);
                    $method = 'telegram';

                    if (!$result->isSuccess() && $hasVerifiedEmail) {
                        Logging::web('MetaAdsNotificationService: Telegram failed, fallback email', [
                            'account_id' => $account->id,
                            'user_id' => $user->id,
                            'telegram_error' => $result->getMessage(),
                        ]);
                        $result = $this->mailService->sendMetaAdsLowBalanceAlert(
                            email: $user->email,
                            username: $user->name ?? $user->username,
                            accountName: $account->account_name ?? $account->account_id,
                            balance: $balanceValue ?? 0,
                            currency: $currency,
                            threshold: $thresholdUsd,
                        );
                        $method = 'email (fallback)';
                    }
                } elseif ($hasVerifiedEmail) {
                    $result = $this->mailService->sendMetaAdsLowBalanceAlert(
                        email: $user->email,
                        username: $user->name ?? $user->username,
                        accountName: $account->account_name ?? $account->account_id,
                        balance: $balanceValue ?? 0,
                        currency: $currency,
                        threshold: $thresholdUsd,
                    );
                    $method = 'email';
                }

                if ($result && $result->isSuccess()) {
                    Caching::setCache(CacheKey::CACHE_META_ACCOUNT_LOW_BALANCE_NOTIFIED, $today, $cacheKey, $expireMinutes);
                    $sent++;
                    Logging::web('MetaAdsNotificationService: Notification sent', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'method' => $method,
                    ]);
                } else {
                    $skipped++;
                    Logging::web('MetaAdsNotificationService: Failed to send notification', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'method' => $method ?? 'unknown',
                        'result' => $result ? ($result->isSuccess() ? 'success' : $result->getMessage()) : 'null',
                    ]);
                }
            }

            return ServiceReturn::success(data: [
                'sent' => $sent,
                'found' => $found,
                'skipped' => $skipped,
            ]);
        } catch (\Throwable $e) {
            Logging::error(message: 'MetaAdsNotificationService@sendLowBalanceAlerts error: ' . $e->getMessage(), exception: $e);
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function minutesUntilEndOfDay(): int
    {
        $now = Carbon::now();
        return $now->diffInMinutes($now->copy()->endOfDay()) ?: 60;
    }
}

