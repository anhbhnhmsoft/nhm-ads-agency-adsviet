<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Core\ServiceReturn;
use Exception;
use FacebookAds\Api;
use FacebookAds\Object\Values\AdDatePresetValues;
use FacebookAds\Object\Values\AdsInsightsDatePresetValues;

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
    private ?Api $api = null;
    private ?array $config = null;

    public function __construct(
        protected PlatformSettingService $platformSettingService,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function initApi(): void
    {
        if ($this->api instanceof Api) {
            return;
        }

        $platformSetting = $this->platformSettingService->findPlatformActive(
            platform: PlatformType::META->value
        );
        if ($platformSetting->isError()) {
            throw new Exception($platformSetting->getMessage());
        }
        $platformData = $platformSetting->getData();
        $config = $platformData->config;
        if (empty($config)) {
            throw new Exception('Meta Business config is empty');
        }
        if (empty($config['app_id']) || empty($config['app_secret']) || empty($config['access_token'])) {
            throw new Exception('Meta Business config is not complete');
        }
        Api::init(
            app_id: $config['app_id'],
            app_secret: $config['app_secret'],
            access_token: $config['access_token'],
        );
        $this->config = $config;
        $this->api = Api::instance();
    }

    /**
     * Lấy id business chính
     * @return string
     */
    public function getIdPrimaryBM(): string
    {
        return $this->config['business_manager_id'] ?? '';
    }

    /**
     * Lấy thông tin người dùng hiện tại
     * @return ServiceReturn
     */
    public function getMe(): ServiceReturn
    {
        try {
            $this->initApi();
            $response = $this->api->call('/me')
                ->getContent();
            return ServiceReturn::success(data: $response);
        } catch (Exception $exception) {
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
            $this->initApi();
            $idPrimaryBM = $this->getIdPrimaryBM();
            $response = $this->api->call(
                '/' . $idPrimaryBM,
                'GET',
                [
                    'fields' => 'id,name,primary_page,verification_status,owned_ad_accounts{name,id,account_status}'
                ]
            )->getContent();
            return ServiceReturn::success(data: $response);
        } catch (Exception $exception) {
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
            $this->initApi();
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
        } catch (Exception $exception) {
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
            $this->initApi();
            $response = $this->api->call(
                '/me/businesses',
                'GET',
                [
                    'fields' => 'id,name,primary_page,verification_status,owned_ad_accounts{name,id,account_status}'
                ]
            )->getContent();
            return ServiceReturn::success(data: $response);
        } catch (Exception $exception) {
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
            $this->initApi();
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
        } catch (Exception $exception) {
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
            $this->initApi();
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

        } catch (Exception $exception) {
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
            $this->initApi();
            // Danh sách các trường (fields) cần lấy
            $fields = [
                'id',
                'account_id',       // -> Account's ID
                'name',             // -> Account's Name
                'account_status',   // -> Account's status (Trả về số 1, 2, ...)
                'disable_reason',   // -> Lý do tài khoản bị disable
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

        } catch (Exception $exception) {
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
            $this->initApi();
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

        } catch (Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy insights của tài khoản quảng cáo được chia nhỏ theo TỪNG NGÀY. (trong vòng 30 ngày)
     * Dùng để đồng bộ dữ liệu lịch sử vào Database.
     * @param string $accountId ID tài khoản (bắt buộc phải có tiền tố 'act_', ví dụ: act_123456)
     * @return ServiceReturn
     */
    public function getAccountDailyInsights(string $accountId): ServiceReturn
    {
        try {
            $this->initApi();
            // Các trường cần lấy để lưu vào DB
            $fields = [
                // 1. Cơ bản
                'date_start',
                'date_stop',
                'spend',
                'impressions',

                // 2. Tiếp cận & Tần suất
                'reach',
                'frequency',

                // 3. Traffic
                'clicks',            // Tổng click
                'inline_link_clicks', // Click vào link (Quan trọng)
                'ctr',
                'cpc',
                'cpm',

                // 4. Chuyển đổi & Doanh thu (Dạng Mảng/JSON)
                'actions',          // Số lượng (VD: 5 leads)
                'purchase_roas',    // Hiệu quả mua hàng
            ];

            // Cấu hình mặc định
            $defaultParams = [
                'fields'         => implode(',', $fields),
                'level'          => 'account', // Lấy tổng cấp tài khoản
                'time_increment' => 1, // Chia theo từng ngày
                'date_preset'    => AdsInsightsDatePresetValues::LAST_30D // Mặc định lấy 30 ngày nếu không truyền gì
            ];

            // Gộp tham số (Ưu tiên tham số truyền vào)
            $params = array_merge($defaultParams);

            $response = $this->api->call(
                "/{$accountId}/insights",
                'GET',
                $params
            )->getContent();

            return ServiceReturn::success(data: $response);

        } catch (Exception $exception) {
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
            $this->initApi();
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

        } catch (Exception $exception) {
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
            $this->initApi();
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
        } catch (Exception $exception) {
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
            $this->initApi();
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

        } catch (Exception $exception) {
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
            $this->initApi();
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
                // 30 ngày thì 5 ngày gộp 1
                AdDatePresetValues::LAST_90D => 2,            // 90 ngày thì 2 ngày gộp 1
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
        } catch (Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Cập nhật trạng thái chiến dịch (ACTIVE, PAUSED, DELETED, ...)
     * Meta Marketing API v24: POST /{campaign_id} với tham số status
     *
     * @param string $campaignId
     * @param string $status
     * @return ServiceReturn
     */
    public function updateCampaignStatus(string $campaignId, string $status): ServiceReturn
    {
        try {
            $this->initApi();

            $normalizedStatus = strtoupper($status);
            $allowed = ['ACTIVE', 'PAUSED', 'DELETED'];
            if (!in_array($normalizedStatus, $allowed, true)) {
                return ServiceReturn::error(message: __('meta.error.invalid_campaign_status'));
            }

            $response = $this->api->call(
                "/{$campaignId}",
                'POST',
                [
                    'status' => $normalizedStatus,
                ]
            )->getContent();

            return ServiceReturn::success(data: $response);
        } catch (Exception $exception) {
            $message = $exception->getMessage();
            if (str_contains($message, 'Permissions error')) {
                return ServiceReturn::error(message: __('meta.error.permissions_error'));
            }
            if (str_contains($message, 'Invalid parameter')) {
                return ServiceReturn::error(message: __('meta.error.invalid_spend_cap_generic'));
            }
            return ServiceReturn::error(message: $message);
        }
    }

    /**
     * Cập nhật giới hạn chi tiêu (spend_cap) của chiến dịch.
     *
     * Lưu ý:
     * - Meta yêu cầu giá trị ở đơn vị "minor unit" (VD: USD -> cent), nên 10 USDT => 1000.
     * - Ở hệ thống hiện tại chúng ta nhập theo đơn vị "USDT", nên cần nhân 100.
     *
     * @param string $campaignId
     * @param float $amountUsd Số tiền giới hạn theo đơn vị USDT/USD (VD: 100.5)
     * @return ServiceReturn
     */
    public function updateCampaignSpendCap(string $campaignId, float $amountUsd): ServiceReturn
    {
        try {
            // Mức tối thiểu bắt buộc)
            if ($amountUsd < 100) {
                return ServiceReturn::error(message: __('meta.error.invalid_spend_cap_min', ['amount' => 100]));
            }

            $this->initApi();

            // Chuyển sang "minor unit" (cent) theo chuẩn Meta
            $spendCapMinor = (int) round($amountUsd * 100);

            $response = $this->api->call(
                "/{$campaignId}",
                'POST',
                [
                    'spend_cap' => $spendCapMinor,
                ]
            )->getContent();

            return ServiceReturn::success(data: $response);
        } catch (Exception $exception) {
            $message = $exception->getMessage();
            if (str_contains($message, 'Permissions error')) {
                return ServiceReturn::error(message: __('meta.error.permissions_error'));
            }
            return ServiceReturn::error(message: $message);
        }
    }

}
