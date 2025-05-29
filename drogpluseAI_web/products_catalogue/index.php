<?php
/**
 * Catalogue produits optimisé
 * Version MVC avec API integration
 */

require_once __DIR__ . '/../../api/config/database.php';
require_once __DIR__ . '/../../api/utils/response.php';

class CatalogueController 
{
    private Database $db;
    private array $config;
    private const ITEMS_PER_PAGE = 12;
    private const VALID_SORT_FIELDS = ['reference', 'name', 'label', 'prix_vente_conseille', 'quantity', 'created_at'];
    
    public function __construct() 
    {
        $this->db = new Database();
        $this->config = $this->loadConfig();
    }
    
    public function index(): void 
    {
        try {
            $filters = $this->getValidatedFilters();
            $products = $this->getProducts($filters);
            $totalCount = $this->getTotalCount($filters);
            $pagination = $this->buildPagination($totalCount, $filters);
            
            $this->render([
                'products' => $products,
                'filters' => $filters,
                'totalCount' => $totalCount,
                'pagination' => $pagination,
                'currentPage' => $filters['page'],
                'totalPages' => ceil($totalCount / self::ITEMS_PER_PAGE)
            ]);
            
        } catch (Exception $e) {
            error_log("Erreur catalogue: " . $e->getMessage());
            $this->renderError("Une erreur est survenue lors du chargement du catalogue.");
        }
    }
    
    private function getValidatedFilters(): array 
    {
        return [
            'search' => $this->sanitizeString($_GET['search'] ?? ''),
            'min_price' => filter_var($_GET['min_price'] ?? null, FILTER_VALIDATE_FLOAT) ?: null,
            'max_price' => filter_var($_GET['max_price'] ?? null, FILTER_VALIDATE_FLOAT) ?: null,
            'stock_filter' => in_array($_GET['stock'] ?? '', ['in_stock', 'low_stock', 'out_of_stock']) ? $_GET['stock'] : '',
            'sort_by' => in_array($_GET['sort'] ?? 'reference', self::VALID_SORT_FIELDS) ? $_GET['sort'] : 'reference',
            'sort_dir' => ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC',
            'page' => max(1, intval($_GET['page'] ?? 1))
        ];
    }
    
    private function getProducts(array $filters): array 
    {
        $pdo = $this->db->getConnection();
        
        [$sql, $params] = $this->buildProductQuery($filters);
        
        // Ajout pagination
        $offset = ($filters['page'] - 1) * self::ITEMS_PER_PAGE;
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = self::ITEMS_PER_PAGE;
        $params[':offset'] = $offset;
        
        $stmt = $pdo->prepare($sql);
        
        // Bind des paramètres avec types appropriés
        foreach ($params as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($key === ':limit' || $key === ':offset') {
                $type = PDO::PARAM_INT;
            } elseif (is_float($value)) {
                $type = PDO::PARAM_STR; // Float géré comme string en PDO
            } elseif (is_int($value)) {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue($key, $value, $type);
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrichissement des données produits
        return array_map([$this, 'enrichProductData'], $products);
    }
    
    private function getTotalCount(array $filters): int 
    {
        $pdo = $this->db->getConnection();
        
        [$sql, $params] = $this->buildProductQuery($filters, true);
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    private function buildProductQuery(array $filters, bool $countOnly = false): array 
    {
        $select = $countOnly ? "SELECT COUNT(*) as total" : "SELECT *";
        $sql = "$select FROM products WHERE 1=1";
        $params = [];
        
        // Filtres dynamiques
        if (!empty($filters['search'])) {
            $sql .= " AND (reference LIKE :search OR name LIKE :search OR label LIKE :search OR description LIKE :search OR barcode LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if ($filters['min_price'] !== null) {
            $sql .= " AND prix_vente_conseille >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if ($filters['max_price'] !== null) {
            $sql .= " AND prix_vente_conseille <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        // Filtres de stock
        switch ($filters['stock_filter']) {
            case 'in_stock':
                $sql .= " AND quantity > 5";
                break;
            case 'low_stock':
                $sql .= " AND quantity > 0 AND quantity <= 5";
                break;
            case 'out_of_stock':
                $sql .= " AND quantity <= 0";
                break;
        }
        
        // Tri (seulement pour les requêtes non-count)
        if (!$countOnly) {
            $sql .= " ORDER BY {$filters['sort_by']} {$filters['sort_dir']}";
        }
        
        return [$sql, $params];
    }
    
    private function enrichProductData(array $product): array 
    {
        // Calcul du statut de stock
        $quantity = intval($product['quantity']);
        if ($quantity <= 0) {
            $stockStatus = ['status' => 'out_of_stock', 'class' => 'badge-danger', 'label' => 'Rupture de stock'];
        } elseif ($quantity <= 5) {
            $stockStatus = ['status' => 'low_stock', 'class' => 'badge-warning', 'label' => 'Stock faible'];
        } else {
            $stockStatus = ['status' => 'in_stock', 'class' => 'badge-success', 'label' => 'En stock'];
        }
        
        return [
            ...$product,
            'stock_status' => $stockStatus,
            'formatted_price' => $this->formatPrice($product['prix_vente_conseille'] ?? 0),
            'image_url' => $this->getImageUrl($product['photo_url'] ?? ''),
            'has_image' => !empty($product['photo_url'])
        ];
    }
    
    private function buildPagination(int $totalCount, array $filters): array 
    {
        $totalPages = ceil($totalCount / self::ITEMS_PER_PAGE);
        $currentPage = $filters['page'];
        
        $pagination = [
            'current' => $currentPage,
            'total' => $totalPages,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_url' => $this->buildPaginationUrl($currentPage - 1, $filters),
            'next_url' => $this->buildPaginationUrl($currentPage + 1, $filters),
            'pages' => []
        ];
        
        // Calcul des pages à afficher (logique smart)
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $pagination['pages'][] = [
                'number' => $i,
                'url' => $this->buildPaginationUrl($i, $filters),
                'current' => $i === $currentPage
            ];
        }
        
        return $pagination;
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
    
    private function formatPrice(float $price): string 
    {
        return number_format($price, 2, ',', ' ') . ' €';
    }
    
    private function getImageUrl(string $photoUrl): string 
    {
        if (empty($photoUrl)) {
            return '/assets/images/no-image.svg';
        }
        return '../api/' . ltrim($photoUrl, '/');
    }
    
    private function sanitizeString(string $input): string 
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    private function loadConfig(): array 
    {
        return [
            'app_name' => 'DrogPulseAI',
            'version' => '2.0',
            'cache_enabled' => false // Désactivé pour cette version
        ];
    }
    
    private function render(array $data): void 
    {
        extract($data);
        include __DIR__ . '/templates/catalogue.phtml';
    }
    
    private function renderError(string $message): void 
    {
        http_response_code(500);
        include __DIR__ . '/templates/error.phtml';
    }
}

// Point d'entrée
$controller = new CatalogueController();
$controller->index();
?>