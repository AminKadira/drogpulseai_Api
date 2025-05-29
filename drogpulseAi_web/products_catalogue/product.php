<?php
/**
 * Point d'entrée détails produit - Compatible AJAX et page complète
 * Détection automatique du type de requête
 */

// Chargement bootstrap et configuration
require_once __DIR__ . '/bootstrap.php';

// Gestion d'erreurs globale adaptée AJAX/Normal
try {
    $controller = new ProductController($productService);
    $controller->show();
    
} catch (Throwable $e) {
    // Log erreur critique
    error_log("Fatal error in product details: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Détection requête AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    http_response_code(500);
    
    if ($isAjax) {
        // Réponse AJAX
        header('Content-Type: text/html; charset=UTF-8');
        
        if (($config['app']['debug'] ?? false)) {
            echo "<div class='alert alert-danger'>";
            echo "<h5>Erreur de développement</h5>";
            echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine();
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<i class='bi bi-exclamation-triangle-fill'></i> ";
            echo "Une erreur technique est survenue lors du chargement du produit.";
            echo "</div>";
        }
    } else {
        // Page complète
        if (($config['app']['debug'] ?? false)) {
            echo "<div style='background:#f8d7da;color:#721c24;padding:1rem;margin:1rem;border-radius:0.5rem;'>";
            echo "<h3>Erreur de développement</h3>";
            echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<strong>Fichier:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "<br>";
            echo "<strong>Trace:</strong><br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } else {
            echo "<!DOCTYPE html>
            <html>
            <head>
                <title>Produit indisponible - DrogPulseAI</title>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
                <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css' rel='stylesheet'>
            </head>
            <body class='bg-light'>
                <div class='container mt-5'>
                    <div class='row justify-content-center'>
                        <div class='col-md-6'>
                            <div class='card text-center'>
                                <div class='card-body py-5'>
                                    <i class='bi bi-exclamation-triangle-fill text-danger' style='font-size: 3rem;'></i>
                                    <h2 class='mt-3'>Produit indisponible</h2>
                                    <p class='text-muted'>Une erreur technique empêche l'affichage de ce produit.</p>
                                    <div class='mt-4'>
                                        <a href='index.php' class='btn btn-primary me-2'>
                                            <i class='bi bi-house-fill'></i> Retour au catalogue
                                        </a>
                                        <button onclick='history.back()' class='btn btn-outline-secondary'>
                                            <i class='bi bi-arrow-left'></i> Retour
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>";
        }
    }
    
    exit;
}