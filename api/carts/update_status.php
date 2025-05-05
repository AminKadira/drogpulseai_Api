<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestion des requêtes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inclure les fichiers de configuration et d'utilitaires
require_once '../config/database.php';
require_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Vérification des données requises
if (!isset($data->cart_id) || !isset($data->status)) {
    Response::error("ID du panier et statut requis");
    exit;
}

$cart_id = intval($data->cart_id);
$status = $data->status;

// Valider le statut
$valid_statuses = ['pending', 'confirmed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    Response::error("Statut non valide. Valeurs acceptées: " . implode(", ", $valid_statuses));
    exit;
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Vérifier si le panier existe
    $check_query = "SELECT id FROM carts WHERE id = :cart_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":cart_id", $cart_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        Response::error("Panier non trouvé", 404);
        exit;
    }
    
    // Mettre à jour le statut
    $update_query = "UPDATE carts SET status = :status WHERE id = :cart_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":status", $status);
    $update_stmt->bindParam(":cart_id", $cart_id);
    
    if ($update_stmt->execute()) {
        // Récupérer le panier mis à jour
        $cart_query = "SELECT * FROM carts WHERE id = :cart_id";
        $cart_stmt = $db->prepare($cart_query);
        $cart_stmt->bindParam(":cart_id", $cart_id);
        $cart_stmt->execute();
        
        $cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Réponse de succès
        Response::success([
            'cart' => $cart
        ], "Statut du panier mis à jour avec succès");
    } else {
        Response::error("Erreur lors de la mise à jour du statut du panier", 500);
    }
    
} catch (Exception $e) {
    // Log l'erreur
    error_log("Erreur lors de la mise à jour du statut du panier: " . $e->getMessage());
    
    // Réponse d'erreur
    Response::error("Erreur lors de la mise à jour du statut du panier: " . $e->getMessage(), 500);
}
?>