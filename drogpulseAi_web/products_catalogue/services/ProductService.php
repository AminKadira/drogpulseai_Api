<?php
/**
 * ProductService - Logique métier centralisée
 * Requêtes optimisées avec JOINs et gestion cache
 */

class ProductService 
{
    use ProductUtilities;
    
    private Database $db;
    private const ITEMS_PER_PAGE = 12;
    
    public function __construct(Database $db) 
    {
        $this->db = $db;
    }
    
   public function getUserInfo(int $userId): ?array 
    {
        try {
            $pdo = $this->db->getConnection();
            
            $sql = "SELECT nom, prenom, telephone, email FROM users WHERE id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log("Error getting user info: " . $e->getMessage());
            return null;
        }
    }
        
   public function getCatalogueData(array $filters): array 
    {
        try {
            $pdo = $this->db->getConnection();
            
            // Requête optimisée avec un seul appel DB
            $sql = $this->buildCatalogueQuery($filters);
            $countSql = $this->buildCountQuery($filters);
            
            // Exécution requête principale
            $stmt = $pdo->prepare($sql);
            $this->bindFiltersParameters($stmt, $filters);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Count total pour pagination
            $countStmt = $pdo->prepare($countSql);
            $this->bindFiltersParameters($countStmt, $filters, true);
            $countStmt->execute();
            $totalCount = $countStmt->fetchColumn();
            
            $totalPages = ceil($totalCount / self::ITEMS_PER_PAGE);
            
            return [
                'products' => array_map([$this, 'enrichProductForCatalogue'], $products),
                'totalCount' => $totalCount,
                'totalPages' => $totalPages,
                'pagination' => $this->buildPagination($totalCount, $filters)
            ];
            
        } catch (PDOException $e) {
            throw new DatabaseException("Erreur catalogue: " . $e->getMessage());
        }
    }
    
