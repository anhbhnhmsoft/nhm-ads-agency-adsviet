# Ghi chú yêu cầu khách hàng - 20/05/2026

Tài liệu này tổng hợp các yêu cầu khách hàng gửi ngày 20/05/2026, kèm hướng xử lý đề xuất. Các ảnh khách gửi đã được đối chiếu với nội dung chat, đặc biệt các màn hình Meta Ads Manager, Billing Hub, extension SMIT, CoinRemitter và giao diện tool hiện tại.

## Todolist triển khai

- [x] Kiểm tra nguyên nhân tool không hiện chi tiêu ngày `20/05/2026` cho BM `01 HYHD SC27-Vip2`.
  - Kết quả kiểm tra local: 3 account khách gửi vẫn có trong `meta_accounts` và mapping BM ở `meta_account_business_manager_accesses`, nhưng bảng `meta_ads_account_insights` chưa có dòng ngày `2026-05-20` cho các account đó. Dữ liệu mới nhất local đang dừng ở `2026-05-18`, access sync khoảng `2026-05-19 13:32`.
  - Đã thêm nút `Cập nhật dữ liệu Meta` ở trang quản lý tài khoản khi chọn Meta + BM/MCC con. Nút này dispatch `SyncMetaPlatformJob` theo BM đang chọn để lấy lại account/insights/campaigns qua queue.
- [x] Giảm chu kỳ đồng bộ nền từ 1 giờ xuống 30 phút.
  - `app:sync-ads-service-user` chạy mỗi 30 phút.
  - `SyncAllPlatformsJob` chạy mỗi 30 phút.
  - Lưu ý: dữ liệu vẫn không phải realtime tuyệt đối; còn phụ thuộc thời gian queue xử lý và Meta API limit.
- [x] Không cộng tổng chi tiêu khi kết quả có nhiều currency.
  - Backend trả thêm `totals_by_currency`.
  - Footer bảng service-management hiển thị tổng theo từng currency thay vì cộng USD và VND thành một số.
- [x] Bỏ cột `Account type / Postpay` khỏi bảng quản lý tài khoản.
- [x] Thêm màu trạng thái tài khoản.
  - Active/Hoạt động: xanh lá.
  - Warning/Nợ thanh toán/Need to pay: cam.
  - Disabled/Suspended/Error: đỏ.
- [x] Rà soát scale tiền USD/VND ở các field Meta (`balance`, `spend_cap`, `amount_spent`, insights `spend`).
  - Account-level money từ Meta (`spend_cap`, `amount_spent`, `balance`) được normalize theo currency trước khi trả ra UI.
  - Zero-decimal currencies như `VND`, `JPY`, `KRW` giữ nguyên.
  - Currency có cents như `USD` chia 100.
  - Insights `spend` theo ngày giữ nguyên vì Meta trả theo đơn vị hiển thị.
- [x] Thêm hiển thị lý do lỗi tài khoản bị disabled/suspend nếu Meta API trả `disable_reason`.
  - Lý do lỗi hiển thị dưới tên ad account.
  - Badge trạng thái có tooltip lý do lỗi.
- [x] Hiển thị thời điểm đồng bộ gần nhất ở trang quản lý tài khoản.
  - Trang `service-management` hiển thị `Cập nhật lần cuối` dựa trên `last_synced_at` mới nhất của các account đang hiển thị.
- [x] Sửa cấu hình nhà cung cấp: hỗ trợ phí cố định 7% và bỏ/ẩn monthly spending tier khi không dùng.
  - Form tạo/sửa nhà cung cấp mặc định không còn tự thêm monthly spending tier.
  - Đã thêm nút `Không dùng biểu phí` để xoá toàn bộ tier và dùng `Chi phí nhà cung cấp (%)` cố định.
  - Vẫn giữ nút `Sử dụng mẫu mặc định` nếu sau này cần bật lại tier.
- [x] Chuyển cấu trúc tier phí thành cashback sau 30 ngày.
  - Service package đã dùng nhãn cashback theo bậc chi tiêu 30 ngày.
  - Đã có `CashbackService` và command `app:calculate-cashback` chạy hằng ngày để tính cashback theo kỳ 30 ngày.
  - Lưu ý: hiện lịch sử cashback đang ghi qua wallet transaction, chưa có bảng đối soát cashback riêng với trạng thái pending/approved/paid.
- [x] Tích hợp CoinRemitter/USDT vào luồng nạp ví hiện tại.
  - Đã kiểm tra official docs/package: API invoice dùng endpoint `https://api.coinremitter.com/v1/invoice/create|get`, header `X-Api-Key`, `X-Api-Password`, mỗi coin/network có credential riêng.
  - Không dùng package `coinremitter/laravel` trong lần này vì project đang Laravel 12 và tích hợp REST trực tiếp dễ kiểm soát webhook/test hơn.
  - Đã thêm tạo invoice CoinRemitter, link hóa đơn, QR pending deposit và webhook tự xác minh invoice trước khi cộng ví.
- [x] Tích hợp Paymento vào luồng nạp ví hiện tại.
  - Đã thêm tạo payment request Paymento, lưu token, hiển thị gateway URL và webhook xác minh payment trước khi cộng ví.
- [x] Hoàn thiện thông báo Telegram vào group hỗ trợ.
  - Đã cấu hình luồng gửi thông báo nạp ví/ticket vào `TELEGRAM_SUPPORT_GROUP_ID`.
  - Đã sửa thông báo ticket để hiển thị chủ đề dạng dễ đọc thay vì mã nội bộ như `transfer_request`.
  - Đã khóa submit các form ticket chính để giảm lỗi spam nút tạo nhiều yêu cầu cùng lúc.
- [ ] Mở rộng ví theo từng ad account.

## 1. Chi tiêu chưa cập nhật gần thời gian thực

**Yêu cầu**

- Khách báo tổng chi tiêu trên Facebook đã là khoảng `14.5tr`, nhưng tool chỉ hiển thị khoảng `158.000`.
- Ảnh Meta Ads Manager cho BM `01 HYHD SC27-Vip2` trong khoảng `May 19, 2026 - May 20, 2026` hiển thị tổng chi tiêu `14,585,413 VND`.
- Ảnh tool cùng filter ngày và BM lại hiển thị tổng kết quả chỉ `158.203 VND`.

**Hướng xử lý**

