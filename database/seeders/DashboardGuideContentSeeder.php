<?php

namespace Database\Seeders;

use App\Common\Constants\Config\ConfigName;
use App\Common\Constants\Config\ConfigType;
use App\Models\Config;
use Illuminate\Database\Seeder;

class DashboardGuideContentSeeder extends Seeder
{
    public function run(): void
    {
        $overwrite = filter_var(env('DASHBOARD_GUIDE_SEED_OVERWRITE', false), FILTER_VALIDATE_BOOLEAN);
        $config = Config::withTrashed()->firstOrNew([
            'key' => ConfigName::DASHBOARD_GUIDE_CONTENT->value,
        ]);

        if (! $overwrite && $config->exists && trim((string) $config->value) !== '') {
            return;
        }

        $config->fill([
            'type' => ConfigType::STRING->value,
            'value' => $this->guideContent(),
            'description' => 'Nội dung mẫu hướng dẫn sử dụng Dashboard cho khách hàng',
        ]);

        if ($config->trashed()) {
            $config->restore();
        }

        $config->save();
    }

    private function guideContent(): string
    {
        return <<<'HTML'
<h2>Hướng dẫn sử dụng Dashboard</h2>
<p>Chào mừng bạn đến với hệ thống ADVIET AGENCY. Khu vực này giúp bạn quản lý ví, mua gói dịch vụ, theo dõi tài khoản quảng cáo và gửi yêu cầu hỗ trợ khi cần.</p>

<h3>1. Nạp tiền vào ví</h3>
<ol>
    <li>Vào menu <strong>Quản lý tài chính</strong> hoặc <strong>Ví của tôi</strong>.</li>
    <li>Chọn <strong>Add Fund</strong>.</li>
    <li>Nhập số tiền cần nạp và chọn mạng thanh toán đang được hỗ trợ.</li>
    <li>Chuyển đúng số tiền vào địa chỉ ví hiển thị trên màn hình.</li>
    <li>Chờ hệ thống hoặc admin xác nhận giao dịch.</li>
</ol>
<blockquote>Lưu ý: Vui lòng kiểm tra đúng mạng ví trước khi chuyển tiền. Nếu chuyển sai mạng, giao dịch có thể không được xử lý.</blockquote>

<h3>2. Mua gói dịch vụ</h3>
<ol>
    <li>Vào menu <strong>Mua gói dịch vụ</strong>.</li>
    <li>Chọn gói phù hợp với nhu cầu quảng cáo của bạn.</li>
    <li>Kiểm tra phí, hạn mức và điều kiện sử dụng.</li>
    <li>Xác nhận mua gói. Hệ thống sẽ trừ tiền từ ví nếu số dư hợp lệ.</li>
</ol>

<h3>3. Quản lý tài khoản quảng cáo</h3>
<ul>
    <li>Vào menu <strong>Quản lý tài khoản</strong> để xem danh sách tài khoản đã đăng ký.</li>
    <li>Theo dõi trạng thái tài khoản: Active, Disabled hoặc các trạng thái khác.</li>
    <li>Xem chi tiêu, giới hạn chi tiêu, số dư còn lại và thông tin BM/MCC.</li>
    <li>Dùng bộ lọc tìm kiếm để kiểm tra tài khoản theo từ khóa, platform, BM/MCC hoặc thời gian.</li>
</ul>

<h3>4. Theo dõi giao dịch</h3>
<ul>
    <li>Kiểm tra lịch sử nạp tiền, rút tiền và các giao dịch liên quan đến ví.</li>
    <li>Nếu giao dịch đang chờ xử lý, vui lòng không tạo nhiều lệnh trùng nhau.</li>
    <li>Nếu cần hủy lệnh nạp đang chờ, dùng nút <strong>Hủy lệnh</strong> trong khu vực giao dịch.</li>
</ul>

<h3>5. Gửi yêu cầu hỗ trợ</h3>
<ol>
    <li>Vào menu <strong>Hỗ trợ</strong>.</li>
    <li>Tạo ticket mới và mô tả rõ vấn đề bạn gặp phải.</li>
    <li>Đính kèm thông tin giao dịch, tài khoản hoặc ảnh màn hình nếu cần.</li>
    <li>Theo dõi phản hồi từ đội ngũ hỗ trợ trong ticket.</li>
</ol>

<h3>6. Liên hệ</h3>
<p>Nếu cần hỗ trợ nhanh, vào menu <strong>Liên hệ</strong> để xem thông tin liên hệ chính thức của ADVIET AGENCY.</p>
HTML;
    }
}
