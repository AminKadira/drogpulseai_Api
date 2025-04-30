<?php
// Fichier: api/products/upload_photo_optimized.php

// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
require_once '../config/database.php';
require_once '../utils/response.php';
require_once '../utils/ImageOptimizer.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Vérifier si un fichier a été envoyé
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0) {
    Response::error("Aucun fichier valide n'a été envoyé");
    exit;
}

// Récupérer l'ID utilisateur
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
if (empty($user_id)) {
    Response::error("ID utilisateur requis");
    exit;
}

try {
    // Configuration des chemins
    $upload_dir = '../uploads/products/';
    $cache_dir = '../uploads/cache/';
    
    // Créer les répertoires s'ils n'existent pas
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0777, true);
    }
    
    // Initialiser l'optimiseur d'images
    $optimizer = new ImageOptimizer();
    
    // Vérifier le type de fichier
    if (!$optimizer->isValidImage($_FILES['photo']['tmp_name'])) {
        Response::error("Type de fichier non autorisé. Seuls JPEG, PNG et GIF sont acceptés.");
        exit;
    }
    
    // Vérifier la taille
    if ($_FILES['photo']['size'] > $optimizer->getMaxFileSize()) {
        Response::error("Le fichier est trop volumineux (max " . ($optimizer->getMaxFileSize() / 1024 / 1024) . " Mo)");
        exit;
    }
    
    // Générer un nom unique pour éviter les collisions
    $file_name = uniqid('product_') . '_' . $user_id . '.jpg';
    $target_path = $upload_dir . $file_name;
    
    // Optimiser et sauvegarder l'image
    $result = $optimizer->optimizeAndSave($_FILES['photo']['tmp_name'], $target_path);
    
    if ($result) {
        // Créer URL relative pour stocker en BDD
        $relative_path = 'uploads/products/' . $file_name;
        
        // Préparer des versions redimensionnées pour différents usages
        $thumbnail_path = $cache_dir . 'thumb_' . $file_name;
        $medium_path = $cache_dir . 'medium_' . $file_name;
        
        // Redimensionner pour les thumbnails (140x140)
        $optimizer->resize($_FILES['photo']['tmp_name'], $thumbnail_path, 140, 140);
        
        // Redimensionner pour les images moyennes (600x600)
        $optimizer->resize($_FILES['photo']['tmp_name'], $medium_path, 600, 600);
        
        // Répondre avec succès et les chemins d'accès
        Response::success([
            'photo_url' => $relative_path,
            'thumbnail_url' => 'uploads/cache/thumb_' . $file_name,
            'medium_url' => 'uploads/cache/medium_' . $file_name,
            'size' => filesize($target_path)
        ], "Fichier uploadé et optimisé avec succès");
    } else {
        Response::error("Erreur lors de l'optimisation du fichier");
    }
} catch (Exception $e) {
    Response::error("Erreur: " . $e->getMessage());
}
?>