    public function getProductDetails(int $productId): ?array 
    {
        try {
            $pdo = $this->db->getConnection();
            
            // Requête unique avec tous les JOINs nécessaires
            $sql = "
                SELECT 
                    p.*,
                    u.nom as owner_nom, 
                    u.prenom as owner_prenom, 
                    u.telephone as owner_telephone
                FROM products p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.id = :product_id AND u.id = '9'
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                return null;
            }
            
            // Chargement des fournisseurs en une requête séparée
            $suppliers = $this->getProductSuppliers($productId);
            
            return $this->enrichProductForDetails($product, $suppliers);
            
        } catch (PDOException $e) {
            throw new DatabaseException("Erreur détails produit: " . $e->getMessage());
        }
    }
    
    private function buildCatalogueQuery(array $filters): string 
    {
        $sql = "
            SELECT 
                p.*,
                u.nom as owner_nom,
                u.prenom as owner_prenom,
                COUNT(ps.id) as suppliers_count
            FROM products p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN product_suppliers ps ON p.id = ps.product_id AND ps.is_active = 1
        ";
        
        $whereConditions = $this->buildWhereConditions($filters);
         $whereConditions[] = "p.user_id = 9";   
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $sql .= " GROUP BY p.id";
        $sql .= " ORDER BY p.{$filters['sort_by']} {$filters['sort_dir']}";
        
        // Pagination
        $offset = ($filters['page'] - 1) * self::ITEMS_PER_PAGE;
        $sql .= " LIMIT " . self::ITEMS_PER_PAGE . " OFFSET " . $offset;
        
        return $sql;
    }
    
    private function buildCountQuery(array $filters): string 
    {
        $sql = "SELECT COUNT(DISTINCT p.id) FROM products p";
        
        $whereConditions = $this->buildWhereConditions($filters);
        $whereConditions[] = "p.user_id = 9";  
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        return $sql;
    }
    
    private function buildWhereConditions(array $filters): array 
    {
        $conditions = [];
        
        if (!empty($filters['search'])) {
            $conditions[] = "(p.reference LIKE :search OR p.name LIKE :search OR p.label LIKE :search OR p.description LIKE :search OR p.barcode LIKE :search)";
        }
        
        if ($filters['min_price'] !== null) {
            $conditions[] = "p.prix_vente_conseille >= :min_price";
        }
        
        if ($filters['max_price'] !== null) {
            $conditions[] = "p.prix_vente_conseille <= :max_price";
        }
        
        switch ($filters['stock_filter']) {
            case 'in_stock':
                $conditions[] = "p.quantity > 5";
                break;
            case 'low_stock':
                $conditions[] = "p.quantity > 0 AND p.quantity <= 5";
                break;
            case 'out_of_stock':
                $conditions[] = "p.quantity <= 0";
                break;
        }
        
        return $conditions;
    }
    
    private function bindFiltersParameters(PDOStatement $stmt, array $filters, bool $isCount = false): void 
    {
        if (!empty($filters['search'])) {
            $stmt->bindValue(':search', '%' . $filters['search'] . '%', PDO::PARAM_STR);
        }
        
        if ($filters['min_price'] !== null) {
            $stmt->bindValue(':min_price', $filters['min_price'], PDO::PARAM_STR);
        }
        
        if ($filters['max_price'] !== null) {
            $stmt->bindValue(':max_price', $filters['max_price'], PDO::PARAM_STR);
        }
    }
    
    private function getProductSuppliers(int $productId): array 
    {
        $pdo = $this->db->getConnection();
        
        $sql = "
            SELECT 
                ps.*,
                c.nom,
                c.prenom,
                c.telephone
            FROM product_suppliers ps
            JOIN contacts c ON ps.contact_id = c.id
            WHERE ps.product_id = :product_id AND ps.is_active = 1
            ORDER BY ps.is_primary DESC, ps.price ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function enrichProductForCatalogue(array $product): array 
    {
        return [
            ...$product,
            'stock_status' => $this->calculateStockStatus($product['quantity']),
            'formatted_price' => $this->formatPrice($product['prix_vente_conseille'] ?? 0),
            'image_url' => $this->getImageUrl($product['photo_url'] ?? ''),
            'owner_name' => trim(($product['owner_prenom'] ?? '') . ' ' . ($product['owner_nom'] ?? '')),
            'has_suppliers' => ($product['suppliers_count'] ?? 0) > 0
        ];
    }
    
    private function enrichProductForDetails(array $product, array $suppliers): array 
    {
        return [
            'product' => [
                ...$product,
                'stock_status' => $this->calculateStockStatus($product['quantity']),
                'formatted_price' => $this->formatPrice($product['prix_vente_conseille'] ?? 0),
                'formatted_cost' => $this->formatPrice($product['cout_de_revient_unitaire'] ?? 0),
                'formatted_min_price' => $this->formatPrice($product['prix_min_vente'] ?? 0),
                'images' => $this->getProductImages($product),
                'has_description' => !empty(trim($product['description'] ?? '')),
                'created_date' => $this->formatDate($product['created_at']),
                'updated_date' => $this->formatDate($product['updated_at'])
            ],
            'owner' => [
                'nom' => $product['owner_nom'],
                'prenom' => $product['owner_prenom'],
                'telephone' => $product['owner_telephone']
            ],
            'suppliers' => $suppliers
        ];
    }
    
    private function buildPagination(int $totalCount, array $filters): array 
    {
        $totalPages = ceil($totalCount / self::ITEMS_PER_PAGE);
        $currentPage = $filters['page'];
        
        return [
            'current' => $currentPage,
            'total' => $totalPages,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_url' => $this->buildPaginationUrl($currentPage - 1, $filters),
            'next_url' => $this->buildPaginationUrl($currentPage + 1, $filters),
            'pages' => $this->buildPaginationPages($currentPage, $totalPages, $filters)
        ];
    }
    
    private function buildPaginationPages(int $current, int $total, array $filters): array 
    {
        $pages = [];
        $start = max(1, $current - 2);
        $end = min($total, $current + 2);
        
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = [
                'number' => $i,
                'url' => $this->buildPaginationUrl($i, $filters),
                'current' => $i === $current
            ];
        }
        
        return $pages;
    }
    
    private function buildPaginationUrl(int $page, array $filters): string 
    {
        $params = array_filter([
            'page' => $page > 1 ? $page : null,
            'search' => !empty($filters['search']) ? $filters['search'] : null,
            'min_price' => $filters['min_price'],
            'max_price' => $filters['max_price'],
            'stock' => !empty($filters['stock_filter']) ? $filters['stock_filter'] : null,
            'sort' => $filters['sort_by'] !== 'reference' ? $filters['sort_by'] : null,
            'dir' => $filters['sort_dir'] !== 'ASC' ? strtolower($filters['sort_dir']) : null
        ]);
        
        return '?' . http_build_query($params);
    }
    
    private function formatDate(string $date): string 
    {
        return date('d/m/Y H:i', strtotime($date));
    }
}