<?php

namespace App\Service;

use App\Core\ServiceReturn;
use FacebookAds\Api;

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
     * Response demo
     * [
     * "data" => array:3 [
     * 0 => array:7 [
     * "id" => "act_1152480950321081"
     * "account_id" => "1152480950321081"
     * "name" => "001-HYHD-SC27-(GMT + 8)-110"
     * "account_status" => 2
     * "currency" => "USD"
     * "spend_cap" => "10000"
     * "amount_spent" => "219"
     * ]
     * 1 => array:7 [
     * "id" => "act_1737039264351297"
     * "account_id" => "1737039264351297"
     * "name" => "001-HYHD-SC27-(GMT + 8)-109"
     * "account_status" => 2
     * "currency" => "USD"
     * "spend_cap" => "0"
     * "amount_spent" => "0"
     * ]
     * 2 => array:7 [
     * "id" => "act_743485138536593"
     * "account_id" => "743485138536593"
     * "name" => "Adviet01"
     * "account_status" => 1
     * "currency" => "VND"
     * "spend_cap" => "0"
     * "amount_spent" => "1259692"
     * ]
     * ]
     * "paging" => array:1 [
     * "cursors" => array:2 [
     * "before" => "QVFIU01IRFEtYi1VdGtSTXgzS25rTlVHY0R3ZAHBncUlKcElTM3IxVllSUi0wUGxjMTFROExpNXE5RTJnZADBFZAUZAJRHNrY2dCb3J1N19FMVRnUHlBUEdHTHpR"
     * "after" => "QVFIU1c0U0hMNXZAYYW9wam02bkFFTU01clNuZAmVkTFpXUmJ3cWhyZAml6N2JjMHFKcUk0QlNoc1ZAyQV9LNWk1R3lITExGcGhKdGZAvTTZAxMHpNaXBKNzFGdW9n"
     * ]
     * ]
     * ]
     */
    /**
     * Lấy danh sách ads account thuộc business
     * @param string $BmId
     * @return ServiceReturn
     */
    public function getOwnerAdsAccount(string $BmId): ServiceReturn
    {
        try {
            $response = $this->api->call(
                "/{$BmId}/owned_ad_accounts",
                'GET',
                ['fields' => 'id,account_id,name,account_status,currency,spend_cap,amount_spent']
            )->getContent();
            return ServiceReturn::success(data: $response);
        }catch (\Exception $exception){
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Response demo
     *  [
     *  "id" => "act_743485138536593"
     *  "account_id" => "743485138536593"
     *  "name" => "Adviet01"
     *  "account_status" => 1
     *  "currency" => "VND"
     *  "spend_cap" => "0"
     *  "amount_spent" => "1259692"
     *  ]
     */
    /**
     * Lấy chi tiết ads account theo id (Lưu ý: id acount phải có act_ ở đầu)
     * @param string $accountId
     * @return ServiceReturn
     */
    public function getDetailAdsAccount(string $accountId): ServiceReturn
    {
        try {
            $response = $this->api->call(
                "/{$accountId}",
                'GET',
                ['fields' => 'id,account_id,name,account_status,currency,spend_cap,amount_spent']
            )->getContent();
            return ServiceReturn::success(data: $response);
        }catch (\Exception $exception){
            return ServiceReturn::error(message: $exception->getMessage());
        }
    }

    /**
     * Lấy danh sách chiến dịch (campaigns) của một ads account
     * @param string $accountId
     * @return ServiceReturn
     */
    public function getCampaigns(string $accountId): ServiceReturn
    {
        try {
            // Các trường (fields) cơ bản của một chiến dịch
            $fields = [
                'id',
                'name',
                'status', // Trạng thái: ACTIVE, PAUSED, ARCHIVED, ...
                'objective', // Mục tiêu: OUTCOME_SALES, OUTCOME_LEADS, ...
                'daily_budget',
                'lifetime_budget',
                'created_time',
            ];

            $response = $this->api->call(
                "/{$accountId}/campaigns", // Endpoint
                'GET',
                ['fields' => implode(',', $fields)] // Truyền các trường bạn muốn lấy
            )->getContent();

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
