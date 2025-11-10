<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use FacebookAds\Api;
use FacebookAds\Object\Fields\AdAccountFields;
class ServiceController extends Controller
{

    public function test()
    {
        // 1. Khởi tạo (Code của bạn)

//
//            // 2. Thực hiện cuộc gọi API để test
//            echo "Khởi tạo thành công. Đang thử gọi API...<br>";
//
//            // Lấy người dùng hiện tại (thông qua access token)
//            $user = new AdUser('me');
//
//            // Lấy danh sách tài khoản quảng cáo của người dùng đó
//            // Đây chính là lúc API được gọi thực sự
//            $adAccounts = $user->getAdAccounts([
//                AdAccountFields::ACCOUNT_ID,
//                AdAccountFields::NAME,
//                AdAccountFields::CURRENCY,
//            ]);
//
//            // 3. Nếu thành công, in kết quả
//            echo "<strong>KẾT NỐI THÀNH CÔNG!</strong><br>";
//            echo "Tìm thấy " . count($adAccounts) . " tài khoản quảng cáo:<br>";
//
//            foreach ($adAccounts as $account) {
//                echo "- ID: " . $account->{AdAccountFields::ACCOUNT_ID} .
//                    " | Tên: " . $account->{AdAccountFields::NAME} .
//                    " | Tiền tệ: " . $account->{AdAccountFields::CURRENCY} . "<br>";
//            }
//
//        } catch (Exception $e) {
//            // 4. Nếu thất bại, bắt lỗi
//            echo "<strong>KẾT NỐI THẤT BẠI!</strong><br>";
//            echo "Lỗi: " . $e->getMessage();
//            echo "<br>Chi tiết: " . $e->getResponse();
//        }
//        Api::init(
//            app_id: config('services.facebook.app_id'),
//            app_secret: config('services.facebook.app_secret'),
//            access_token: config('services.facebook.access_token'),
//        );
//
//        $instance = Api::instance();
    }
}
