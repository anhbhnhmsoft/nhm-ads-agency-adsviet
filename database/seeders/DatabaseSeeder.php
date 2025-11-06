<?php

namespace Database\Seeders;

use App\Common\Constants\User\UserRole;
use App\Common\Constants\Wallet\WalletStatus;
use App\Common\Helper;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'username' => 'admin@admin.com',
            'password' => Hash::make('Test12345678@'),
            'role' => UserRole::ADMIN->value,
            'disabled' => false,
            'referral_code' => Helper::generateReferCodeUser(UserRole::ADMIN),
        ]);

        // Tạo ví cho Admin (ACTIVE, balance = 0, password = null)
        UserWallet::query()->create([
            'user_id' => $admin->id,
            'balance' => 0,
            'password' => null,
            'status' => WalletStatus::ACTIVE->value,
        ]);
    }
}
