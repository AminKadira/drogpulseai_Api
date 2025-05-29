<?php
/**
 * ProductUtilities - Trait pour méthodes communes
 * Centralisation code réutilisable avec bonnes pratiques
 */

trait ProductUtilities 
{
    protected function calculateStockStatus(int $quantity): array 
    {
        if ($quantity <= 0) {
            return [
                'status' => 'out_of_stock',
                'class' => 'danger',
                'label' => 'Rupture de stock',
                'icon' => 'x-circle'
            ];
        } elseif ($quantity <= 5) {
            return [
                'status' => 'low_stock',
                'class' => 'warning',
                'label' => 'Stock faible',
                'icon' => 'exclamation-triangle'
            ];
        }
        
        return [
            'status' => 'in_stock',
            'class' => 'success',
            'label' => 'En stock',
            'icon' => 'check-circle'
        ];
    }
    
    protected function formatPrice(float $price): string 
    {
        if ($price == 0) {
            return 'N/A';
        }
        return number_format($price, 2, ',', ' ') . ' €';
    }
    
    protected function getImageUrl(string $photoUrl): string 
    {
        if (empty($photoUrl)) {
            return '/assets/images/no-image.svg';
        }
        
        // Nettoyage et validation de l'URL
        $cleanUrl = '../api/' . ltrim($photoUrl, '/');
        
        // Vérification sécurité basique (pas de traversal)
        if (strpos($cleanUrl, '..') !== false && strpos($cleanUrl, '../api/') !== 0) {
            return '/assets/images/no-image.svg';
        }
        
        return $cleanUrl;
    }
    
    protected function getProductImages(array $product): array 
    {
        $images = [];
        $imageKeys = ['photo_url', 'photo_url2', 'photo_url3'];
        
        foreach ($imageKeys as $key) {
            if (!empty($product[$key])) {
                $images[] = [
                    'url' => $this->getImageUrl($product[$key]),
                    'alt' => sprintf("Image %d - %s", count($images) + 1, $product['name'] ?? 'Produit')
                ];
            }
        }
        
        // Image par défaut si aucune image
        if (empty($images)) {
            $images[] = [
                'url' => '/assets/images/no-image.svg',
                'alt' => 'Pas d\'image disponible'
            ];
        }
        
        return $images;
    }
    
    protected function truncateText(string $text, int $maxLength = 100): string 
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    protected function generateProductUrl(array $product): string 
    {
        return sprintf(
            'product.php?id=%d&slug=%s',
            $product['id'],
            $this->generateSlug($product['name'] ?? 'produit')
        );
    }
    
    protected function generateSlug(string $text): string 
    {
        // Conversion en slug SEO-friendly
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    protected function isValidImageExtension(string $filename): bool 
    {
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return in_array($extension, $validExtensions);
    }
    
    protected function sanitizeFilename(string $filename): string 
    {
        // Suppression caractères dangereux
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Limitation longueur
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }
        
        return $filename;
    }
}

/**
 * Exceptions personnalisées pour gestion d'erreurs typées
 */

class ValidationException extends Exception 
{
    public function __construct(string $message = "Données invalides", int $code = 400) 
    {
        parent::__construct($message, $code);
    }
}

class DatabaseException extends Exception 
{
    public function __construct(string $message = "Erreur base de données", int $code = 500) 
    {
        parent::__construct($message, $code);
    }
}

class ProductNotFoundException extends Exception 
{
    public function __construct(string $message = "Produit introuvable", int $code = 404) 
    {
        parent::__construct($message, $code);
    }
}