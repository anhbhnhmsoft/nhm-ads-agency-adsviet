---
name: zero-downtime-deploy
description: Hướng dẫn triển khai không gián đoạn (Zero-Downtime Deployment & Atomic Swap) cho các dự án web Laravel, React/Vue/Inertia với Vite, Node.js trên VPS hoặc qua CI/CD (GitLab CI, GitHub Actions). Kích hoạt khi cần viết hoặc sửa deploy.sh, git pull, build frontend trên production, tối ưu tốc độ deploy, hoặc xử lý lỗi HTTP 500 do ViteManifestNotFoundException, mất assets, gián đoạn dịch vụ khi build web.
---

# 🚀 Zero-Downtime Deployment & Atomic Build Skill

## 🎯 Goal
Đảm bảo mọi thao tác deploy, cập nhật mã nguồn (`git pull`), biên dịch frontend (`vite build`), và tái tạo cache (`optimize:clear`) trên server VPS diễn ra **100% không gián đoạn (Zero-Downtime)**, triệt tiêu hoàn toàn lỗi **HTTP 500 `ViteManifestNotFoundException`** và tình trạng nghẽn server.

---

## 💥 Bối Cảnh & Nguyên Nhân Gốc Của Lỗi 500 Khi Deploy

### 1. Vite Manifest bị xóa sạch trong suốt 60s – 90s build
- Khi chạy `npm run build` mặc định, Vite sẽ **xóa sạch thư mục đích (`public/build`)** để biên dịch lại từ đầu.
- Quá trình biên dịch hàng ngàn modules của React / Vue / Inertia tốn từ 45 giây đến 1.5 phút.
- Trong suốt thời gian này, file `public/build/manifest.json` **không tồn tại**.
- Bất kỳ khách nào truy cập web trong lúc này đều bị Laravel ném exception:
  `Illuminate\View\ViteManifestNotFoundException: Vite manifest not found at: public/build/manifest.json`
  ➡️ **Khách truy cập thấy toàn trang báo HTTP 500 liên tục.**

### 2. Chạy `optimize:clear` quá sớm trước khi build
- Xóa sạch cache cấu hình và route trước khi build xong khiến PHP-FPM phải đọc lại toàn bộ config thô giữa lúc Node.js đang ăn 100% CPU.

### 3. Rủi ro Build Lỗi Làm Sập Web (Broken State)
- Nếu code mới bị lỗi cú pháp TypeScript hoặc thiếu package, `vite build` sẽ gãy giữa chừng. Vì Vite đã xóa thư mục cũ, website sẽ bị sập vĩnh viễn cho đến khi có người vào sửa.

---

## 🌟 Phương Pháp 1: Atomic Directory Swap (Áp dụng cho `deploy.sh` trên VPS)

Khi deploy trực tiếp trên VPS, **bắt buộc** biên dịch ra thư mục tạm rồi hoán đổi nguyên tử (Atomic Swap) trong **0.01 giây**.

### 🔄 Thứ Tự Các Bước Tiêu Chuẩn:

1. **Bật chế độ bảo vệ bash**: `set -e` (gặp lỗi lập tức dừng, không chạy tiếp các lệnh phá hoại).
2. **Kéo code mới**: `git pull origin <branch>`
3. **Cập nhật Backend PHP**:
   - `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`
   - `php artisan migrate --force`
4. **Biên dịch Frontend ra thư mục tạm (`build_tmp`)**:
   - `npm install --prefer-offline --no-audit` (hoặc `npm ci`)
   - `npx vite build --outDir public/build_tmp`
5. **Kiểm tra tính toàn vẹn của bản build**:
   - Xác nhận file `public/build_tmp/manifest.json` tồn tại. Nếu không có ➡️ Báo lỗi và hủy tiến trình, giữ nguyên web cũ.
6. **Hoán đổi nguyên tử (Atomic Swap trong 0.01 giây)**:
   ```bash
   rm -rf public/build_old
   mv public/build public/build_old 2>/dev/null || true
   mv public/build_tmp public/build
   rm -rf public/build_old
   ```
7. **Tái tạo cache Laravel và khởi động lại Queue Worker (Sau khi frontend đã xong)**:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan queue:restart
   ```

---

## 📜 Template Mẫu: `deploy.sh` Chuẩn Zero-Downtime

Sao chép template này vào thư mục gốc của bất kỳ dự án Laravel + Vite nào:

```bash
#!/usr/bin/env bash
# ==============================================================================
# ZERO-DOWNTIME DEPLOYMENT SCRIPT FOR LARAVEL + VITE
# ==============================================================================
set -e

