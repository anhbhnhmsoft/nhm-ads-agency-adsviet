# 📋 NHẬT KÝ LƯU TRỮ YÊU CẦU & TIN NHẮN TỪ KHÁCH HÀNG (CUSTOMER FEEDBACK & REQUIREMENTS LOG)

> **Mục đích**: Tài liệu này lưu trữ toàn bộ các yêu cầu, phản hồi thực tế và tin nhắn của khách hàng/đối tác. Dù AI có bị nén ngữ cảnh (compaction) thì file này luôn được lưu vĩnh viễn trong mã nguồn để đối chiếu và tuân thủ tuyệt đối mọi logic nghiệp vụ.

---

## 📌 [07/10/2026] YÊU CẦU QUAN TRỌNG: KHÔNG ĐƯỢC TỰ ĐỘNG PAUSE CAMPAIGN KHI VÍ CÒN TIỀN

### 1. Nội dung tin nhắn của khách:
> **Tin nhắn 1:**
> *"⚠️ WALLET BALANCE LOW (MAINTENANCE REQUIRED) ⚠️*  
> *Customer: charlie*  
> *Order: 95743500083005161*  
> *Package: charlie -Facebook Source 10: Your Card ( Account from BM Platinum 2500) (Meta Ads)*  
> *Wallet Balance: 99.33 USDT*  
> *Minimum Required: 100.00 USDT*  
> *Status: No active campaigns to pause.*  
> *Time: 07/10/2026 10:22:56*  
> *tool nó báo no active camps to pause*  
> *vay neu khach có camp active la tool nó pause do e*  
> *e tat cai nay di*  
> *a van cho khach chay hết balance mà*  
> *e bỏ cái minimum required di nha e*  
> *tool nó tự pause khi khach con tien ne e."*

> **Tin nhắn 2 (Hình ảnh bằng chứng tài khoản Jinsk bị dừng toàn bộ camp):**
> *`Customer: Jinsk`*  
> *`Order: 95614386006131723`*  
> *`Wallet Balance: 97.65 USDT`*  
> *`Minimum Required: 100.00 USDT`*  
> *`Status: All campaigns (6/6) have been paused successfully due to insufficient wallet balance.`*  
> *`Time: 07/10/2026 10:22:23`*

### 2. Ý muốn của khách và quy tắc nghiệp vụ bắt buộc:
1. **Khách được chạy hết số dư trong ví**: Khách nạp tiền vào ví (ví dụ 100 USDT), khách có toàn quyền chạy quảng cáo cho đến khi dùng hết số dư ví (đến khi balance $\le$ 0 hoặc không đủ trừ khoản phí phát sinh).
2. **Tuyệt đối không tự động pause campaign khi ví còn tiền**:
   - Khi ví còn 99.33 USDT hay 97.65 USDT, tool KHÔNG ĐƯỢC tự động pause chiến dịch của khách.
   - Việc so sánh với ngưỡng `Minimum Required (100 USDT)` rồi cưỡng chế pause là sai nghiệp vụ của khách.
3. **Bỏ kiểm tra `Minimum Required` gây pause chiến dịch**:
   - Chỉ được pause chiến dịch khi: **Khách phát sinh phí dịch vụ mà số dư ví không đủ để trừ khoản phí đó** (`currentBalance < chargeAmount`), hoặc khi số dư ví $\le$ 0.

---

## 📌 [07/10/2026] YÊU CẦU: SỬA LỖI HIỂN THỊ SPEND TRÊN MODAL (22.19$ vs 2219.00$) & TĂNG KÍCH THƯỚC MODAL

### 1. Tin nhắn của khách:
> *"ah account 6519 day e ma sao nó tiêu den 2219$ mà ko thấy tool charge phí trong balance vay e"*  
> *"ma sao a nhìn o trên tổng chi tiêu chi có 22.19$ mà ở dưới có tài khoản như tài khoản hiển thị ở đầu thì là 2219.00$"*

