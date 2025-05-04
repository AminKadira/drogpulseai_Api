<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
require_once '../config/database.php';
require_once '../utils/response.php';

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

// Configuration
$upload_dir = '../uploads/products/';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5 Mo

// Créer le répertoire s'il n'existe pas
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Vérification du type MIME
$file_info = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($file_info, $_FILES['photo']['tmp_name']);
finfo_close($file_info);

if (!in_array($mime_type, $allowed_types)) {
    Response::error("Type de fichier non autorisé. Seuls JPEG, PNG et GIF sont acceptés.");
    exit;
}

// Vérification de la taille
if ($_FILES['photo']['size'] > $max_size) {
    Response::error("Le fichier est trop volumineux (max 5 Mo)");
    exit;
}

// Générer un nom unique pour éviter les collisions
$file_name = uniqid('product_') . '_' . $user_id . '.' . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
$target_path = $upload_dir . $file_name;

// Déplacer le fichier
if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
    // Créer URL relative pour stocker en BDD
    $relative_path = 'uploads/products/' . $file_name;
    
    Response::success([
        'data' => $relative_path
    ], "Fichier uploadé avec succès");
} else {
    Response::error("Erreur lors de l'upload du fichier");
}
?>