- Giảm chu kỳ đồng bộ dữ liệu Meta từ 1 giờ xuống 15-30 phút nếu quota API cho phép.
- Thêm nút đồng bộ thủ công cho admin ở trang quản lý tài khoản/BM.
- Hiển thị `Cập nhật lần cuối` trên bảng để biết dữ liệu đang mới hay cũ.
- Với filter ngày hiện tại, cần ưu tiên lấy insights mới hoặc refresh cache ngắn hạn thay vì dùng dữ liệu cũ.
- Cần kiểm tra lại query tổng kết quả đang lấy theo đúng `start_date`, `end_date`, `platform`, `BM/MCC con` chưa.

**Ưu tiên:** Cao.

**Đã xử lý**

- Đã giảm lịch sync nền từ `hourly()` xuống `everyThirtyMinutes()` cho:
  - `app:sync-ads-service-user`.
  - `SyncAllPlatformsJob`.
- Đã thêm nút `Cập nhật dữ liệu Meta` để admin có thể chủ động sync lại BM đang chọn khi cần đối chiếu ngay.
- Đã hiển thị `Cập nhật lần cuối` ở trang quản lý tài khoản, lấy theo dữ liệu account đang hiển thị.
- Khách xác nhận chu kỳ sync 30 phút là đủ; không cần fetch realtime trực tiếp khi mở filter ngày hiện tại.

## 2. Sai số tiền USD / dư số 0 / lệch đơn vị tiền

**Yêu cầu**

- Khách báo có tài khoản Facebook nợ `32$`, nhưng tool hiển thị `3193$`.
- Khách báo giới hạn chi tiêu tài khoản trên Facebook là khoảng `163$`, nhưng tool hiển thị `35.6$`.
- Ảnh Billing Hub cho thấy:
  - `Remaining amount: $163.02`
  - `$193.26 spent | $356.28 spending limit`
- Ảnh khác cho thấy:
  - `Remaining amount: $2,868.13`
  - `$1,131.87 spent | $4,000.00 spending limit`
- Ảnh tool có dòng USD đang hiển thị:
  - `Chi tiêu: 113.187 USD`
  - `Giới hạn chi tiêu tài khoản: 400.000 USD`
  - `Số dư còn lại: 286.813 USD`

**Hướng xử lý**

- Rà lại toàn bộ logic normalize tiền từ Meta:
  - `VND`: thường dùng số nguyên, không chia cent.
  - `USD`: cần xác định field API trả về dollar hay cent trước khi format.
- Không dùng chung một công thức chia/nhân cho mọi field.
- Tạo helper backend thống nhất cho monetary fields:
  - `amount_spent`
  - `balance`
  - `spend_cap`
  - `remaining_amount`
  - `insights.spend`
- Đối chiếu ít nhất 2 tài khoản mẫu khách gửi:
  - `001-HYHD-SC27-(GMT-8)-NA-185`
  - tài khoản có `Remaining amount: $163.02`.

**Ưu tiên:** Rất cao, vì ảnh hưởng trực tiếp báo cáo tiền.

**Đã xử lý**

- Thêm normalize tiền Meta ở backend cho các field account-level:
  - `spend_cap`
  - `amount_spent`
  - `balance`
  - `remaining_amount`
- `VND` và các zero-decimal currency giữ nguyên.
- `USD` và các currency có cents chia 100 khi hiển thị.
- Không chia `insights.spend` theo ngày, vì field này Meta trả về theo đơn vị hiển thị.
- Đã đối chiếu local với account `act_691510320667452`:
  - raw `spend_cap = 400000` -> hiển thị `4000.0 USD`.
  - raw `amount_spent = 113187` -> hiển thị `1131.87 USD`.
  - raw `balance = 2121` -> hiển thị `21.21 USD`.
  - `remaining_amount = 2868.13 USD`.
- Account VND mẫu `act_978273446145835` giữ nguyên:
  - raw `spend_cap = 152500000` -> hiển thị `152500000 VND`.
  - raw `amount_spent = 102500000` -> hiển thị `102500000 VND`.

## 3. Bỏ cột Postpay / Account type

**Yêu cầu**

- Khách muốn bỏ cột `Postpay`, vì mô hình vận hành của khách là trả trước.
- Khách giải thích:
  - Dùng thẻ của agency hay thẻ của khách đều phải trả phí trước.
  - Dùng thẻ khách thì tính phí theo spending.
  - Dùng thẻ agency thì tính phí top up.

**Hướng xử lý**

- Ẩn cột `Account type / Loại tài khoản` khỏi bảng quản lý tài khoản.
- Có thể giữ dữ liệu trong DB để phục vụ kỹ thuật, nhưng không hiển thị cho khách.
- Nếu cần phân loại nghiệp vụ, nên tạo field riêng:
  - `payment_mode = agency_card | customer_card`
  - không phụ thuộc vào `Postpay/Prepay` của Meta.

**Ưu tiên:** Trung bình.

**Đã xử lý**

- Đã ẩn cột `Account type / Postpay` khỏi bảng quản lý tài khoản.
- Dữ liệu vẫn giữ trong DB/resource để không phá các nghiệp vụ kỹ thuật cũ.

## 4. Tích hợp cổng thanh toán USDT CoinRemitter

**Yêu cầu**

- Khách muốn tích hợp CoinRemitter: `https://coinremitter.com/`.
- Ảnh CoinRemitter cho thấy phí khoảng `0.23%`, hỗ trợ USDT ERC20/TRC20 và một số coin khác.
- Khách muốn khi khách hàng thanh toán qua cổng này thì app tự động ghi nhận.

**Hướng xử lý**

- Tạo module thanh toán crypto:
  - tạo invoice nạp tiền.
  - chọn coin/network, ưu tiên `USDTTRC20` vì phí thấp.
  - lưu trạng thái `pending`, `paid`, `confirmed`, `expired`, `failed`.
- Tích hợp webhook CoinRemitter:
  - xác thực webhook.
  - chống ghi nhận trùng giao dịch.
  - tự cộng ví sau khi thanh toán đủ điều kiện xác nhận.
- Lưu transaction:
  - user/customer
  - ad account nếu nạp cho từng tài khoản
  - amount
  - coin/network
  - fee
  - tx hash
  - exchange rate nếu có quy đổi.

**Ưu tiên:** Cao nhưng là hạng mục mới, cần làm riêng sau khi ổn định báo cáo tiền.

**Đã xử lý bước tích hợp nền tảng**

- Đã thêm `CoinRemitterService` gọi REST API official:
  - `POST /invoice/create` để tạo hóa đơn.
  - `POST /invoice/get` để xác minh trạng thái hóa đơn trước khi cộng ví.
  - Dùng header `X-Api-Key` và `X-Api-Password` theo từng network/coin.
