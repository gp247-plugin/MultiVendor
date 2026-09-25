> 🌐 **Ngôn ngữ:** 🇻🇳 Tiếng Việt (hiện tại) · [🇬🇧 English](./v1.md)

# Lịch sử phát hành — MultiVendor

## Giới thiệu
Trang này ghi các phiên bản của plugin MultiVendor và những thay đổi đáng chú ý trong mỗi bản, để chủ sàn biết mình đang dùng bản nào và nâng cấp thì được gì. Tính năng chi tiết xem [tài liệu Tổng quan sàn](https://gp247.net/vi/docs/plugin-multi-vendor/multi-vendor-overview.html) trên gp247.net.

## Phiên bản 1.0.3
- **Khối "Nhà cung cấp mới nhất" được làm lại cho khớp giao diện trang chủ.** Tiêu đề và lề nay thẳng hàng với các khối khác, có liên kết **Xem tất cả** tới danh bạ gian hàng; mỗi gian hàng là một thẻ gồm logo (hiện trọn, không bị cắt), tên thật của gian hàng và ngày tham gia. Liên kết tới gian hàng trước đây bị hỏng (luôn rỗng) — nay mở đúng trang `/shop/{mã}`.
- **Cài plugin là khối tự có mặt trên trang chủ.** Bản cài đặt đặt khối ở vị trí **Top**, ngay dưới banner của trang chủ sàn. Chỉ đặt một lần: nếu bạn đã xoá hoặc di chuyển khối, cập nhật plugin sẽ không đặt lại.
- **Gỡ plugin là khối gỡ theo.** Mọi vị trí đặt khối này ở màn **Layout block** được xoá cùng plugin; các khối khác không bị động tới. Khi plugin đang **tắt**, khối không hiện trên trang chủ và cũng không có trong ô chọn.

## Phiên bản 1.0.2
- **Khối "Nhà cung cấp mới nhất" trên trang chủ chạy với mọi giao diện.** Trước đây bản cài chép tệp khối vào thư mục giao diện đang dùng, nên nó chỉ có mặt ở giao diện tại thời điểm cài: đổi sang giao diện khác (hoặc thêm cửa hàng mới) là khối biến mất, và trên máy chủ **chỉ cho đọc** thì bước chép bị bỏ qua lặng lẽ — bạn không thấy khối mà cũng không thấy lỗi. Nay plugin **đăng ký** khối với hệ thống, nên khối luôn có trong ô chọn của màn **Layout block** với bất kỳ giao diện nào. Cập nhật lên bản này **không đổi gì** trên site đang chạy: tệp đã chép trước đây vẫn được ưu tiên dùng, kể cả khi bạn đã sửa nó.
- **Gỡ plugin không để lại tệp thừa.** Trước đây tệp khối đã chép vào thư mục giao diện vẫn nằm lại sau khi gỡ. Muốn dọn tệp cũ trên site đang chạy, xoá `app/GP247/Templates/{TÊN_GIAO_DIỆN}/blocks/vendor_new.blade.php` — trừ khi bạn đã sửa tệp đó và muốn giữ bản của mình.
- **Muốn tự vẽ lại khối** theo giao diện riêng: tạo tệp `app/GP247/Templates/{TÊN_GIAO_DIỆN}/blocks/vendor_new.blade.php`; bản của bạn luôn thắng bản của plugin.

## Phiên bản 1.0.1
- **Đường dẫn gian hàng đổi từ `/vendor` sang `/shop`.** Đường cũ trùng tên với một thư mục có thật trên máy chủ (`public/vendor/`, nơi các thư viện đặt tệp tĩnh), nên máy chủ web trả về lỗi **403** trước khi yêu cầu kịp vào tới S-Cart — danh bạ gian hàng không mở được. Danh bạ nay ở `/shop`, trang gian hàng ở `/shop/{mã}`. **Site đã chạy đường cũ**: đặt chuyển hướng 301 từ `/vendor/{mã}` sang `/shop/{mã}` để không mất thứ hạng tìm kiếm; đừng đặt lại giá trị cũ trong `.env` vì lỗi 403 sẽ quay lại. Muốn dùng đường dẫn khác thì chọn tên **không trùng** thư mục nào trong `public/` (tránh `vendor`, `storage`, `GP247`).
- **Công tắc của bản Pro trên bản miễn phí nay hiển thị đúng thứ đang chạy.** Trước đây chúng hiện giá trị đã lưu, nên site từng dùng Pro rồi tắt đi sẽ thấy ô vẫn tick và *Phạm vi xử lý đơn* vẫn ghi "Xác nhận + giao hàng" — trong khi bản miễn phí chỉ chạy "Chỉ trạng thái vận chuyển". Giá trị bạn từng lưu **không mất**, mở lại Pro là trở về như cũ. Dải khoá cũng gọn lại: một nhãn duy nhất nằm cùng dòng với ô cấu hình.
- **Dữ liệu mẫu tự lo plugin đánh giá.** `php artisan gp247:vendor-sample` nay cài sẵn *Product Rating & Review* để trang gian hàng có tab **Đánh giá**: đã cài thì giữ nguyên (chỉ bật nếu đang tắt), chưa cài thì cài từ thư mục plugin hoặc tải từ kho. Không cài được (không có mạng, bản cần giấy phép) thì lệnh **vẫn seed xong** và in ra câu lệnh cần chạy. Thêm `--skip-rating` nếu bạn tự quản lý plugin.
- **Tài liệu chi tiết chuyển hẳn lên gp247.net.** Thư mục plugin chỉ còn README (và trang lịch sử này); hướng dẫn cài đặt, vận hành và tùy chỉnh nay có một nguồn duy nhất, luôn là bản mới nhất: [gp247.net/vi/docs/plugin-multi-vendor](https://gp247.net/vi/docs/plugin-multi-vendor/multi-vendor-overview.html).

## Phiên bản 1.0.0 — bản phát hành đầu tiên
- **Tương thích:** `gp247/core` 3.0, `gp247/shop` 3.x (S-Cart 3.x). Yêu cầu Livewire.
- **Đóng gói:** bản **MultiVendor** miễn phí (tối đa 3 gian hàng) và bản **MultiVendorPro** trả phí mở khoá toàn bộ. Cài Pro chồng lên Free, không phải cài lại.
- **Bản miễn phí luôn cho biết bản Pro có gì:** menu và thanh bên vẫn hiện đủ các màn Pro, bấm vào là trang giải thích tính năng kèm danh sách đầy đủ. Cài Pro xong, chính những lối vào đó mở thẳng màn thật — không phải sắp lại menu.

### Mô hình sàn
- Sàn nhiều người bán trên **một domain**: gian hàng tại `/shop/{code}`, danh bạ `/shop`, giỏ tách theo gian hàng, **mỗi vendor một đơn**, sàn thu tiền và trả hoa hồng theo kỳ.
- Trang gian hàng có bìa, logo, banner, tab Sản phẩm / Đánh giá / Thông tin, tìm kiếm trong gian hàng.
- **Chỉ số tin cậy công khai** trên trang gian hàng: tỷ lệ khiếu nại, thời gian chuẩn bị hàng, tỷ lệ trả lời khiếu nại và đánh giá — tính trên 90 ngày gần nhất, ẩn khi gian hàng chưa đủ đơn.
- Nhãn **tên gian hàng kèm biểu tượng** trên thẻ sản phẩm và trang chi tiết, bấm vào mở trang gian hàng.

### Dành cho chủ sàn
- Quản lý gian hàng, tài khoản vendor và cấu hình sàn; **hàng chờ kiểm duyệt** vendor, sản phẩm và hồ sơ xác minh, từ chối phải có lý do và ghi nhật ký.
- **Hoa hồng theo từng gian hàng**, ưu tiên: giá riêng của gian hàng, rồi gói, rồi tỷ lệ chung của sàn.
- **Xử lý thanh toán theo kỳ** với phương thức nhận tiền, bảng kê Excel, và **file lệnh chi ngân hàng theo lô** kèm ghi nhận đã chi cả lô bằng một mã giao dịch. Chỉ quản trị hệ thống tải được file, mỗi lần tải đều vào nhật ký.
- **Thu hồi sau thanh toán** tự động khi đơn bị hoàn hoặc huỷ, bù trừ vào kỳ kế tiếp.
- **Xác minh danh tính vendor** với hồ sơ mã hoá, huy hiệu đã xác minh, và tuỳ chọn bắt buộc xác minh mới được lên sàn và nhận tiền.
- **Bàn khiếu nại** hai cấp: vendor trả lời trước, sàn quyết cuối, hoàn tiền ghi thẳng vào sổ đơn.
- **Gói vendor**: trần sản phẩm, hoa hồng riêng, phí theo kỳ trừ vào kỳ thanh toán; đánh dấu gói nào mở cho vendor tự chọn.
- **Báo cáo hoa hồng** theo gian hàng và theo kỳ, hai cơ sở tính rõ ràng, xuất Excel.

### Dành cho gian hàng
- Khu quản trị riêng `/vendor_admin`: dashboard, sản phẩm, danh mục, banner, nhà cung cấp, thông tin gian hàng, lịch sử thanh toán; tự đăng ký khi sàn cho phép.
- **Xử lý đơn**: chuyển trạng thái trong phạm vi cho phép, nhập mã vận đơn, in phiếu giao.
- **Tự tạo đơn cho khách**, đơn giá điền sẵn theo nhóm giá của khách và vẫn sửa được.
- **Nhóm giá riêng**: khai phần trăm chiết khấu cho từng nhóm khách và gán khách vào nhóm; khách đăng nhập thấy giá của mình ở mọi nơi trong gian hàng, giá này không lộ ra ngoài.
- **Trả lời đánh giá** của sản phẩm mình bán; việc duyệt, từ chối và xoá đánh giá vẫn thuộc về sàn.
- **Tự chọn gói** từ các gói sàn mở công khai, phí trừ vào kỳ thanh toán kế tiếp.
- **Tự cấu hình plugin** mà sàn mở cho gian hàng (vận chuyển, khuyến mãi); cổng thanh toán luôn do sàn cấu hình.

### Dành cho khách hàng
- Mua hàng của nhiều gian hàng trong một giỏ.
- **Đặt hàng nhanh số lượng lớn** theo gian hàng: tìm theo danh mục, dán danh sách SKU, đặt lại đơn cũ, xuất báo giá Excel.
- **Mở khiếu nại** ngay trên trang đơn hàng và theo dõi tiến trình.

### Thông báo
- Email cho vendor khi có đơn mới, khi được duyệt, khi thanh toán xong, khi có thu hồi, khi hồ sơ xác minh được xử lý, và theo từng giai đoạn khiếu nại.
- Email cho sàn khi có vendor hoặc sản phẩm chờ duyệt.

### Kỹ thuật
- Khu quản trị root và vendor chạy trên **Livewire + TailAdmin** của GP247 v2, không jQuery, không framework CSS cũ.
- Tài khoản vendor tách khỏi tài khoản admin bằng guard riêng; dữ liệu mọi màn vendor khoá theo gian hàng đang đăng nhập.
- Đa ngôn ngữ Việt và Anh. Sự kiện `Creating/Created VendorStore|VendorUser`, `Paying/Paid Vendor` để mở rộng.
- Chạy được trên hosting hạn chế: **không cần cron, không cần queue, không cần websocket**.
- Loại trừ lẫn nhau với plugin MultiStore — hai mô hình kinh doanh khác nhau trên cùng bảng cửa hàng.

### Ghi chú về đánh số phiên bản
Đây là **bản phát hành công khai đầu tiên**. Các mốc 1.1 đến 2.6 xuất hiện trong tài liệu phát triển nội bộ là **mốc nội bộ trong quá trình xây dựng**, chưa từng phát hành ra ngoài.
