<?php

namespace App\Service;

use App\Core\ServiceReturn;
use FacebookAds\Api;
use FacebookAds\Object\Values\AdDatePresetValues;

/**
 * Class MetaBusinessService phục vụ tương tác với Meta Business API (không dùng lưu trữ database ở đây nhé)
 *
 * @note: Các hàm trong class này đều không lưu trữ dữ liệu vào database, chỉ dùng để tương tác với API.
 *
 * Các note:
 * - date_preset: today, yesterday, this_month, last_month, this_quarter, maximum, data_maximum, last_3d, last_7d, last_14d, last_28d, last_30d, last_90d, last_week_mon_sun, last_week_sun_sat, last_quarter, last_year, this_week_mon_today, this_week_sun_today, this_year
 *
 */
class MetaBusinessService
{
    private ?Api $api;

    public function __construct()
    {
        // tạm thời khởi tạo API ở đây, về sau refactor lại
        Api::init(
            app_id: env('META_APP_ID'),
            app_secret: env('META_APP_SECRET'),
            access_token: env('META_ACCESS_TOKEN'),
        );
        $this->api = Api::instance();
    }

    /**
     * Lấy id business chính
     * @return string
     */
    public function getIdPrimaryBM(): string
    {
        return "1537217683931546"; // Tạm thời fix cứng business id
    }

