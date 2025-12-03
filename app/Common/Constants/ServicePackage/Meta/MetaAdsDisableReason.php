<?php

namespace App\Common\Constants\ServicePackage\Meta;

/**
 * Enum lý do tài khoản Meta Ads bị vô hiệu hóa (disable_reason của AdAccount)
 *
 * Tham khảo từ Meta Marketing API:
 * - disable_reason chỉ có ý nghĩa khi account_status = 2 (DISABLED)
 * - Giá trị là số nguyên, mapping sang các nhóm lỗi chính bên dưới
 */
enum MetaAdsDisableReason: int
{
    case NONE = 0;
    case POLICY = 1;            // Vi phạm chính sách quảng cáo / tiêu chuẩn cộng đồng
    case CREDIT_CARD = 2;       // Lỗi thanh toán / thẻ tín dụng
    case CHARGEBACK = 3;        // Chargeback / tranh chấp thanh toán từ ngân hàng
    case RISK_PAYMENT = 4;      // Rủi ro thanh toán / hoạt động bất thường
    case ADS_INTEGRITY = 5;     // Vấn đề tính toàn vẹn quảng cáo (spam, gian lận, chất lượng kém)
    case BUSINESS_INTEGRITY = 6;// Vấn đề tính toàn vẹn doanh nghiệp / Business Manager
    case DOMAIN_VERIFICATION = 7;// Lỗi xác minh tên miền
    case OTHER = 999;            // Lỗi không xác định

    public static function fromValue(?int $value): ?self
    {
        return $value !== null ? self::tryFrom($value) : null;
    }

    public function label(): string
    {
        return match ($this) {
            self::NONE => __('meta.disable_reason.none'),
            self::POLICY => __('meta.disable_reason.policy'),
            self::CREDIT_CARD => __('meta.disable_reason.credit_card'),
            self::CHARGEBACK => __('meta.disable_reason.chargeback'),
            self::RISK_PAYMENT => __('meta.disable_reason.risk_payment'),
            self::ADS_INTEGRITY => __('meta.disable_reason.ads_integrity'),
            self::BUSINESS_INTEGRITY => __('meta.disable_reason.business_integrity'),
            self::DOMAIN_VERIFICATION => __('meta.disable_reason.domain_verification'),
            self::OTHER => __('meta.disable_reason.other'),
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::NONE => 'warning',
            self::POLICY,
            self::CHARGEBACK,
            self::RISK_PAYMENT,
            self::ADS_INTEGRITY,
            self::BUSINESS_INTEGRITY => 'error',
            self::CREDIT_CARD,
            self::DOMAIN_VERIFICATION => 'warning',
            self::OTHER => 'error',
        };
    }
}


