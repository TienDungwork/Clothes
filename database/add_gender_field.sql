-- ========================================
-- Thêm trường gender cho products
-- Giá trị: 'nam', 'nu', 'unisex', 'phu-kien'
-- ========================================

ALTER TABLE products 
ADD COLUMN gender ENUM('nam', 'nu', 'unisex', 'phu-kien') DEFAULT 'unisex' AFTER category_id,
ADD INDEX idx_gender (gender);

-- Cập nhật gender cho các sản phẩm hiện có dựa trên category
-- Category 1 (Thời Trang Nam) và các sub-category của nó (5, 6, 7)
UPDATE products SET gender = 'nam' WHERE category_id IN (1, 5, 6, 7);

-- Category 2 (Thời Trang Nữ) và các sub-category của nó (8, 9, 10)
UPDATE products SET gender = 'nu' WHERE category_id IN (2, 8, 9, 10);

-- Category 3 (Phụ Kiện) và các sub-category của nó (11, 12, 13)
UPDATE products SET gender = 'phu-kien' WHERE category_id IN (3, 11, 12, 13);
