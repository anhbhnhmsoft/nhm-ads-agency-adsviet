<?php

namespace App\Service;

use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Repositories\PlatformSettingRepository;
use Illuminate\Database\QueryException;

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
            $config = $data['config'] ?? [];
            $platform = (int) $data['platform'];

            $payload = [
                'platform' => $platform,
                'config' => $config,
                'disabled' => (bool) ($data['disabled'] ?? false),
            ];
            $created = $this->platformSettingRepository->create($payload);
            
            // Nếu disabled false vô hiệu hóa các config khác cùng platform
            if ((bool) $data['disabled'] === false) {
                $affected = $this->platformSettingRepository->deactivateOthersByPlatform($created->platform, (string)$created->id);
                $message = $affected > 0
                    ? __('Cấu hình đã được kích hoạt. Các cấu hình khác cùng nền tảng đã được vô hiệu hóa và có thể ảnh hưởng tới user client đang dùng.')
                    : __('Cấu hình đã được kích hoạt.');
                return ServiceReturn::success(message: $message);
            }
            return ServiceReturn::success(message: __('Đã tạo cấu hình ở trạng thái vô hiệu hóa.')); 
        } catch (\Throwable $e) {
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
                    $message = __('Đã kích hoạt cấu hình và vô hiệu hóa các cấu hình khác cùng nền tảng. Lưu ý: thay đổi này ảnh hưởng tới toàn bộ user client sử dụng nền tảng này.');
                }
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
}


