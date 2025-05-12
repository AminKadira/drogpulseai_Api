<?php
// api/products/delete_product_supplier.php
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

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../utils/response.php';

// Vérification de la méthode de requête (accepter DELETE ou GET pour compatibilité)
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération de l'ID de l'association à supprimer
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Response::error("ID de l'association requis");
    exit;
}

$id = intval($_GET['id']);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Préparation de la requête de suppression
    $query = "DELETE FROM product_suppliers WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        // Vérification si une ligne a été affectée
        if ($stmt->rowCount() > 0) {
            Response::success(null, "Association produit-fournisseur supprimée avec succès");
        } else {
            Response::error("Association non trouvée", 404);
        }
    } else {
        Response::error("Impossible de supprimer l'association", 500);
    }
} catch (PDOException $e) {
    Response::error("Erreur de base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error("Une erreur est survenue: " . $e->getMessage(), 500);
}
?>