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
if (!isset($data->cart_id) || empty($data->cart_id) || !isset($data->contact_id) || empty($data->contact_id)) {
    Response::error("ID du panier et ID du contact requis");
    exit;
}

// Facultatif: Vérifier si user_id est fourni pour renforcer la sécurité
$user_id = isset($data->user_id) ? intval($data->user_id) : null;

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    $cart_id = intval($data->cart_id);
    $contact_id = intval($data->contact_id);
    
    // Vérifier que le panier existe
    $cart_check = "SELECT id, user_id FROM carts WHERE id = :cart_id";
    $cart_stmt = $db->prepare($cart_check);
    $cart_stmt->bindParam(":cart_id", $cart_id);
    $cart_stmt->execute();
    
    if ($cart_stmt->rowCount() == 0) {
        Response::error("Panier non trouvé", 404);
        exit;
    }
    
    // Récupérer l'user_id du panier si pas fourni
    if ($user_id === null) {
        $cart_data = $cart_stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $cart_data['user_id'];
    }
    
    // Vérifier que le contact existe et appartient à l'utilisateur
    $contact_check = "SELECT id FROM contacts WHERE id = :contact_id AND user_id = :user_id";
    $contact_stmt = $db->prepare($contact_check);
    $contact_stmt->bindParam(":contact_id", $contact_id);
    $contact_stmt->bindParam(":user_id", $user_id);
    $contact_stmt->execute();
    
    if ($contact_stmt->rowCount() == 0) {
        Response::error("Contact non trouvé ou non associé à l'utilisateur", 404);
        exit;
    }
    
    // Mettre à jour le panier avec le contact_id
    $update_query = "UPDATE carts SET contact_id = :contact_id, updated_at = NOW() WHERE id = :cart_id";
    if ($user_id !== null) {
        // Ajouter une vérification supplémentaire par user_id si fourni
        $update_query .= " AND user_id = :user_id";
    }
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":contact_id", $contact_id);
    $update_stmt->bindParam(":cart_id", $cart_id);
    if ($user_id !== null) {
        $update_stmt->bindParam(":user_id", $user_id);
    }
    
    if ($update_stmt->execute()) {
        if ($update_stmt->rowCount() > 0) {
            // Récupérer les informations mises à jour du panier
            $cart_query = "SELECT c.*, 
                          co.nom as contact_nom, 
                          co.prenom as contact_prenom,
                          co.telephone as contact_telephone,
                          co.email as contact_email
                        FROM carts c
                        JOIN contacts co ON c.contact_id = co.id
                        WHERE c.id = :cart_id";
            
            $cart_stmt = $db->prepare($cart_query);
            $cart_stmt->bindParam(":cart_id", $cart_id);
            $cart_stmt->execute();
            $cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);
            
            Response::success(['cart' => $cart], "Contact assigné au panier avec succès");
        } else {
            Response::error("Aucune modification effectuée, vérifiez les paramètres", 400);
        }
    } else {
        Response::error("Erreur lors de l'assignation du contact au panier", 500);
    }
    
} catch (Exception $e) {
    // Log l'erreur
    error_log("Erreur lors de l'assignation du contact au panier: " . $e->getMessage());
    
    // Réponse d'erreur
    Response::error("Erreur lors de l'assignation du contact au panier: " . $e->getMessage(), 500);
}
?>