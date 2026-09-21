> 🌐 **Ngôn ngữ:** 🇻🇳 Tiếng Việt (hiện tại) · [🇬🇧 English](./how_to_setup.md)

# Hướng dẫn cài đặt MultiVendor

## Giới thiệu
Tài liệu này hướng dẫn cài S-Cart 3.x, cài và kích hoạt plugin MultiVendor, tạo vendor đầu tiên và kiểm tra sàn chạy đúng. Dành cho chủ website hoặc người quản trị kỹ thuật cơ bản; làm theo từng bước là chạy được, không cần biết lập trình. Cách sàn vận hành và các cấu hình xem ở [Hướng dẫn chi tiết](./multi-vendor-detail_vi.md).

## Yêu cầu
- PHP **8.3** trở lên, Composer, MySQL/MariaDB (SQLite chỉ để thử nhanh).
- S-Cart **3.x** với `gp247/core` **3.0** và `gp247/shop` đã cài (plugin yêu cầu `gp247/shop`).
- **Chưa cài** plugin MultiStore trên cùng website (hai plugin loại trừ lẫn nhau).
- Có quyền chạy lệnh `php artisan` trên máy chủ (hoặc dùng cách cài qua giao diện admin ở Bước 2).

## Bước 1: Cài S-Cart 3.x
Bỏ qua bước này nếu website S-Cart 3.x của bạn đã chạy.

1. Mở **Terminal** tại thư mục muốn đặt website, chạy:

   ```bash
   composer create-project gp247/s-cart website-folder
   cd website-folder
   ```

   Nếu thành công, thư mục `website-folder` xuất hiện với file `.env` được tạo sẵn.

2. Mở file `.env`, sửa phần kết nối cơ sở dữ liệu cho đúng máy của bạn:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=s-cart
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Tạo khóa ứng dụng:

   ```bash
   php artisan key:generate
   ```

4. Khởi tạo S-Cart (tạo bảng, dữ liệu mặc định, tài khoản admin):

   ```bash
   php artisan gp247:install
   ```

   Làm theo các câu hỏi trên màn hình. Nếu thành công, lệnh in ra đường dẫn khu admin và tài khoản đăng nhập.

5. (Tùy chọn) Nạp dữ liệu mẫu cho cửa hàng để có sản phẩm thử:

   ```bash
   php artisan gp247:shop-sample
   ```

