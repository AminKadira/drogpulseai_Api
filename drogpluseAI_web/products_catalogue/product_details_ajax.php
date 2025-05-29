<?php
/**
 * Détails produit AJAX - Version optimisée
 * Intégration API avec gestion d'erreurs avancée
 */

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../../api/config/database.php';

class ProductDetailsController 
{
    private Database $db;
    private ?array $product = null;
    private ?array $productOwner = null;
    private array $suppliers = [];
    
    public function __construct() 
    {
        $this->db = new Database();
    }
    
    public function show(): void 
    {
        try {
            $productId = $this->getValidatedProductId();
            $this->loadProductData($productId);
            
            if (!$this->product) {
                $this->renderError('Produit introuvable', 404);
                return;
            }
            
            $this->render();
            
        } catch (Exception $e) {
            error_log("Erreur détails produit: " . $e->getMessage());
            $this->renderError('Une erreur est survenue lors du chargement du produit');
        }
    }
    
    private function getValidatedProductId(): int 
    {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            throw new InvalidArgumentException('ID du produit manquant');
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new InvalidArgumentException('ID du produit invalide');
        }
        
        return $id;
    }
    
    private function loadProductData(int $productId): void 
    {
        $pdo = $this->db->getConnection();
        
        // Requête optimisée avec LEFT JOIN pour récupérer toutes les données en une fois
        $sql = "
            SELECT 
                p.*,
                u.nom as owner_nom,
                u.prenom as owner_prenom,
                u.email as owner_email
            FROM products p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = :product_id
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $this->product = $this->enrichProductData($result);
            $this->productOwner = [
                'nom' => $result['owner_nom'],
                'prenom' => $result['owner_prenom'],
                'email' => $result['owner_email']
            ];
            
            // Chargement des fournisseurs
            $this->loadSuppliers($productId);
        }
    }
    
    private function loadSuppliers(int $productId): void 
    {
        $pdo = $this->db->getConnection();
        
        $sql = "
            SELECT 
                ps.*,
                c.nom,
                c.prenom,
                c.telephone,
                c.email
            FROM product_suppliers ps
            JOIN contacts c ON ps.contact_id = c.id
            WHERE ps.product_id = :product_id AND ps.is_active = 1
            ORDER BY ps.is_primary DESC, ps.price ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        $this->suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function enrichProductData(array $product): array 
    {
        $quantity = intval($product['quantity']);
        
        // Calcul du statut de stock
        if ($quantity <= 0) {
            $stockStatus = [
                'status' => 'out_of_stock',
                'class' => 'danger',
                'label' => 'Rupture de stock',
                'icon' => 'x-circle'
            ];
        } elseif ($quantity <= 5) {
            $stockStatus = [
                'status' => 'low_stock',
                'class' => 'warning',
                'label' => 'Stock faible',
                'icon' => 'exclamation-triangle'
            ];
        } else {
            $stockStatus = [
                'status' => 'in_stock',
                'class' => 'success',
                'label' => 'En stock',
                'icon' => 'check-circle'
            ];
        }
        
        return [
            ...$product,
            'stock_status' => $stockStatus,
            'formatted_price' => $this->formatPrice($product['prix_vente_conseille'] ?? 0),
            'formatted_cost' => $this->formatPrice($product['cout_de_revient_unitaire'] ?? 0),
            'formatted_min_price' => $this->formatPrice($product['prix_min_vente'] ?? 0),
            'images' => $this->getProductImages($product),
            'has_description' => !empty(trim($product['description'])),
            'created_date' => $this->formatDate($product['created_at']),
            'updated_date' => $this->formatDate($product['updated_at'])
        ];
    }
    
    private function getProductImages(array $product): array 
    {
        $images = [];
        
        foreach (['photo_url', 'photo_url2', 'photo_url3'] as $key) {
            if (!empty($product[$key])) {
                $images[] = [
                    'url' => '../api/' . ltrim($product[$key], '/'),
                    'alt' => "Image " . (count($images) + 1) . " - " . $product['name']
                ];
            }
        }
        
        if (empty($images)) {
            $images[] = [
                'url' => '/assets/images/no-image.svg',
                'alt' => 'Pas d\'image disponible'
            ];
        }
        
        return $images;
    }
    
    private function formatPrice(float $price): string 
    {
        return number_format($price, 2, ',', ' ') . ' €';
    }
    
    private function formatDate(string $date): string 
    {
        return date('d/m/Y H:i', strtotime($date));
    }
    
    private function render(): void 
    {
        $product = $this->product;
        $owner = $this->productOwner;
        $suppliers = $this->suppliers;
        
        include __DIR__ . '/templates/product_details.phtml';
    }
    
    private function renderError(string $message, int $httpCode = 500): void 
    {
        http_response_code($httpCode);
        echo '<div class="alert alert-' . ($httpCode === 404 ? 'warning' : 'danger') . '">';
        echo '<i class="bi bi-exclamation-triangle-fill"></i> ';
        echo htmlspecialchars($message);
        echo '</div>';
    }
}

// Point d'entrée avec gestion d'erreurs globale
try {
    $controller = new ProductDetailsController();
    $controller->show();
} catch (Throwable $e) {
    error_log("Erreur fatale product_details_ajax: " . $e->getMessage());
    http_response_code(500);
    echo '<div class="alert alert-danger">';
    echo '<i class="bi bi-exclamation-triangle-fill"></i> ';
    echo 'Une erreur technique est survenue.';
    echo '</div>';
}
?>