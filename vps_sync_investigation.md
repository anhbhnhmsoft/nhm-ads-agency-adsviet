# Nhật ký điều tra đồng bộ dữ liệu VPS

**Ngày ghi nhận:** 22/06/2026

## 1. Hiện trạng lỗi
* **Hiện tượng:** Admin lọc theo BM con `1310584400254306` (`01 HYHD SC27-Vip2`) trên trang quản lý tài khoản chỉ hiển thị duy nhất 1 tài khoản `SC27-(GMT+7)-Adpro-13` (`act_478745820545731`).
* **Kỳ vọng:** Phải hiển thị đầy đủ cả 31 tài khoản quảng cáo (theo báo cáo trực tiếp từ trình quản lý kinh doanh của Meta), bao gồm các tài khoản đang chạy quảng cáo như `Adpro-22`, `Adpro-18`, `Adpro-24`.
* **Mốc cập nhật lần cuối:** Trên UI báo `02:31 22/06/2026` nhưng thực tế các tài khoản mới không có trong DB.

## 2. Dữ liệu đã kiểm tra trên VPS
* **Trạng thái Queue Jobs:** Đếm số lượng bảng `jobs` trả về `0` (không có job nào đang chạy hoặc kẹt).
* **Trạng thái Database:** 
  * Chạy lệnh `DB::table('meta_accounts')->whereIn('account_id', ['act_1601488500218022', 'act_380893730036367', 'act_630269054739398'])->pluck('account_name', 'account_id');` trả về `all: []` (trống rỗng).
  * Chứng minh các tài khoản này chưa hề được thêm vào database của VPS.
* **Grep logs:** Chạy `grep` tìm kiếm ID BM hoặc từ khóa `MetaService` trong `laravel.log` không ra kết quả nào.

## 3. Nguyên nhân kỹ thuật phát hiện
* Khi chạy lệnh đồng bộ thủ công qua tinker: `app(App\Service\MetaService::class)->syncFromBusinessManagerId('1310584400254306')` mà không truyền tham số thứ hai (Setting ID).
* Vì chạy trong môi trường Artisan Console (Tinker), hệ thống không có HTTP Session, nên `session('active_meta_setting_id')` trả về `null`.
* Dẫn đến hàm `initApi()` của `MetaBusinessService` bị lỗi phân quyền / không tìm thấy cấu hình và trả về `ServiceReturn::error()`.
* Trong code hiện tại của `MetaService@syncFromBusinessManagerIdBasic`, khi hàm API trả về lỗi, hệ thống chỉ ghi nhận log lỗi (hoặc bỏ qua) chứ không ném exception dừng hẳn luồng, nên kết quả cuối cùng của Tinker vẫn trả về một đối tượng `ServiceReturn` báo thành công (`success`). Điều này gây ra nhầm lẫn là đã đồng bộ xong nhưng thực chất chưa đồng bộ được gì.

## 4. Hành động tiếp theo
1. Chạy lệnh đồng bộ truyền kèm Setting ID đang hoạt động trên VPS.
2. Kiểm tra xem các tài khoản đã được lưu vào DB VPS chưa.
3. Nếu đã lưu thành công, kiểm tra hiển thị trên web.
