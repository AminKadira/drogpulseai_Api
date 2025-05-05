<?php
// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Méthode non autorisée", 405);
    exit;
}

// Récupération des données soumises
$data = json_decode(file_get_contents("php://input"));

// Vérification des données requises
if (!isset($data->contact_id) || !isset($data->user_id) || !isset($data->items) || !is_array($data->items)) {
    Response::error("Données incomplètes. ID du contact, ID de l'utilisateur et articles sont requis.");
    exit;
}

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Commencer une transaction
    $db->beginTransaction();
    
    // Vérifier si le contact existe
    $contact_check = "SELECT COUNT(*) FROM contacts WHERE id = :contact_id AND user_id = :user_id";
    $stmt = $db->prepare($contact_check);
    $stmt->bindParam(":contact_id", $data->contact_id);
    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        // Annuler la transaction
        $db->rollBack();
        Response::error("Contact invalide ou non autorisé", 404);
        exit;
    }
    
    // Insérer le panier
    $query = "INSERT INTO carts (contact_id, user_id, notes, status) VALUES (:contact_id, :user_id, :notes, :status)";
    
    $stmt = $db->prepare($query);
    
    // Liaison des paramètres
    $contact_id = intval($data->contact_id);
    $user_id = intval($data->user_id);
    $notes = isset($data->notes) ? $data->notes : null;
    $status = 'pending';
    
    $stmt->bindParam(":contact_id", $contact_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":notes", $notes);
    $stmt->bindParam(":status", $status);
    
    // Exécution de la requête
    if (!$stmt->execute()) {
        // Annuler la transaction
        $db->rollBack();
        Response::error("Erreur lors de la création du panier", 500);
        exit;
    }
    
    // Récupérer l'ID du panier créé
    $cart_id = $db->lastInsertId();
    
    // Insérer les éléments du panier
    $total_amount = 0;
    $item_insert_query = "INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (:cart_id, :product_id, :quantity, :price)";
    
    foreach ($data->items as $item) {
        // Vérifier les données requises
        if (!isset($item->product_id) || !isset($item->quantity) || !isset($item->price)) {
            // Annuler la transaction
            $db->rollBack();
            Response::error("Données d'article incomplètes. ID produit, quantité et prix sont requis.", 400);
            exit;
        }
        
        // Récupérer les données du produit pour vérification
        $product_query = "SELECT * FROM products WHERE id = :product_id";
        $product_stmt = $db->prepare($product_query);
        $product_stmt->bindParam(":product_id", $item->product_id);
        $product_stmt->execute();
        
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            // Annuler la transaction
            $db->rollBack();
            Response::error("Produit invalide (ID: " . $item->product_id . ")", 404);
            exit;
        }
        
        // Préparer l'insertion de l'article
        $item_stmt = $db->prepare($item_insert_query);
        
        // Liaison des paramètres
        $item_stmt->bindParam(":cart_id", $cart_id);
        $item_stmt->bindParam(":product_id", $item->product_id);
        $item_stmt->bindParam(":quantity", $item->quantity);
        $item_stmt->bindParam(":price", $item->price);
        
        // Exécution de la requête
        if (!$item_stmt->execute()) {
            // Annuler la transaction
            $db->rollBack();
            Response::error("Erreur lors de l'ajout d'un article au panier", 500);
            exit;
        }
        
        // Ajouter au montant total
        $total_amount += ($item->quantity * $item->price);
    }
    
    // Valider la transaction
    $db->commit();
    
    // Récupérer le panier créé avec ses articles
    $cart = getCartWithItems($db, $cart_id);
    
    // Réponse de succès
    Response::success(['cart' => $cart], "Panier créé avec succès");
    
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    // Log l'erreur
    error_log("Erreur lors de la création du panier: " . $e->getMessage());
    
    // Réponse d'erreur
    Response::error("Erreur lors de la création du panier: " . $e->getMessage(), 500);
}

/**
 * Récupère un panier avec tous ses articles
 */
function getCartWithItems($db, $cart_id) {
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
    
    $cart = $cart_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cart) {
        return null;
    }
    
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
    
    return $cart;
}
?>