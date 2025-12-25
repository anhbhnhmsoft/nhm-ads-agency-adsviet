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
                'value' => '0x8d079bd99ad8a4afb925498ab329f458c27119fb',
                'description' => 'Địa chỉ ví Binance Smart Chain (BEP20) để nhận tiền',
            ],
            [
                'key' => ConfigName::TRC20_WALLET_ADDRESS->value,
                'type' => ConfigType::STRING->value,
                'value' => 'TKUi5RjHSczoEfLd4NQ8ae7SxbMxvNJtbx',
                'description' => 'Địa chỉ ví Tron (TRC20) để nhận tiền',
            ],
            [
                'key' => ConfigName::POSTPAY_MIN_BALANCE->value,
                'type' => ConfigType::STRING->value,
                'value' => '200',
                'description' => 'Số dư tối thiểu (USDT) để đăng ký thanh toán trả sau',
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