- Đã thêm cấu hình trong `config/services.php`:
  - `COINREMITTER_TRC20_COIN=USDTTRC20`.
  - `COINREMITTER_TRC20_API_KEY`.
  - `COINREMITTER_TRC20_PASSWORD`.
  - `COINREMITTER_INVOICE_EXPIRE_MINUTES`.
  - Có sẵn slot cho `BEP20` nếu sau này CoinRemitter ví đó được cấu hình.
- Đã nối vào luồng nạp ví hiện tại:
  - Nếu network có cấu hình CoinRemitter, tạo invoice tự động thay vì yêu cầu chuyển thủ công vào ví cấu hình.
  - Lưu `payment_id` là `invoice_id`.
  - Lưu link hóa đơn vào `reference_id`.
  - Card lệnh nạp đang chờ hiển thị nút mở hóa đơn và QR.
- Đã thêm webhook:
  - `POST /webhooks/coinremitter`.
  - Webhook không tin payload trực tiếp; hệ thống gọi lại `invoice/get` để xác minh trạng thái.
  - Status `1 Paid` và `3 Over Paid` sẽ duyệt nạp/cộng ví.
  - Status `4 Expired` và `5 Cancelled` sẽ đánh dấu giao dịch bị từ chối.
  - Webhook đã được loại khỏi CSRF để CoinRemitter gọi được từ bên ngoài.
- Đã cấu hình/đã xác nhận luồng CoinRemitter ở mức cần thiết cho giai đoạn hiện tại. Notify URL trên dashboard CoinRemitter:
  - `https://domain-cua-app/webhooks/coinremitter`.

## 5. Tự động tăng giới hạn chạy khi khách nạp tiền

**Yêu cầu**

- Khách muốn khi user nạp thêm USDT, tool tự tăng số tiền khách có thể chạy trên từng tài khoản.
- Ví dụ:
  - tài khoản còn chạy thêm được `163$`.
  - khách nạp thêm `100$`.
  - tool tự điều chỉnh thành `263$` sau khi xử lý phí.
- Khách xác nhận logic này áp dụng cho từng tài khoản quảng cáo, không phải theo BM.
- Cập nhật note khách ngày `28/05/2026`:
  - `Nạp tiền` là nạp vào từng tài khoản quảng cáo, không phải nạp vào BM/MCC.
  - Nếu khách có 10 tài khoản thì mỗi tài khoản cần có khu vực/hành động nạp tiền riêng.
  - Khi khách nạp vào tài khoản nào, tool lấy tiền từ ví của khách để tạo yêu cầu nạp cho đúng tài khoản đó.
  - Nút `Nạp tiền` ở trang `Quản lý BM/MCC` dễ gây hiểu nhầm là nạp vào BM/MCC, cần bỏ/ẩn khỏi trang này.
- Cập nhật xác nhận khách ngày `30/05/2026`:
  - Không tách nghiệp vụ theo BM/MCC, mà tách theo từng **dịch vụ/gói dịch vụ**.
  - Trong cùng một BM/MCC có thể có nhiều tài khoản quảng cáo dùng các nguồn thanh toán khác nhau.
  - Tài khoản dùng thẻ của khách là một dịch vụ riêng.
  - Tài khoản dùng thẻ bên Adviet là một dịch vụ riêng.
  - Tài khoản dùng credit line của nhà cung cấp là một dịch vụ riêng.
  - Tài khoản dùng thẻ bên Adviet và credit line nhà cung cấp tính phí top up.
  - Tài khoản dùng thẻ khách tính phí `% spending`; tool tự charge phần trăm theo chi tiêu của khách.
  - Nếu dùng thẻ bên Adviet thì khi khách nạp tiền cần tăng **giới hạn chi tiêu của tài khoản**.
  - Không phải tăng ngân sách campaign trực tiếp trong yêu cầu này.

**Quyết định nghiệp vụ rút ra từ câu trả lời của khách**

- Cấu hình nguồn thanh toán/billing nên gắn vào gói dịch vụ hoặc account được giao, không gắn vào BM/MCC.
- Cần phân biệt ít nhất 3 loại nguồn thanh toán:
  - `customer_card`: thẻ khách, tính phí `% spending`.
  - `adviet_card`: thẻ Adviet, tính phí top up khi khách nạp tiền từ ví vào account.
  - `supplier_credit_line`: credit line/thẻ nhà cung cấp, tính phí top up khi khách nạp tiền từ ví vào account.
- Luồng nạp tiền vào account chỉ nên tự động tăng giới hạn chi tiêu với các account mà Adviet/nhà cung cấp có quyền billing/API phù hợp.
- Với `customer_card`, trọng tâm không phải tăng giới hạn chi tiêu sau nạp, mà là theo dõi spend và charge phí `% spending` từ số dư/phí trong tool.

**Luồng nghiệp vụ khách mô tả**

- Trường hợp dùng thẻ agency:
  - khách thanh toán USDT cho agency.
  - agency chuyển tiền vào thẻ của agency.
  - agency điều chỉnh tài khoản Facebook để khách chạy tiếp.
  - khách được chạy thêm số tiền sau khi trừ phí dịch vụ.
- Trường hợp dùng credit line của nhà cung cấp:
  - đây là một dịch vụ riêng.
  - khách nạp tiền từ ví vào tài khoản và bị tính phí top up.
  - nếu Adviet không có quyền billing/API trực tiếp thì team xử lý thủ công theo yêu cầu nạp.
- Trường hợp dùng thẻ khách:
  - khách tự add thẻ vào Facebook.
  - khách phải nạp trước tối thiểu `100$` vào tool để kích hoạt.
  - nếu chạy hết phí trong tool mà không nạp thêm thì campaign tự động dừng.

**Hướng xử lý**

- Thiết kế ví theo từng ad account:
  - `wallet_balance`
  - `reserved_amount`
  - `service_fee_rate`
  - `payment_mode`
- Khi webhook thanh toán thành công:
  - cộng tiền vào ví của ad account.
  - trừ phí dịch vụ theo cấu hình.
  - tính số tiền được phép chạy thêm.
- Với thẻ agency:
  - nếu API Meta cho phép cập nhật giới hạn chi tiêu của tài khoản (`spend cap`), gọi API tự động.
  - nếu không đủ quyền, tạo task/notification cho admin xử lý thủ công.
