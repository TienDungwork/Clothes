# Hướng dẫn Sử dụng MySQL

## 1. Truy cập MySQL

Bạn đang có terminal MySQL đang chạy. Để truy cập:

```bash
docker exec -it luxe_mysql mysql -uroot -pluxe123 luxe_fashion
```

## 2. Các lệnh SQL cơ bản

### Xem danh sách bảng
```sql
SHOW TABLES;
```

### Xem dữ liệu sản phẩm
```sql
SELECT * FROM products;
SELECT id, name, price, stock_quantity FROM products LIMIT 10;
```

### Xem người dùng
```sql
SELECT id, full_name, email, role FROM users;
```

### Xem danh mục
```sql
SELECT * FROM categories;
```

### Thêm sản phẩm mới
```sql
INSERT INTO products (name, slug, sku, price, category_id, description, stock_quantity, status) 
VALUES ('Áo Sơ Mi Trắng', 'ao-so-mi-trang', 'ASM001', 599000, 1, 'Áo sơ mi trắng nam cao cấp', 50, 'active');
```

### Cập nhật giá sản phẩm
```sql
UPDATE products SET price = 699000, sale_price = 599000 WHERE id = 1;
```

### Xóa sản phẩm
```sql
DELETE FROM products WHERE id = 10;
```

### Tìm kiếm sản phẩm
```sql
SELECT * FROM products WHERE name LIKE '%váy%';
SELECT * FROM products WHERE price BETWEEN 500000 AND 1000000;
```

### Xem đơn hàng
```sql
SELECT * FROM orders ORDER BY created_at DESC LIMIT 10;
```

### Xem thống kê
```sql
-- Tổng số sản phẩm
SELECT COUNT(*) FROM products;

-- Sản phẩm theo danh mục
SELECT c.name, COUNT(p.id) as total 
FROM categories c 
LEFT JOIN products p ON c.id = p.category_id 
GROUP BY c.id;

-- Doanh thu
SELECT SUM(total_amount) FROM orders WHERE status = 'completed';
```

## 3. Thoát MySQL

```sql
exit;
```

## 4. Import/Export dữ liệu

### Export
```bash
docker exec luxe_mysql mysqldump -uroot -pluxe123 luxe_fashion > backup.sql
```

### Import
```bash
docker exec -i luxe_mysql mysql -uroot -pluxe123 luxe_fashion < schema.sql
```
