# Audit lệch mobile so với web admin - 2026-06-23

## Mục tiêu

Mobile app trong `adsviet/` đang lệch nhiều sau các thay đổi ở web admin/backend về gói dịch vụ, BM/MCC, tài khoản quảng cáo và dữ liệu insight. File này ghi lại lần quét đầu để các vòng sau xử lý theo checklist, tránh mất ngữ cảnh.

## Phạm vi đã quét

- Mobile Expo app: `adsviet/`
- API mobile: `routes/api.php`, `app/Http/Controllers/API/*`
- Web admin liên quan: `resources/js/pages/service-*`, `resources/js/pages/business-manager`, `app/Service/BusinessManagerService.php`, `app/Service/MetaService.php`
- Backend resources/request: `ServicePackageResource`, `ServiceOwnerResource`, `MetaAdsAccountResource`, `MetaAdsCampaignResource`, `ServicePurchaseApiRequest`

## Tình trạng tổng quan

- Backend đã có nhiều logic mới dùng chung cho web admin: account-management API gọi `BusinessManagerService::getListBusinessManagers()` với `view=account`, lọc BM/MCC con qua `meta_account_business_manager_accesses.source_bm_id`.
- Mobile đã có màn `account-management` và API `/business-managers/account-management`, nhưng type/UI còn thiếu nhiều field so với web admin.
- Gói dịch vụ trên backend/web admin đã có các khái niệm mới: `payment_type`, `billing_source`, `can_use_postpay`, tồn kho tài khoản, nhiều account trong một yêu cầu. Mobile mới bắt đầu có phần UI này nhưng type/API payload chưa đồng bộ đầy đủ.
- Insight/report mobile vẫn dùng API cũ theo `date_preset`, chưa có bộ lọc theo ngày cụ thể/BM con như web admin.

## Vấn đề ưu tiên cao

### 1. Mobile mua gói dịch vụ lệch contract mới

Backend API `POST /api/service/register-package` nhận thêm:

- `payment_type`: `prepay` hoặc `postpay`
- `accounts[]`, tối đa 3 account
- `accounts.*.bm_ids[]`, `fanpages[]`, `websites[]`, `timezone_bm`, `asset_access`

Mobile file `adsviet/app/(app)/(service)/purchase.tsx` đã có UI nhiều account và trả sau, nhưng type trong `adsviet/features/service/utils/types.ts` còn cũ:

- `ServicePackageItem` thiếu hoặc đặt sai field backend trả về: `payment_type`, `billing_source`, `can_use_postpay`, `inventory_total_count`, `inventory_available_count`, `spending_fee`, `supplier_fee_percent`, `supplier_id`.
- Type đang có `postpay_allowed`, `postpay_min_balance`, `postpay_days_options`, `is_postpay_allowed`, trong khi `ServicePackageResource` trả `can_use_postpay`.
- `ServicePurchasePayload` chưa khai báo `accounts`, `billing_source`, các list `bm_ids/fanpages/websites`.

Hệ quả: Mobile dễ tính sai quyền chọn trả sau, sai phí, sai dữ liệu gửi khi khách mua gói.

### 2. Màn account-management mobile chưa khớp web admin

Backend API `/api/business-managers/account-management` đã dùng logic web admin, gồm stats, childManagers, lọc `child_manager_id`, dữ liệu tài khoản theo BM source.

Mobile liên quan:

- `adsviet/app/(app)/(tab)/account-management.tsx`
- `adsviet/components/app/account-management.tsx`
- `adsviet/features/business-managers/type.tsx`
- `adsviet/features/business-managers/hook/index.ts`

Điểm lệch:

- Type `AccountItem` thiếu nhiều field web admin đang hiển thị/khách dùng: trạng thái tài khoản, giới hạn chi tiêu, số dư còn lại, tổng chi tiêu, nợ chưa thanh toán, thời gian tạo, múi giờ, trạng thái chiến dịch, `last_synced_at`.
- UI mobile chỉ hiển thị tên, BM/MCC, owner, spend, balance, top up và view campaigns. Web admin có bảng giàu dữ liệu hơn và tổng kết theo ngày.
- Khi bấm từ danh sách BM sang account-management, mobile truyền `bm_id` rồi `BusinessHeader` tự set cả `manager_id` và `child_manager_id`. Cần kiểm tra với backend vì web admin chủ yếu dùng `child_manager_id` cho BM/MCC con.

### 3. Report/insight mobile chưa có bộ lọc theo BM/MCC con và ngày cụ thể

Mobile `adsviet/app/(app)/(tab)/report.tsx` dùng:

- `/api/service/report?platform=...`
- `/api/service/report-insight` với `date_preset`

Web admin quản lý tài khoản/report đang có filter:

