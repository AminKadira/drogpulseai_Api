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

// Récupération de l'ID du frais à supprimer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    Response::error("ID de frais invalide");
    exit;
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Vérification de l'existence du frais avant suppression
    $check_query = "SELECT receipt_photo_url FROM expenses WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        // Frais non trouvé
        Response::error("Frais non trouvé", 404);
        exit;
    }
    
    // Récupérer l'URL de la photo pour suppression du fichier
    $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $receipt_photo_url = $row['receipt_photo_url'];
    
    // Si une photo existe, la supprimer du serveur
    if (!empty($receipt_photo_url) && file_exists("../" . $receipt_photo_url)) {
        unlink("../" . $receipt_photo_url);
    }
    
    // Préparation de la requête de suppression
    $query = "DELETE FROM expenses WHERE id = :id";
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":id", $id);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        // Vérification si une ligne a été affectée
        if ($stmt->rowCount() > 0) {
            Response::success(null, "Frais supprimé avec succès");
        } else {
            Response::error("Frais non trouvé", 404);
        }
    } else {
        Response::error("Impossible de supprimer le frais", 500);
    }
} catch (PDOException $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    error_log("Database error: " . $e->getMessage());
    Response::error("Erreur de base de données", 500);
} catch (Exception $e) {
    // Log l'erreur en interne mais ne pas exposer les détails techniques
    error_log("Error: " . $e->getMessage());
    Response::error("Une erreur est survenue", 500);
}
?>