- Với credit line nhà cung cấp:
  - tính phí top up theo cấu hình dịch vụ.
  - chỉ tự động tăng giới hạn chi tiêu nếu có quyền/API từ nhà cung cấp hoặc nền tảng.
- Với thẻ khách:
  - theo dõi spending.
  - tự charge phí `% spending` theo chi tiêu phát sinh.
  - khi phí trong tool hết, tự pause campaign/adset bằng Meta API.
  - cần cảnh báo trước khi pause.

**Ưu tiên:** Cao, nhưng cần chia thành phase vì liên quan tiền thật và quyền Meta API.

**Đã xử lý một phần - Backend auto tăng giới hạn đã được nối**

- Hiện mới sửa phần hiển thị `spend_cap`, `remaining_amount`, `amount_spent` cho đúng scale.
- Đã bỏ/ẩn nút `Nạp tiền` ở trang `Quản lý BM/MCC` để tránh hiểu nhầm là nạp vào BM/MCC.
- Đã thêm hành động `Nạp tiền` theo từng tài khoản quảng cáo ở trang `Quản lý tài khoản`.
- Khi khách/agency gửi yêu cầu nạp cho một tài khoản, hệ thống trừ tiền từ ví user và tạo giao dịch chờ admin xử lý theo đúng tài khoản quảng cáo đã chọn.
- Admin có thể duyệt giao dịch để đánh dấu hoàn thành hoặc hủy để hoàn tiền lại ví.
- Backend đã bắt đầu nối auto tăng giới hạn chi tiêu account khi admin duyệt giao dịch nạp:
  - lưu metadata account vào giao dịch nạp;
  - phân loại nguồn billing theo `billing_source`/`supplier_id`/`payment_type`;
  - `adviet_card` sẽ thử gọi API tăng giới hạn chi tiêu account;
  - `customer_card` không tăng limit vì loại này tính phí `% spending`;
  - `supplier_credit_line` tạm xử lý thủ công nếu chưa có API/quyền nhà cung cấp;
  - Meta dùng `spend_cap` cấp ad account;
  - Google dùng `AccountBudgetProposal` cấp account budget.
- Đã thêm field backend `billing_source` cho service package để chuẩn bị tách dịch vụ theo nguồn thanh toán:
  - `customer_card`;
  - `adviet_card`;
  - `supplier_credit_line`.
- Khi admin duyệt lệnh nạp account:
  - nếu là `adviet_card`, hệ thống gọi API tăng giới hạn chi tiêu account;
  - nếu API trả lỗi quyền/billing thì giao dịch không bị complete để admin biết cần xử lý thủ công;
  - nếu là `customer_card`, hệ thống chỉ complete theo nghiệp vụ ví, không tăng limit account;
  - nếu là `supplier_credit_line`, hệ thống để admin/nhà cung cấp xử lý thủ công nếu chưa có API riêng.

**Chưa xử lý**

- Chưa test production với token/quyền billing thật để xác nhận Meta/Google cho phép tăng giới hạn chi tiêu account trong từng trường hợp.
- Chưa có ví/phân bổ số dư riêng theo từng ad account (`wallet_balance`, `reserved_amount`, `service_fee_rate`, `payment_mode`).
- Chưa có UI đầy đủ để admin chọn nguồn thanh toán `customer_card`, `adviet_card`, `supplier_credit_line`; backend đã có field `billing_source` ở service package.

**Đang chờ khách xác nhận/cung cấp thêm**

- Đã hỏi khách xác nhận lại 3 loại dịch vụ theo nguồn thanh toán:
  - tài khoản dùng thẻ khách;
  - tài khoản dùng thẻ Adviet;
  - tài khoản dùng credit line/thẻ nhà cung cấp.
- Đã hỏi khách cung cấp mỗi loại 1 account mẫu đang chạy thật để kiểm tra API Meta/Google có trả ra dấu hiệu phân biệt nguồn thanh toán hay không.
- Nếu API phân biệt được ổn định, có thể map tự động `billing_source`.
- Nếu API không phân biệt được, cần dùng cấu hình admin ở gói dịch vụ/account để tránh hệ thống tự tăng giới hạn sai loại tài khoản.

## 6. Hiển thị lỗi tài khoản bị suspend/disabled

**Yêu cầu**

- Khách muốn tool hiển thị lỗi khi tài khoản bị suspend/disabled.
- Ảnh Business Settings cho thấy nhãn lỗi:
  - `Ad account disabled, payment method`
- Ảnh Billing Hub cho thấy:
  - `Ad account disabled`
  - `We noticed some unusual activity, so we've disabled your ad account...`
- Ảnh khác cho thấy:
  - `Your ads aren't delivering`
  - lỗi không đặt được temporary hold do payment provider hoặc insufficient funds.

**Hướng xử lý**

- Lấy thêm field từ Meta nếu có:
  - `account_status`
  - `disable_reason`
  - các thông tin payment/verification liên quan nếu API trả về.
- Map mã lỗi sang text dễ hiểu:
  - payment method issue
  - insufficient funds
  - unusual activity
  - disabled/suspended
  - need to pay.
- Hiển thị lỗi ngay dưới tên tài khoản hoặc trong tooltip cột trạng thái.
- Không nên chỉ hiển thị `Bị vô hiệu hóa`; cần thêm lý do nếu có.

**Ưu tiên:** Cao.

**Đã xử lý**

- Backend đã lấy và lưu `disable_reason` từ Meta nếu API trả về.
- Đã map `disable_reason` sang nhãn dễ hiểu bằng enum `MetaAdsDisableReason`.
- Trang quản lý tài khoản hiển thị lý do lỗi ngay dưới tên ad account.
- Badge trạng thái có tooltip lý do lỗi.
- Lưu ý: các lỗi dạng câu đầy đủ trên Billing Hub như `Your ads aren't delivering...` chỉ hiển thị nếu Meta API trả được dữ liệu tương ứng; hiện chưa scraping giao diện Facebook.

## 7. Màu trạng thái tài khoản

**Yêu cầu**

- Hoạt động: màu xanh lá.
- Vô hiệu hóa: màu đỏ.
- Nợ thanh toán: màu cam.

**Hướng xử lý**

- Chuẩn hóa component badge trạng thái dùng chung.
- Mapping đề xuất:
  - `Active / Hoạt động`: xanh lá.
  - `Disabled / Bị vô hiệu hóa / Suspended`: đỏ.
  - `Need to pay / Nợ thanh toán / Unsettled`: cam.
  - trạng thái không rõ: xám.

