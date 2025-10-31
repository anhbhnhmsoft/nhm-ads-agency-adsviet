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
                    'device_type' => DeviceType::WEB->value
                ],
                [
                    'user_id' => $userId,
                    'last_active_at' => now(),
                ]
            );
        }
    }
}
