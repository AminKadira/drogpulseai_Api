<?php
/**
 * Point d'entrée catalogue - Version MVC optimisée
 * Gestion d'erreurs globale et configuration centralisée
 */

// Chargement bootstrap et configuration
require_once __DIR__ . '/bootstrap.php';

// Gestion d'erreurs globale pour éviter exposer les détails techniques
try {
    $controller = new CatalogueController($productService, $config);
    $controller->index();
    
} catch (Throwable $e) {
    // Log erreur critique
    error_log("Fatal error in catalogue: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Affichage erreur générique
    http_response_code(500);
    
    if (($config['app']['debug'] ?? false)) {
        // Mode debug - afficher détails erreur
        echo "<div style='background:#f8d7da;color:#721c24;padding:1rem;margin:1rem;border-radius:0.5rem;'>";
        echo "<h3>Erreur de développement</h3>";
        echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
        echo "<strong>Trace:</strong><br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        // Mode production - message générique
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Service temporairement indisponible</title>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial; text-align: center; padding: 2rem; background: #f8fafc; }
                .error { background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px; margin: 2rem auto; }
                .icon { font-size: 3rem; color: #dc3545; margin-bottom: 1rem; }
                h1 { color: #1f2937; margin-bottom: 1rem; }
                p { color: #6b7280; margin-bottom: 1.5rem; }
                a { background: #2563eb; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 0.5rem; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='error'>
                <div class='icon'>⚠️</div>
                <h1>Service temporairement indisponible</h1>
                <p>Nous rencontrons actuellement des difficultés techniques. Veuillez réessayer dans quelques instants.</p>
                <a href='javascript:history.back()'>Retour</a>
            </div>
        </body>
        </html>";
    }
    
    exit;
}