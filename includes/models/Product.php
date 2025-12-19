<?php
/**
 * LUXE Fashion - Product Model
 * 
 * Xử lý tất cả operations liên quan đến products
 */

class Product
{
    private $db;
    private $table = 'products';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all products with filters
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = PRODUCTS_PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['p.status = ?'];
        $params = ['active'];

        // Category filter
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = ?';
            $params[] = $filters['category_id'];
        }

        // Category slug filter
        if (!empty($filters['category_slug'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category_slug'];
        }

        // Gender filter (nam, nu, unisex, phu-kien)
        if (!empty($filters['gender'])) {
            $where[] = 'p.gender = ?';
            $params[] = $filters['gender'];
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $where[] = 'COALESCE(p.sale_price, p.price) >= ?';
            $params[] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[] = 'COALESCE(p.sale_price, p.price) <= ?';
            $params[] = $filters['max_price'];
        }

        // Featured filter
        if (!empty($filters['is_featured'])) {
            $where[] = 'p.is_featured = 1';
        }

        // New arrivals filter
        if (!empty($filters['is_new'])) {
            $where[] = 'p.is_new = 1';
        }

        // Bestseller filter
        if (!empty($filters['is_bestseller'])) {
            $where[] = 'p.is_bestseller = 1';
        }

        // Sale filter
        if (!empty($filters['on_sale'])) {
            $where[] = 'p.sale_price IS NOT NULL AND p.sale_price < p.price';
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = 'MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)';
            $params[] = $filters['search'];
        }

        $whereClause = implode(' AND ', $where);

        // Sorting
        $orderBy = 'p.created_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $orderBy = 'COALESCE(p.sale_price, p.price) ASC';
                    break;
                case 'price_desc':
                    $orderBy = 'COALESCE(p.sale_price, p.price) DESC';
                    break;
                case 'name_asc':
                    $orderBy = 'p.name ASC';
                    break;
                case 'name_desc':
                    $orderBy = 'p.name DESC';
                    break;
                case 'popular':
                    $orderBy = 'p.sold_count DESC';
                    break;
                case 'rating':
                    $orderBy = 'p.rating_avg DESC';
                    break;
                case 'newest':
                default:
                    $orderBy = 'p.created_at DESC';
            }
        }

        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     WHERE {$whereClause}";
        $total = $this->db->query($countSql, $params)->fetchColumn();

        // Get products
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";

        $products = $this->db->query($sql, $params)->fetchAll();

        return [
            'data' => $products,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get single product by ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ? AND p.status = 'active'";
        
        $product = $this->db->query($sql, [$id])->fetch();
        
        if ($product) {
            $product['images'] = $this->getImages($id);
            $product['variants'] = $this->getVariants($id);
            
            // Increment views
            $this->incrementViews($id);
        }

        return $product;
    }

    /**
     * Get single product by slug
     */
    public function getBySlug(string $slug): ?array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = ? AND p.status = 'active'";
        
        $product = $this->db->query($sql, [$slug])->fetch();
        
        if ($product) {
            $product['images'] = $this->getImages($product['id']);
            $product['variants'] = $this->getVariants($product['id']);
            
            // Increment views
            $this->incrementViews($product['id']);
        }

        return $product;
    }

    /**
     * Get product images
     */
    public function getImages(int $productId): array
    {
        $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC";
        return $this->db->query($sql, [$productId])->fetchAll();
    }

    /**
     * Get product variants
     */
    public function getVariants(int $productId): array
    {
        $sql = "SELECT * FROM product_variants WHERE product_id = ? ORDER BY size, color";
        return $this->db->query($sql, [$productId])->fetchAll();
    }

    /**
     * Get featured products
     */
    public function getFeatured(int $limit = 8): array
    {
        $sql = "SELECT p.*, c.name as category_name,
                       COALESCE(p.image, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1)) as image
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' AND p.is_featured = 1
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }

    /**
     * Get new arrivals
     */
    public function getNewArrivals(int $limit = 8): array
    {
        $sql = "SELECT p.*, c.name as category_name,
                       COALESCE(p.image, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1)) as image
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' AND p.is_new = 1
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }

    /**
     * Get bestsellers
     */
    public function getBestsellers(int $limit = 8): array
    {
        $sql = "SELECT p.*, c.name as category_name,
                       COALESCE(p.image, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1)) as image
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' AND p.is_bestseller = 1
                ORDER BY p.sold_count DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }

    /**
     * Get on sale products
     */
    public function getOnSale(int $limit = 8): array
    {
        $sql = "SELECT p.*, c.name as category_name,
                       COALESCE(p.image, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1)) as image,
                       ROUND((1 - p.sale_price / p.price) * 100) as discount_percent
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' AND p.sale_price IS NOT NULL AND p.sale_price < p.price
                ORDER BY discount_percent DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }

    /**
     * Get related products
     */
    public function getRelated(int $productId, int $categoryId, int $limit = 4): array
    {
        $sql = "SELECT p.*, c.name as category_name,
                       COALESCE(p.image, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1)) as image
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' AND p.category_id = ? AND p.id != ?
                ORDER BY RAND()
                LIMIT ?";
        
        return $this->db->query($sql, [$categoryId, $productId, $limit])->fetchAll();
    }

    /**
     * Search products
     */
    public function search(string $keyword, int $limit = 10): array
    {
        $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price,
                       COALESCE(p.image, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1)) as image
                FROM {$this->table} p
                WHERE p.status = 'active' AND (
                    p.name LIKE ? OR 
                    p.sku LIKE ? OR
                    MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)
                )
                ORDER BY 
                    CASE WHEN p.name LIKE ? THEN 0 ELSE 1 END,
                    p.is_bestseller DESC
                LIMIT ?";
        
        $searchPattern = "%{$keyword}%";
        return $this->db->query($sql, [$searchPattern, $searchPattern, $keyword, $searchPattern, $limit])->fetchAll();
    }

    /**
     * Increment product views
     */
    private function incrementViews(int $id): void
    {
        $sql = "UPDATE {$this->table} SET views = views + 1 WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    /**
     * Update product stock
     */
    public function updateStock(int $productId, int $quantity, ?int $variantId = null): bool
    {
        if ($variantId) {
            $sql = "UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
            $this->db->query($sql, [$quantity, $variantId, $quantity]);
        }
        
        $sql = "UPDATE {$this->table} SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
        $this->db->query($sql, [$quantity, $productId, $quantity]);
        
        return $this->db->rowCount() > 0;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(int $productId, ?int $variantId = null): bool
    {
        if ($variantId) {
            $sql = "SELECT stock_quantity FROM product_variants WHERE id = ?";
            $stock = $this->db->query($sql, [$variantId])->fetchColumn();
        } else {
            $sql = "SELECT stock_quantity FROM {$this->table} WHERE id = ?";
            $stock = $this->db->query($sql, [$productId])->fetchColumn();
        }
        
        return $stock > 0;
    }
}
