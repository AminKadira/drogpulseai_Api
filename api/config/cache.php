<?php
// Fichier: api/config/cache.php

/**
 * Configuration du cache Redis
 * Utilisé pour initialiser le CacheManager
 */
return [
    'enabled' => true,
    'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
    'port' => getenv('REDIS_PORT') ?: 6379,
    'password' => getenv('REDIS_PASSWORD') ?: null,
    'db' => getenv('REDIS_DB') ?: 0,
    'timeout' => 2.0,
    'prefix' => 'drogpulseai:',
    
    // Durées de vie par défaut pour différents types d'entrées (en secondes)
    'ttl' => [
        'products' => 300,      // 5 minutes
        'product_details' => 600,  // 10 minutes
        'user' => 1800,         // 30 minutes
    ]
];
?>