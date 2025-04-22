<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestion des requêtes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inclure les fichiers de configuration et d'utilitaires
include_once '../config/database.php';
include_once '../utils/response.php';

// Vérification de la méthode de requête (accepter DELETE ou GET pour rétrocompatibilité)
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération de l'ID du produit à supprimer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    Response::error("ID de produit invalide");
    exit;
}

// Dans un environnement de production, il faudrait également vérifier 
// que l'utilisateur est authentifié et a les droits de supprimer ce produit
// Ici, on pourrait utiliser par exemple:
// $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Vérification que le produit existe et que l'utilisateur a le droit de le supprimer
    // Si vous implémentez le contrôle d'accès, décommentez et adaptez ces lignes:
    /*
    $check_query = "SELECT id FROM products WHERE id = :id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->bindParam(":user_id", $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        Response::error("Produit non trouvé ou vous n'avez pas les droits nécessaires", 403);
        exit;
    }
    */
    
    // Supprimer d'abord les fichiers associés (photos) si nécessaire
    $photo_query = "SELECT photo_url FROM products WHERE id = :id";
    $photo_stmt = $db->prepare($photo_query);
    $photo_stmt->bindParam(":id", $id);
    $photo_stmt->execute();
    
    if ($photo_stmt->rowCount() > 0) {
        $row = $photo_stmt->fetch(PDO::FETCH_ASSOC);
        $photo_url = $row['photo_url'];
        
        // Si une photo existe, on peut la supprimer du serveur
        if (!empty($photo_url) && file_exists("../" . $photo_url)) {
            unlink("../" . $photo_url);
        }
    }
    
    // Préparation de la requête de suppression
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":id", $id);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        // Vérification si une ligne a été affectée
        if ($stmt->rowCount() > 0) {
            Response::success("Produit supprimé avec succès");
        } else {
            Response::error("Produit non trouvé", 404);
        }
    } else {
        Response::error("Impossible de supprimer le produit", 500);
    }
} catch (PDOException $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    error_log("Database error in product deletion: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    error_log("General error in product deletion: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>