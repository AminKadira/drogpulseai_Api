<?php
/**
 * Bootstrap application - Point d'entrée centralisé
 * Configuration et initialisation des services
 */

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Timezone
date_default_timezone_set('Europe/Paris');

// Autoloader simple
spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/controllers/',
        __DIR__ . '/models/',
        __DIR__ . '/services/',
        __DIR__ . '/utils/',
        __DIR__ . '/../../api/config/'
    ];
    
    foreach ($directories as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Configuration
$config = require_once __DIR__ . '/config/config.php';

// Initialisation base de données
try {
    $database = new Database();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Service temporairement indisponible");
}

// Initialisation services
$productService = new ProductService($database);