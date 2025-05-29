<?php
// test.php - Diagnostic étape par étape
echo "1. PHP fonctionne<br>";

try {
    echo "2. Test autoloader...<br>";
    require_once __DIR__ . '/bootstrap.php';
    echo "3. Bootstrap OK<br>";
    
    echo "4. Test config...<br>";
    var_dump($config);
    
    echo "5. Test Database...<br>";
    var_dump($database);
    
    echo "6. Test ProductService...<br>";
    var_dump($productService);
    
} catch (Throwable $e) {
    echo "<pre>ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "</pre>";
}