- platform
- child_manager_id
- start_date/end_date hoặc ngày cụ thể
- view account/BM

Hệ quả: Sau khi web admin sửa logic BM hidden/source BM, mobile report có thể không kiểm được cùng kết quả như web admin hoặc Meta report theo BM con.

### 4. Meta account/campaign mobile thiếu field mới từ Resource

Backend `MetaAdsAccountResource` đã trả thêm:

- `status_label`, `status_severity`, `status_message`
- `disable_reason`, `disable_reason_code`, `disable_reason_severity`
- `balance_exhausted`, `payment_card`

Mobile type `adsviet/features/meta/utils/types.ts` thiếu một số field này hoặc khai kiểu string cho money trong khi backend trả float/null sau normalize.

Backend `MetaAdsCampaignResource` có `status_label`, `status_severity`; mobile mới dùng một phần.

## Vấn đề nền tảng dev/build

Chạy `npx tsc --noEmit` trong `adsviet/` hiện không kiểm tra được đúng vì `adsviet/node_modules` không tồn tại, trong khi có `node_modules` ở root repo. Lỗi chính:

- `File 'expo/tsconfig.base' not found`
- nhiều module Expo/React Native không tìm thấy
- `--jsx` không set do tsconfig base không resolve được

Việc này cần xử lý trước khi dùng typecheck làm cổng an toàn cho mobile.

## Checklist xử lý đề xuất

1. Sửa môi trường mobile dev: cài dependency đúng trong `adsviet/`, chạy được `npx tsc --noEmit` hoặc `npx expo start -c --web`.
2. Đồng bộ type mobile với API backend:
   - `ServicePackageItem`
   - `ServicePurchasePayload`
   - `ServiceOwnerItem.config_account`
   - `AccountItem`/`BusinessManager`
   - `MetaAccount`/`MetaCampaign`
3. Sửa mua gói mobile theo contract web admin:
   - dùng `can_use_postpay` thay vì `postpay_allowed`
   - hiển thị `billing_source`, tồn kho, phí spending nếu cần
   - gửi `accounts[]` đúng schema backend
4. Sửa account-management mobile:
   - map đủ field API web admin
   - hiển thị trạng thái account, spend cap, balance, total spend, unpaid, timezone, last synced
   - kiểm tra filter `child_manager_id` khi đi từ màn BM/MCC sang tài khoản
5. Sửa report mobile:
   - thêm filter BM/MCC con và ngày/range nếu khách cần so với web admin
   - cân nhắc dùng API account-management hoặc thêm API report mới dùng chung logic web admin
6. Sau mỗi nhóm: test trên 3 case đã xác nhận web admin OK:
   - BM `1310584400254306`
   - ngày `2026-06-21`, `2026-06-22`, `2026-06-23`
   - so sánh số account có delivery và tổng chi tiêu

## Cập nhật vòng 2 - 2026-06-28

Đã xử lý bước đầu nhóm mobile contract/type:

- Đồng bộ `ServicePackageItem` với backend resource mới: `payment_type`, `billing_source`, `can_use_postpay`, tồn kho, `spending_fee`, supplier fields.
- Mở rộng `ServicePurchasePayload` để gửi được `accounts[]`, `bm_ids[]`, `fanpages[]`, `websites[]`, `timezone_bm`.
- Mở rộng `MetaAdsServiceConfig` để mang `payment_type`, `billing_source`.
- Mở rộng `AccountItem` và `BusinessManager` cho dữ liệu account-management mới từ backend: status, disable reason, spend cap, remaining amount, timezone, last synced, scope BM.
- Màn mua gói mobile và form tạo account đã ưu tiên dùng `can_use_postpay`, vẫn giữ fallback key cũ để tránh vỡ dữ liệu cũ.
- Màn `account-management` mobile đã hiển thị thêm trạng thái, múi giờ, số dư còn lại, thời điểm cập nhật gần nhất.

Chưa xử lý ở vòng này:

- report mobile theo BM/MCC con và ngày cụ thể
- luồng filter/account-management sâu hơn với `manager_id` vs `child_manager_id`
- chuẩn hóa dev env/typecheck cho `adsviet/`

## File đáng đọc tiếp vòng sau

- `adsviet/features/service/utils/types.ts`
- `adsviet/app/(app)/(service)/purchase.tsx`
- `adsviet/features/business-managers/type.tsx`
- `adsviet/components/app/account-management.tsx`
- `adsviet/app/(app)/(tab)/report.tsx`
- `app/Http/Resources/ServicePackageResource.php`
- `app/Http/Requests/API/Service/ServicePurchaseApiRequest.php`
- `app/Service/BusinessManagerService.php`
- `app/Service/MetaService.php`
