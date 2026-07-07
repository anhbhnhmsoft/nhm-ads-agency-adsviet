# Các mục yêu cầu khách hàng còn chưa xong - 30/05/2026

File này tách từ `docs/customer-requirements-2026-05-20.md`, chỉ liệt kê các mục `##` còn phần chưa xử lý, xử lý một phần hoặc cần kiểm chứng thêm.

## 5. Tự động tăng giới hạn chạy khi khách nạp tiền

- Đã bỏ nút nạp tiền ở BM/MCC và thêm nút nạp tiền theo từng tài khoản quảng cáo.
- Đã trừ ví user và tạo giao dịch chờ admin xử lý khi khách/agency nạp tiền vào account.
- Khách xác nhận ngày `30/05/2026`: yêu cầu này là tăng giới hạn chi tiêu của tài khoản, không phải tăng ngân sách campaign; nghiệp vụ tách theo dịch vụ/gói dịch vụ, không tách theo BM/MCC.
- Đã xử lý một phần - backend auto tăng giới hạn đã được nối:
  - Giao dịch nạp account lưu metadata `platform_type`, `service_user_id`, `account_id`, `account_name`.
  - Khi admin duyệt giao dịch nạp account, hệ thống phân loại nguồn billing theo dịch vụ.
  - `adviet_card`: thử tự động tăng giới hạn chi tiêu account.
  - `customer_card`: không tăng limit, vì loại này tính phí `% spending`.
  - `supplier_credit_line`: tạm để admin/nhà cung cấp xử lý thủ công nếu chưa cấu hình API riêng.
  - Meta: thêm hàm tăng `spend_cap` cấp ad account.
  - Google: thêm hàm tạo `AccountBudgetProposal` để tăng account spending limit.
  - Đã thêm field backend `billing_source` ở service package để chuẩn bị tách dịch vụ theo nguồn thanh toán.
  - Nếu API Meta/Google trả lỗi quyền/billing khi duyệt lệnh nạp, giao dịch không bị complete để admin xử lý thủ công.

**Các ý chính khách đã trả lời để xử lý mục này**

- Một BM/MCC có thể có nhiều tài khoản quảng cáo, và mỗi tài khoản có thể dùng nguồn thanh toán khác nhau.
- Không tách sản phẩm theo BM/MCC; khách muốn tách riêng thành các **dịch vụ/gói dịch vụ** khác nhau.
- Các loại dịch vụ cần phân biệt:
  - `customer_card`: tài khoản dùng thẻ của khách.
  - `adviet_card`: tài khoản dùng thẻ bên Adviet.
  - `supplier_credit_line`: tài khoản dùng credit line/thẻ của nhà cung cấp.
- `customer_card`:
  - không tính phí top up khi khách nạp tiền vào tài khoản theo kiểu thẻ Adviet/nhà cung cấp.
  - tính phí `% spending`, tức tool tự charge phần trăm theo chi tiêu thực tế của khách.
  - vẫn cần giữ rule số dư/nạp tối thiểu để kích hoạt tài khoản dùng thẻ khách nếu khách chốt giữ logic này.
- `adviet_card`:
  - khách nạp tiền từ ví trong tool vào tài khoản quảng cáo.
  - hệ thống tính phí top up theo phần trăm cấu hình của dịch vụ.
  - sau khi nạp, mục tiêu là tăng **giới hạn chi tiêu của tài khoản**.
- `supplier_credit_line`:
  - là một dịch vụ riêng.
  - khách nạp tiền từ ví trong tool vào tài khoản quảng cáo.
  - hệ thống tính phí top up theo phần trăm cấu hình của dịch vụ.
  - việc tự động tăng giới hạn chi tiêu phụ thuộc quyền/API của nhà cung cấp hoặc nền tảng.
- Phạm vi tự động tăng khi nạp:
  - tăng giới hạn chi tiêu ở cấp tài khoản quảng cáo (`spend cap`/account spending limit).
  - không phải tăng ngân sách campaign.
- Với tài khoản dùng thẻ bên Adviet, khách đã xác nhận khi khách nạp tiền thì cần tăng giới hạn chi tiêu của tài khoản.
- Vì nghiệp vụ tách theo dịch vụ, cấu hình nên nằm ở gói dịch vụ/account được giao, không nằm ở BM/MCC.
- Cần lưu nguồn thanh toán/billing của từng account hoặc từng service package để biết nạp tiền sẽ xử lý theo `top_up_fee` hay `% spending_fee`.

