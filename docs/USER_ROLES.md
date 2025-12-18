# Hệ thống Tài khoản - Admin vs User

## Sự khác biệt giữa Admin và User

### User (Người dùng thường)
- Đăng ký/đăng nhập qua website
- Xem sản phẩm, thêm giỏ hàng
- Đặt hàng và theo dõi đơn hàng
- Quản lý địa chỉ giao hàng
- Xem wishlist (sản phẩm yêu thích)

### Admin (Quản trị viên)
- Tất cả quyền của User
- Thêm/sửa/xóa sản phẩm
- Quản lý danh mục
- Xử lý đơn hàng (xác nhận, hủy, giao hàng)
- Xem thống kê doanh thu
- Quản lý mã giảm giá
- Quản lý người dùng

## Giỏ hàng theo User

Mỗi tài khoản có giỏ hàng riêng:

```sql
-- Xem giỏ hàng của user cụ thể
SELECT c.id, u.full_name, ci.product_id, p.name, ci.quantity 
FROM carts c 
JOIN users u ON c.user_id = u.id
JOIN cart_items ci ON c.id = ci.cart_id
JOIN products p ON ci.product_id = p.id
WHERE u.email = 'user@example.com';
```

## Cách hệ thống hoạt động

1. **Khách (chưa đăng nhập)**
   - Giỏ hàng lưu theo session_id
   - Mất khi đóng trình duyệt

2. **Đăng nhập**
   - Giỏ hàng của khách merge vào giỏ của user
   - Lưu vĩnh viễn trong database

3. **Đăng xuất**
   - Giỏ hàng vẫn giữ trong database
   - Đăng nhập lại sẽ thấy lại

## Đăng ký tài khoản

Truy cập: http://localhost:8000/auth.html

1. Click tab "Đăng ký"
2. Điền: Họ tên, Email, Số điện thoại, Mật khẩu
3. Click "Đăng ký"
4. Đăng nhập với email/password vừa tạo

## Tạo Admin qua SQL

```sql
-- Nâng user thành admin
UPDATE users SET role = 'admin' WHERE email = 'user@example.com';

-- Xem danh sách admins
SELECT id, full_name, email, role FROM users WHERE role = 'admin';
```
