<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
include_once '../config/database.php';
include_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Méthode non autorisée", 405);
}

// Récupération de l'ID du contact à supprimer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    Response::error("ID de contact invalide");
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête de suppression
    $query = "DELETE FROM contacts WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":id", $id);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        // Vérification si une ligne a été affectée
        if ($stmt->rowCount() > 0) {
            Response::success("Contact supprimé avec succès");
        } else {
            Response::error("Contact non trouvé");
        }
    } else {
        Response::error("Impossible de supprimer le contact");
    }
} catch (Exception $e) {
    Response::error("Erreur : " . $e->getMessage());
}
?>