> 🌐 **Ngôn ngữ:** 🇻🇳 Tiếng Việt (hiện tại) · [🇬🇧 English](./README.md)

# MultiVendor — Sàn thương mại nhiều người bán cho S-Cart (Free) · MultiVendorPro (Pro)

## Giới thiệu
MultiVendor biến một website S-Cart thành **sàn thương mại điện tử nhiều người bán trên một domain duy nhất**: các nhà cung cấp (vendor) đăng sản phẩm lên cùng một storefront, khách mua hàng của nhiều vendor trong một lần ghé, sàn đứng ra thu tiền rồi trả lại vendor sau khi giữ hoa hồng. Tài liệu này dành cho **chủ doanh nghiệp và chủ website S-Cart** đang cân nhắc mở sàn: plugin làm được những gì, bản Free cho gì và bản Pro thêm gì. Đây là điểm vào dẫn tới các tài liệu chi tiết bên dưới.

## Plugin làm được gì
MultiVendor cho **nhiều người bán cùng bán trên một website của bạn**. Mỗi người bán có trang gian hàng riêng và khu quản trị riêng, tự đăng sản phẩm và tự xử lý đơn của mình.

Khách mua hàng như bình thường: bỏ hàng của nhiều gian hàng vào một giỏ rồi thanh toán một lần — hệ thống tự tách thành mỗi gian hàng một đơn.

Bạn giữ tiền và giữ quyền: **sàn thu tiền của khách, giữ hoa hồng, rồi chi trả lại cho người bán theo kỳ**; bạn quyết ai được bán và sản phẩm nào được lên sàn.

Bản Pro thêm công cụ cho lúc sàn đông người bán: gói gian hàng, thu hồi tiền khi đơn bị hoàn, khiếu nại, xác minh danh tính, báo cáo và bán sỉ cho đại lý.

### Chi tiết
Tính năng ghi **Pro** cần cài thêm `MultiVendorPro` bên cạnh bản miễn phí.

#### Bán hàng trên sàn

| Tính năng | Nội dung | Bản |
| --- | --- | --- |
| Danh bạ và trang gian hàng | `/shop` liệt kê mọi gian hàng; mỗi gian hàng có trang riêng tại `/shop/{mã}` với ảnh bìa, logo, banner, danh mục riêng và ba tab Sản phẩm · Đánh giá · Thông tin | Free |
| Nhãn người bán trên sản phẩm | Thẻ sản phẩm hiện tên và biểu tượng của gian hàng bán nó | Free |
| Giỏ hàng tách theo gian hàng | Khách mua hàng nhiều gian hàng trong một lần thanh toán; hệ thống tạo mỗi gian hàng một đơn riêng | Free |
| Chỉ số tin cậy công khai | Tỷ lệ khiếu nại, thời gian chuẩn bị hàng, tỷ lệ trả lời khiếu nại và đánh giá; tính trên 90 ngày, ẩn khi gian hàng chưa đủ đơn | Free |
| Bán sỉ cho đại lý | Trang đặt hàng nhanh theo gian hàng (dán danh sách "SKU, số lượng", đặt lại đơn cũ, xuất báo giá Excel); nhóm giá đại lý do từng gian hàng tự đặt; nhân viên gian hàng tạo đơn hộ khách theo đúng giá của khách đó | Pro |

#### Dòng tiền

| Tính năng | Nội dung | Bản |
| --- | --- | --- |
| Sàn thu tiền | Chỉ chủ sàn cấu hình cổng thanh toán; người bán không nhận tiền trực tiếp từ khách | Free |
| Sổ chi trả theo kỳ | Đơn hoàn thành được gom theo kỳ: số tiền phải trả sau hoa hồng, tài khoản nhận tiền của người bán, mã giao dịch | Free |
| Hoa hồng toàn sàn | Một tỷ lệ áp cho mọi gian hàng | Free |
| Hoa hồng riêng từng gian hàng | Phân giải theo thứ tự: tỷ lệ riêng của gian hàng → tỷ lệ của gói → tỷ lệ sàn | Pro |
| Gói gian hàng | Trần sản phẩm, hoa hồng theo gói và phí theo kỳ được trừ thẳng vào lần chi trả kế tiếp — nguồn thu thứ hai ngoài hoa hồng | Pro |
| Chi trả theo lô | Một file lệnh chi ngân hàng cho nhiều gian hàng, bảng kê Excel cho cả hai bên đối soát | Pro |
| Thu hồi tự động | Đơn đã trả tiền bị hoàn hoặc huỷ sẽ tạo dòng điều chỉnh âm, bù trừ vào kỳ kế tiếp | Pro |
| Báo cáo | Đơn hàng theo gian hàng; hoa hồng theo gian hàng và theo kỳ, xuất Excel | Pro |