### 2. Nguyên nhân & Giải pháp:
- **Nguyên nhân**: Meta API trả về `amount_spent` dạng Cent (`2219` cent = `22.19 USD`). Backend tính tổng chi tiêu đã chia 100 thành `22.19 USD`, nhưng resource chi tiết tài khoản lấy số thô `2219` và gắn `.00 USD` vào thành `2219.00 USD`, khiến khách tưởng tiêu hơn 2.200 đô.
- **Giải pháp**:
  - `ServiceOrderResource.php`: Thêm hàm chuẩn hóa tiền tệ `normalizeMetaAccountMoney`, chia 100 cho đơn vị USD/EUR.
  - `OrderDetailsDialog.tsx`: Mở rộng kích thước modal (`w-[95vw] lg:max-w-6xl`), chia 5 cột thông tin dàn đều, hiển thị chuẩn `22.19 USD`.

---

## 📌 [07/10/2026] YÊU CẦU: VỀ VIỆC GỠ TÀI KHOẢN KHÔNG CÒN TRONG BM & GÁN ĐƠN HÀNG

### 1. Tin nhắn của khách:
> *"cái ac tên charlie 18 1004-03 nó đã ko con nam trong bm cua a giở a gỡ nó ra ntn e"*  
> *"khi khach hang ho order a assign 5 ac sau do cung cái đơn đó a assgin thêm 7 ac vay tong cong là a assgin cho khach 12 ac hay là cái đơn đó nó nhận 7ac mới nó bỏ 5 ac cũ vậy e"*

### 2. Nghiệp vụ:
- Khi gỡ tài khoản: Tách `service_user_id = null` khỏi bảng `meta_accounts`, đơn hàng không quét chi tiêu tài khoản này nữa.
- Khi gán đơn: Mỗi đơn hàng khách order bao nhiêu tài khoản thì assign đúng số lượng đó. Muốn cấp thêm đợt mới thì tạo đơn mới để quản lý tách bạch.

---

## 📌 [07/10/2026] SỰ CỐ BOT TELEGRAM SPAM TIN NHẮN PHÍ 5 PHÚT/LẦN
- **Nguyên nhân**: Thiếu hàm `notifySupportGroupPostpayLowBalance` trong `WalletTransactionService` dẫn đến Database Rollback sau khi gửi Telegram thành công.
- **Đã xử lý**: Bổ sung method, lưu DB thành công 100% trước khi gửi Telegram, bọc `try-catch` an toàn.

---

## 📌 [07/10/2026] TIẾP TỤC: XÁC NHẬN MỞ LẠI TOÀN BỘ CÁC TÀI KHOẢN / CAMPAIGN ĐANG BỊ PAUSE
### 1. Nội dung trao đổi:
> [07/10/2026 10:27:45] Kevinho: tool nó tự pause khi khach con tien ne e  
> [07/10/2026 10:42:32] Mai Dương: e bỏ rồi nha a  
> [07/10/2026 10:42:48] Kevinho: ok thank you e  
> [07/10/2026 10:43:17] Mai Dương: cần mở lại các tài khoản bị đóng mà tài khoản đang còn tầm 90 97$ như tk này ko hả a  
> [07/10/2026 10:44:33] Kevinho: mở lai het nha e  

### 2. Hành động thực hiện:
- Quét toàn bộ tài khoản quảng cáo của các khách hàng có số dư ví còn tiền (balance > 0).
- Lọc ra các chiến dịch (campaigns) đang ở trạng thái PAUSED do tool tự động ngắt sai trước đó.
- Kích hoạt lại (ACTIVE) toàn bộ chiến dịch trên Meta Graph API và cập nhật trạng thái trong cơ sở dữ liệu.

---

## 📌 [07/10/2026 - 08/10/2026] CÁC YÊU CẦU & VẤN ĐỀ MỚI TỪ KEVINHO

