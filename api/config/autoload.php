<?php
// api/config/autoload.php
spl_autoload_register(function ($class_name) {
    // Liste des dossiers à rechercher
    $directories = [
        'models/',
        'utils/',
        'config/'
    ];
    
    // Parcourir les dossiers pour trouver la classe
    foreach ($directories as $dir) {
        $file = dirname(__FILE__, 2) . '/' . $dir . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});
?>