**Ưu tiên:** Nhanh, nên làm cùng lúc với phần lỗi tài khoản.

**Đã xử lý**

- Badge trạng thái đã đổi màu theo severity:
  - Active/Hoạt động: xanh lá.
  - Nợ thanh toán/Need to pay/Unsettled: cam.
  - Disabled/Suspended/Error: đỏ.

## 8. Phí spending và phí top up

**Yêu cầu**

- Dùng thẻ khách: tính phí spending.
- Dùng thẻ agency: tính phí top up.
- Dùng credit line/thẻ của nhà cung cấp: tính phí top up.
- Với thẻ khách, khách phải nạp vào tool ít nhất `100$` mới kích hoạt.
- Khách xác nhận ngày `30/05/2026`: nghiệp vụ phí tách theo **dịch vụ/gói dịch vụ**, không tách theo BM/MCC.
  - `customer_card`: tính phí `% spending`, tool tự charge theo chi tiêu của khách.
  - `adviet_card`: tính phí top up khi khách nạp tiền từ ví vào tài khoản.
  - `supplier_credit_line`: tính phí top up khi khách nạp tiền từ ví vào tài khoản.

**Hướng xử lý**

- Thêm cấu hình phí:
  - phí top up theo phần trăm hoặc cố định.
  - phí spending theo phần trăm chi tiêu.
  - số dư tối thiểu để kích hoạt thẻ khách.
  - nguồn thanh toán/billing của dịch vụ: thẻ khách, thẻ Adviet, credit line nhà cung cấp.
- Thêm trạng thái kích hoạt cho từng ad account:
  - active
  - waiting_deposit
  - paused_by_low_balance.
- Tính phí theo transaction rõ ràng, có lịch sử đối soát.

**Ưu tiên:** Sau CoinRemitter hoặc làm cùng module ví.

**Chưa xử lý**

- Chưa thêm module phân biệt `customer_card`, `adviet_card`, `supplier_credit_line` theo dịch vụ/gói dịch vụ.
- Chưa thêm điều kiện nạp tối thiểu `100$` để kích hoạt thẻ khách.
- Đây là nghiệp vụ ví/phí, cần làm cùng module CoinRemitter hoặc sau khi chốt thiết kế ví theo ad account.

## 10. Các điểm cần xác nhận thêm với khách

- Khi ví hết tiền, tool pause ở cấp campaign hay adset?
- Với tài khoản dùng thẻ khách, khi phí spending trong tool hết thì tool pause ở cấp campaign hay adset?
- Nếu Meta/Google API không cho tự động tăng giới hạn chi tiêu của tài khoản, khách chấp nhận cơ chế tạo task cho admin không?
- Với dịch vụ `adviet_card` và `supplier_credit_line`, cần xác nhận API/quyền thực tế của từng nguồn billing để biết có thể tự động tăng giới hạn chi tiêu hay phải xử lý thủ công.

**Chưa xử lý**

- Các điểm này vẫn cần xác nhận với khách trước khi triển khai module tiền thật.

## 13. Nhà cung cấp: phí cố định 7%, không muốn Monthly Spending giảm phí

**Yêu cầu**

- Khách nói phần nhà cung cấp hiện chỉ có phí `7%`, chạy bao nhiêu cũng `7%`.
- Khách không xoá được phần `Monthly Spending & Fee Structure`.
- Ảnh trang tạo nhà cung cấp hiển thị:
  - `Chi phí mở tài khoản (trả trước)`.
  - `Chi phí nhà cung cấp (%)`: đang nhập `7`.
  - `Monthly Spending & Fee Structure` vẫn còn dòng mức chi tiêu và `Fee %`.
- Khách thấy phần monthly spending bị cấn vì nhà cung cấp này không giảm phí theo mức chi tiêu.

**Hướng xử lý**

- Tách rõ 2 kiểu cấu hình phí nhà cung cấp:
  - `Fixed fee`: luôn tính một mức phí cố định, ví dụ `7%`.
  - `Tiered fee`: giảm/tăng phí theo monthly spending.
- Nếu chọn `Fixed fee`:
  - ẩn hoặc disable `Monthly Spending & Fee Structure`.
  - không bắt buộc nhập tier.
  - backend tính phí bằng `supplier_fee_percent`.
- Nếu chọn `Tiered fee`:
  - hiển thị bảng monthly spending như hiện tại.
  - cho phép thêm/xóa dòng rõ ràng.
- Cần thêm nút xoá dòng tier nếu vẫn dùng cấu trúc tier.

**Đã xử lý**

- Form tạo/sửa nhà cung cấp hiện có thể để trống `Monthly Spending & Fee Structure`.
- Khi để trống, hệ thống hiểu nhà cung cấp dùng phí cố định theo `Chi phí nhà cung cấp (%)`.
- Thêm nút `Không dùng biểu phí` để xoá toàn bộ tier, tránh trường hợp khách không xoá được dòng cuối.

**Ưu tiên:** Trung bình.

## 14. Cashback sau 30 ngày thay vì giảm phí theo monthly spending

**Yêu cầu**

- Khách muốn setup kiểu:
  - ban đầu charge phí theo `%`.
  - sau đó nếu chạy đúng ngân sách bao nhiêu thì được cashback sau 30 ngày.
- Ảnh service package có field `Tỉ lệ cashback (%)`, ghi chú: `Phần trăm khách hàng được hoàn lại sau 30 ngày sử dụng dịch vụ`.
- Ảnh khác có các mức monthly spending với fee giảm dần: `8`, `7.5`, `7`, `6.5`, `6`, `5.5`, `5`, `4.5`.
- Khách nói có 2 phần điều chỉnh và muốn chỉnh setup này thành cashback sau 30 ngày.

**Nhận định**

- Logic hiện tại có vẻ đang dùng tier monthly spending để điều chỉnh phí trực tiếp.
- Khách muốn đổi sang mô hình:
  - phí ban đầu vẫn thu đủ theo phần trăm cấu hình.
  - sau 30 ngày mới xét điều kiện để cashback.

**Hướng xử lý**

- Đổi ý nghĩa `Monthly Spending & Fee Structure` trong gói dịch vụ thành `Monthly Spending & Cashback Structure`.
- Mỗi tier nên có:
  - chi tiêu tối thiểu.
  - chi tiêu tối đa.
  - cashback percent.