echo "🚀 [1/6] Pulling latest code..."
git pull origin master

echo "📦 [2/6] Updating PHP dependencies..."
if [ -f "composer.lock" ]; then
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
fi

echo "🗄️ [3/6] Running database migrations..."
php artisan migrate --force

echo "🎨 [4/6] Building frontend assets (Atomic build into temp directory)..."
npm install --prefer-offline --no-audit
npx vite build --outDir public/build_tmp

# Verify manifest exists before swapping
if [ ! -f "public/build_tmp/manifest.json" ]; then
    echo "❌ ERROR: Vite manifest.json not found in build_tmp! Aborting swap to keep existing build safe."
    rm -rf public/build_tmp
    exit 1
fi

echo "🔄 [5/6] Performing atomic swap (0.01s swap duration)..."
rm -rf public/build_old
mv public/build public/build_old 2>/dev/null || true
mv public/build_tmp public/build
rm -rf public/build_old

echo "⚡ [6/6] Refreshing Laravel caches and queue workers..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

echo "✅ [SUCCESS] Zero-Downtime Deployment completed successfully!"
```

---

## 🚀 Phương Pháp 2: GitLab CI/CD Pipeline (Offload Build Khỏi VPS)

Đối với các dự án lớn hoặc server VPS có RAM hạn chế (< 2GB RAM), việc chạy `npm run build` trên VPS sẽ làm nghẽn CPU. Giải pháp chuẩn doanh nghiệp là: **Build trên GitLab Runner, sau đó chỉ Rsync bản build lên VPS**.

### Cấu hình `.gitlab-ci.yml` chuẩn:

```yaml
stages:
  - build
  - deploy

variables:
  NODE_OPTIONS: "--max-old-space-size=2048"

# -------------------------------------------------------------
# 1. Giai đoạn Build trên máy CI (Không đụng chạm VPS)
# -------------------------------------------------------------
build_assets:
  stage: build
  image: node:20-alpine
  only:
    - master
  cache:
    key: ${CI_COMMIT_REF_SLUG}
    paths:
      - node_modules/
  script:
    - npm ci
    - npm run build
  artifacts:
    name: "build-assets-${CI_COMMIT_SHORT_SHA}"
    expire_in: 1 hour
    paths:
      - public/build/

# -------------------------------------------------------------
# 2. Giai đoạn Deploy (Chỉ kéo code và rsync build lên VPS)
# -------------------------------------------------------------
deploy_vps:
  stage: deploy
  image: alpine:latest
  only:
    - master
  dependencies:
    - build_assets
  before_script:
    - apk add --no-cache openssh-client rsync bash
    - eval $(ssh-agent -s)
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
    - echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config
  script:
    # 1. Kéo code Laravel trên VPS
    - ssh $SSH_USER@$SSH_HOST "cd $PROJECT_DIR && git pull origin master && php artisan migrate --force"
    # 2. Đẩy thư mục public/build từ CI lên VPS (Atomic Rsync không gián đoạn)
    - rsync -avz --delete public/build/ $SSH_USER@$SSH_HOST:$PROJECT_DIR/public/build/
    # 3. Nạp lại cache trên VPS (mất đúng 1 giây)
    - ssh $SSH_USER@$SSH_HOST "cd $PROJECT_DIR && php artisan config:cache && php artisan route:cache && php artisan queue:restart"
```

---

## ⚠️ Checklist An Toàn Khi Cấu Hình Dự Án Mới

1. **Thêm các thư mục tạm vào `.gitignore`**:
   Mọi dự án áp dụng Atomic Swap **bắt buộc** phải có các dòng sau trong `.gitignore`:
   ```gitignore
   /public/build_tmp/
   /public/build_old/
   ```
2. **Không chạy `optimize:clear` trước khi build**:
   Chỉ chạy `optimize:clear` và tạo lại cache **sau khi** thư mục `public/build` mới đã được hoán đổi thành công.
3. **Queue workers**:
   Luôn luôn có `php artisan queue:restart` ở cuối script để queue workers nhận code mới trong PHP RAM.
4. **Quyền thư mục (File Permissions)**:
   Đảm bảo user chạy deploy (vd: `root` hoặc `www-data`) có quyền ghi (`write`) trên thư mục `public/`.
