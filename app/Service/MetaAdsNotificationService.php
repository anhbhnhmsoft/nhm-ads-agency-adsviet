<?php

namespace App\Service;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Core\UserLocale;
use App\Models\MetaAccount;
use App\Repositories\MetaAccountRepository;
use Illuminate\Support\Carbon;

class MetaAdsNotificationService
{
    public function __construct(
        protected MetaAccountRepository $metaAccountRepository,
        protected TelegramService $telegramService,
        protected MailService $mailService,
    ) {}

    public function sendLowBalanceAlerts(float $thresholdUsd = 100): ServiceReturn
    {
        try {
            $accounts = $this->metaAccountRepository
                ->getAccountsWithLowBalance($thresholdUsd)
                ->filter(function ($account) use ($thresholdUsd) {
                    $currency = $account->currency ?? 'USD';
                    $balance = $this->normalizeMetaAccountMoney($account->balance, $currency);
                    $spendCap = $this->normalizeMetaAccountMoney($account->spend_cap, $currency);
                    $amountSpent = $this->normalizeMetaAccountMoney($account->amount_spent, $currency);

                    return ($balance !== null && $balance <= $thresholdUsd)
                        || ($spendCap !== null && $spendCap > 0
                            && $amountSpent !== null && $amountSpent >= $spendCap);
                });
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
                if (! $serviceUser) {
                    $skipped++;
                    Logging::web('MetaAdsNotificationService: Skipped account (no serviceUser)', [
                        'account_id' => $account->id,
                        'account_name' => $account->account_name,
                    ]);

                    continue;
                }

                $user = $serviceUser->user;
                if (! $user) {
                    $skipped++;
                    Logging::web('MetaAdsNotificationService: Skipped account (no user)', [
                        'account_id' => $account->id,
                        'service_user_id' => $serviceUser->id,
                    ]);

                    continue;
                }

                $hasTelegram = ! empty($user->telegram_id);
                $hasVerifiedEmail = ! empty($user->email) && ! empty($user->email_verified_at);

                if (! $hasTelegram && ! $hasVerifiedEmail) {
                    $skipped++;
                    Logging::web('MetaAdsNotificationService: Skipped account (no telegram/email)', [
                        'account_id' => $account->id,
                        'user_id' => $user->id,
                        'has_telegram' => $hasTelegram,
                        'has_email' => ! empty($user->email),
                        'email_verified' => ! empty($user->email_verified_at),
                    ]);

                    continue;
                }

                $currency = $account->currency ?? 'USD';
                $balanceValue = $this->normalizeMetaAccountMoney($account->balance, $currency);
                $spendCap = $this->normalizeMetaAccountMoney($account->spend_cap, $currency);
                $amountSpent = $this->normalizeMetaAccountMoney($account->amount_spent, $currency);

                $balanceLow = $balanceValue !== null && $balanceValue <= $thresholdUsd;
                $spendCapReached = $spendCap !== null && $spendCap > 0 && $amountSpent !== null && $amountSpent >= $spendCap;

                if (! $balanceLow && ! $spendCapReached) {
                    continue;
                }

                // Cache để tránh gửi nhiều lần trong ngày
                $cacheKey = (string) $account->id.'_'.$user->id;
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
                $result = null;
                $method = null;

                [$result, $method] = UserLocale::run($user, function () use ($user, $account, $hasTelegram, $hasVerifiedEmail, $balanceValue, $balanceFormatted, $thresholdFormatted, $thresholdUsd, $currency) {
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

                        if (! $result->isSuccess() && $hasVerifiedEmail) {
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

                    return [$result, $method];
                });

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
            Logging::error(message: 'MetaAdsNotificationService@sendLowBalanceAlerts error: '.$e->getMessage(), exception: $e);

            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    private function minutesUntilEndOfDay(): int
    {
        $now = Carbon::now();

        return $now->diffInMinutes($now->copy()->endOfDay()) ?: 60;
    }

    private function normalizeMetaAccountMoney(mixed $value, ?string $currency): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;
        $zeroDecimalCurrencies = [
            'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW',
            'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ];

        return in_array(strtoupper($currency ?: 'USD'), $zeroDecimalCurrencies, true)
            ? $amount
            : $amount / 100;
    }

    /**
     * Gửi thông báo số dư thấp cho 1 account (dùng khi auto-pause do balance < threshold)
     */
    public function sendLowBalanceAlert(MetaAccount $account, float $threshold = 100.0): ServiceReturn
    {
        try {
            $serviceUser = $account->serviceUser;
            if (! $serviceUser) {
                return ServiceReturn::error(message: __('meta.error.service_not_found'));
            }

            $user = $serviceUser->user;
            if (! $user) {
                return ServiceReturn::error(message: __('meta.error.user_not_found'));
            }

            $hasTelegram = ! empty($user->telegram_id);
            $hasVerifiedEmail = ! empty($user->email) && ! empty($user->email_verified_at);

            if (! $hasTelegram && ! empty($user->telegram_id)) {
            }
            if (! $hasTelegram && ! $hasVerifiedEmail) {
                return ServiceReturn::error(message: __('meta.error.no_contact_method'));
            }

            // Dùng cache để chỉ gửi 1 lần mỗi ngày
            $today = now()->toDateString();
            $cacheKeyStr = (string) $account->id.'_'.$user->id;
            $cacheValue = Caching::getCache(CacheKey::CACHE_META_ACCOUNT_LOW_BALANCE_NOTIFIED, $cacheKeyStr);
            if ($cacheValue === $today) {
                return ServiceReturn::success(data: ['skipped' => true, 'reason' => 'already notified today']);
            }

            $currency = $account->currency ?? 'USD';
            $balance = $this->normalizeMetaAccountMoney($account->balance, $currency) ?? 0.0;
            $balanceFormatted = number_format($balance, 2);
            $thresholdFormatted = number_format($threshold, 2);

            [$result, $method] = UserLocale::run($user, function () use ($user, $account, $hasTelegram, $hasVerifiedEmail, $balance, $balanceFormatted, $threshold, $thresholdFormatted, $currency) {
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

                    if (! $result->isSuccess() && $hasVerifiedEmail) {
                        $result = $this->mailService->sendMetaAdsLowBalanceAlert(
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
                    $result = $this->mailService->sendMetaAdsLowBalanceAlert(
                        email: $user->email,
                        username: $user->name ?? $user->username,
                        accountName: $account->account_name ?? $account->account_id,
                        balance: $balance,
                        currency: $currency,
                        threshold: $threshold,
                    );
                    $method = 'email';
                }

                return [$result, $method];
            });

            if ($result && $result->isSuccess()) {
                Caching::setCache(CacheKey::CACHE_META_ACCOUNT_LOW_BALANCE_NOTIFIED, $today, $cacheKeyStr, $this->minutesUntilEndOfDay());
                Logging::web('MetaAdsNotificationService: Low balance alert sent (auto-pause)', [
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'method' => $method,
                ]);

                return ServiceReturn::success();
            }

            return ServiceReturn::error(message: $result ? $result->getMessage() : __('common_error.server_error'));
        } catch (\Throwable $e) {
            Logging::error(message: 'MetaAdsNotificationService@sendLowBalanceAlert error: '.$e->getMessage(), exception: $e);

            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Gửi thông báo khi spending > balance + threshold
     */
    public function sendSpendingExceededAlert(MetaAccount $account, float $spending, float $threshold = 100.0): ServiceReturn
    {
        try {
            $serviceUser = $account->serviceUser;
            if (! $serviceUser) {
                return ServiceReturn::error(message: __('meta.error.service_not_found'));
            }

            $user = $serviceUser->user;
            if (! $user) {
                return ServiceReturn::error(message: __('meta.error.user_not_found'));
            }

            $hasTelegram = ! empty($user->telegram_id);
            $hasVerifiedEmail = ! empty($user->email) && ! empty($user->email_verified_at);

            if (! $hasTelegram && ! $hasVerifiedEmail) {
                return ServiceReturn::error(message: __('meta.error.no_contact_method'));
            }

            // Dùng cache để chỉ gửi 1 lần mỗi ngày
            $today = now()->toDateString();
            $cacheKeyStr = 'spending_exceeded_'.$account->id.'_'.$user->id;
            $cacheValue = Caching::getCache(CacheKey::CACHE_META_ACCOUNT_LOW_BALANCE_NOTIFIED, $cacheKeyStr);
            if ($cacheValue === $today) {
                return ServiceReturn::success(data: ['skipped' => true, 'reason' => 'already notified today']);
            }

            $currency = $account->currency ?? 'USD';
            $balance = $this->normalizeMetaAccountMoney($account->balance, $currency) ?? 0.0;
            $thresholdAmount = $balance + $threshold;
            $balanceFormatted = number_format($balance, 2);
            $spendingFormatted = number_format($spending, 2);
            $thresholdFormatted = number_format($threshold, 2); // Ngưỡng an toàn riêng (100)
            $limitFormatted = number_format($thresholdAmount, 2); // Giới hạn tổng (balance + threshold)

            [$result, $method] = UserLocale::run($user, function () use ($user, $account, $hasTelegram, $hasVerifiedEmail, $spending, $spendingFormatted, $balance, $balanceFormatted, $threshold, $thresholdFormatted, $thresholdAmount, $limitFormatted, $currency) {
                $result = null;
                $method = null;
                if ($hasTelegram) {
                    $message = __('meta.telegram.spending_exceeded', [
                        'accountName' => $account->account_name ?? $account->account_id,
                        'spending' => $spendingFormatted,
                        'balance' => $balanceFormatted,
                        'threshold' => $thresholdFormatted,
                        'limit' => $limitFormatted,
                        'currency' => $currency,
                    ]);
                    $result = $this->telegramService->sendNotification($user->telegram_id, $message);
                    $method = 'telegram';

                    if (! $result->isSuccess() && $hasVerifiedEmail) {
                        $result = $this->mailService->sendMetaAdsSpendingExceededAlert(
                            email: $user->email,
                            username: $user->name ?? $user->username,
                            accountName: $account->account_name ?? $account->account_id,
                            spending: $spending,
                            balance: $balance,
                            threshold: $threshold,
                            limit: $thresholdAmount,
                            currency: $currency,
                        );
                        $method = 'email (fallback)';
                    }
                } elseif ($hasVerifiedEmail) {
                    $result = $this->mailService->sendMetaAdsSpendingExceededAlert(
                        email: $user->email,
                        username: $user->name ?? $user->username,
                        accountName: $account->account_name ?? $account->account_id,
                        spending: $spending,
                        balance: $balance,
                        threshold: $threshold,
                        limit: $thresholdAmount,
                        currency: $currency,
                    );
                    $method = 'email';
                }

                return [$result, $method];
            });

            if ($result && $result->isSuccess()) {
                Caching::setCache(CacheKey::CACHE_META_ACCOUNT_LOW_BALANCE_NOTIFIED, $today, $cacheKeyStr, $this->minutesUntilEndOfDay());
                Logging::web('MetaAdsNotificationService: Spending exceeded alert sent', [
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'method' => $method,
                ]);

                return ServiceReturn::success();
            }

            return ServiceReturn::error(message: $result ? $result->getMessage() : __('common_error.server_error'));
        } catch (\Throwable $e) {
            Logging::error(message: 'MetaAdsNotificationService@sendSpendingExceededAlert error: '.$e->getMessage(), exception: $e);

            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}