- Không dùng tier này để giảm phí ngay lúc thanh toán.
- Tạo job/check sau 30 ngày:
  - tính tổng chi tiêu hợp lệ trong 30 ngày.
  - xác định tier cashback.
  - tạo giao dịch hoàn tiền vào ví khách.
- Cần có lịch sử cashback:
  - kỳ tính cashback.
  - tổng spending.
  - tỷ lệ cashback.
  - số tiền hoàn.
  - trạng thái pending/approved/paid.

**Ưu tiên:** Trung bình đến cao, vì ảnh hưởng tính tiền và đối soát.

**Đã xử lý phần nền**

- Service package đã đổi nhãn/ý nghĩa sang cashback theo bậc chi tiêu 30 ngày.
- Backend đã có `CashbackService`:
  - chỉ xét dịch vụ đã hoạt động tối thiểu 30 ngày;
  - tính tổng chi tiêu Meta/Google trong kỳ;
  - tìm tier cashback theo `monthly_spending_fee_structure`;
  - cộng cashback vào ví user bằng wallet transaction loại `CASHBACK`.
- Đã có command `app:calculate-cashback` và lịch chạy hằng ngày lúc `03:00`.
- Vẫn cần cân nhắc bổ sung bảng đối soát cashback riêng nếu khách cần quy trình `pending/approved/paid`; hiện lịch sử đang nằm trong wallet transaction với `reference_id` là service user.

## 16. Kiểm tra sau khi sửa

- `php -l app/Http/Resources/MetaAdsAccountResource.php`: pass.
- `php -l app/Service/BusinessManagerService.php`: pass.
- `php -l routes/console.php`: pass.
- `php -l app/Service/CoinRemitterService.php`: pass.
- `php -l app/Http/Controllers/CoinRemitterWebhookController.php`: pass.
- `php -l app/Http/Controllers/WalletController.php`: pass.
- `php -l app/Service/WalletTransactionService.php`: pass.
- `php artisan route:list --path=webhooks/coinremitter`: pass, có route `POST webhooks/coinremitter`.
- `php artisan test tests/Unit/CoinRemitterServiceTest.php`: pass.
- `php artisan test tests/Unit/CoinRemitterWebhookControllerTest.php`: pass.
- `npm run build`: pass.
- `php artisan test tests/Unit/ExampleTest.php`: pass.
- `php artisan test tests/Feature/DashboardTest.php`: fail do migration SQLite cũ `2025_11_10_105832_change_table_service_packages.php` drop column `platform_setting_id`, không phải lỗi từ thay đổi lần này.
- `service-management` đã có `last_synced_at` trong dữ liệu account và UI hiển thị thời điểm sync mới nhất.
- Kiểm tra service bằng local DB:
  - `act_691510320667452`: USD đã normalize đúng `400000 -> 4000.0`, `113187 -> 1131.87`, `2121 -> 21.21`.
  - `act_978273446145835`: VND giữ nguyên scale.

**Đã xử lý**

- Đã ghi lại kết quả kiểm tra sau sửa.

## 17. Kho tài khoản bán tự động và tự liên kết cho khách

**Yêu cầu mới ngày 27/05/2026**

- Khách muốn ở phần dịch vụ có thể nạp sẵn một số lượng tài khoản để bán như sản phẩm tồn kho.
- Ví dụ:
  - Admin tạo dịch vụ/sản phẩm A.
  - Admin nhập sẵn `10` tài khoản thuộc sản phẩm A.
  - Khách vào tự mua sản phẩm A.
  - Khách điền email hoặc MCC của khách.
  - Hệ thống tự liên kết/giao tài khoản đó cho khách, không cần admin duyệt tay từng đơn.
- Khách gọi đây là luồng bán hàng tự động.
- Khách gửi video tham khảo về việc khách đặt mua tài khoản và liên kết tài khoản vào MCC của khách.
- Khách nói phần nạp tiền tự động có thể để thủ công vì bên Adviet là đơn vị trung gian, không phải nhà cung cấp Google invoice gốc.

**Nhận định**

- Đây là module tồn kho tài khoản quảng cáo và auto fulfillment, khác với luồng `service_user` hiện tại.
- Hiện luồng đang là:
  - khách mua gói;
  - hệ thống tạo đơn `PENDING`;
  - admin vào duyệt/gắn tài khoản;
  - service mới chuyển `ACTIVE`.
- Khách muốn đổi/bo sung một luồng tự động:
  - admin chuẩn bị sẵn account inventory;
  - khi khách thanh toán thành công, hệ thống tự lấy một account còn trống;
  - tự gửi invite/liên kết account theo email hoặc MCC khách nhập;
  - tự gắn account đó vào service/order của khách;
  - đơn chuyển active nếu bước liên kết thành công.

**Phạm vi nên tách**

- Làm được trước:
  - kho tài khoản theo từng service package/product.
  - trạng thái tài khoản tồn kho: `available`, `reserved`, `assigned`, `failed`.
  - tự chọn một account còn trống khi khách mua.
  - lưu email/MCC khách nhập.
  - tạo đơn và gắn account tự động nếu account đã có sẵn trong hệ thống.
- Cần kiểm tra API/quyền trước khi cam kết:
  - Meta/Google có cho API tự invite/link account vào email hoặc MCC khách theo đúng quyền hiện có không.
  - Với Google Ads, liên kết vào MCC thường cần gửi link request và phía khách có thể phải accept.
  - Với Meta, share asset/ad account vào business/email phụ thuộc Business Manager, asset group và quyền token.
- Không nên làm ngay trong cùng module:
  - nạp tiền tự động vào account sau khi bán.
  - tự chỉnh invoice/creditline/spend limit nếu không chắc quyền API.

**Hướng xử lý đề xuất**

1. Thêm bảng kho tài khoản quảng cáo:
   - `service_package_id`.
   - `platform`.
   - `account_id`.
   - `account_name`.
   - `business_manager_id` hoặc `customer_manager_id`.
   - `status`.
   - `assigned_user_id`.
   - `assigned_service_user_id`.
   - `reserved_until`.
   - metadata invite/link.
2. Admin có màn hình nhập/import account tồn kho cho từng gói dịch vụ.
3. Form mua dịch vụ cho khách thêm input email/MCC nhận tài khoản.
4. Khi thanh toán thành công:
   - lock một account `available`.
   - chuyển sang `reserved`.
   - gọi job auto link/invite nếu API hỗ trợ.
   - nếu thành công: chuyển `assigned`, active service.
   - nếu thất bại: giữ đơn chờ admin xử lý và log lỗi.
