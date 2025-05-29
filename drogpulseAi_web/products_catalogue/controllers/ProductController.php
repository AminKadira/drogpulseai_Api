<?php
/**
 * ProductController - Gestion détails produit
 * AJAX et page complète avec gestion d'erreurs optimisée
 */

class ProductController 
{
    use ProductUtilities;
    
    private ProductService $productService;
    private bool $isAjax;
    
    public function __construct(ProductService $productService) 
    {
        $this->productService = $productService;
        $this->isAjax = $this->detectAjaxRequest();
    }
    
    public function show(): void 
    {
        try {
            $productId = $this->validateProductId($_GET['id'] ?? null);
            $productData = $this->productService->getProductDetails($productId);
            
            if (!$productData) {
                $this->renderError("Produit introuvable", 404);
                return;
            }
            
            if ($this->isAjax) {
                header('Content-Type: text/html; charset=UTF-8');
                $this->renderPartial('product_details_content', $productData);
            } else {
                $this->render('product_details', $productData);
            }
            
        } catch (ValidationException $e) {
            $this->renderError($e->getMessage(), 400);
        } catch (DatabaseException $e) {
            error_log("Product details DB error: " . $e->getMessage());
            $this->renderError("Erreur de chargement du produit", 500);
        } catch (Exception $e) {
            error_log("Product details error: " . $e->getMessage());
            $this->renderError("Une erreur technique est survenue", 500);
        }
    }
    
    private function validateProductId($id): int 
    {
        if ($id === null || $id === '') {
            throw new ValidationException("ID produit manquant");
        }
        
        if (!is_numeric($id) || intval($id) <= 0) {
            throw new ValidationException("ID produit invalide");
        }
        
        return intval($id);
    }
    
    private function detectAjaxRequest(): bool 
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    private function render(string $view, array $data): void 
    {
        extract($data);
        require_once __DIR__ . "/../views/{$view}.phtml";
    }
    
    private function renderPartial(string $view, array $data): void 
    {
        extract($data);
        require_once __DIR__ . "/../views/partials/{$view}.phtml";
    }
    
    private function renderError(string $message, int $code = 500): void 
    {
        http_response_code($code);
        
        if ($this->isAjax) {
            $alertClass = $code === 404 ? 'warning' : 'danger';
            echo "<div class='alert alert-{$alertClass}'>";
            echo "<i class='bi bi-exclamation-triangle-fill'></i> ";
            echo htmlspecialchars($message);
            echo "</div>";
        } else {
            $error = ['message' => $message, 'code' => $code];
            require_once __DIR__ . '/../views/error.phtml';
        }
    }
}