    /**
     * Lấy thông tin người dùng hiện tại
     * @return ServiceReturn
     */
    public function getMe(): ServiceReturn
    {
        try {
            $response = $this->api->call('/me')
                ->getContent();
            return ServiceReturn::success(data: $response);
        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy thông tin business chính
     * @return ServiceReturn
     */
    public function getPrimaryBusiness(): ServiceReturn
    {
        try {
            $idPrimaryBM = $this->getIdPrimaryBM();
            $response = $this->api->call(
                '/' . $idPrimaryBM,
                'GET',
                [
                    'fields' => 'id,name,primary_page,verification_status,owned_ad_accounts{name,id,account_status}'
                ]
            )->getContent();
            return ServiceReturn::success(data: $response);
        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }

    }

    /**
     * Tạo mới một business
     * @param string $userId
     * @param array $params
     * @return ServiceReturn
     */
    public function createBM(string $userId, array $params): ServiceReturn
    {
        try {
            $response = $this->api->call(
                '/' . $userId . '/businesses',
                'POST',
                [
                    'name' => $params['name'], // Tên business
                    'vertical' => $params['vertical'], // Ngành nghề kinh doanh, tham khảo: https://developers.facebook.com/docs/marketing-api/business-manager/reference/businesses#Verticals
                    'timezone_id' => $params['timezone_id'], // Múi giờ, tham khảo: https://developers.facebook.com/docs/marketing-api/reference/ad-account/timezone-id/
                ]
            )->getContent();
            return ServiceReturn::success(data: $response);
        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy thông tin tất cả business của người dùng hiện tại
     * @return ServiceReturn
     */
    public function getSelfBMs(): ServiceReturn
    {
        try {
            $response = $this->api->call(
                '/me/businesses',
                'GET',
                [
                    'fields' => 'id,name,primary_page,verification_status,owned_ad_accounts{name,id,account_status}'
                ]
            )->getContent();
            return ServiceReturn::success(data: $response);
        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Tạo mới một ads account
     * @param string $BmId
     * @param array $params
     * @return ServiceReturn
     */
    public function createAdsAccount(string $BmId, array $params): ServiceReturn
    {
        try {
            $response = $this->api->call(
                '/' . $BmId . '/adaccount',
                'POST',
                [
                    'name' => $params['name'], // Tên ads account
                    'currency' => 'USD', // Loại tiền tệ , Mặc định USD
                    'timezone_id' => $params['timezone_id'], // Múi giờ, tham khảo: https://developers.facebook.com/docs/marketing-api/reference/ad-account/timezone-id/
                    'end_advertiser' => $BmId, // Business quản lý ads account
                    'media_agency' => 'NONE', // Business đại lý
                    'partner' => 'NONE', // Business đối tác
                    'invoice' => false,
                ]
            )->getContent();
            return ServiceReturn::success(data: $response);
        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy MỘT TRANG danh sách ads account thuộc business
     * @param string $bmId
     * @param int $limit Số lượng muốn lấy (ví dụ: 25)
     * @param string|null $after Con trỏ "trang kế tiếp" (lấy từ request)
     * @param string|null $before Con trỏ "trang trước" (lấy từ request)
     * @return ServiceReturn
     */
    public function getOwnerAdsAccountPaginated(string $bmId, int $limit = 25, ?string $after = null, ?string $before = null): ServiceReturn
    {
        try {
            $endpoint = "/{$bmId}/owned_ad_accounts";
            $params = [
                'fields' => 'id,account_id,name',
                'limit' => $limit
            ];
            // Nếu frontend gửi 'after' (để xem trang kế), thêm nó vào
            if ($after) {
                $params['after'] = $after;
            }
            // Nếu frontend gửi 'before' (để xem trang trước), thêm nó vào
            if ($before) {
                $params['before'] = $before;
            }

            // Chỉ gọi API 1 LẦN DUY NHẤT
            $response = $this->api->call($endpoint, 'GET', $params)->getContent();

            // Trả về cả 'data' và 'paging'
            // Frontend sẽ dùng 'paging.cursors.after' để gọi trang tiếp theo
            return ServiceReturn::success(data: $response);

        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy chi tiết ads account theo id (Lưu ý: id acount phải có act_ ở đầu)
     * @param string $accountId
     * @return ServiceReturn
     */
    public function getDetailAdsAccount(string $accountId): ServiceReturn
    {
        try {
            // Danh sách các trường (fields) cần lấy
            $fields = [
                'id',
                'account_id',       // -> Account's ID
                'name',             // -> Account's Name
                'account_status',   // -> Account's status (Trả về số 1, 2, ...)
                'spend_cap',        // -> Limit (và Hidden Limit)
                'balance',          // -> Balance (Số dư hiện tại, thường là nợ)
                'currency',         // -> Currency (VD: "USD")
                'amount_spent',     // -> Total spending
                'created_time',     // -> Creation time
                'is_prepay_account',// -> Là tài khoản trả trước hay không (boolean)
                'timezone_id',      // -> Timezone ID (VD: 1)
                'timezone_name',    // -> Timezone (VD: "America/Creston")
            ];

            $response = $this->api->call(
                "/{$accountId}",
                'GET',
                ['fields' => implode(',', $fields)]
            )->getContent();

            return ServiceReturn::success(data: $response);

        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy MỘT TRANG danh sách chiến dịch (campaigns) của một ads account
     * @param string $accountId ID tài khoản (phải có 'act_')
     * @param int $limit Số lượng muốn lấy
     * @param string|null $after Con trỏ trang kế tiếp
     * @param string|null $before Con trỏ trang trước
     * @return ServiceReturn
     */
    public function getCampaignsPaginated(string $accountId, int $limit = 25, ?string $after = null, ?string $before = null): ServiceReturn
    {
        try {
            // Các trường (fields) cơ bản của một chiến dịch
            $fields = [
                'id',
                'name',
                'status',           // Trạng thái cài đặt (ACTIVE, PAUSED)
                'effective_status', // <-- QUAN TRỌNG: Trạng thái thực tế
                'objective',
                'daily_budget',
                'lifetime_budget',
                'budget_remaining', // Ngân sách còn lại (nếu dùng lifetime)
                'spend_cap',        // Giới hạn chi tiêu
                'created_time',
                'start_time',       // Ngày bắt đầu
                'stop_time',        // Ngày kết thúc
            ];
            $params = [
                'fields' => implode(',', $fields),
                'limit' => $limit
            ];
            // Thêm con trỏ phân trang (nếu có)
            if ($after) {
                $params['after'] = $after;
            }
            if ($before) {
                $params['before'] = $before;
            }
            $response = $this->api->call(
                "/{$accountId}/campaigns", // Endpoint
                'GET',
                $params
            )->getContent();

            // Trả về cả 'data' và 'paging' cho frontend xử lý
            return ServiceReturn::success(data: $response);

        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy insights chi tiêu (và insights khác)cho toàn bộ tài khoản THEO TỪNG CHIẾN DỊCH.
     * @param string $accountId ID tài khoản (act_...)
     * @param string $datePreset ('today', 'maximum', 'last_7d', ...)
     * @param array $fields Mảng các trường muốn lấy (nếu để trống sẽ lấy mặc định)
     * @return ServiceReturn
     */
    public function getAccountInsightsByCampaign(string $accountId, string $datePreset, array $fields = []): ServiceReturn
    {
        try {
            // Nếu không truyền fields, dùng mặc định
            if (empty($fields)) {
                $fields = [
                    'campaign_id', // <-- Trường breakdown
                    'campaign_name',
                    'spend',
                    'clicks',
                    'impressions',
                ];
            }
            $params = [
                'fields' => implode(',', $fields),
                'date_preset' => $datePreset, // Dùng biến
                'level' => 'campaign',
                'limit' => 500, // Lấy tối đa 500 chiến dịch
            ];

            // Lưu ý: Hàm này cũng có thể cần phân trang (pagination)
            // nếu tài khoản có > 500 chiến dịch, nhưng với
            // hầu hết các trường hợp thì 500 là đủ.
            $response = $this->api->call(
                "/{$accountId}/insights", // Gọi từ cấp tài khoản
                'GET',
                $params
            )->getContent();

            return ServiceReturn::success(data: $response);

        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy thông tin chi tiết của một chiến dịch.
     * @param string $campaignId
     * @return ServiceReturn
     */
    public function getCampaignDetail(string $campaignId): ServiceReturn
    {

        try {
            $fields = [
                'id',
                'name',
                'account_id',
                'status',           // Trạng thái cài đặt (ACTIVE, PAUSED)
                'objective',
                'budget_remaining', // Ngân sách còn lại (nếu dùng lifetime)
                'spend_cap',        // Giới hạn chi tiêu
                'start_time',       // Ngày bắt đầu
                'stop_time',
                'brand_lift_studies',
                'effective_status', // -> "Active"
                'daily_budget',     // -> "Ngân sách" (nếu hàng ngày)
                'lifetime_budget',  // -> "Ngân sách" (nếu trọn đời)
                'issues_info', // -> "Vấn đề nghiêm trọng"
                'created_time',
            ];
            $response = $this->api->call(
                "/{$campaignId}", // Endpoint
                'GET',
                [
                    'fields' => implode(',', $fields),
                ]
            )->getContent();
            return ServiceReturn::success(data: $response);
        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy dữ liệu Insights TỔNG HỢP cho một chiến dịch.
     * @param string $campaignId
     * @param string $datePreset ('today', 'maximum', 'last_7d', ...)
     * @return ServiceReturn
     */
    public function getCampaignInsights(string $campaignId, string $datePreset = 'maximum'): ServiceReturn
    {
        try {
            $fields = [
                'spend',         // -> Chi tiêu
                'impressions',   // -> Lượt hiển thị
                'clicks',        // -> Lượt nhấp
                'cpc',           // -> Chi phí cho mỗi click
                'cpm',           // -> Chi phí cho 1000 lượt hiển thị
                'purchase_roas', // -> Lợi nhuận mỗi lần mua hàng
                'actions{action_type, value}', // -> Chuyển đổi
                'results{action_type, value}', // -> Chuyển đổi
            ];

            $params = [
                'fields' => implode(',', $fields),
                'date_preset' => $datePreset,
            ];

            $response = $this->api->call(
                "/{$campaignId}/insights",
                'GET',
                $params
            )->getContent();

            // API sẽ tự động trả về dữ liệu đã tính toán %
            return ServiceReturn::success(data: $response);

        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy dữ liệu Insights HÀNG NGÀY (cho biểu đồ)
     * @param string $campaignId
     * @param string $datePreset ('last_7d', 'last_30d', 'this_week', 'this_month', ...)
     * @return ServiceReturn
     */
    public function getCampaignDailyInsights(string $campaignId, string $datePreset = 'last_7d'): ServiceReturn
    {
        // Chỉ chấp nhận các date_preset trong array này
        if (!in_array($datePreset, [
            AdDatePresetValues::LAST_7D,
            AdDatePresetValues::LAST_14D,
            AdDatePresetValues::LAST_30D,
            AdDatePresetValues::LAST_28D,
            AdDatePresetValues::LAST_90D
        ])) {
            return ServiceReturn::error(message: __('meta.error.date_preset_invalid'));
        }
        try {
            $fields = [
                'spend',         // -> Chi tiêu
                'impressions',   // -> Lượt hiển thị
                'clicks',        // -> Lượt nhấp
                'cpc',           // -> Chi phí cho mỗi click
                'cpm',           // -> Chi phí cho 1000 lượt hiển thị
                'date_start',    // Ngày bắt đầu
            ];
            $params = [
                'fields' => implode(',', $fields),
                'date_preset' => $datePreset,
                'time_increment' => 1,
                'limit' => 100,
            ];

            $response = $this->api->call(
                "/{$campaignId}/insights",
                'GET',
                $params
            )->getContent();


            $dailyData = $response['data'] ?? [];
            // 1. Xác định kích thước gộp (Chunk size)
            $chunkSize = match ($datePreset) {
                AdDatePresetValues::LAST_30D, AdDatePresetValues::LAST_28D => 5, // 30 ngày thì 5 ngày gộp 1
                AdDatePresetValues::LAST_90D => 15,            // 90 ngày thì 15 ngày gộp 1
                default => 1,                // 7, 14 ngày thì giữ nguyên từng ngày
            };
            // Nếu không cần gộp (size = 1), trả về luôn
            if ($chunkSize === 1) {
                return ServiceReturn::success(data: $dailyData);
            }
            // array_chunk sẽ cắt mảng $dailyData thành các mảng con có $chunkSize phần tử
            $chunks = array_chunk($dailyData, $chunkSize);
            $result = [];
            foreach ($chunks as $chunk) {
                $mergedPoint = [
                    'spend' => 0,
                    'impressions' => 0,
                    'clicks' => 0,
                    // Lấy ngày bắt đầu của phần tử đầu tiên trong nhóm
                    'date_start' => $chunk[0]['date_start'],
                    // Lấy ngày kết thúc của phần tử cuối cùng trong nhóm
                    'date_stop' => end($chunk)['date_stop'],
                ];

                // Cộng dồn các chỉ số thô (Raw Metrics)
                foreach ($chunk as $day) {
                    $mergedPoint['spend'] += (float) ($day['spend'] ?? 0);
                    $mergedPoint['impressions'] += (int) ($day['impressions'] ?? 0);
                    $mergedPoint['clicks'] += (int) ($day['clicks'] ?? 0);
                }

                // Tính toán lại các chỉ số trung bình (Derived Metrics)
                // QUAN TRỌNG: Không được cộng trung bình rồi chia, mà phải tính từ tổng
                // CPC = Spend / Clicks
                $mergedPoint['cpc'] = $mergedPoint['clicks'] > 0
                    ? round($mergedPoint['spend'] / $mergedPoint['clicks'], 2)
                    : 0;

                // CPM = (Spend / Impressions) * 1000
                $mergedPoint['cpm'] = $mergedPoint['impressions'] > 0
                    ? round(($mergedPoint['spend'] / $mergedPoint['impressions']) * 1000, 2)
                    : 0;
                // Format lại số liệu thành string (để giống format API trả về)
                $mergedPoint['spend'] = (string) $mergedPoint['spend'];
                $result[] = $mergedPoint;
            }
            return ServiceReturn::success(data: $result);
        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }


    /**
     * Lấy lịch sử hoạt động của chiến dịch (Gọi từ cấp Tài khoản và lọc).
     *
     * @param string $accountId ID tài khoản (Bắt buộc, vd: act_123456)
     * @param string $campaignId ID chiến dịch cần xem
     * @return ServiceReturn
     */
    public function getCampaignActivity(string $accountId, string $campaignId): ServiceReturn
    {
        try {
            $fields = [
                'event_type',   // Loại sự kiện (CAMPAIGN_PAUSED, CAMPAIGN_BUDGET_UPDATE...)
                'event_time',   // Thời gian
                'actor_name',   // Người thực hiện
                'extra_data',   // Dữ liệu cũ/mới
                'translated_event_type', // Tên sự kiện dễ đọc
            ];

            $params = [
                'fields' => implode(',', $fields),
                'limit' => 20,

                // 🚀 QUAN TRỌNG: Phải lọc theo ID chiến dịch
                'filtering' => [
                    [
                        'field' => 'object_id',
                        'operator' => 'EQUAL',
                        'value' => $campaignId
                    ],
                ],
            ];

            // Gọi vào endpoint của TÀI KHOẢN (/activities) chứ không phải Campaign
            $response = $this->api->call(
                "/{$accountId}/activities",
                'GET',
                $params
            )->getContent();

            return ServiceReturn::success(data: $response);

        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

}