- Còn lại:
  - Cần test production với token/quyền billing thật của Meta/Google để xác nhận API cho phép tăng limit.
  - Chưa có ví/phân bổ số dư riêng theo từng ad account.
  - Chưa có UI đầy đủ để admin chọn nguồn thanh toán `customer_card`, `adviet_card`, `supplier_credit_line`; backend đã có field `billing_source` ở service package và có cơ chế fallback theo `supplier_id`/`payment_type`.
- Đang chờ khách cung cấp mỗi loại 1 account mẫu (`customer_card`, `adviet_card`, `supplier_credit_line`) để kiểm tra API Meta/Google có tự phân biệt được nguồn thanh toán hay cần admin cấu hình thủ công.

## 8. Phí spending và phí top up

- Còn lại:
  - Chưa thêm module phân biệt `customer_card`, `adviet_card`, `supplier_credit_line` theo dịch vụ/gói dịch vụ.
  - Chưa thêm điều kiện nạp tối thiểu `100$` để kích hoạt thẻ khách.
  - Chưa có logic `customer_card` tính phí `% spending`, còn `adviet_card`/`supplier_credit_line` tính phí top up khi khách nạp tiền vào tài khoản.
  - Cần làm cùng thiết kế ví/phí theo ad account.

## 9. Thứ tự triển khai đề xuất

- Đã hoàn thành các mục ưu tiên 1-4 ở mức sửa báo cáo/UI.
- Còn lại:
  - Thiết kế ví theo từng ad account.
  - Tự động tăng giới hạn/pause campaign theo số dư ví.

## 10. Các điểm cần xác nhận thêm với khách

- Cần xác nhận:
  - Khi ví hết tiền, tool pause ở cấp campaign hay adset.
  - Nếu Meta API không cho sửa spending limit tự động, khách có chấp nhận cơ chế tạo task cho admin không.

## 14. Cashback sau 30 ngày thay vì giảm phí theo monthly spending

- Đã có nền `CashbackService`, command tính cashback hằng ngày và ghi wallet transaction.
- Còn lại:
  - Chưa có bảng đối soát cashback riêng với trạng thái `pending/approved/paid` nếu khách cần quy trình duyệt/đối soát riêng.

## 15. Điều chỉnh lại thứ tự ưu tiên sau feedback ngày 20/05 buổi tối

- Đây là mục tổng hợp ưu tiên.
- Còn lại:
  - Nếu khách cần quy trình duyệt/đối soát cashback riêng thì bổ sung bảng lịch sử/trạng thái.

## 17. Kho tài khoản bán tự động và tự liên kết cho khách

- Đã có bước nền: bảng kho account, import kho theo gói, auto assign account có sẵn khi khách mua, release account khi hủy đơn.
- Còn lại:
  - Chưa gọi API invite/link thật sang Meta/Google vì cần xác minh quyền token production.
  - Với account chưa sync trong hệ thống, hiện mới lưu metadata để admin/API job xử lý sau.
  - Chưa tự động nạp tiền vào account sau khi bán.

## 18. Luồng nạp tiền từ số dư tool vào tài khoản quảng cáo

- Đã có phase 1 mức cơ bản: khách/agency nạp tiền theo từng ad account, hệ thống trừ ví và tạo giao dịch chờ admin duyệt.
- Đã ẩn `Quản lý BM/MCC` khỏi khu vực khách hàng/agency; khách quản lý tài khoản đã gán theo Meta/Google tại `Quản lý tài khoản`.
- Khách xác nhận không tách theo BM/MCC; các sản phẩm sẽ tách theo dịch vụ/gói dịch vụ dựa trên nguồn billing của account.
- Còn lại:
  - Chưa có bảng/lịch sử yêu cầu nạp tiền account riêng tách khỏi wallet transaction.
  - Chưa có màn hình admin chuyên biệt theo nghiệp vụ nạp account; hiện dùng luồng duyệt giao dịch ví.
  - Chưa tự động nạp/tăng giới hạn chi tiêu account bằng Google Ads API/Meta API.

## 19. Bổ sung Paymento làm cổng nạp crypto

- Đã tích hợp Paymento, tạo payment request, gateway URL và webhook verify trước khi cộng ví.
- Còn lại:
  - Cần cấu hình IPN URL trên dashboard Paymento.
  - Cần test giao dịch thực tế/testnet theo store Paymento.
