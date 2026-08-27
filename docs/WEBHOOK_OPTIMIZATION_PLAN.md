# Meta Webhook Optimization Plan

## Mục tiêu
Giảm 70% API calls bằng cách thay thế polling bằng webhook và bỏ insights polling không cần thiết.

## Tổng quan hiện tại

### API Calls hiện tại (~5,000+ calls/ngày)
| Job | Tần suất | API Calls/SU | Tổng/ngày (20 SU) |
|-----|----------|-------------|-------------------|
| syncMetaAccounts | 5 phút | 2 calls | ~5,760 |
| syncMetaAdsAndCampaigns (insights) | 5 phút | 60 calls | ~172,800 |
| syncMetaAdsAndCampaigns (campaigns) | 5 phút | 60 calls | ~172,800 |
| check-and-auto-pause | 5 phút | ~5 calls | ~1,440 |
| SyncAllPlatformsJob | 1 tiếng | Dispatch sub-jobs | ~240 |

**Lưu ý**: SyncMetaJob chỉ chạy cho SU có service, không phải tất cả 20 SU cùng lúc.
Ước tính thực tế: ~5,000-10,000 calls/ngày.

### Mục tiêu sau khi optimize
Giảm xuống ~1,500 calls/ngày (giảm ~70%).

---

## Phase 1: Webhook cho Account Status (Ưu tiên CAO)

### Mục tiêu
Thay thế `accounts:check-and-auto-pause` bằng Meta webhook cho event `ad_account.disabled`.

### Hiện tại
```
Cron 5 phút → check account status qua Meta API → pause nếu disabled
```
- ~5 API calls mỗi lần chạy
- Delay: tối đa 5 phút trước khi phát hiện account die

### Sau khi implement
```
Meta webhook push → account disabled → pause campaigns ngay
```
- 0 API calls cho check status
- Delay: realtime (webhook push tức thì)

### Meta Webhook Events hỗ trợ
- `ad_account.disabled` → account bị vô hiệu hóa
- `ad_account.credit_spend_reached` → reached credit limit
- `campaign.status_changed` → campaign bị pause/unpause

### Implementation steps
1. [ ] Tạo webhook endpoint: `POST /webhooks/meta`
2. [ ] Verify webhook (Meta challenge + signature)
3. [ ] Handle event `ad_account.disabled` → pause campaigns
4. [ ] Handle event `campaign.status_changed` → update DB
5. [ ] Subscribe webhook trong Meta App Dashboard
6. [ ] Giữ cron `check-and-auto-pause` làm fallback (giảm tần suất xuống 30 phút)

### Files thay đổi
- `routes/web.php` — thêm route webhook
- `app/Http/Controllers/MetaWebhookController.php` — controller mới
- `app/Console/Kernel.php` hoặc `routes/console.php` — giảm tần suất check-and-auto-pause

### Ước tính tiết kiệm
- ~1,440 API calls/ngày
- Giảm delay từ 5 phút → realtime

---

## Phase 2: Giảm Insights Polling (Ưu tiên CAO)

### Mục tiêu
Bỏ `getAccountDailyInsights` polling, dùng `amount_spent` (đã sync trong syncMetaAccounts) cho billing.

### Hiện tại
```
syncMetaAdsAndCampaigns() → per account:
  ├── getAccountDailyInsights()  → 1 call × 60 accounts = 60 calls
  └── getCampaignsPaginated()    → 1 call × 60 accounts = 60 calls
```
- ~120 API calls/service user mỗi 5 phút

### Sau khi implement
```
syncMetaAccounts() → sync amount_spent (đã có, 2 calls/SU)
billing: đọc amount_spent từ DB (0 calls)
```
- Chỉ 2 API calls/service user
- Insights vẫn sync khi admin bấm "Cập nhật dữ liệu Meta" thủ công

### Implementation steps
1. [x] SyncMetaJob: bỏ `syncMetaAdsAndCampaigns()` — chỉ giữ `syncMetaAccounts()`
2. [x] Manual sync giữ nguyên qua `SyncMetaPlatformJob` → `syncFromBusinessManagerId`
3. [x] Billing (`services:bill-postpay`) dùng `amount_spent` (đã fix Phase trước)

### Files thay đổi
- `app/Jobs/MetaApi/SyncMetaJob.php` — bỏ syncMetaAdsAndCampaigns, giữ syncMetaAccounts

### Ước tính tiết kiệm
- ~720 API calls/ngày (cho 20 SU × 60 accounts)

---

## Phase 3: Webhook cho Campaign Status (Ưu tiên TRUNG BÌNH)

### Mục tiêu
Thay thế polling campaign status bằng webhook.

### Hiện tại
```
syncMetaAdsAndCampaigns() → getCampaignsPaginated() per account
```
- ~60 API calls/service user mỗi 5 phút

### Sau khi implement
```
Meta webhook push → campaign.status_changed → update DB
```
- 0 API calls cho campaign status polling
- Campaign sync thủ công khi admin cần

### Meta Webhook Events hỗ trợ
- `campaign.status_changed` → pause/activate/delete
- `adset.status_changed` → ad set changes
- `ad.status_changed` → ad changes

### Implementation steps
1. [x] Extend MetaWebhookController — handle `campaign` object events
2. [x] Update campaign status + effective_status in DB khi nhận webhook
3. [x] Alert admin khi campaign bị PAUSED/DELETED từ Meta
4. [x] SyncAllPlatformsJob: giảm từ 1 tiếng → 2 tiếng

### Files thay đổi
- `app/Http/Controllers/MetaWebhookController.php` — thêm `handleCampaignChange()`
- `routes/console.php` — giảm SyncAllPlatformsJob

### Ước tính tiết kiệm
- ~720 API calls/ngày (từ việc giảm SyncAllPlatformsJob)

---

## Tổng kết tiết kiệm

| Phase | Tiết kiệm calls/ngày | Difficulty | Priority |
|-------|----------------------|------------|----------|
| Phase 1: Webhook account status | ~1,440 | Dễ | 🔴 CAO |
| Phase 2: Bỏ insights polling | ~720 | Dễ | 🔴 CAO |
| Phase 3: Webhook campaign status | ~720 | Trung bình | 🟡 TRUNG BÌNH |
| **Tổng** | **~2,880** | | |

**Sau khi hoàn thành:**
- API calls: ~5,000+ → ~2,100/ngày (**giảm 58%**)
- Rate limit risk giảm đáng kể
- Account die → pause realtime (không chờ 5 phút)
- Billing vẫn chính xác vì dùng amount_spent

## Meta Webhook Setup Requirements
1. Meta App (Business) với quyền `ads_management`
2. Callback URL: `https://advietagency.biz/webhooks/meta`
3. Verify token: configured in Meta App Dashboard
4. Subscribe events: `ad_account`, `campaign`, `ad`
5. HTTPS required (đã có)

## Risks & Mitigations
| Risk | Mitigation |
|------|-----------|
| Webhook missed (Meta có retry) | Giữ cron fallback 30 phút |
| Webhook payload sai format | Validate + logging |
| Rate limit webhook delivery | Meta tự manage, không cần worry |
| Server down → webhook lost | Meta retry 3 lần trong 1 giờ |
