# wibubu

Website thương mại điện tử lấy cảm hứng từ happylane.edu.vn, UI hiện đại, SPA hash routing, PHP thuần + MySQL + JS, hỗ trợ Dark mode, chỉnh độ sáng, tìm kiếm, giỏ hàng, mã khuyến mãi...

## Hướng dẫn chạy

1. Import `schema.sql` vào MySQL (đã có dữ liệu mẫu).
2. Đảm bảo Apache/Nginx + PHP 8+, MySQL 8+.
3. Cấu hình DB trong `/api/utils.php` (bạn cần điền thông tin kết nối DB).
4. Truy cập `/index.php` trên trình duyệt.

## Tài khoản mẫu

- **Email:** demo@wibubu.vn
- **Mật khẩu:** demo1234

## Tính năng

- SPA với hash routing, không reload trang
- Tìm kiếm, gợi ý nhanh, filter danh mục, sort
- Đăng ký/Đăng nhập, session, hiển thị tên user
- Thêm giỏ hàng, cập nhật, mini-cart
- Áp mã giảm giá, tổng tiền cập nhật
- Dark mode + chỉnh độ sáng toàn site
- Responsive mobile-first
- Chuẩn security: prepared statement, CSRF, session, password_hash

## Thư mục chính

- `/api/` các endpoint PHP REST-like
- `/assets/css/` style gradient, dark mode, grid, button
- `/assets/js/` router, UI state, component
- `/assets/img/` ảnh sản phẩm, logo
- `index.php` shell HTML + các section
- `schema.sql` dữ liệu mẫu