5. Có cơ chế timeout/release account nếu khách không thanh toán hoặc job thất bại.

**Ưu tiên:** Cao nếu khách muốn bán hàng tự động, nhưng cần làm thành module riêng vì liên quan cấp quyền tài khoản thật.

**Đã xử lý bước nền**

- Đã thêm bảng kho tài khoản theo service package: `service_account_inventories`.
- Đã thêm trạng thái tồn kho: `available`, `reserved`, `assigned`, `failed`.
- Đã thêm panel import kho trong màn sửa gói dịch vụ.
  - Mỗi dòng import: `account_id, account_name, BM/MCC, note`.
  - Account được gắn với platform của gói dịch vụ.
- Đã thêm API quản lý kho theo gói:
  - `GET /service-packages/{id}/account-inventory`.
  - `POST /service-packages/{id}/account-inventory/import`.
  - `DELETE /service-packages/{id}/account-inventory/{inventoryId}`.
- Đã nối luồng mua dịch vụ:
  - Sau khi khách mua, hệ thống tìm account `available` trong kho của gói.
  - Nếu đủ số lượng, account được chuyển `assigned`, gắn `assigned_user_id`, `assigned_service_user_id`.
  - Nếu account đã được sync sẵn trong `meta_accounts` hoặc `google_accounts`, hệ thống tự set `service_user_id` để khách thấy trong quản lý tài khoản.
  - Đơn dịch vụ tự chuyển `ACTIVE` khi auto-assign đủ tài khoản.
  - Nếu kho không đủ, đơn vẫn `PENDING` và lưu `auto_fulfillment.status = pending_manual` để admin xử lý tay.
- Khi admin hủy đơn, account đã giữ/giao từ kho được release về `available`.

**Chưa xử lý / cần kiểm chứng**

- Chưa gọi API invite/link thật sang Meta/Google vì cần xác minh quyền token production.
- Với account chưa sync trong hệ thống, hiện module lưu metadata mục tiêu để admin/API job xử lý sau.
- Chưa tự động nạp tiền vào account sau khi bán; phần này tách sang luồng nạp tiền từng ad account.

## 18. Luồng nạp tiền từ số dư tool vào tài khoản quảng cáo

**Yêu cầu mới ngày 27/05/2026**

- Khách gửi video tham khảo luồng nạp tiền tự động vào tài khoản quảng cáo.
- Theo video/screenshot:
  - Khách vào danh sách tài khoản đã mua/đã gán.
  - Chọn một hoặc nhiều tài khoản.
  - Chọn hành động `Yêu cầu nạp tài khoản`.
  - Nhập số tiền muốn nạp cho từng tài khoản.
  - Hệ thống trừ từ số dư trong tool của khách.
  - Hệ thống tạo yêu cầu nạp tiền và hiển thị lịch sử yêu cầu.
  - Sau khi xử lý thành công, tài khoản Google Ads hiển thị ngân sách tài khoản đã được nạp.
- Khách nhận định nạp tiền tự động bên Adviet có thể khó hơn hệ thống tham khảo vì Adviet là đơn vị trung gian, không sở hữu Google invoice gốc như nhà cung cấp trong video.
- Khách chấp nhận để team Adviet nạp tiền thủ công nếu tự động hóa nạp tiền chưa khả thi.

**Khách bổ sung/đính chính ngày 28/05/2026**

- Đây là note bổ sung/làm rõ cho mục này, không phải yêu cầu hoàn toàn mới.
- Khách nhấn mạnh `nạp tiền` là nạp vào **từng tài khoản quảng cáo**, không phải nạp vào BM/MCC.
- Khu vực khách hàng nếu có nhiều tài khoản thì mỗi tài khoản cần có hành động/khu vực nạp tiền riêng.
- Khi khách chọn nạp vào tài khoản nào, hệ thống lấy tiền từ ví khách để tạo yêu cầu nạp cho đúng tài khoản đó.
- Cần bỏ/ẩn nút `Nạp tiền` ở trang `Quản lý BM/MCC` vì vị trí đó làm hiểu nhầm là nạp tiền vào BM/MCC.
- Vị trí đúng nên là danh sách tài khoản khách đã mua/được gán, ví dụ trang `Quản lý tài khoản` / `service-management`.

**Khách bổ sung ngày 29-30/05/2026**

- Khu vực khách hàng không cần có trang `Quản lý BM/MCC`.
- Khách không mua BM/MCC; khách có BM/email/MCC sẵn để kết nối.
- Khu vực khách hàng chỉ cần quản lý tài khoản quảng cáo đã được gán, chia theo nền tảng Meta và Google.
- Ví dụ: một khách có thể dùng cả 2 nền tảng, Google có 5 tài khoản và Meta có 2 tài khoản.
- Khách xác nhận không tách sản phẩm theo BM/MCC, mà tách theo dịch vụ:
  - tài khoản dùng thẻ khách;
  - tài khoản dùng thẻ Adviet;
  - tài khoản dùng credit line nhà cung cấp.
- Yêu cầu tự động tăng khi nạp tiền là tăng **giới hạn chi tiêu của tài khoản**, không phải tăng ngân sách campaign.

**Nhận định**

- Đây là luồng khác với CoinRemitter:
  - CoinRemitter: khách nạp USDT vào ví user trong tool.
  - Luồng này: khách dùng số dư ví trong tool để yêu cầu nạp vào từng ad account.
- Luồng này cũng khác với auto assign account:
  - Auto assign account: bán/giao account cho khách.
  - Auto top-up account: tăng ngân sách/số tiền khả dụng cho account đã được giao.
- Có thể triển khai theo 2 phase:
  - Phase 1: tạo yêu cầu nạp tiền vào ad account và admin xử lý thủ công.
  - Phase 2: nếu có API/quyền phù hợp, tự động tạo budget/payment/invoice/top-up trên Google/Meta.

**Luồng phase 1 đề xuất**

1. Khách vào danh sách tài khoản đã mua/đã gán.
2. Khách chọn một hoặc nhiều tài khoản.
3. Khách nhập số tiền muốn nạp cho từng tài khoản.
4. Backend kiểm tra số dư ví user.
5. Backend trừ/hold số tiền từ ví:
   - có thể trừ ngay;
   - hoặc tạo `reserved_amount`/pending transaction để tránh khách dùng trùng tiền.
