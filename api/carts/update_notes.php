<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclusion des fichiers de configuration et d'utilitaires
require_once '../config/database.php';
require_once '../utils/response.php';

// Gestion des requêtes OPTIONS (pre-flight pour CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Vérification des données requises
if (!isset($data->cart_id) || empty($data->cart_id)) {
    Response::error("ID du panier non spécifié ou invalide");
    exit;
}

// La note peut être vide, mais elle doit être définie
if (!isset($data->notes)) {
    Response::error("Le champ 'notes' est requis, même s'il est vide");
    exit;
}

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    // Vérifier si le panier existe
    $checkQuery = "SELECT id FROM carts WHERE id = :cart_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":cart_id", $data->cart_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        Response::error("Panier non trouvé");
        exit;
    }
    
    // Préparation de la requête de mise à jour
    $query = "UPDATE carts SET notes = :notes, updated_at = NOW() WHERE id = :cart_id";
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $stmt->bindParam(":notes", $data->notes);
    $stmt->bindParam(":cart_id", $data->cart_id);
    
    // Exécution de la requête
    if ($stmt->execute()) {
        // Récupérer les données mises à jour pour les renvoyer
        $selectQuery = "SELECT * FROM carts WHERE id = :cart_id";
        $selectStmt = $db->prepare($selectQuery);
        $selectStmt->bindParam(":cart_id", $data->cart_id);
        $selectStmt->execute();
        
        $cart = $selectStmt->fetch(PDO::FETCH_ASSOC);
        
        // Réponse de succès
        Response::success(['cart' => $cart], "Notes du panier mises à jour avec succès");
    } else {
        Response::error("Impossible de mettre à jour les notes du panier");
    }
} catch (PDOException $e) {
    Response::error("Erreur de base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error("Erreur: " . $e->getMessage(), 500);
}
?>