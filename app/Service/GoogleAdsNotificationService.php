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
            if ($accounts->isEmpty()) {
                return ServiceReturn::success(data: ['sent' => 0, 'found' => 0, 'skipped' => 0]);
            }

            $accountsGroupedByUser = [];
            foreach ($accounts as $account) {
                $serviceUser = $account->serviceUser;
                if (!$serviceUser || !$serviceUser->user) {
                    continue;
                }
                $accountsGroupedByUser[$serviceUser->user->id]['user'] = $serviceUser->user;
                $accountsGroupedByUser[$serviceUser->user->id]['accounts'][] = $account;
            }

            $sent = 0;
            $skipped = 0;
            $today = now()->toDateString();
            $expireMinutes = $this->minutesUntilEndOfDay();

            foreach ($accountsGroupedByUser as $userId => $data) {
                $user = $data['user'];
                $userAccounts = $data['accounts'];

                $hasTelegram = !empty($user->telegram_id);
                $hasVerifiedEmail = !empty($user->email) && !empty($user->email_verified_at);

                if (!$hasTelegram && !$hasVerifiedEmail) {
                    $skipped += count($userAccounts);
                    continue;
                }

                // Cache theo user để tránh gửi nhiều tin nhắn gom nhóm trong ngày
                $cacheKey = "low_balance_notified_user_" . $user->id;
                $cacheValue = Caching::getCache(CacheKey::CACHE_GOOGLE_ACCOUNT_LOW_BALANCE_NOTIFIED, $cacheKey);
                
                if ($cacheValue === $today) {
                    $skipped += count($userAccounts);
                    continue;
                }

                $balanceFormatted = "";
                foreach ($userAccounts as $acc) {
                    $balanceFormatted .= sprintf("- %s: %s %s\n", 
                        $acc->account_name ?? $acc->account_id, 
                        number_format((float) $acc->balance, 2), 
                        $acc->currency ?? 'USD'
                    );
                }

                $result = null;
                $method = null;

                if ($hasTelegram) {
                    $message = "⚠️ *Cảnh báo số dư thấp (Google Ads)*\n\n";
                    $message .= "Các tài khoản sau có số dư dưới ngưỡng " . number_format($thresholdUsd, 2) . " USD:\n";
                    $message .= $balanceFormatted;
                    $message .= "\nVui lòng nạp thêm tiền để tránh gián đoạn quảng cáo.";

                    $result = $this->telegramService->sendNotification($user->telegram_id, $message);
                    $method = 'telegram';

                    if (!$result->isSuccess() && $hasVerifiedEmail) {
                        $result = $this->mailService->sendGoogleAdsLowBalanceAlertGrouped(
                            email: $user->email,
                            username: $user->name ?? $user->username,
                            accountsData: $userAccounts,
                            threshold: $thresholdUsd
                        );
                        $method = 'email (fallback)';
                    }
                } elseif ($hasVerifiedEmail) {
                    $result = $this->mailService->sendGoogleAdsLowBalanceAlertGrouped(
                        email: $user->email,
                        username: $user->name ?? $user->username,
                        accountsData: $userAccounts,
                        threshold: $thresholdUsd
                    );
                    $method = 'email';
                }

                if ($result && $result->isSuccess()) {
                    Caching::setCache(CacheKey::CACHE_GOOGLE_ACCOUNT_LOW_BALANCE_NOTIFIED, $today, $cacheKey, $expireMinutes);
                    $sent++;
                } else {
                    $skipped += count($userAccounts);
                }
            }

            return ServiceReturn::success(data: ['sent' => $sent, 'found' => $accounts->count(), 'skipped' => $skipped]);
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

