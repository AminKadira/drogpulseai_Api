<?php
/**
 * Configuration centralisée - Version production ready
 * Variables d'environnement et paramètres applicatifs
 */

return [
    // Application
    'app' => [
        'name' => 'DrogPulseAI Catalogue',
        'version' => '2.0.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => ($_ENV['APP_ENV'] ?? 'production') === 'development',
        'timezone' => 'Europe/Paris'
    ],
    
    // Base de données (utilise la classe Database existante)
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'name' => $_ENV['DB_NAME'] ?? 'drogpulseai',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    ],
    
    // Catalogue
    'catalogue' => [
        'items_per_page' => 12,
        'max_items_per_page' => 50,
        'default_sort' => 'reference',
        'default_direction' => 'ASC',
        'allowed_sorts' => ['reference', 'name', 'label', 'prix_vente_conseille', 'quantity', 'created_at'],
        'search_min_length' => 2,
        'max_search_length' => 100
    ],
    
    // Images
    'images' => [
        'base_path' => '../api/',
        'default_image' => '/assets/images/no-image.svg',
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'max_size' => 5 * 1024 * 1024, // 5MB
        'thumbnail_sizes' => [
            'small' => [150, 150],
            'medium' => [400, 400],
            'large' => [800, 600]
        ]
    ],
    
    // Cache (pour évolution future)
    'cache' => [
        'enabled' => false,
        'driver' => 'file',
        'ttl' => 300, // 5 minutes
        'path' => __DIR__ . '/../var/cache/'
    ],
    
    // Sécurité
    'security' => [
        'csrf_enabled' => true,
        'session_timeout' => 3600,
        'max_login_attempts' => 5,
        'allowed_ips' => [], // Vide = toutes IPs autorisées
        'rate_limit' => [
            'enabled' => false,
            'requests_per_minute' => 60
        ]
    ],
    
    // Performance
    'performance' => [
        'compression_enabled' => true,
        'minify_output' => !($_ENV['APP_ENV'] ?? 'production' === 'development'),
        'lazy_loading' => true,
        'pagination_delta' => 2
    ],
    
    // URLs et chemins
    'paths' => [
        'base_url' => $_ENV['BASE_URL'] ?? '',
        'api_url' => $_ENV['API_URL'] ?? '/api',
        'assets_url' => '/assets',
        'uploads_path' => __DIR__ . '/../../api/uploads/',
        'logs_path' => __DIR__ . '/../var/logs/'
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'level' => $_ENV['LOG_LEVEL'] ?? 'warning', // debug, info, warning, error
        'file' => __DIR__ . '/../var/logs/app.log',
        'max_files' => 5,
        'max_size' => 10 * 1024 * 1024 // 10MB
    ],
    
    // Features flags (pour évolution progressive)
    'features' => [
        'advanced_search' => false,
        'favorites' => false,
        'cart' => false,
        'user_accounts' => false,
        'multi_language' => false,
        'analytics' => false
    ]
];