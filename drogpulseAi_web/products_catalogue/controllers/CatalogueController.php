<?php
/**
 * CatalogueController - Gestion affichage catalogue
 * Architecture MVC avec validation et gestion d'erreurs
 */

class CatalogueController 
{
    use ProductUtilities;
    
    private ProductService $productService;
    private array $config;
    private const VALID_SORT_FIELDS = ['reference', 'name', 'label', 'prix_vente_conseille', 'quantity', 'created_at'];
    private const VALID_STOCK_FILTERS = ['in_stock', 'low_stock', 'out_of_stock'];
    
    public function __construct(ProductService $productService, array $config = []) 
    {
        $this->productService = $productService;
        $this->config = $config;
    }
    
    public function index(): void 
    {
        try {
         
            $filters = $this->validateAndSanitizeFilters($_GET);
           
            $catalogueData = $this->productService->getCatalogueData($filters);
        
            $viewData = [
                'products' => $catalogueData['products'],
                'totalCount' => $catalogueData['totalCount'],
                'pagination' => $catalogueData['pagination'],
                'filters' => $filters,
                'currentPage' => $filters['page'],
                'totalPages' => $catalogueData['totalPages'],
                'config' => $this->config
            ];
      
            $this->render('catalogue', $viewData);
            
        } catch (ValidationException $e) {
            echo "ValidationException: " . $e->getMessage();
            $this->renderError($e->getMessage(), 400);
        } catch (DatabaseException $e) {
            echo "DatabaseException: " . $e->getMessage();
            error_log("Catalogue DB error: " . $e->getMessage());
            $this->renderError("Erreur de chargement du catalogue", 500);
        } catch (Exception $e) {
            echo "Exception générale: " . $e->getMessage();
            error_log("Catalogue error: " . $e->getMessage());
            $this->renderError("Une erreur technique est survenue", 500);
        }
    }
    
    private function validateAndSanitizeFilters(array $input): array 
    {
        return [
            'search' => $this->sanitizeString($input['search'] ?? ''),
            'min_price' => $this->validatePrice($input['min_price'] ?? null),
            'max_price' => $this->validatePrice($input['max_price'] ?? null),
            'stock_filter' => $this->validateStockFilter($input['stock'] ?? ''),
            'sort_by' => $this->validateSortField($input['sort'] ?? 'reference'),
            'sort_dir' => in_array(strtoupper($input['dir'] ?? 'ASC'), ['ASC', 'DESC']) ? strtoupper($input['dir']) : 'ASC',
            'page' => max(1, intval($input['page'] ?? 1))
        ];
    }
    
    private function validatePrice(?string $price): ?float 
    {
        if ($price === null || $price === '') {
            return null;
        }
        
        $validated = filter_var($price, FILTER_VALIDATE_FLOAT);
        if ($validated === false || $validated < 0) {
            throw new ValidationException("Prix invalide: $price");
        }
        
        return $validated;
    }
    
    private function validateStockFilter(string $filter): string 
    {
        return in_array($filter, self::VALID_STOCK_FILTERS) ? $filter : '';
    }
    
    private function validateSortField(string $field): string 
    {
        return in_array($field, self::VALID_SORT_FIELDS) ? $field : 'reference';
    }
    
    private function sanitizeString(string $input): string 
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    private function render(string $view, array $data): void 
    {
        extract($data);
        require_once __DIR__ . "/../views/{$view}.phtml";
    }
    
    private function renderError(string $message, int $code = 500): void 
    {
        http_response_code($code);
        $error = ['message' => $message, 'code' => $code];
        require_once __DIR__ . '/../views/error.phtml';
    }
}