<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers de configuration et d'utilitaires
require_once '../config/database.php';
require_once '../utils/response.php';

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Vérification de l'ID du panier
if (!isset($_GET['id']) || empty($_GET['id'])) {
    Response::error("ID du panier requis");
    exit;
}

$cart_id = intval($_GET['id']);

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Récupérer les informations du panier
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
    
    if ($cart_stmt->rowCount() == 0) {
        Response::error("Panier non trouvé", 404);
        exit;
    }
    
    $cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les articles du panier
    $items_query = "SELECT ci.*, 
                  p.reference as product_reference,
                  p.name as product_name,
                  p.label as product_label
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = :cart_id";
    
    $items_stmt = $db->prepare($items_query);
    $items_stmt->bindParam(":cart_id", $cart_id);
    $items_stmt->execute();
    
    $cart['items'] = [];
    $total_quantity = 0;
    $total_amount = 0;
    
    while ($item = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
        $cart['items'][] = $item;
        $total_quantity += $item['quantity'];
        $total_amount += ($item['quantity'] * $item['price']);
    }
    
    // Ajouter les totaux
    $cart['total_quantity'] = $total_quantity;
    $cart['total_amount'] = $total_amount;
    
    // Réponse de succès
    Response::success(['cart' => $cart]);
    
} catch (Exception $e) {
    // Log l'erreur
    error_log("Erreur lors de la récupération du panier: " . $e->getMessage());
    
    // Réponse d'erreur
    Response::error("Erreur lors de la récupération du panier: " . $e->getMessage(), 500);
}
?>