Tham khảo thêm: [Tổng quan S-Cart](https://gp247.net/vi/s-cart/s-cart-overview.html).

## Bước 2: Cài plugin MultiVendor
Chọn **một** trong ba cách.

**Cách 1 — Từ thư viện GP247 (khuyến nghị)**
1. Đăng nhập khu admin → **Phần mở rộng** → **Tiện ích (Plugins)**.
2. Mở tab **Thư viện**, tìm **MultiVendor**, bấm **Cài đặt**.

**Cách 2 — Nhập file zip**
1. Vào **Phần mở rộng** → **Tiện ích** → tab **Nhập file**.
2. Chọn file zip của plugin và tải lên.

**Cách 3 — Chép tay (khi hai cách trên lỗi)**
1. Giải nén, chép thư mục mã nguồn vào `app/GP247/Plugins/MultiVendor/`.
2. Chép thư mục `public` của plugin vào `public/GP247/Plugins/MultiVendor/`.
3. Vào **Phần mở rộng** → **Tiện ích** → tab **Đã lưu trên máy**, tìm **MultiVendor**, bấm **Cài đặt**.

Nếu thành công, plugin hiện trong danh sách với nút **Kích hoạt**. Chi tiết: [Hướng dẫn cài phần mở rộng](https://gp247.net/vi/user-guide-extension/guide-to-installing-the-extension.html).

## Bước 3: Kích hoạt và cấu hình sàn
1. Trong danh sách Tiện ích, bấm **Kích hoạt** ở dòng MultiVendor. Menu **Chợ bán hàng** xuất hiện ở thanh bên admin.
2. Vào **Chợ bán hàng** → **Cấu hình nhanh**, đặt:
   - **Tỷ lệ hoa hồng (%)** sàn giữ lại.
   - **Cho phép đăng ký vendor** (bật nếu muốn người bán tự đăng ký).
   - **Tự động duyệt vendor** / **Tự động duyệt sản phẩm** (tắt nếu muốn kiểm duyệt tay).
   - **Đặt hàng nhanh** (Pro — hiện ở trạng thái khoá trên Free; bật cho mô hình đại lý/B2B).
3. Bấm **Lưu**. Ý nghĩa từng cấu hình xem [Hướng dẫn chi tiết](./multi-vendor-detail_vi.md#cấu-hình-sàn).

## Bước 4: Tạo vendor đầu tiên
**Cách A — Admin tạo**
1. **Chợ bán hàng** → **Gian hàng người bán** → **Thêm mới**: nhập mã gian hàng (chỉ chữ/số, tối đa 20 ký tự, ví dụ `vendor01`), tên, mô tả → **Lưu**.
2. **Chợ bán hàng** → **Tài khoản người bán** → **Thêm mới**: nhập email, mật khẩu, chọn gian hàng vừa tạo, trạng thái **Kích hoạt** → **Lưu**.
3. Mở `https://ten-mien-cua-ban/vendor_admin`, đăng nhập bằng email/mật khẩu trên. Nếu thành công, bạn thấy dashboard gian hàng.

**Cách B — Vendor tự đăng ký** (khi đã bật "Cho phép đăng ký vendor")
1. Người bán mở `https://ten-mien-cua-ban/vendor_admin/register`, điền thông tin và mã gian hàng.
2. Nếu sàn tắt "Tự động duyệt vendor", gian hàng ở trạng thái chờ; admin vào **Gian hàng người bán** bật trạng thái mở để vendor đăng nhập được.

**Dữ liệu mẫu để thử nhanh** (chỉ dùng trên site thử nghiệm):

```bash
php artisan gp247:vendor-sample
```

Lệnh tạo 3 gian hàng mẫu `vendor01`–`vendor03`. Mỗi gian hàng có sẵn một tài khoản đăng nhập, một
nhà cung cấp, 3 danh mục riêng và 9 sản phẩm chia đều 3 sản phẩm mỗi danh mục, để cả trang gian hàng
lẫn bộ lọc danh mục đều có nội dung để xem:

| Trang gian hàng | Tài khoản vendor | Mật khẩu |
| --- | --- | --- |
| `/shop/vendor01` | `vendor01@gp247.local` | `123456` |
| `/shop/vendor02` | `vendor02@gp247.local` | `123456` |
| `/shop/vendor03` | `vendor03@gp247.local` | `123456` |

Ba gian hàng đúng bằng giới hạn của bản miễn phí, nên dữ liệu mẫu dùng được cho cả hai bản. Chạy lại lệnh
sẽ thay thế chính các gian hàng mẫu đó chứ không tạo thêm, và không đụng tới gian hàng bạn tự tạo. Hãy đổi
hoặc xoá các tài khoản này trước khi đưa site lên chạy thật.

Lệnh còn **tự lo plugin *Product Rating & Review*** để trang gian hàng có sẵn tab **Đánh giá**: plugin đã cài
thì giữ nguyên (chỉ bật lên nếu đang tắt), chưa cài thì cài — lấy từ thư mục plugin nếu đã có sẵn trên site,
không có thì tải từ kho plugin. Nếu site không ra được Internet, hoặc bản bạn dùng cần giấy phép, lệnh **vẫn
seed xong dữ liệu mẫu** và chỉ in ra câu lệnh cần chạy để cài tiếp. Không muốn lệnh chạm tới plugin nào khác:

```bash
php artisan gp247:vendor-sample --skip-rating
```

## Bước 5: Kiểm tra
1. Mở `https://ten-mien-cua-ban/shop/vendor01` — trang gian hàng hiện danh mục và sản phẩm của vendor (trống nếu chưa đăng sản phẩm).
2. Đăng nhập `/vendor_admin`, tạo một sản phẩm; nếu sàn tắt tự duyệt, vào admin S-Cart → Sản phẩm để duyệt.
3. Mua sản phẩm của hai vendor khác nhau trong một giỏ và thanh toán — phải ra **hai đơn hàng**, mỗi đơn thuộc một gian hàng.
4. Kiểm tra sức khỏe hệ thống:

   ```bash
   php artisan gp247:info
   php artisan gp247:doctor
   ```

## Tùy chỉnh đường dẫn (tùy chọn)
Thêm vào `.env` nếu muốn đổi đường dẫn mặc định, rồi chạy `php artisan gp247:cache-rebuild`:

```env
MULTIVENDOR_FRONT_PATH=shop
MULTIVENDOR_ADMIN_PATH=vendor_admin
PREFIX_QUICK_ORDER_VENDOR=quick-order
PREFIX_CATEGORY_VENDOR=category-vendor
```

| Biến | Đổi đường dẫn nào | Mặc định |
| --- | --- | --- |
| `MULTIVENDOR_FRONT_PATH` | Danh bạ gian hàng và trang gian hàng khách xem: `/shop`, `/shop/{mã}` | `shop` |
| `MULTIVENDOR_ADMIN_PATH` | Khu quản trị của người bán: `/vendor_admin`, kể cả trang đăng nhập và đăng ký | `vendor_admin` |
| `PREFIX_QUICK_ORDER_VENDOR` | Đoạn cuối của trang đặt hàng nhanh: `/shop/{mã}/quick-order` | `quick-order` |
| `PREFIX_CATEGORY_VENDOR` | Trang danh mục riêng của gian hàng | `category-vendor` |

Bốn lưu ý trước khi đổi:

- **Không đặt trùng** với đường dẫn đang có: tiền tố admin (mặc định `gp247_admin`), các biến còn lại trong bảng, hoặc đường dẫn trang/sản phẩm của storefront. Trùng thì một trong hai trang sẽ không mở được.
- **Tuyệt đối không dùng tên đang là thư mục trong `public/`** — hiện là `vendor`, `storage` và `GP247`. Thư mục thật do web server tự trả, request không bao giờ vào tới S-Cart, nên trang báo **403 Forbidden** (hoặc trắng) bất kể route viết thế nào. Đây chính là lý do đường dẫn gian hàng là `shop` **chứ không phải** `vendor`: `public/vendor/` là nơi chứa asset của trình quản lý file. Triệu chứng rất dễ đọc sai — trang gian hàng `/{đường-dẫn}/{mã}` vẫn chạy bình thường, chỉ trang danh bạ `/{đường-dẫn}` chết, và khi bật tiền tố ngôn ngữ SEO thì lỗi bị che luôn (`/en/vendor` không trùng).
- **Đường dẫn cũ sẽ báo 404.** Site đã chạy thật và đã được Google lập chỉ mục thì nên tạo chuyển hướng 301 từ đường cũ sang đường mới trước khi đổi.
- Viết **không có dấu `/`** ở đầu và cuối. Trên hosting không dùng được dòng lệnh, sau khi sửa `.env` hãy xoá file `bootstrap/cache/config.php` (nếu có) thay cho lệnh `gp247:cache-rebuild`.

Muốn đổi chữ hiển thị, nội dung email, giao diện trang gian hàng hay phân quyền cho nhân sự: xem mục **Tùy chỉnh** trong [Hướng dẫn chi tiết](./multi-vendor-detail_vi.md#tùy-chỉnh).

## Điều kiện & ràng buộc (hiểu trước khi thao tác)
- **Plugin từ chối cài nếu website đã cài MultiStore** — hai plugin dùng chung cơ chế cửa hàng theo hai mô hình khác nhau; gỡ MultiStore trước.
- **Cần `gp247/shop` đã cài** — plugin dựa vào sản phẩm, giỏ hàng, đơn hàng của shop.
- **Mã gian hàng là duy nhất, tối đa 20 ký tự** — mã trở thành đường dẫn `/shop/{code}`.
- **Gỡ plugin sẽ xóa tài khoản vendor, danh mục gian hàng và sổ trả tiền vendor** (sản phẩm, đơn hàng của S-Cart giữ nguyên) — xuất dữ liệu cần giữ trước khi gỡ.

## Xử lý sự cố
| Hiện tượng | Cách xử lý |
| --- | --- |
| Không thấy menu **Chợ bán hàng** | Kiểm tra plugin đã **Kích hoạt** (không chỉ Cài đặt); chạy `php artisan gp247:cache-rebuild` |
| Vào `/vendor_admin` báo "tài khoản chưa kích hoạt" | Admin mở trạng thái tài khoản vendor và trạng thái gian hàng |
| Lỗi quyền ghi | Cấp quyền ghi cho `storage/` và `bootstrap/cache/` |
| Lỗi kết nối cơ sở dữ liệu | Kiểm tra lại các dòng `DB_*` trong `.env` |
| Muốn xem lỗi chi tiết | Mở `storage/logs/laravel.log` |

## Hỏi & Đáp (Q&A)
**Câu 1: Site của tôi đang chạy S-Cart 2.x hoặc còn dùng bộ lệnh `sc:*` cũ, có cài được không?**

→ Plugin yêu cầu `gp247/core` 3.0. Nâng cấp S-Cart lên 3.x trước; các lệnh `gp247:*` thay cho `sc:*` cũ.

**Câu 2: Tôi không có quyền chạy lệnh trên hosting thì sao?**

→ Dùng Cách 1 hoặc Cách 2 ở Bước 2 (qua giao diện admin). Plugin không cần cron hay queue để chạy.

**Câu 3: Vendor đăng nhập ở đâu, admin sàn đăng nhập ở đâu?**

→ Vendor: `/vendor_admin`. Admin sàn: khu admin S-Cart như bình thường.

**Câu 4: Đổi đường dẫn `/shop` thành tên khác được không?**

→ Được, đặt `MULTIVENDOR_FRONT_PATH` trong `.env` rồi chạy `php artisan gp247:cache-rebuild`. Chọn tên **không** phải thư mục trong `public/` (nên tránh `vendor`, `storage`, `GP247`) — xem bốn lưu ý ở trên.

**Câu 5: Dữ liệu mẫu có xóa được không?**

→ Xóa tay các gian hàng `vendor01`–`vendor03` và tài khoản tương ứng trong **Chợ bán hàng**. Không chạy lệnh mẫu trên site thật.

---

<sub>📅 **Cập nhật lần cuối:** 2026-09-21 · ✍️ **Tác giả (Author):** GP247</sub>
