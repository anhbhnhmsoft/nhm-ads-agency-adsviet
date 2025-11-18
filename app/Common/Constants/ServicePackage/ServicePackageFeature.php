<?php

namespace App\Common\Constants\ServicePackage;

use Exception;

enum ServicePackageFeature: string
{
    /**
     * Meta
     */
    case META_NEW_BM = "meta_new_bm"; // BM mới 2023
    case META_FANPAGE_ATTACHED = "meta_fanpage_attached"; // Fanpage đính kèm
    case META_MULTIBRAND_SUPPORT = "meta_multibrand_support"; // Hỗ trợ thương hiệu đa thương hiệu
    case META_TIMEZONE_ID = 'meta_timezone_id'; // Múi giờ BM

    /**
     * Google
     */
    case GOOGLE_TRUST_SCORE_HIGH = 'google_trust_score_high';

    /**
     * Dùng chung
     */
    case NEW_ACCOUNT = "new_account";
    case GUARANTEE = "guarantee";
    case SUPPORT_247 = "support_247";


    public function label(): string
    {
        return match ($this) {
            self::META_NEW_BM => __('enum.ServicePackageFeature.META_NEW_BM'),
            self::META_FANPAGE_ATTACHED => __('enum.ServicePackageFeature.META_FANPAGE_ATTACHED'),
            self::META_MULTIBRAND_SUPPORT => __('enum.ServicePackageFeature.META_MULTIBRAND_SUPPORT'),
            self::META_TIMEZONE_ID => __('enum.ServicePackageFeature.META_TIMEZONE_ID'),

            self::GOOGLE_TRUST_SCORE_HIGH => __('enum.ServicePackageFeature.GOOGLE_TRUST_SCORE_HIGH'),

            self::GUARANTEE => __('enum.ServicePackageFeature.GUARANTEE'),
            self::SUPPORT_247 => __('enum.ServicePackageFeature.SUPPORT_247'),
            self::NEW_ACCOUNT => __('enum.ServicePackageFeature.NEW_ACCOUNT'),
        };
    }

    public function type(): string
    {
        return match ($this) {
            self::META_NEW_BM => 'boolean',
            self::META_FANPAGE_ATTACHED => 'boolean',
            self::META_MULTIBRAND_SUPPORT => 'boolean',
            self::META_TIMEZONE_ID => 'number',

            self::GOOGLE_TRUST_SCORE_HIGH => 'boolean',
            self::GUARANTEE => 'number',
            self::SUPPORT_247 => 'boolean',
            self::NEW_ACCOUNT => 'boolean',
        };
    }

    public function platform(): string
    {
        return match ($this) {
            self::META_NEW_BM,
            self::META_FANPAGE_ATTACHED,
            self::META_TIMEZONE_ID,
            self::META_MULTIBRAND_SUPPORT => 'meta',

            self::GOOGLE_TRUST_SCORE_HIGH => 'google',

            self::NEW_ACCOUNT,
            self::GUARANTEE,
            self::SUPPORT_247 => 'common',
        };
    }

    /**
     * Lấy danh sách các feature (enum cases) theo platform.
     * (Hàm này giữ nguyên)
     */
    public static function getFeaturesByPlatform(string $platform): array
    {
        if (!in_array($platform, ['meta', 'google'])) {
            return [];
        }

        return array_filter(
            self::cases(),
            fn($case) => $case->platform() === $platform || $case->platform() === 'common'
        );
    }

    /**
     * Lấy danh sách options theo cấu trúc [key, type, label]
     * @param string $platform ('meta' hoặc 'google')
     * @return array
     */
    public static function getOptionsByPlatform(string $platform): array
    {
        $features = self::getFeaturesByPlatform($platform);

        // Dùng array_map để biến đổi từng case
        return array_map(
            function (self $case) {
                return [
                    'key'   => $case->value,
                    'type'  => $case->type(),
                    'label' => $case->label(),
                ];
            },
            array_values($features)
        );
    }

    /**
     * Lấy danh sách options múi giờ cho Meta
     * @return string[]
     */
    public static function getOptionsMetaTimezoneId(): array
    {
        return [

        ];
    }
}