6. Tạo yêu cầu nạp tiền trạng thái `pending`.
7. Admin/team Adviet nhìn danh sách yêu cầu và nạp tiền thủ công vào account ngoài nền tảng.
8. Admin bấm xác nhận thành công:
   - yêu cầu chuyển `completed`;
   - ghi log giao dịch;
   - cập nhật trạng thái/tổng tiền đã nạp cho account nếu cần.
9. Nếu admin từ chối/thất bại:
   - hoàn tiền/giải phóng số tiền đã giữ;
   - yêu cầu chuyển `failed` hoặc `rejected`.

**Luồng phase 2 nếu API cho phép**

- Với Google:
  - Cần xác minh Google Ads API có cho tài khoản/manager hiện tại tạo hoặc cập nhật `Account budget`/billing setup/payment không.
  - Nhiều thao tác billing của Google bị giới hạn rất chặt và phụ thuộc invoice/credit line của chủ tài khoản.
  - Nếu Adviet không là chủ invoice gốc, khả năng tự động nạp giống video có thể không khả thi.
- Với Meta:
  - Cần xác minh quyền chỉnh `spend_cap`, payment/funding hoặc các API billing liên quan.
  - Nếu không đủ quyền, chỉ nên tạo yêu cầu cho admin xử lý thủ công.

**Dữ liệu cần thiết kế**

- Bảng yêu cầu nạp tiền account:
  - `user_id`.
  - `service_user_id`.
  - `platform`.
  - `account_id`.
  - `account_name`.
  - `amount`.
  - `fee_amount` nếu có.
  - `net_amount` hoặc `amount_to_account`.
  - `status`: `pending`, `processing`, `completed`, `failed`, `rejected`, `refunded`.
  - `wallet_transaction_id`.
  - `admin_note`.
  - `completed_at`.
- Cần liên kết với ví hiện tại hoặc ví theo từng ad account nếu module ví account được mở rộng.

**Ưu tiên:** Cao nếu khách muốn tự phục vụ sau khi mua account, nhưng nên làm phase 1 thủ công trước để giảm rủi ro tiền thật/API billing.

**Đã xử lý một phần**

- Đã bỏ/ẩn nút nạp tiền ở danh sách BM/MCC để tránh hiểu nhầm là nạp vào BM/MCC.
- Đã ẩn menu/trang `Quản lý BM/MCC` khỏi khu vực khách hàng/agency; trang này chỉ dành cho nội bộ admin/manager/employee.
- Đã thêm nút/khu vực `Nạp tiền` riêng trên từng dòng tài khoản quảng cáo ở trang `Quản lý tài khoản`.
- Khi khách/agency tạo yêu cầu nạp tiền account, hệ thống kiểm tra và trừ tiền ví user ngay, tạo wallet transaction trạng thái chờ xử lý theo đúng tài khoản quảng cáo.
- Admin có thể duyệt để hoàn tất giao dịch hoặc hủy để hoàn tiền lại ví.

**Chưa xử lý**

- Chưa có bảng/lịch sử yêu cầu nạp tiền account riêng tách khỏi wallet transaction.
- Chưa có màn hình admin chuyên biệt để xử lý/duyệt yêu cầu nạp account theo nghiệp vụ riêng; hiện dùng luồng duyệt giao dịch ví.
- Chưa tự động nạp/tăng ngân sách account bằng Google Ads API/Meta API; cần kiểm chứng quyền billing/spend cap production.

## 19. Bổ sung Paymento làm cổng nạp crypto

**Yêu cầu mới ngày 28/05/2026**

- Khách muốn cấu hình sẵn 3 kiểu nạp crypto:
  1. Ví thủ công.
  2. CoinRemitter.
  3. Paymento: `https://app.paymento.io/`.

**Docs Paymento đã đối chiếu**

- Paymento tạo payment bằng `POST https://api.paymento.io/v1/payment/request`.
- Header dùng `Api-key`.
- API trả về `token`; khách được redirect sang `https://app.paymento.io/gateway?token=...`.
- Paymento gửi callback/IPN về URL đã cấu hình, kèm `Token`, `PaymentId`, `OrderId`, `OrderStatus`.
- Webhook cần kiểm HMAC SHA256 bằng secret key trong dashboard Paymento.
- Sau callback vẫn phải gọi `POST https://api.paymento.io/v1/payment/verify` để xác minh payment trước khi cộng ví.

**Hướng xử lý**

- Thêm cấu hình Paymento ở `/config`:
  - `PAYMENTO_API_KEY`.
  - `PAYMENTO_SECRET_KEY`.
  - hiển thị IPN URL: `/webhooks/paymento`.
- Khi admin chọn Paymento làm phương thức nạp:
  - khách tạo lệnh nạp từ ví user;
  - hệ thống tạo Paymento payment request;
  - lưu token vào `payment_id`;
  - hiển thị nút mở gateway Paymento cho khách thanh toán.
- Webhook `/webhooks/paymento`:
  - kiểm chữ ký HMAC;
  - tìm transaction pending theo token;
  - gọi Paymento verify API;
  - `Paid/Approve` thì duyệt lệnh nạp và cộng ví;
  - `Timeout/UserCanceled/Reject` thì reject;
  - `PartialPaid` thì giữ pending/underpaid.

**Lưu ý vận hành**

- Paymento không cấu hình theo từng network TRC20/BEP20 như CoinRemitter trong docs hiện tại; khách chọn coin/network trên gateway Paymento.
- Cần cấu hình IPN URL trong Paymento dashboard hoặc payment settings API:
  - `https://advietagency.biz/webhooks/paymento`.

**Đã xử lý**

- Đã thêm `PaymentoService`:
  - tạo payment bằng `payment/request`;
  - lấy token từ response;
  - dựng gateway URL;
  - gọi `payment/verify`;
  - map trạng thái `Paid/Approve`, `Timeout/UserCanceled/Reject`, `PartialPaid`.
- Đã nối Paymento vào luồng nạp ví:
  - khi phương thức nạp là `paymento`, hệ thống tạo payment request;
  - lưu token vào `payment_id`;
  - lưu gateway URL vào `reference_id`;
  - card pending deposit có thể mở gateway để khách thanh toán.
- Đã thêm webhook `POST /webhooks/paymento`:
  - kiểm HMAC SHA256 bằng secret key;
  - tìm transaction pending theo token;
  - gọi verify lại Paymento trước khi cộng ví;
  - paid/approve thì duyệt nạp;
  - timeout/cancel/reject thì reject;
  - partial paid thì giữ pending/underpaid.
- Còn cần cấu hình IPN URL trên dashboard Paymento và test giao dịch thực tế/testnet theo store Paymento.
