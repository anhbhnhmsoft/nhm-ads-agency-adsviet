<?php

namespace App\Common\Constants\Platform;

class PlatformSettingFields
{
    public static function getGoogleFields(): array
    {
        return [
            [
                'key' => 'developer_token',
                'label' => __('platform.fields.developer_token.label'),
                'type' => 'text',
                'required' => true,
                'placeholder' => __('platform.fields.developer_token.placeholder'),
                'description' => __('platform.fields.developer_token.description'),
            ],
            [
                'key' => 'client_id',
                'label' => __('platform.fields.client_id.label'),
                'type' => 'text',
                'required' => true,
                'placeholder' => __('platform.fields.client_id.placeholder'),
                'description' => __('platform.fields.client_id.description'),
            ],
            [
                'key' => 'client_secret',
                'label' => __('platform.fields.client_secret.label'),
                'type' => 'password',
                'required' => true,
                'placeholder' => __('platform.fields.client_secret.placeholder'),
                'description' => __('platform.fields.client_secret.description'),
            ],
            [
                'key' => 'refresh_token',
                'label' => __('platform.fields.refresh_token.label'),
                'type' => 'password',
                'required' => true,
                'placeholder' => __('platform.fields.refresh_token.placeholder'),
                'description' => __('platform.fields.refresh_token.description'),
            ],
            [
                'key' => 'login_customer_id',
                'label' => __('platform.fields.login_customer_id.label'),
                'type' => 'text',
                'required' => true,
                'placeholder' => __('platform.fields.login_customer_id.placeholder'),
                'description' => __('platform.fields.login_customer_id.description'),
            ],
            [
                'key' => 'customer_ids',
                'label' => __('platform.fields.customer_ids.label'),
                'type' => 'textarea',
                'required' => false,
                'placeholder' => __('platform.fields.customer_ids.placeholder'),
                'description' => __('platform.fields.customer_ids.description'),
            ],
        ];
    }

    public static function getMetaFields(): array
    {
        return [
            [
                'key' => 'app_id',
                'label' => __('platform.fields.app_id.label'),
                'type' => 'text',
                'required' => true,
                'placeholder' => __('platform.fields.app_id.placeholder'),
                'description' => __('platform.fields.app_id.description'),
            ],
            [
                'key' => 'app_secret',
                'label' => __('platform.fields.app_secret.label'),
                'type' => 'password',
                'required' => true,
                'placeholder' => __('platform.fields.app_secret.placeholder'),
                'description' => __('platform.fields.app_secret.description'),
            ],
            [
                'key' => 'access_token',
                'label' => __('platform.fields.access_token.label'),
                'type' => 'password',
                'required' => true,
                'placeholder' => __('platform.fields.access_token.placeholder'),
                'description' => __('platform.fields.access_token.description'),
            ],
            [
                'key' => 'business_manager_id',
                'label' => __('platform.fields.business_manager_id.label'),
                'type' => 'text',
                'required' => false,
                'placeholder' => __('platform.fields.business_manager_id.placeholder'),
                'description' => __('platform.fields.business_manager_id.description'),
            ]
        ];
    }

    public static function getFieldsByPlatform(int $platform): array
    {
        return match ($platform) {
            PlatformType::GOOGLE->value => self::getGoogleFields(),
            PlatformType::META->value => self::getMetaFields(),
            default => [],
        };
    }
}
