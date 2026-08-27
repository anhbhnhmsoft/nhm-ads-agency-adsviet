<?php

namespace App\Http\Controllers;

use App\Common\Constants\Platform\PlatformType;
use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Jobs\GoogleAds\SyncGooglePlatformJob;
use App\Jobs\MetaApi\SyncMetaPlatformJob;
use App\Models\User;
use App\Service\BusinessManagerService;
use App\Service\PlatformSettingService;
use App\Service\ServiceUserService;
use App\Service\WalletTransactionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceManagementController extends Controller
{
    private const MANUAL_SYNC_COOLDOWN_SECONDS = 300;

    public function __construct(
        protected BusinessManagerService $businessManagerService,
        protected PlatformSettingService $platformSettingService,
        protected ServiceUserService $serviceUserService,
        protected WalletTransactionService $walletTransactionService,
    ) {
    }

    public function index(Request $request): \Inertia\Response
    {
        $params = $this->extractQueryPagination($request);
        $filter = $params->get('filter') ?? [];
        // Trang quản lý tài khoản: hiển thị theo từng account
        $filter['view'] = 'account';

        $result = $this->businessManagerService->getListBusinessManagers(
            new QueryListDTO(
                perPage: $params->get('per_page'),
                page: $params->get('page'),
                filter: $filter,
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        $data = $result->isError() ? null : $result->getData();
        $paginator = null;
        $stats = [
            'total_accounts' => 0,
            'active_accounts' => 0,
            'disabled_accounts' => 0,
            'by_platform' => [],
        ];
        $totals = [
            'total_spend' => 0,
            'total_reach' => 0,
            'currency' => 'USD',
            'totals_by_currency' => [],
            'last_synced_at' => null,
        ];
        if ($data) {
            if (is_array($data) && isset($data['paginator'])) {
                $paginator = $data['paginator'];
                $stats = $data['stats'] ?? $stats;
                $totals = $data['totals'] ?? $totals;
            } elseif ($data instanceof LengthAwarePaginator) {
                $paginator = $data;
            }
        }

        if (!$paginator) {
            return $this->rendering(
                view: 'service-management/index',
                data: [
                    'paginator' => fn () => [
                        'data' => [],
                        'links' => [
                            'first' => null,
                            'last' => null,
                            'prev' => null,
                            'next' => null,
                        ],
                        'meta' => [
                            'links' => [],
                            'current_page' => 1,
                            'from' => null,
                            'last_page' => 1,
                            'per_page' => $params->get('per_page', 10),
                            'to' => null,
                            'total' => 0,
                        ],
                    ],
                    'stats' => fn () => $stats,
                    'totals' => fn () => $totals,
                ]
            );
        }

        $paginatorData = $paginator->toArray();
        $refundLookup = $this->walletTransactionService->getCompletedAccountRefundLookup($paginatorData['data'] ?? []);
        $paginatorArray = [
            'data' => array_map(
                fn (array $item) => $this->attachRefundState($item, $refundLookup),
                $paginatorData['data'] ?? [],
            ),
            'links' => [
                'first' => $paginatorData['first_page_url'] ?? null,
                'last' => $paginatorData['last_page_url'] ?? null,
                'prev' => $paginatorData['prev_page_url'] ?? null,
                'next' => $paginatorData['next_page_url'] ?? null,
            ],
            'meta' => [
                'links' => array_map(function ($link) {
                    return [
                        'url' => $link['url'] ?? null,
                        'label' => $link['label'] ?? '',
                        'active' => $link['active'] ?? false,
                        'page' => $link['page'] ?? null,
                    ];
                }, $paginatorData['links'] ?? []),
                'current_page' => $paginatorData['current_page'] ?? 1,
                'from' => $paginatorData['from'] ?? null,
                'last_page' => $paginatorData['last_page'] ?? 1,
                'per_page' => $paginatorData['per_page'] ?? 10,
                'to' => $paginatorData['to'] ?? null,
                'total' => $paginatorData['total'] ?? 0,
            ],
        ];

        return $this->rendering(
            view: 'service-management/index',
            data: [
                'paginator' => fn () => $paginatorArray,
                'stats' => fn () => $stats,
                'totals' => fn () => $totals,
                'childManagers' => fn () => $this->businessManagerService->getChildManagersForFilter(),
                'customers' => fn () => User::query()
                    ->whereHas('serviceUsers')
                    ->select('id', 'name', 'username', 'email')
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($u) => ['id' => (string) $u->id, 'name' => $u->name ?: ($u->username ?: $u->email)])
                    ->toArray(),
            ]
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $params = $this->extractQueryPagination($request);
        $filter = $params->get('filter') ?? [];
        $filter['view'] = 'account';

        $result = $this->businessManagerService->getListBusinessManagers(
            new QueryListDTO(
                perPage: 0,
                page: 1,
                filter: $filter,
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        $data = $result->isError() ? null : $result->getData();
        $items = $data['items'] ?? [];

        $filename = 'service_management_report_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($items) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                __('service_management.export_column.account_name'),
                __('service_management.export_column.account_id'),
                __('service_management.export_column.customer_name'),
                __('service_management.export_column.bm_name'),
                __('service_management.export_column.platform'),
                __('service_management.export_column.total_spend'),
                __('service_management.export_column.currency'),
                __('service_management.export_column.account_status'),
                __('service_management.export_column.disable_reason'),
                __('service_management.export_column.spend_cap'),
                __('service_management.export_column.remaining_amount'),
                __('service_management.export_column.amount_spent'),
                __('service_management.export_column.total_balance'),
                __('service_management.export_column.created_time'),
                __('service_management.export_column.last_synced_at'),
            ], ',', '"', '\\');

            foreach ($items as $item) {
                $platformText = match ((int) ($item['platform'] ?? 0)) {
                    PlatformType::META->value => 'Meta Ads',
                    PlatformType::GOOGLE->value => 'Google Ads',
                    default => __('service_management.export_column.platform_other'),
                };

                $totalSpend = is_numeric($item['total_spend'] ?? null)
                    ? number_format((float) $item['total_spend'], 2, '.', '')
                    : '0.00';

                $spendCap = is_numeric($item['spend_cap'] ?? null)
                    ? number_format((float) $item['spend_cap'], 2, '.', '')
                    : '';

                $remainingAmount = is_numeric($item['remaining_amount'] ?? null)
                    ? number_format((float) $item['remaining_amount'], 2, '.', '')
                    : '';

                $amountSpent = is_numeric($item['amount_spent'] ?? null)
                    ? number_format((float) $item['amount_spent'], 2, '.', '')
                    : '';

                $totalBalance = is_numeric($item['total_balance'] ?? null)
                    ? number_format((float) $item['total_balance'], 2, '.', '')
                    : '';

                $createdTime = !empty($item['created_time'])
                    ? date('Y-m-d H:i:s', is_numeric($item['created_time']) ? (int) $item['created_time'] : strtotime((string) $item['created_time']))
                    : '';

                $lastSyncedAt = !empty($item['last_synced_at'])
                    ? date('Y-m-d H:i:s', strtotime((string) $item['last_synced_at']))
                    : '';

                fputcsv($file, [
                    $item['account_name'] ?? '',
                    $item['account_id'] ?? '',
                    $item['customer_name'] ?? ($item['owner_name'] ?? ''),
                    $item['bm_name'] ?? '',
                    $platformText,
                    $totalSpend,
                    strtoupper((string) ($item['currency'] ?? 'USD')),
                    $item['account_status_label'] ?? ($item['account_status'] ?? ''),
                    $item['disable_reason'] ?? '',
                    $spendCap,
                    $remainingAmount,
                    $amountSpent,
                    $totalBalance,
                    $createdTime,
                    $lastSyncedAt,
                ], ',', '"', '\\');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function attachRefundState(array $account, array $refundLookup): array
    {
        $lookupKey = $this->buildRefundLookupKey(
            isset($account['service_user_id']) ? (string) $account['service_user_id'] : null,
            isset($account['account_id']) ? (string) $account['account_id'] : null,
        );
        $refundMeta = $lookupKey !== null ? ($refundLookup[$lookupKey] ?? null) : null;

        $account['is_refunded'] = $refundMeta !== null;
        $account['refunded_at'] = $refundMeta['refunded_at'] ?? null;
        $account['refund_transaction_id'] = $refundMeta['transaction_id'] ?? null;
        $account['refund_amount'] = $refundMeta['amount'] ?? null;

        return $account;
    }

    private function buildRefundLookupKey(?string $serviceUserId, ?string $accountId): ?string
    {
        $serviceUserId = trim((string) $serviceUserId);
        $accountId = trim((string) $accountId);

        if ($serviceUserId === '' || $accountId === '') {
            return null;
        }

        if (str_starts_with($accountId, 'act_')) {
            $accountId = preg_replace('/^act_/', '', $accountId);
        }

        return $serviceUserId.'::'.$accountId;
    }

    public function syncMetaInsights(Request $request): \Illuminate\Http\JsonResponse
    {
        $bmId = trim((string) $request->input('bm_id', ''));
        $settingId = session('active_meta_setting_id');

        if ($bmId === '') {
            $settingResult = $this->platformSettingService->findPlatformActive(
                PlatformType::META->value,
                $settingId ? (string) $settingId : null
            );
            $setting = $settingResult->isSuccess() ? $settingResult->getData() : null;
            $settingId = $setting?->id ? (string) $setting->id : $settingId;
            $config = $setting?->config ?? [];
            $bmId = $this->platformSettingService->getMetaScopedBusinessManagerId($config) ?? '';
        }

        if ($bmId === '' && !$settingId) {
            return response()->json([
                'message' => 'Vui lòng chọn hoặc cấu hình Meta BM cần đồng bộ',
            ], 422);
        }

        if (!$this->reserveSyncSlot('meta', $settingId ? (string) $settingId : ($bmId ?: 'default'))) {
            return response()->json([
                'message' => 'Dữ liệu Meta đang được cập nhật hoặc vừa được cập nhật gần đây. Vui lòng thử lại sau ít phút.',
            ], 429);
        }

        SyncMetaPlatformJob::dispatch($bmId !== '' ? $bmId : null, $settingId ? (string) $settingId : null);

        return response()->json([
            'message' => __('common.processing'),
        ]);
    }

    public function syncGoogleInsights(Request $request): \Illuminate\Http\JsonResponse
    {
        $mccId = preg_replace('/[^0-9]/', '', (string) $request->input('mcc_id', ''));
        $settingId = session('active_google_setting_id');

        if ($mccId === '') {
            $settingResult = $this->platformSettingService->findPlatformActive(
                PlatformType::GOOGLE->value,
                $settingId ? (string) $settingId : null
            );
            $setting = $settingResult->isSuccess() ? $settingResult->getData() : null;
            $settingId = $setting?->id ? (string) $setting->id : $settingId;
            $config = $setting?->config ?? [];
            $mccId = preg_replace('/[^0-9]/', '', (string) ($config['login_customer_id'] ?? ''));
        }

        if ($mccId === '') {
            return response()->json([
                'message' => 'Vui lòng chọn hoặc cấu hình Google MCC cần đồng bộ',
            ], 422);
        }

        if (!$this->reserveSyncSlot('google', $settingId ? (string) $settingId : $mccId)) {
            return response()->json([
                'message' => 'Dữ liệu Google đang được cập nhật hoặc vừa được cập nhật gần đây. Vui lòng thử lại sau ít phút.',
            ], 429);
        }

        SyncGooglePlatformJob::dispatch($mccId, $settingId ? (string) $settingId : null);

        return response()->json([
            'message' => __('common.processing'),
        ]);
    }

    private function reserveSyncSlot(string $platform, string $scope): bool
    {
        return Cache::add(
            "platform-sync-cooldown:{$platform}:{$scope}",
            now()->toDateTimeString(),
            self::MANUAL_SYNC_COOLDOWN_SECONDS,
        );
    }

    public function getActiveServices(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->ensureInternalAccess();

        $platform = $request->input('platform');

        $query = \App\Models\ServiceUser::query()
            ->with(['user:id,name,username', 'package:id,name,platform'])
            ->where('status', \App\Common\Constants\ServiceUser\ServiceUserStatus::ACTIVE->value);

        if ($platform) {
            $query->whereHas('package', function ($q) use ($platform) {
                $q->where('platform', (int) $platform);
            });
        }

        $services = $query->get()->map(function ($serviceUser) {
            $ownerName = $serviceUser->user->name ?? $serviceUser->user->username ?? 'Unknown';
            $packageName = $serviceUser->package->name ?? 'Unknown Package';
            return [
                'id' => (string) $serviceUser->id,
                'user_id' => $serviceUser->user_id,
                'customer_name' => $ownerName,
                'package_name' => $packageName,
                'display_label' => "ID: {$serviceUser->id} - {$ownerName} ({$packageName})",
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $services,
        ]);
    }

    private function ensureInternalAccess(): void
    {
        if (!in_array((int) auth()->user()?->role, [
            \App\Common\Constants\User\UserRole::ADMIN->value,
            \App\Common\Constants\User\UserRole::MANAGER->value,
            \App\Common\Constants\User\UserRole::EMPLOYEE->value,
        ], true)) {
            abort(403);
        }
    }

    /**
     * Gỡ gán tài khoản khỏi service_user
     */
    public function unassignAccount(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->ensureInternalAccess();

        $request->validate([
            'service_user_id' => ['required', 'string'],
            'account_id' => ['required', 'string'],
        ]);

        $result = $this->serviceUserService->unassignAccount(
            serviceUserId: $request->input('service_user_id'),
            accountId: $request->input('account_id'),
        );

        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('services.flash.account_unassigned'),
        ]);
    }
}
