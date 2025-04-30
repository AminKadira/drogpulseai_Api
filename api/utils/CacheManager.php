<?php
// Fichier: api/utils/CacheManager.php

/**
 * Gestionnaire de cache avec Redis
 */
class CacheManager {
    /**
     * @var Redis|null Instance de Redis
     */
    private $redis = null;
    
    /**
     * @var int Durée de vie par défaut du cache en secondes (5 minutes)
     */
    private $defaultExpiry = 300;
    
    /**
     * @var bool Indique si le cache est activé
     */
    private $enabled = true;
    
    /**
     * @var string Préfixe pour les clés de cache
     */
    private $keyPrefix = 'drogpulseai:';
    
    /**
     * Constructeur - initialise la connexion Redis
     * 
     * @param array $config Configuration Redis (host, port, password, etc.)
     */
    public function __construct(array $config = []) {
        // Valeurs par défaut
        $config = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'db' => 0,
            'timeout' => 2.0,
            'enabled' => true,
            'prefix' => 'drogpulseai:'
        ], $config);
        
        $this->enabled = $config['enabled'];
        $this->keyPrefix = $config['prefix'];
        
        if (!$this->enabled) {
            return;
        }
        
        try {
            if (!class_exists('Redis')) {
                throw new Exception("Extension Redis non disponible");
            }
            
            $this->redis = new Redis();
            $connected = $this->redis->connect(
                $config['host'],
                $config['port'],
                $config['timeout']
            );
            
            if (!$connected) {
                throw new Exception("Impossible de se connecter au serveur Redis");
            }
            
            if ($config['password']) {
                $this->redis->auth($config['password']);
            }
            
            if ($config['db'] !== 0) {
                $this->redis->select($config['db']);
            }
        } catch (Exception $e) {
            error_log("Erreur Redis: " . $e->getMessage());
            $this->enabled = false;
            $this->redis = null;
        }
    }
    
    /**
     * Récupère une valeur depuis le cache
     * 
     * @param string $key Clé de cache
     * @return mixed|null Valeur mise en cache ou null si non trouvée
     */
    public function get($key) {
        if (!$this->enabled || !$this->redis) {
            return null;
        }
        
        $fullKey = $this->keyPrefix . $key;
        $value = $this->redis->get($fullKey);
        
        if ($value === false) {
            return null;
        }
        
        return $this->decode($value);
    }
    
    /**
     * Stocke une valeur dans le cache
     * 
     * @param string $key Clé de cache
     * @param mixed $value Valeur à mettre en cache
     * @param int|null $expiry Durée de vie en secondes (null = valeur par défaut)
     * @return bool Succès de l'opération
     */
    public function set($key, $value, $expiry = null) {
        if (!$this->enabled || !$this->redis) {
            return false;
        }
        
        $fullKey = $this->keyPrefix . $key;
        $expiry = $expiry ?: $this->defaultExpiry;
        $encodedValue = $this->encode($value);
        
        return $this->redis->setex($fullKey, $expiry, $encodedValue);
    }
    
    /**
     * Supprime une ou plusieurs entrées du cache
     * 
     * @param string|array $keys Clé(s) à supprimer
     * @return int Nombre d'entrées supprimées
     */
    public function delete($keys) {
        if (!$this->enabled || !$this->redis) {
            return 0;
        }
        
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        
        $fullKeys = [];
        foreach ($keys as $key) {
            $fullKeys[] = $this->keyPrefix . $key;
        }
        
        return $this->redis->del($fullKeys);
    }
    
    /**
     * Supprime toutes les entrées qui commencent par un préfixe donné
     * 
     * @param string $pattern Motif de recherche (par ex. "products:*")
     * @return int Nombre d'entrées supprimées
     */
    public function deletePattern($pattern) {
        if (!$this->enabled || !$this->redis) {
            return 0;
        }
        
        $fullPattern = $this->keyPrefix . $pattern;
        $keys = $this->redis->keys($fullPattern);
        
        if (empty($keys)) {
            return 0;
        }
        
        return $this->redis->del($keys);
    }
    
    /**
     * Invalide le cache pour un utilisateur spécifique
     * 
     * @param int $userId ID de l'utilisateur
     * @return int Nombre d'entrées supprimées
     */
    public function invalidateUserCache($userId) {
        return $this->deletePattern("user:{$userId}:*");
    }
    
    /**
     * Invalide le cache pour un produit spécifique
     * 
     * @param int $productId ID du produit
     * @return int Nombre d'entrées supprimées
     */
    public function invalidateProductCache($productId) {
        return $this->deletePattern("product:{$productId}:*");
    }
    
    /**
     * Vérifie si le cache est activé
     * 
     * @return bool État d'activation du cache
     */
    public function isEnabled() {
        return $this->enabled && $this->redis !== null;
    }
    
    /**
     * Génère une clé de cache pour les produits d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $params Paramètres supplémentaires (par ex. pagination)
     * @return string Clé de cache
     */
    public function generateProductsKey($userId, array $params = []) {
        $key = "user:{$userId}:products";
        
        if (!empty($params)) {
            $key .= ":" . md5(json_encode($params));
        }
        
        return $key;
    }
    
    /**
     * Génère une clé de cache pour un produit spécifique
     * 
     * @param int $productId ID du produit
     * @return string Clé de cache
     */
    public function generateProductKey($productId) {
        return "product:{$productId}";
    }
    
    /**
     * Encode une valeur pour le stockage dans Redis
     * 
     * @param mixed $value Valeur à encoder
     * @return string Valeur encodée
     */
    private function encode($value) {
        return json_encode($value);
    }
    
    /**
     * Décode une valeur récupérée de Redis
     * 
     * @param string $value Valeur à décoder
     * @return mixed Valeur décodée
     */
    private function decode($value) {
        return json_decode($value, true);
    }
}
?>