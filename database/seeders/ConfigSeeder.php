<?php

namespace Database\Seeders;

use App\Common\Constants\Config\ConfigName;
use App\Common\Constants\Config\ConfigType;
use App\Models\Config;
use Illuminate\Database\Seeder;

class ConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'key' => ConfigName::BEP20_WALLET_ADDRESS->value,
                'type' => ConfigType::STRING->value,
                'value' => '',
                'description' => 'Địa chỉ ví Binance Smart Chain (BEP20) để nhận tiền',
            ],
            [
                'key' => ConfigName::TRC20_WALLET_ADDRESS->value,
                'type' => ConfigType::STRING->value,
                'value' => '',
                'description' => 'Địa chỉ ví Tron (TRC20) để nhận tiền',
            ],
        ];

        foreach ($configs as $config) {
            Config::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}

