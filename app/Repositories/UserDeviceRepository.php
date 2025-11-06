<?php

namespace App\Repositories;

use App\Common\Constants\DeviceType;
use App\Common\Helper;
use App\Core\BaseRepository;
use App\Models\UserDevice;

class UserDeviceRepository extends BaseRepository
{
    protected function model(): UserDevice
    {
        return new UserDevice();
    }

    public function syncActiveUserWeb($userId): void
    {
        $idDeviceWeb = Helper::getWebDeviceId();
        if (!empty($idDeviceWeb)){
            $this->query()->updateOrCreate(
                [
                    'device_id' => $idDeviceWeb,
                ],
                [
                    'user_id' => $userId,
                    'device_type' => DeviceType::WEB->value,
                    'last_active_at' => now(),
                ]
            );
        }
    }
    public function syncActiveUserMobile($userId, $deviceId, $deviceName, DeviceType $deviceType): void
    {
        $this->query()->updateOrCreate(
            [
                'device_id' => $deviceId,
            ],
            [
                'user_id' => $userId,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'last_active_at' => now(),
            ]
        );
    }
}