#### Kiểm soát và vận hành

| Tính năng | Nội dung | Bản |
| --- | --- | --- |
| Khu quản trị riêng cho người bán | `/vendor_admin`: bảng điều khiển, sản phẩm, danh mục, banner, nhà cung cấp, đơn hàng (mã vận đơn, phiếu giao), thông tin gian hàng, lịch sử thanh toán | Free |
| Kiểm duyệt người bán và sản phẩm | Cho hoặc chặn tự đăng ký; tự động duyệt hoặc duyệt tay từng gian hàng, từng sản phẩm | Free |
| Hàng chờ duyệt | Gom gian hàng, sản phẩm và hồ sơ xác minh về một màn; từ chối bắt buộc có lý do và được ghi nhật ký | Pro |
| Phạm vi người bán xử lý đơn | Chủ sàn chọn: chỉ trạng thái giao hàng · thêm xác nhận đơn · thêm hoàn tất đơn | Pro (Free ở mức xác nhận + giao hàng) |
| Khiếu nại hai cấp | Khách mở ngay dưới trang đơn; người bán trả lời trước, sàn quyết sau; khoản hoàn ghi thẳng vào đơn | Pro |
| Xác minh danh tính (KYC) | Hồ sơ lưu mã hoá, huy hiệu "đã xác minh"; tuỳ chọn chặn sản phẩm lên sàn và giữ tiền chi trả tới khi xác minh xong | Pro |
| Người bán tự cấu hình plugin | Vận chuyển, khuyến mãi… trong phạm vi sàn mở; cổng thanh toán luôn thuộc về sàn | Pro |
| Email thông báo | Free: người bán nhận email khi có đơn mới. Pro: thêm email duyệt gian hàng, chi trả, điều chỉnh, khiếu nại và mục chờ duyệt cho sàn | Free · Pro |

> 👉 Xem thử trực tiếp tại trang demo: **https://m-vendor.s-cart.org**

## MultiVendor khác Multi-Store thế nào
Hai plugin cùng nói về "nhiều cửa hàng" nhưng là **hai mô hình kinh doanh khác nhau**, và **không cài chung được** trên một website (hệ thống chặn để tránh hỏng dữ liệu cửa hàng). Chọn đúng ngay từ đầu:

| Tiêu chí | 🛒 **Multi-Vendor** (plugin này) | 🏢 **Multi-Store** |
|---|---|---|
| Ai sở hữu hàng hoá | **Nhiều người bán** độc lập cùng bán trên sàn của bạn | **Một chủ** — chính doanh nghiệp của bạn |
| Mô hình | Sàn thương mại điện tử (marketplace) | Chuỗi cửa hàng / nhiều thương hiệu của cùng một chủ |
| Tên miền | **Một domain duy nhất**; mỗi gian hàng là một trang `/shop/{mã}` | **Mỗi cửa hàng một tên miền** riêng |
| Ai đăng sản phẩm | Từng người bán tự đăng, sàn duyệt | Bạn đăng, chọn sản phẩm thuộc cửa hàng nào |
| Dòng tiền | Sàn thu tiền khách, **giữ hoa hồng**, chi trả lại người bán theo kỳ | Tiền về thẳng doanh nghiệp bạn, không chia cho ai |
| Ai đăng nhập quản trị | Người bán vào khu riêng `/vendor_admin`, chỉ thấy gian hàng mình | Bạn (và quản trị viên từng cửa hàng ở bản Pro) |
| Phù hợp khi | Bạn muốn **mời người khác** vào bán và ăn hoa hồng | Bạn muốn nhiều website/tên miền cho **cùng một doanh nghiệp** |

