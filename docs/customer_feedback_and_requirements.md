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
