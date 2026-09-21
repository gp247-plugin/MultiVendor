> 🌐 **Ngôn ngữ:** 🇻🇳 Tiếng Việt (hiện tại) · [🇬🇧 English](./v1.md)

# Lịch sử phát hành — MultiVendor

## Giới thiệu
Trang này ghi các phiên bản của plugin MultiVendor và những thay đổi đáng chú ý trong mỗi bản, để chủ sàn biết mình đang dùng bản nào và nâng cấp thì được gì. Tính năng chi tiết xem [Hướng dẫn chi tiết](../multi-vendor-detail_vi.md).

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