### 1. Nội dung tin nhắn:
> [07/10/2026 12:06:40] Kevinho: e để balance còn 20$ thì pause camp cung dc em  
> [07/10/2026 12:08:44] Kevinho: e làm cho a 1 cái thông báo màu đỏ chử trắng trong cái dashboard của khách de thong báo nap balance de tiep tuc camp chạy nhé  
> [07/10/2026 15:56:35] Kevinho: de mac dinh 20$ di e, cài từng tk mất công lam  
> [07/10/2026 22:10:00] Kevinho: ua ma sao a check spending khach chay ca 1000$ ma ko thay tool ben minh charge phi vay e  
> [07/10/2026 23:11:16] Kevinho: Khách hang tên Bula :  
> - Bm phonix nhận đủ 20 ac nhưng tool chỉ nhận 16 ac  
> Thiếu 4 ac này: 1062954236580435, 1097602449672942, 1817203749471338, 2318653165539734  
> [07/10/2026 23:12:36] Kevinho: Khách hang tên  NGoc VT :  
> - Bm phonix nhận Ac (28492884047018135 )ngocvt18-3 nhưng tool lại ko thấy:  
> [07/10/2026 23:30:20] Kevinho: Khách hang : Chalie: Tool 23 ac  Bm : 29 ac  
> - 7 ac BM phonix có nhưng tool chưa có: 2369175773908330, 1777348093516566, 1090287016690704, 4604616469815923, 1839484680800581, 1610378393892150, 2974884006198747  
> [08/10/2026 00:25:31] Kevinho: hiên nay dang bi van de trong BM có tk nhưng trong tool ko hiện đủ ac nha e  

### 2. Các hạng mục cần giải quyết:
1. **Ngưỡng ví tự động pause camp**: Đặt cố định mặc định **20$** (balance < 20 USDT thì tự động pause campaigns).
2. **Dashboard thông báo số dư thấp**: Hiển thị banner màu đỏ chữ trắng trên dashboard của khách khi số dư ví < 20 USDT, nhắc khách nạp thêm tiền để tiếp tục chạy chiến dịch.
3. **Kiểm tra tại sao khách chạy cả 1000$ mà không bị trừ phí (charge phí)**: Rà soát logic `services:bill-postpay` và cron job trên VPS.
4. **Sự cố thiếu tài khoản quảng cáo giữa BM Phoenix và Tool**:
   - Khách Bula: Thiếu 4 account (`1062954236580435`, `1097602449672942`, `1817203749471338`, `2318653165539734`).
   - Khách NGoc VT: Thiếu 1 account (`28492884047018135` ngocvt18-3).
   - Khách Charlie: Thiếu 7 account (`2369175773908330`, `1777348093516566`, `1090287016690704`, `4604616469815923`, `1839484680800581`, `1610378393892150`, `2974884006198747`).
   - Điều tra cơ chế sync tài khoản từ BM vào tool: tại sao tài khoản đã có trong BM Phoenix mà tool không nhận diện được?
   - Khách Alita: Thiếu 5 account (`1733717025421383`, `2169484307287159`, `2154378888448470`, `1134714542215089`, `4612088575695153`).

### 3. Kết quả xử lý & Trạng thái:
- **Đã fix nguyên nhân gốc**: Lệnh `CleanupRemovedMetaAccounts` cũ thiếu Pagination và fetch rỗng BM đã tự ý soft delete và unassign tài khoản. Đã thêm Pagination và chặn tuyệt đối `discardMetaAccount` không xóa các tài khoản đang có `service_user_id`.
- **Đã khôi phục 100% tài khoản trên VPS**:
  + Bula: 20/20 tài khoản đủ.
  + Ngoc VT: Đã gán `28492884047018135` (`ngocvt18-3`).
  + Charlie: 29/29 tài khoản đủ.
  + Alita: 20/20 tài khoản đủ (đã khôi phục 4 tài khoản cũ và tạo mới `2154378888448470`).
- **Đã cập nhật ngưỡng an toàn 20$** trong DB và Code.
- **Đã thêm Banner đỏ chữ trắng** trên Dashboard khách hàng khi balance < 20$.
- **Đã deploy lên VPS 103.180.134.192** thành công 100%.


