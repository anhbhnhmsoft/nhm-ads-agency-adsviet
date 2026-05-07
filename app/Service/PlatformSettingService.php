<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Jobs\GoogleAds\SyncGooglePlatformJob;
use App\Jobs\MetaApi\SyncMetaPlatformJob;
use App\Repositories\PlatformSettingRepository;
use App\Repositories\ServiceUserRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PlatformSettingService
{
    public function __construct(
        protected PlatformSettingRepository $platformSettingRepository,
        protected ServiceUserRepository $serviceUserRepository,
    ) {
    }

    public function list(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $query = $this->platformSettingRepository->filterQuery($queryListDTO->filter ?? []);
            $query = $this->platformSettingRepository->sortQuery($query, $queryListDTO->sortBy ?? 'id', $queryListDTO->sortDirection ?? 'desc');
            $paginator = $query->paginate($queryListDTO->perPage, ['*'], 'page', $queryListDTO->page);
            return ServiceReturn::success(data: $paginator);
        } catch (QueryException $e) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách cấu hình nền tảng PlatformSettingService@list: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function find(string $id): ServiceReturn
    {
        $setting = $this->platformSettingRepository->findById($id);
        if (!$setting) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        return ServiceReturn::success(data: $setting);
    }

    public function create(array $data): ServiceReturn
    {
        try {
            return DB::transaction(function () use ($data) {
                $platform = (int) $data['platform'];
                $config = $this->normalizePlatformConfig($platform, $data['config'] ?? []);
                $disabled = (bool) ($data['disabled'] ?? false);

                $payload = [
                    'name' => $data['name'] ?? null,
                    'platform' => $platform,
                    'config' => $config,
                    'disabled' => $disabled,
                ];

                $created = $this->platformSettingRepository->create($payload);

                // Tự động chọn BM mới này làm context đang quản lý nếu nó được kích hoạt
                if (!$disabled) {
                    if ($platform === PlatformType::META->value) {
                        session(['active_meta_setting_id' => (string) $created->id]);
                    } elseif ($platform === PlatformType::GOOGLE->value) {
                        session(['active_google_setting_id' => (string) $created->id]);
                    }
                }

                Caching::clearCache(
                    key: CacheKey::CACHE_PLATFORM_SETTING_ACTIVE,
                    uniqueKey: $platform,
                );

                // Kích hoạt đồng bộ ngay lập tức
                if (!$disabled) {
                    $this->dispatchSyncJob($created);
                }

                return ServiceReturn::success(
                    data: $created,
                    message: __('platform_setting.created_success')
                );
            });
        }
        catch (\Throwable $e) {
            Logging::error(
                message: 'Lỗi khi tạo cấu hình nền tảng PlatformSettingService@create: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function update(string $id, array $data): ServiceReturn
    {
        $setting = $this->platformSettingRepository->findById($id);
        if (!$setting) {
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        }
        try {
            $payload = [
                'name' => $data['name'] ?? $setting->name,
                'platform' => isset($data['platform']) ? (int) $data['platform'] : $setting->platform,
                'config' => $this->normalizePlatformConfig(
                    isset($data['platform']) ? (int) $data['platform'] : (int) $setting->platform,
                    $data['config'] ?? $setting->config ?? []
                ),
                'disabled' => isset($data['disabled']) ? (bool) $data['disabled'] : $setting->disabled,
            ];
            $setting->update($payload);
            // Xóa cache
            Caching::clearCache(
                key: CacheKey::CACHE_PLATFORM_SETTING_ACTIVE,
                uniqueKey: $setting->platform,
            );

            // Kích hoạt đồng bộ nếud dnag hoạt động
            if (!$setting->disabled) {
                $this->dispatchSyncJob($setting);
            }

            return ServiceReturn::success(
                data: $setting,
                message: __('platform_setting.updated_success')
            );
        } catch (\Throwable $e) {
            Logging::error(
                message: 'Lỗi khi cập nhật cấu hình nền tảng PlatformSettingService@update: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function toggleDisabled(string $id, bool $disabled): ServiceReturn
    {
        try {
            $setting = $this->platformSettingRepository->findById($id);
            if (!$setting) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }
            $ok = $this->platformSettingRepository->toggleDisabled($id, $disabled);
            if (!$ok) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }
            $message = __('common_success.update_success');
            // Xóa cache
            Caching::clearCache(
                key: CacheKey::CACHE_PLATFORM_SETTING_ACTIVE,
                uniqueKey: $setting->platform,
            );

            // Nếu vừa bật, kích hoạt đồng bộ
            if (!$disabled) {
                $this->dispatchSyncJob($setting);
            }

            return ServiceReturn::success(message: $message);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'Lỗi khi đổi trạng thái cấu hình nền tảng PlatformSettingService@toggleDisabled: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function findByPlatform(int $platform): ServiceReturn
    {
        try {
            $settings = \App\Models\PlatformSetting::where('platform', $platform)
                ->orderBy('id', 'desc')
                ->get();
            return ServiceReturn::success(data: $settings);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'Lỗi khi lấy cấu hình nền tảng PlatformSettingService@findByPlatform: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy cấu hình nền tảng đang hoạt động theo platform
     * @param int $platform
     * @return ServiceReturn
     */
    public function findPlatformActive(int $platform, ?string $id = null): ServiceReturn
    {
        try {
            $targetId = $id;
            if (!$targetId) {
                if ($platform === PlatformType::META->value) {
                    $targetId = session('active_meta_setting_id');
                } elseif ($platform === PlatformType::GOOGLE->value) {
                    $targetId = session('active_google_setting_id');
                }
            }

            if ($targetId) {
                $setting = $this->platformSettingRepository->findActiveByPlatform($platform, (string) $targetId);
                if ($setting) {
                    return ServiceReturn::success(data: $setting);
                }
            }

            // Nếu không có session ID hoặc session ID không tồn tại/bị disable, KHÔNG fallback (tránh lộ data BM khác)
            return ServiceReturn::error(message: __('common_error.data_not_found'));
        } catch (\Throwable $e) {
            Logging::error(
                message: 'Lỗi khi lấy cấu hình nền tảng PlatformSettingService@findPlatformActive: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Lấy danh sách toàn bộ các cấu hình đang hoạt động của một nền tảng
     */
    public function getAllActiveByPlatform(int $platform): ServiceReturn
    {
        try {
            $settings = $this->platformSettingRepository->getAllActiveByPlatform($platform);
            return ServiceReturn::success(data: $settings);
        } catch (\Throwable $e) {
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tìm cấu hình nền tảng dựa trên một trường cụ thể trong config JSON
     */
    public function findByConfigField(int $platform, string $field, string $value): ServiceReturn
    {
        try {
            $setting = $this->platformSettingRepository->findByConfigField($platform, $field, $value);
            return ServiceReturn::success(data: $setting);
        } catch (\Throwable $e) {
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Xóa cấu hình nền tảng
     */
    public function delete(string $id): ServiceReturn
    {
        try {
            $setting = $this->platformSettingRepository->findById($id);
            if (!$setting) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }

            // Kiểm tra xem có service_user nào đang sử dụng BM/MCC này không
            $config = $setting->config ?? [];
            $platform = (int) $setting->platform;
            $count = 0;
            
            if ($platform === PlatformType::META->value && ($bmId = $this->getMetaScopedBusinessManagerId($config))) {
                $count = $this->serviceUserRepository->query()
                    ->where(function ($q) use ($bmId) {
                        $q->whereJsonContains('config_account->business_manager_id', $bmId)
                          ->orWhereJsonContains('config_account->bm_id', $bmId)
                          ->orWhereJsonContains('config_account->child_bm_id', $bmId);
                    })
                    ->count();
            } elseif ($platform === PlatformType::GOOGLE->value && isset($config['login_customer_id'])) {
                $mccId = (string) $config['login_customer_id'];
                $count = $this->serviceUserRepository->query()
                    ->where(function ($q) use ($mccId) {
                        $q->whereJsonContains('config_account->login_customer_id', $mccId)
                          ->orWhereJsonContains('config_account->customer_manager_id', $mccId);
                    })
                    ->count();
            }

            if ($count > 0) {
                return ServiceReturn::error(message: __('platform_setting.delete_error_used_by_service_user', ['count' => $count]));
            }

            $setting->delete();

            if ($platform === PlatformType::META->value && session('active_meta_setting_id') === $id) {
                session()->forget('active_meta_setting_id');
            } elseif ($platform === PlatformType::GOOGLE->value && session('active_google_setting_id') === $id) {
                session()->forget('active_google_setting_id');
            }

            Caching::clearCache(
                key: CacheKey::CACHE_PLATFORM_SETTING_ACTIVE,
                uniqueKey: $platform,
            );

            return ServiceReturn::success(message: __('common_success.delete_success'));
        } catch (\Throwable $e) {
            Logging::error(
                message: 'Lỗi khi xóa cấu hình nền tảng PlatformSettingService@delete: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function shouldSyncAllAccessibleMetaBusinesses(array $config): bool
    {
        if (array_key_exists('sync_all_accessible_businesses', $config)) {
            return filter_var($config['sync_all_accessible_businesses'], FILTER_VALIDATE_BOOLEAN);
        }

        return empty($config['business_manager_id']);
    }

    public function getMetaScopedBusinessManagerId(array $config): ?string
    {
        if ($this->shouldSyncAllAccessibleMetaBusinesses($config)) {
            return null;
        }

        $bmId = trim((string) ($config['business_manager_id'] ?? ''));

        return $bmId !== '' ? $bmId : null;
    }

    private function normalizePlatformConfig(int $platform, array $config): array
    {
        if ($platform !== PlatformType::META->value) {
            return $config;
        }

        $config['sync_all_accessible_businesses'] = $this->shouldSyncAllAccessibleMetaBusinesses($config);

        if (isset($config['business_manager_id'])) {
            $config['business_manager_id'] = trim((string) $config['business_manager_id']);
        }

        return $config;
    }

    protected function dispatchSyncJob($setting): void
    {
        try {
            $config = $setting->config ?? [];
            
            if ($setting->platform === PlatformType::META->value) {
                $bmId = $this->getMetaScopedBusinessManagerId($config);
                SyncMetaPlatformJob::dispatch($bmId ? (string)$bmId : null, (string)$setting->id);
                Logging::web("PlatformSettingService: Dispatched Meta sync for setting ID {$setting->id}");
            } elseif ($setting->platform === PlatformType::GOOGLE->value) {
                $loginCustomerId = $config['login_customer_id'] ?? null;
                if ($loginCustomerId) {
                    SyncGooglePlatformJob::dispatch((string)$loginCustomerId, (string)$setting->id);
                    Logging::web("PlatformSettingService: Dispatched Google sync for setting ID {$setting->id}");
                }
            }
        } catch (\Throwable $e) {
            Logging::error("PlatformSettingService@dispatchSyncJob error: " . $e->getMessage());
        }
    }
}
