<?php

namespace App\Service;

use App\Core\ServiceReturn;
use FacebookAds\Api;

/**
 * Class MetaBusinessService phục vụ tương tác với Meta Business API (không dùng lưu trữ database ở đây nhé)
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
        }catch (\Exception $exception){
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
        }catch (\Exception $exception){
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
        }catch (\Exception $exception){
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
        }catch (\Exception $exception){
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
                    'name'              => $params['name'], // Tên ads account
                    'currency'          => 'USD', // Loại tiền tệ , Mặc định USD
                    'timezone_id'       => $params['timezone_id'] , // Múi giờ, tham khảo: https://developers.facebook.com/docs/marketing-api/reference/ad-account/timezone-id/
                    'end_advertiser'    => $BmId, // Business quản lý ads account
                    'media_agency'      => 'NONE', // Business đại lý
                    'partner'           => 'NONE', // Business đối tác
                    'invoice'           => false,
                ]
            )->getContent();
            return ServiceReturn::success(data: $response);
        }catch (\Exception $exception){
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
    public function getOwnerAdsAccountPaginated(string $bmId, int $limit = 25, ?string $after = null , ?string $before = null): ServiceReturn
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

        } catch (\Exception $exception){
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

        } catch (\Exception $exception){
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
     * Lấy dữ liệu chi tiêu (và insights khác) HÀNG NGÀY cho một chiến dịch.
     * Dùng hàm này để vẽ biểu đồ (chart).
     * @param string $campaignId ID chiến dịch (ví dụ: '238...')
     * @param array $timeRange Ví dụ: ['since' => '2025-11-01', 'until' => '2025-11-10']
     * @return ServiceReturn
     */
    public function getCampaignDailyInsights(string $campaignId, array $timeRange): ServiceReturn
    {
        try {
            // Các trường (fields) bạn muốn lấy cho biểu đồ
            $fields = [
                'spend',         // Chi tiêu
                'impressions',   // Lượt hiển thị
                'clicks',        // Lượt nhấp
                'cpc',           // Chi phí/lượt nhấp
                'date_start',    // Ngày bắt đầu (cho time_increment)
                'date_stop',     // Ngày kết thúc (cho time_increment)
            ];

            // Tham số
            $params = [
                'fields' => implode(',', $fields),
                // time_range phải được encode thành JSON string
                'time_range' => json_encode($timeRange),
                // time_increment = 1 nghĩa là "chia nhỏ dữ liệu theo từng ngày"
                'time_increment' => 1,
//                'level' => 'campaign', // Chỉ định rõ level (mặc dù là mặc định)
            ];

            $response = $this->api->call(
                "/{$campaignId}/insights", // Endpoint
                'GET',
                $params
            )->getContent();

            // Dữ liệu trả về sẽ là một mảng 'data' chứa nhiều object (mỗi object 1 ngày)
            return ServiceReturn::success(data: $response);

        } catch (\Exception $exception) {
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }
}
