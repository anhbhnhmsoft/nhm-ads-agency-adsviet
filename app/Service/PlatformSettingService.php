<?php

namespace App\Service;

use App\Core\Cache\CacheKey;
use App\Core\Cache\Caching;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\PlatformSettingRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PlatformSettingService
{
    public function __construct(
        protected PlatformSettingRepository $platformSettingRepository,
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
                $config = $data['config'] ?? [];
                $platform = (int) $data['platform'];
                $disabled = (bool) ($data['disabled'] ?? false);

                $payload = [
                    'platform' => $platform,
                    'config' => $config,
                    'disabled' => $disabled,
                ];
                $created = $this->platformSettingRepository->create($payload);

                // Nếu disabled false vô hiệu hóa các config khác cùng platform
                if (!$disabled) {
                    $affected = $this->platformSettingRepository->deactivateOthersByPlatform($created->platform, (string)$created->id);
                    $message = $affected > 0
                        ? __('platform_setting.activated_with_deactivation')
                        : __('platform_setting.activated');
                    return ServiceReturn::success(message: $message);
                }
                return ServiceReturn::success(message: __('platform_setting.created_disabled'));
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
                'platform' => isset($data['platform']) ? (int) $data['platform'] : $setting->platform,
                'config' => $data['config'] ?? $setting->config,
                'disabled' => isset($data['disabled']) ? (bool) $data['disabled'] : $setting->disabled,
            ];
            $setting->update($payload);
            // Xóa cache
            Caching::clearCache(
                key: CacheKey::CACHE_PLATFORM_SETTING_ACTIVE,
                uniqueKey: $setting->platform,
            );
            return ServiceReturn::success();
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
            if ($disabled === false) {
                // Kích hoạt sau đó vô hiệu hóa các cấu hình khác cùng platform
                $affected = $this->platformSettingRepository->deactivateOthersByPlatform($setting->platform, $id);
                if ($affected > 0) {
                    $message = __('platform_setting.toggled_activated_with_deactivation');
                }
            }
            // Xóa cache
            Caching::clearCache(
                key: CacheKey::CACHE_PLATFORM_SETTING_ACTIVE,
                uniqueKey: $setting->platform,
            );
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
            $setting = $this->platformSettingRepository->findByPlatform($platform);
            if (!$setting) {
                return ServiceReturn::error(message: __('common_error.data_not_found'));
            }
            return ServiceReturn::success(data: $setting);
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
    public function findPlatformActive(int $platform): ServiceReturn
    {
        try {
            $setting = Caching::getCache(
                key: CacheKey::CACHE_PLATFORM_SETTING_ACTIVE,
                uniqueKey: $platform,
            );
            if (!$setting) {
                $setting = $this->platformSettingRepository->findActiveByPlatform($platform);
                if (!$setting) {
                    return ServiceReturn::error(message: __('common_error.data_not_found'));
                }
                Caching::setCache(
                    key: CacheKey::CACHE_PLATFORM_SETTING_ACTIVE,
                    value: $setting,
                    uniqueKey: $platform,
                    expire: 60 * 24 // cache 1 ngày
                );
            }
            return ServiceReturn::success(data: $setting);
        } catch (\Throwable $e) {
            Logging::error(
                message: 'Lỗi khi lấy cấu hình nền tảng PlatformSettingService@findPlatformActive: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}


