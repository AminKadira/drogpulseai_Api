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

// Vérification de l'ID de l'utilisateur
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    Response::error("ID utilisateur requis");
    exit;
}

$user_id = intval($_GET['user_id']);

// Paramètres de pagination optionnels
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();

try {
    // Requête pour obtenir le total de paniers
    $count_query = "SELECT COUNT(*) as total FROM carts WHERE user_id = :user_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(":user_id", $user_id);
    $count_stmt->execute();
    $total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer le nombre total de pages
    $total_pages = ceil($total_rows / $limit);
    
    // Requête pour récupérer les paniers avec informations de base
    $carts_query = "SELECT c.id, c.contact_id, c.created_at, c.status,
                  co.nom as contact_nom, co.prenom as contact_prenom, 
                  (SELECT COUNT(*) FROM cart_items WHERE cart_id = c.id) as items_count,
                  (SELECT SUM(quantity) FROM cart_items WHERE cart_id = c.id) as total_quantity,
                  (SELECT SUM(quantity * price) FROM cart_items WHERE cart_id = c.id) as total_amount
                FROM carts c
                JOIN contacts co ON c.contact_id = co.id
                WHERE c.user_id = :user_id
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset";
    
    $carts_stmt = $db->prepare($carts_query);
    $carts_stmt->bindParam(":user_id", $user_id);
    $carts_stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $carts_stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
    $carts_stmt->execute();
    
    $carts = [];
    
    while ($cart = $carts_stmt->fetch(PDO::FETCH_ASSOC)) {
        // Formater le nom du contact
        $cart['contact_name'] = $cart['contact_prenom'] . ' ' . $cart['contact_nom'];
        
        // Supprimer les champs redondants
        unset($cart['contact_prenom']);
        unset($cart['contact_nom']);
        
        $carts[] = $cart;
    }
    
    // Réponse de succès avec pagination
    Response::success([
        'carts' => $carts,
        'pagination' => [
            'total_rows' => $total_rows,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    // Log l'erreur
    error_log("Erreur lors de la récupération des paniers: " . $e->getMessage());
    
    // Réponse d'erreur
    Response::error("Erreur lors de la récupération des paniers: " . $e->getMessage(), 500);
}
?>