Nói ngắn gọn: **Multi-Vendor = một cái chợ cho nhiều người bán; Multi-Store = nhiều cửa hàng của chính bạn.** Nếu tất cả hàng hoá đều là của bạn và bạn chỉ cần nhiều tên miền, hãy dùng Multi-Store: [gp247.net/vi/product/multi-store-pro.html](https://gp247.net/vi/product/multi-store-pro.html).

## Tài liệu
Hướng dẫn chi tiết nằm trên trang tài liệu của GP247 — **một nguồn duy nhất**, luôn là bản mới nhất:

| Tài liệu | Nội dung |
| --- | --- |
| [Tổng quan sàn](https://gp247.net/vi/docs/plugin-multi-vendor/multi-vendor-overview.html) | Mô hình vận hành, các luồng chính (sơ đồ), ai làm được gì |
| [Cài đặt](https://gp247.net/vi/docs/plugin-multi-vendor/multi-vendor-setup.html) | Cài S-Cart 3.x, cài và kích hoạt plugin, tạo gian hàng đầu tiên, dữ liệu mẫu, kiểm tra |
| [Vận hành](https://gp247.net/vi/docs/plugin-multi-vendor/multi-vendor-operations.html) | Toàn bộ cấu hình sàn (kèm khoá `admin_config`), tiền và chi trả, kiểm duyệt, khiếu nại, xác minh |
| [Tùy chỉnh](https://gp247.net/vi/docs/plugin-multi-vendor/multi-vendor-customize.html) | Đổi chữ hiển thị, thay view trong template, điểm cắm cho lập trình viên |
| [Lịch sử phát hành](./Release-history/v1_vi.md) | Các phiên bản và thay đổi |
| Trang sản phẩm | [gp247.net — MultiVendor](https://gp247.net/vi/product/multi-vendor-pro.html) |
| Bản tiếng Anh của README này | [README.md](./README.md) |

## So sánh Free và Pro
**Ai mua Pro**: chủ sàn. Vendor và khách hàng không trả gì cho GP247 và không bao giờ thấy lời mời nâng cấp — bản Free chỉ cho chủ sàn thấy mỗi tính năng Pro sẽ nằm ở đâu, bấm vào là có trang giải thích.

Free là một sàn chạy thật cho tối đa 3 vendor. Pro là plugin thứ hai cài **thêm** lên Free (`MultiVendorPro`, yêu cầu `MultiVendor`): cài xong, mọi lối vào đang khoá mở thẳng màn thật — không cài lại, không chuyển dữ liệu, và các bảng Pro tạo ra được giữ nguyên nếu bạn gỡ Pro.

| | **MultiVendor (Free, miễn phí)** | **MultiVendorPro (Pro)** |
| --- | --- | --- |
| Số gian hàng | tối đa 3 | không giới hạn |
| Trang gian hàng, danh bạ, chỉ số tin cậy, giỏ tách theo vendor | có | có |
| Hoa hồng | một tỷ lệ toàn sàn | + **tỷ lệ riêng từng vendor** và tỷ lệ theo gói |
| Gói gian hàng (trần sản phẩm, hoa hồng theo gói, phí kỳ trừ vào chi trả, vendor tự chọn gói) | — | có |
| Sổ chi trả theo kỳ | có | + **tài khoản nhận tiền** của vendor (mã hoá), **bảng kê Excel** hai bên, **file lệnh chi ngân hàng theo lô** và ghi nhận đã chi cả lô một lần |
| Thu hồi sau chi trả (đơn đã trả bị hoàn/huỷ) | — | tự động, bù trừ vào kỳ kế tiếp |
| Khiếu nại / yêu cầu hoàn tiền qua sàn | — | bàn hai cấp: vendor trả lời, sàn quyết, hoàn tiền ghi vào đơn |
| Xác minh danh tính vendor (KYC) | — | hồ sơ mã hoá, huy hiệu đã xác minh, tuỳ chọn giữ sản phẩm và chi trả |
| Kiểm duyệt | qua danh sách gian hàng và sản phẩm | **hàng chờ duyệt** gom vendor, sản phẩm và hồ sơ xác minh; từ chối có lý do được ghi nhật ký |
| Vendor xử lý đơn | trạng thái giao hàng | + xác nhận / hoàn tất theo preset sàn chọn |
| Vendor tạo đơn cho khách | — | có, giá điền sẵn theo nhóm giá của khách |
| Nhóm giá của vendor (chiết khấu đại lý theo nhóm khách) | — | có, khách đăng nhập chỉ thấy giá của mình |
| Vendor trả lời đánh giá của gian hàng mình | — | có (cùng plugin ProductRating) |
| Vendor tự cấu hình plugin sàn mở (vận chuyển, khuyến mãi…) | — | có; cổng thanh toán luôn thuộc sàn |
| Đặt hàng nhanh B2B theo gian hàng (danh sách SKU, dán, đặt lại, báo giá Excel) | — | có |
| Báo cáo (đơn theo gian hàng) và báo cáo hoa hồng theo vendor theo kỳ, xuất Excel | — | có |
| Email | vendor: đơn mới | + vendor được duyệt, đã chi trả, thu hồi, kết quả xác minh, các bước khiếu nại; sàn: có mục chờ duyệt |
| Hỗ trợ | cộng đồng | kênh trả phí của GP247 |

## Yêu cầu
- S-Cart 3.x với `gp247/core` **3.0** và `gp247/shop` đã cài.
- **Không** cài đồng thời với plugin MultiStore (hai mô hình loại trừ lẫn nhau).
- Chạy trên hosting chia sẻ thông thường: không cần cron, queue worker hay websocket.

## Cài bằng dòng lệnh (CLI, gp247 3.x)
Từ gp247 3.x, bạn có thể tải **MultiVendor** từ thư viện GP247 và cài ngay bằng dòng lệnh mà không cần mở admin. Mở Terminal tại thư mục gốc website rồi chạy:

```bash
# 1) Chỉ làm 1 lần cho mỗi website: đăng ký API License (miễn phí) để kết nối thư viện GP247
php artisan gp247:ext-register-license

# 2) Tải plugin từ thư viện và cài
php artisan gp247:ext-install --type=plugin --key=MultiVendor
```

- Trước bước 1, kiểm tra `APP_URL` trong `.env` là **domain thật** của website (không để `http://localhost`), vì license được gắn với domain này.
- Cài xong, plugin được **bật sẵn** và cache tự làm mới, bạn không cần thao tác gì thêm trong admin.
- Lệnh tự kiểm tra điều kiện khai báo trong `gp247.json` (core 3.0, `gp247/shop`, plugin phụ thuộc, quy tắc không cài chung với MultiStore). Nếu thiếu, lệnh dừng lại và báo rõ thiếu gì.
- Nếu thư mục plugin đã có sẵn trong `app/GP247/Plugins/` (chép thủ công hoặc giải nén từ file zip), lệnh sẽ **cài tại chỗ**, không tải lại.
- Nếu plugin đã được cài, lệnh sẽ từ chối. Để lên bản mới, chạy `php artisan gp247:ext-update --type=plugin --key=MultiVendor`.
- Thêm `--json` vào cuối lệnh để nhận kết quả dạng máy đọc được (dùng cho script/CI).
- Chi tiết: [Hướng dẫn cài đặt tiện ích](https://gp247.net/vi/docs/user-guide-extension/guide-to-installing-the-extension.html).

## Hỏi & Đáp (Q&A)
**Câu 1: MultiVendor khác Multi-Store ở đâu?**

→ Multi-Store = mỗi cửa hàng một domain, hàng hoá của chính bạn. MultiVendor = một sàn chung trên một domain, nhiều người bán bên ngoài đăng ký bán, sàn thu tiền rồi trả hoa hồng. Bảng so sánh đầy đủ ở mục [MultiVendor khác Multi-Store thế nào](#multivendor-khác-multi-store-thế-nào); hai plugin không cài chung được.

**Câu 2: Vendor có website hay tên miền của mình không?**

→ Không. Mọi gian hàng nằm trên domain của sàn, truy cập theo đường dẫn `/shop/{mã-gian-hàng}`.

**Câu 3: Ai thu tiền của khách?**

→ Sàn thu. Vendor không nhập khóa cổng thanh toán; sàn trả vendor theo kỳ sau khi giữ hoa hồng (xem mục "Quy trình trả tiền" trong Hướng dẫn chi tiết).

**Câu 4: Bắt đầu bằng Free rồi lên Pro sau có mất gì không?**

→ Không. Pro cài thêm lên Free; gian hàng, vendor, đơn hàng và sổ chi trả giữ nguyên chỗ. Tỷ lệ hoa hồng riêng bạn từng đặt trên Free vẫn được lưu và bắt đầu có hiệu lực ngay khi cài Pro.

**Câu 5: Vendor hay khách hàng có bao giờ thấy giá bán hay nút nâng cấp không?**

→ Không. Chỉ quản trị viên sàn thấy các mục Pro; vendor chạm tới một tính năng Pro chỉ được báo rằng tính năng thuộc bản Pro của sàn và hãy đề nghị sàn.

**Câu 6: Muốn xem thử trước khi quyết định thì xem ở đâu?**

→ Sàn demo công khai: https://m-vendor.s-cart.org — mở trang gian hàng, danh bạ và luồng mua hàng như một khách thật.

---

<sub>📅 **Cập nhật lần cuối:** 2026-09-25 · ✍️ **Tác giả (Author):** GP247</sub>
