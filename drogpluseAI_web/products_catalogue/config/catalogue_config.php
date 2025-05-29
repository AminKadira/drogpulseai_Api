<?php
/**
 * Configuration centralisée pour le catalogue produits
 * Version optimisée avec validation et environnements
 */

class CatalogueConfig 
{
    private static ?self $instance = null;
    private array $config = [];
    
    private function __construct() 
    {
        $this->loadConfiguration();
        $this->validateConfiguration();
    }
    
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfiguration(): void 
    {
        $this->config = [
            // Application
            'app' => [
                'name' => 'DrogPulseAI',
                'version' => '2.0.0',
                'environment' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => in_array($_ENV['APP_ENV'] ?? 'production', ['dev', 'development']),
                'timezone' => 'Africa/Casablanca'
            ],
            
            // Catalogue
            'catalogue' => [
                'items_per_page' => 12,
                'max_items_per_page' => 50,
                'default_sort' => 'reference',
                'default_direction' => 'ASC',
                'allowed_sorts' => ['reference', 'name', 'label', 'prix_vente_conseille', 'quantity', 'created_at'],
                'search_min_length' => 2,
                'cache_ttl' => 300 // 5 minutes
            ],
            
            // Images
            'images' => [
                'base_path' => '../api/',
                'default_image' => '/assets/images/no-image.svg',
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'max_size' => 5 * 1024 * 1024, // 5MB
                'thumbnail_size' => [150, 150],
                'medium_size' => [400, 400]
            ],
            
            // Base de données
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? 3306,
                'name' => $_ENV['DB_NAME'] ?? 'drogpulseai',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            ],
            
            // Cache
            'cache' => [
                'enabled' => $_ENV['CACHE_ENABLED'] ?? false,
                'driver' => 'file', // file, redis, memcached
                'ttl' => 300,
                'path' => __DIR__ . '/../var/cache/'
            ],
            
            // Sécurité
            'security' => [
                'csrf_enabled' => true,
                'session_timeout' => 3600,
                'max_login_attempts' => 5,
                'password_min_length' => 8
            ],
            
            // Performance
            'performance' => [
                'compression_enabled' => true,
                'minify_html' => !$this->isDebug(),
                'lazy_loading' => true,
                'pagination_delta' => 2 // Pages affichées autour de la page courante
            ],
            
            // API
            'api' => [
                'base_url' => $_ENV['API_BASE_URL'] ?? '/api',
                'timeout' => 30,
                'retry_attempts' => 3,
                'rate_limit' => 100 // requêtes par minute
            ]
        ];
        
        // Chargement de l'environnement spécifique
        $this->loadEnvironmentConfig();
    }
    
    private function loadEnvironmentConfig(): void 
    {
        $envFile = __DIR__ . '/.env.' . $this->config['app']['environment'];
        
        if (file_exists($envFile)) {
            $envConfig = parse_ini_file($envFile, true);
            if ($envConfig !== false) {
                $this->config = array_merge_recursive($this->config, $envConfig);
            }
        }
    }
    
    private function validateConfiguration(): void 
    {
        $required = [
            'database.host',
            'database.name',
            'database.username'
        ];
        
        foreach ($required as $key) {
            if (empty($this->get($key))) {
                throw new InvalidArgumentException("Configuration manquante: {$key}");
            }
        }
        
        // Validation des valeurs numériques
        if ($this->get('catalogue.items_per_page') <= 0) {
            throw new InvalidArgumentException("catalogue.items_per_page doit être > 0");
        }
        
        if (!in_array($this->get('catalogue.default_sort'), $this->get('catalogue.allowed_sorts'))) {
            throw new InvalidArgumentException("catalogue.default_sort invalide");
        }
    }
    
    public function get(string $key, $default = null) 
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public function set(string $key, $value): void 
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach (array_slice($keys, 0, -1) as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config[end($keys)] = $value;
    }
    
    public function isDebug(): bool 
    {
        return $this->get('app.debug', false);
    }
    
    public function isDevelopment(): bool 
    {
        return in_array($this->get('app.environment'), ['dev', 'development']);
    }
    
    public function isProduction(): bool 
    {
        return $this->get('app.environment') === 'production';
    }
    
    public function getAll(): array 
    {
        return $this->config;
    }
    
    public function getDatabaseConfig(): array 
    {
        return $this->get('database');
    }
    
    public function getCacheConfig(): array 
    {
        return $this->get('cache');
    }
    
    public function getCatalogueConfig(): array 
    {
        return $this->get('catalogue');
    }
    
    // Helpers pour configuration courante
    public static function catalogueItemsPerPage(): int 
    {
        return self::getInstance()->get('catalogue.items_per_page');
    }
    
    public static function catalogueAllowedSorts(): array 
    {
        return self::getInstance()->get('catalogue.allowed_sorts');
    }
    
    public static function imagesBasePath(): string 
    {
        return self::getInstance()->get('images.base_path');
    }
    
    public static function imagesDefaultImage(): string 
    {
        return self::getInstance()->get('images.default_image');
    }
}

// Fonction helper globale
function config(string $key = null, $default = null) 
{
    $instance = CatalogueConfig::getInstance();
    
    if ($key === null) {
        return $instance;
    }
    
    return $instance->get($key, $default);
}

// Auto-configuration du timezone
date_default_timezone_set(config('app.timezone'));

// Configuration globale des erreurs selon l'environnement
if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Configuration de la session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.gc_maxlifetime', config('security.session_timeout'));
    session_start();
}

return CatalogueConfig::getInstance();
?>