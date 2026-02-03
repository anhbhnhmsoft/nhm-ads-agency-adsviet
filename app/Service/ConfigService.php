<?php

namespace App\Service;

use App\Common\Constants\Config\ConfigName;
use App\Core\Logging;
use App\Core\ServiceReturn;
use App\Repositories\ConfigRepository;
use Illuminate\Database\QueryException;

class ConfigService
{
    public function __construct(
        protected ConfigRepository $configRepository,
    ) {
    }

    public function getAll(): ServiceReturn
    {
        try {
            $configs = $this->configRepository->findAll();
            $result = $configs->keyBy('key')->map(fn($config) => [
                'id' => $config->id,
                'key' => $config->key,
                'type' => $config->type,
                'value' => $config->value,
            ])->toArray();
            return ServiceReturn::success(data: $result);
        } catch (QueryException $e) {
            Logging::error(
                message: 'Lỗi khi lấy danh sách cấu hình ConfigService@getAll: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    public function getValue(ConfigName $key, mixed $default = null): mixed
    {
        try {
            $config = $this->configRepository->findByKey($key->value);
            return $config?->value ?? $default;
        } catch (QueryException $e) {
            Logging::error(
                message: 'Lỗi khi lấy cấu hình ConfigService@getValue: ' . $e->getMessage(),
                exception: $e
            );
            return $default;
        }
    }

    public function update(array $data): ServiceReturn
    {
        try {
            // Validate: chỉ cho phép update các key hợp lệ từ enum ConfigName
            $validKeys = array_column(ConfigName::cases(), 'value');
            $invalidKeys = array_diff(array_keys($data), $validKeys);

            if (!empty($invalidKeys)) {
                return ServiceReturn::error(message: __('Cấu hình không hợp lệ: :key', ['key' => implode(', ', $invalidKeys)]));
            }

            $this->configRepository->updateMany($data);
            return ServiceReturn::success();
        } catch (QueryException $e) {
            Logging::error(
                message: 'Lỗi khi cập nhật cấu hình ConfigService@update: ' . $e->getMessage(),
                exception: $e
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}

